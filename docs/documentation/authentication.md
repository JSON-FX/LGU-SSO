# LGU-SSO Authentication Guide

This document provides a deep dive into the authentication mechanisms used by LGU-SSO.

---

## Overview

LGU-SSO uses a **two-layer authentication system**:

1. **Application Authentication** - Your app identifies itself to the SSO server
2. **User Authentication** - Employees authenticate via JWT tokens

```
┌─────────────────────────────────────────────────────────────────┐
│                        Client Application                        │
├─────────────────────────────────────────────────────────────────┤
│  Layer 1: App Auth        │  Layer 2: User Auth                 │
│  X-Client-ID              │  Authorization: Bearer {jwt}        │
│  X-Client-Secret          │                                     │
└─────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────┐
│                         LGU-SSO Server                           │
│  1. Validate App Credentials                                     │
│  2. Validate JWT Token                                          │
│  3. Check Employee Access & Role                                │
└─────────────────────────────────────────────────────────────────┘
```

---

## Layer 1: Application Authentication

### Purpose

Application authentication ensures that only registered, authorized applications can interact with the SSO system. This prevents unauthorized services from validating tokens or accessing employee data.

### How It Works

1. **Registration**: An SSO administrator registers your application and provides:
   - `client_id` - Public identifier for your application
   - `client_secret` - Secret key (keep this secure!)

2. **Request Authentication**: Include credentials in request headers:
   ```
   X-Client-ID: your-client-id
   X-Client-Secret: your-client-secret
   ```

### Which Endpoints Require App Authentication?

All endpoints under `/api/v1/sso/*` require application authentication:

| Endpoint | Requires App Auth | Requires User Auth |
|----------|-------------------|-------------------|
| `POST /sso/validate` | Yes | No |
| `POST /sso/authorize` | Yes | No |
| `GET /sso/employee` | Yes | Yes |

### Security Best Practices

1. **Never expose `client_secret` in frontend code**
   - Store it in environment variables
   - Only use it in server-side code

2. **Use HTTPS in production**
   - Credentials are transmitted in headers

3. **Rotate secrets periodically**
   - Use `POST /applications/{uuid}/regenerate-secret`

---

## Layer 2: User (Employee) Authentication

### JWT Token Structure

LGU-SSO uses JSON Web Tokens (JWT) for employee authentication. The token contains:

```json
{
  "iss": "lgu-sso",
  "iat": 1704067200,
  "nbf": 1704067200,
  "jti": "unique-token-id",
  "sub": "1",
  "prv": "hash-of-employee-model"
}
```

| Claim | Description |
|-------|-------------|
| `iss` | Issuer (LGU-SSO) |
| `iat` | Issued at timestamp |
| `nbf` | Not valid before timestamp |
| `jti` | Unique token identifier |
| `sub` | Subject (employee ID) |
| `prv` | Provider hash (prevents model impersonation) |

### Token Lifecycle

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│    Login     │────▶│ Token Issued │────▶│  Token Used  │
└──────────────┘     └──────────────┘     └──────────────┘
                                                  │
                           ┌──────────────────────┴──────────────────────┐
                           ▼                                             ▼
                    ┌──────────────┐                              ┌──────────────┐
                    │   Refresh    │                              │   Logout     │
                    │ (Optional)   │                              │  (Revoke)    │
                    └──────────────┘                              └──────────────┘
```

### Token Characteristics

| Property | Value | Description |
|----------|-------|-------------|
| **TTL** | None (never expires) | Tokens don't expire by time |
| **Revocation** | Database-backed | Tokens are invalidated via database |
| **Algorithm** | HS256 | HMAC with SHA-256 |
| **Storage** | Your choice | See recommendations below |

### Why Long-Lived Tokens?

LGU-SSO uses long-lived tokens that don't expire by time. Instead, tokens are revoked when:

1. Employee explicitly logs out (`/auth/logout`)
2. Employee logs out from all sessions (`/auth/logout-all`)
3. Admin deactivates the employee account
4. Token is refreshed (old token is revoked)

**Benefits:**
- Better user experience (no unexpected logouts)
- Simpler token management
- Explicit control over session termination

**Trade-offs:**
- Must always validate tokens server-side
- Cannot rely on expiration for security

---

## Authentication Flows

### Flow 1: Employee Login

```
┌────────────┐          ┌────────────┐          ┌────────────┐
│   Client   │          │  Your App  │          │  LGU-SSO   │
└─────┬──────┘          └─────┬──────┘          └─────┬──────┘
      │                       │                       │
      │ 1. Enter credentials  │                       │
      │──────────────────────▶│                       │
      │                       │                       │
      │                       │ 2. POST /auth/login   │
      │                       │──────────────────────▶│
      │                       │                       │
      │                       │ 3. JWT + Employee     │
      │                       │◀──────────────────────│
      │                       │                       │
      │ 4. Session created    │                       │
      │◀──────────────────────│                       │
      │                       │                       │
