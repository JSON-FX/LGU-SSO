# LGU-SSO Integration Guide for AI Assistants

> **Read this file first** when helping developers integrate with the LGU-SSO system.

## System Overview

LGU-SSO is a centralized Single Sign-On (SSO) backend for Local Government Unit (LGU) applications. It provides:

- **Employee authentication** via JWT tokens
- **Multi-application support** with per-app roles
- **Single Logout (SLO)** across all applications
- **Philippine location data** (PSGC provinces, cities, barangays)
- **Full audit logging** of authentication events

## Quick Reference

| Item | Value |
|------|-------|
| Base URL | `{LGU_SSO_BASE_URL}/api/v1/` |
| Auth Protocol | OAuth 2.0 + JWT |
| Token Type | Bearer (long-lived, revoked via database) |
| App Auth | `X-Client-ID` + `X-Client-Secret` headers |

## Documentation Files

| File | Purpose |
|------|---------|
| [api-reference.md](./api-reference.md) | Complete API endpoint documentation |
| [authentication.md](./authentication.md) | Auth flows, JWT handling, token lifecycle |
| [integration-guide.md](./integration-guide.md) | Step-by-step integration patterns |
| [error-handling.md](./error-handling.md) | Error codes and troubleshooting |

---

## Integration Checklist

When helping a developer integrate with LGU-SSO, ensure they have:

1. **Client credentials** from the SSO administrator
   - `client_id` - Public identifier for the application
   - `client_secret` - Secret key (keep secure, never expose to frontend)

2. **Environment variables configured**
   ```env
   LGU_SSO_BASE_URL=https://sso.example.gov.ph
   LGU_SSO_CLIENT_ID=your-client-id
   LGU_SSO_CLIENT_SECRET=your-client-secret
   ```

3. **Understanding of the two authentication layers**
   - **Application Authentication**: Your app identifies itself to SSO
   - **User Authentication**: Employee's JWT token

---

## Two-Layer Authentication

### Layer 1: Application Authentication

Client applications must authenticate themselves using headers on SSO endpoints (`/api/v1/sso/*`):

```
X-Client-ID: {client_id}
X-Client-Secret: {client_secret}
```

### Layer 2: User Authentication

Employees authenticate via JWT tokens:

```
Authorization: Bearer {jwt_token}
```

---

## Common Integration Patterns

### Pattern A: Login Form in Your App

Your application provides its own login form that submits to LGU-SSO.

```
1. User enters credentials in YOUR app's login form
2. Your backend calls POST /api/v1/auth/login
3. SSO returns JWT token + employee data
4. Your app stores the token and creates a session
5. For protected routes, validate token via POST /api/v1/sso/validate
```

### Pattern B: Backend Token Validation

Your app receives a token (from another SSO-integrated app or stored session) and validates it.

```
1. Receive JWT token from request
2. Call POST /api/v1/sso/validate with token
3. SSO returns employee data if valid
4. Optionally call POST /api/v1/sso/authorize to check app-specific access
```

---

## Key Endpoints for Client Apps

### 1. Validate Token (Most Common)

```http
POST /api/v1/sso/validate
Headers:
  X-Client-ID: {client_id}
  X-Client-Secret: {client_secret}
  Content-Type: application/json

Body:
{
  "token": "{jwt_token}"
}

Response (200):
{
  "valid": true,
  "data": { /* employee object */ }
}

Response (401):
{
  "valid": false,
  "message": "Invalid token."
}
```

### 2. Check Authorization + Role

```http
POST /api/v1/sso/authorize
Headers:
  X-Client-ID: {client_id}
  X-Client-Secret: {client_secret}
  Content-Type: application/json

Body:
{
  "token": "{jwt_token}"
}

Response (200):
{
  "authorized": true,
  "role": "standard",
  "employee": {
    "uuid": "...",
    "full_name": "...",
    "email": "..."
  }
}

Response (403):
{
  "authorized": false,
  "message": "Employee does not have access to this application."
}
```

### 3. Employee Login