```

**Your App's Responsibilities:**
1. Collect credentials from user
2. Forward to LGU-SSO `/auth/login`
3. Store the JWT token securely
4. Create a session for the user

### Flow 2: Token Validation (On Each Request)

```
┌────────────┐          ┌────────────┐          ┌────────────┐
│   Client   │          │  Your App  │          │  LGU-SSO   │
└─────┬──────┘          └─────┬──────┘          └─────┬──────┘
      │                       │                       │
      │ 1. Request + Token    │                       │
      │──────────────────────▶│                       │
      │                       │                       │
      │                       │ 2. POST /sso/validate │
      │                       │   + Client Creds      │
      │                       │──────────────────────▶│
      │                       │                       │
      │                       │ 3. Valid + Employee   │
      │                       │◀──────────────────────│
      │                       │                       │
      │ 4. Response           │                       │
      │◀──────────────────────│                       │
      │                       │                       │
```

**Your App's Responsibilities:**
1. Extract token from request (cookie, header, etc.)
2. Validate with LGU-SSO `/sso/validate`
3. Cache validation result if needed (with short TTL)
4. Proceed or reject based on response

### Flow 3: Authorization Check

```
┌────────────┐          ┌────────────┐          ┌────────────┐
│   Client   │          │  Your App  │          │  LGU-SSO   │
└─────┬──────┘          └─────┬──────┘          └─────┬──────┘
      │                       │                       │
      │ 1. Access protected   │                       │
      │    resource           │                       │
      │──────────────────────▶│                       │
      │                       │                       │
      │                       │ 2. POST /sso/authorize│
      │                       │──────────────────────▶│
      │                       │                       │
      │                       │ 3. authorized: true   │
      │                       │    role: "admin"      │
      │                       │◀──────────────────────│
      │                       │                       │
      │                       │ 4. Check role against │
      │                       │    required permission│
      │                       │                       │
      │ 5. Allow/Deny         │                       │
      │◀──────────────────────│                       │
      │                       │                       │
```

### Flow 4: Single Logout (SLO)

```
┌────────────┐          ┌────────────┐          ┌────────────┐
│   Client   │          │  Your App  │          │  LGU-SSO   │
└─────┬──────┘          └─────┬──────┘          └─────┬──────┘
      │                       │                       │
      │ 1. Logout from all    │                       │
      │──────────────────────▶│                       │
      │                       │                       │
      │                       │ 2. POST /auth/logout-all
      │                       │──────────────────────▶│
      │                       │                       │
      │                       │     (All tokens for   │
      │                       │      this employee    │
      │                       │      are revoked)     │
      │                       │                       │
      │                       │ 3. Success            │
      │                       │◀──────────────────────│
      │                       │                       │
      │ 4. Clear local session│                       │
      │◀──────────────────────│                       │
      │                       │                       │
```

**Important:** When an employee uses `/auth/logout-all`, ALL their tokens across ALL applications are invalidated. Other applications will get `valid: false` on their next validation attempt.

---

## Token Storage Recommendations

### Option 1: HTTP-Only Cookie (Recommended for Web)

```javascript
// After login, set cookie
res.cookie('sso_token', token, {
  httpOnly: true,      // Prevents XSS access
  secure: true,        // HTTPS only
  sameSite: 'strict',  // CSRF protection
  maxAge: 30 * 24 * 60 * 60 * 1000 // 30 days
});
```

**Pros:**
- Protected from XSS attacks
- Automatically sent with requests

**Cons:**
- Requires CSRF protection
- Same-origin policy restrictions

### Option 2: Authorization Header (Recommended for APIs/Mobile)

```javascript
// Store token securely (e.g., encrypted storage)
// Include in requests
fetch('/api/resource', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
```

**Pros:**
- Works across origins
- Standard approach for APIs

**Cons:**
- Must be stored somewhere accessible to JavaScript
- Vulnerable to XSS if stored in localStorage

### Option 3: Secure Storage (Mobile Apps)

- **iOS**: Keychain
- **Android**: EncryptedSharedPreferences or Keystore
- **React Native**: react-native-keychain

---

## Rate Limiting

Each registered application has a configured rate limit (requests per minute). This applies to all endpoints under `/api/v1/sso/*`.

**Default:** 60 requests per minute (configurable per app)

**Response when rate limited (429 Too Many Requests):**
```json
{
  "message": "Too many requests. Please try again later."
}
```

**Headers returned:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
Retry-After: 30
```

---

## Roles

Employees can have different roles per application:

| Role | Value | Typical Use |
|------|-------|-------------|
| Guest | `guest` | Read-only access, limited features |
| Standard | `standard` | Normal user access |
| Administrator | `administrator` | Admin privileges within the app |
| Super Administrator | `super_administrator` | Full system access |

**Note:** Roles are per-application. An employee can be an `administrator` in one app and a `guest` in another.

---

## Security Checklist

- [ ] Store `client_secret` in environment variables, never in code
- [ ] Use HTTPS in production
- [ ] Validate tokens on every request (they're long-lived)
- [ ] Handle logout events properly (clear local sessions)
- [ ] Implement proper CSRF protection if using cookies
- [ ] Check employee roles before allowing sensitive operations
- [ ] Handle rate limiting gracefully (implement backoff)
- [ ] Log authentication events for audit purposes