```http
POST /api/v1/auth/login
Content-Type: application/json

Body:
{
  "email": "employee@lgu.gov.ph",
  "password": "password123"
}

Response (200):
{
  "access_token": "eyJ...",
  "token_type": "bearer",
  "employee": { /* employee object */ }
}
```

---

## Employee Object Shape

```typescript
interface Employee {
  uuid: string;
  first_name: string;
  middle_name: string | null;
  last_name: string;
  suffix: string | null;
  full_name: string;        // Computed: "First Middle Last Suffix"
  initials: string;         // Computed: "F.M.L"
  birthday: string;         // Format: "YYYY-MM-DD"
  age: number;              // Computed from birthday
  civil_status: "single" | "married" | "widowed" | "separated" | "divorced";
  email: string;
  is_active: boolean;
  nationality: string;
  residence: string;
  block_number: string | null;
  building_floor: string | null;
  house_number: string | null;
  province: { code: string; name: string } | null;
  city: { code: string; name: string } | null;
  barangay: { code: string; name: string } | null;
  created_at: string;       // ISO 8601
  updated_at: string;       // ISO 8601
}
```

---

## Roles (Per-Application)

Each employee can have a different role per application:

| Role | Value | Description |
|------|-------|-------------|
| Guest | `guest` | Read-only access |
| Standard | `standard` | Normal user access |
| Administrator | `administrator` | Admin privileges for the app |
| Super Administrator | `super_administrator` | Full system access |

---

## Critical Warnings

1. **Never expose client_secret to frontend code** - Keep it server-side only

2. **Tokens are long-lived** - They don't expire by time, only by revocation. Always validate tokens before trusting them.

3. **Rate limiting is per-application** - Each registered app has a configured rate limit (requests per minute)

4. **Single Logout revokes ALL tokens** - When an employee calls `/logout-all`, all their tokens across all apps are invalidated

5. **UUIDs for public references** - Always use `uuid` (not `id`) when referencing employees or applications in URLs

---

## Code Examples

### JavaScript/TypeScript (fetch)

```typescript
const SSO_BASE_URL = process.env.LGU_SSO_BASE_URL;
const CLIENT_ID = process.env.LGU_SSO_CLIENT_ID;
const CLIENT_SECRET = process.env.LGU_SSO_CLIENT_SECRET;

async function validateToken(token: string) {
  const response = await fetch(`${SSO_BASE_URL}/api/v1/sso/validate`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Client-ID': CLIENT_ID,
      'X-Client-Secret': CLIENT_SECRET,
    },
    body: JSON.stringify({ token }),
  });

  return response.json();
}

async function login(email: string, password: string) {
  const response = await fetch(`${SSO_BASE_URL}/api/v1/auth/login`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ email, password }),
  });

  return response.json();
}
```

### Python (requests)

```python
import os
import requests

SSO_BASE_URL = os.environ['LGU_SSO_BASE_URL']
CLIENT_ID = os.environ['LGU_SSO_CLIENT_ID']
CLIENT_SECRET = os.environ['LGU_SSO_CLIENT_SECRET']

def validate_token(token: str) -> dict:
    response = requests.post(
        f"{SSO_BASE_URL}/api/v1/sso/validate",
        headers={
            "Content-Type": "application/json",
            "X-Client-ID": CLIENT_ID,
            "X-Client-Secret": CLIENT_SECRET,
        },
        json={"token": token}
    )
    return response.json()

def login(email: str, password: str) -> dict:
    response = requests.post(
        f"{SSO_BASE_URL}/api/v1/auth/login",
        headers={"Content-Type": "application/json"},
        json={"email": email, "password": password}
    )
    return response.json()
```

---

## Next Steps

For detailed documentation, see:
- [api-reference.md](./api-reference.md) - All endpoints with full request/response schemas
- [authentication.md](./authentication.md) - Deep dive on auth flows
- [integration-guide.md](./integration-guide.md) - Framework-specific examples
- [error-handling.md](./error-handling.md) - Error codes and troubleshooting
