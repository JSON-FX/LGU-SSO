# LGU-SSO Error Handling Guide

This document covers error codes, response formats, and troubleshooting tips for LGU-SSO integration.

---

## Error Response Format

All error responses follow a consistent JSON format:

```json
{
  "message": "Human-readable error description"
}
```

For validation errors:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

---

## HTTP Status Codes

| Code | Status | Description |
|------|--------|-------------|
| 200 | OK | Request succeeded |
| 201 | Created | Resource created successfully |
| 204 | No Content | Request succeeded, no response body |
| 400 | Bad Request | Invalid request format or missing parameters |
| 401 | Unauthorized | Authentication failed or missing |
| 403 | Forbidden | Authenticated but not authorized |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server-side error |

---

## Common Errors and Solutions

### 401 Unauthorized

#### "Invalid credentials."

**Cause:** Email or password is incorrect during login.

**Solution:**
- Verify the email and password are correct
- Check if the employee account exists
- Ensure the employee account is active

```json
{
  "message": "Invalid credentials."
}
```

#### "Invalid token."

**Cause:** The JWT token is malformed or has been tampered with.

**Solution:**
- Ensure the token is being transmitted correctly
- Check for token truncation
- Verify the token hasn't been modified

```json
{
  "valid": false,
  "message": "Invalid token."
}
```

#### "Invalid or inactive employee."

**Cause:** The token is valid but the employee account has been deactivated.

**Solution:**
- Contact SSO administrator to reactivate the account
- Clear cached tokens and re-authenticate

```json
{
  "valid": false,
  "message": "Invalid or inactive employee."
}
```

#### "Missing client credentials."

**Cause:** `X-Client-ID` or `X-Client-Secret` headers are missing.

**Solution:**
- Ensure both headers are included in SSO endpoint requests
- Check environment variables are loaded correctly

```json
{
  "message": "Missing client credentials."
}
```

#### "Invalid client credentials."

**Cause:** Client ID doesn't exist or secret is wrong.

**Solution:**
- Verify client credentials with SSO administrator
- Check for typos in credentials
- Ensure application is registered and active

```json
{
  "message": "Invalid client credentials."
}
```

---

### 403 Forbidden

#### "Employee does not have access to this application."

**Cause:** The employee's token is valid, but they haven't been granted access to your application.

**Solution:**
- Request access from SSO administrator
- Verify the employee should have access to your app

```json
{
  "authorized": false,
  "message": "Employee does not have access to this application."
}
```

---

### 400 Bad Request

#### "Token is required."

**Cause:** The `token` field is missing from the request body.

**Solution:**
- Include the token in the request body: `{"token": "..."}`
- Or send as Bearer token in Authorization header

```json
{
  "valid": false,
  "message": "Token is required."
}
```

---

### 422 Unprocessable Entity

#### Validation Errors

**Cause:** Request data failed validation.

**Solution:**
- Check the `errors` object for specific field issues
- Fix the data and retry

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

Common validation error messages:

| Field | Error | Meaning |
|-------|-------|---------|
| `email` | "The email field is required." | Email not provided |
| `email` | "The email must be a valid email address." | Invalid email format |
| `password` | "The password field is required." | Password not provided |
| `token` | "The token field is required." | Token not provided |
| `role` | "The selected role is invalid." | Invalid role value |

---

### 429 Too Many Requests

#### Rate Limit Exceeded

**Cause:** Your application has exceeded its rate limit.

**Solution:**
- Implement request throttling
- Cache responses where possible
- Wait for the rate limit window to reset
- Contact SSO administrator to increase limit if needed

```json
{
  "message": "Too many requests. Please try again later."
}
```

**Rate Limit Headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
Retry-After: 30
```

**Implementation:**

```typescript
async function fetchWithRetry(url: string, options: RequestInit, retries = 3) {
  for (let i = 0; i < retries; i++) {
    const response = await fetch(url, options);

    if (response.status === 429) {
      const retryAfter = response.headers.get('Retry-After') || '30';
      await sleep(parseInt(retryAfter) * 1000);
      continue;
    }

    return response;
  }
  throw new Error('Rate limit exceeded after retries');
}
```

---

### 404 Not Found

#### "Resource not found."

**Cause:** The requested employee, application, or other resource doesn't exist.

**Solution:**
- Verify the UUID is correct
- Check if the resource was deleted
- Ensure you're using UUIDs, not internal IDs

```json
{
  "message": "Resource not found."
}
```

---

### 500 Internal Server Error

#### Server Error

**Cause:** An unexpected error occurred on the SSO server.

**Solution:**
- Retry the request after a short delay
- If persists, contact SSO administrator
- Check server logs (if accessible)

```json
{
  "message": "Server error."
}
```

---

## Error Handling Best Practices

### 1. Handle All Status Codes

```typescript
async function handleSSORequest(response: Response) {
  switch (response.status) {
    case 200:
    case 201:
      return await response.json();

    case 401:
      // Clear local session, redirect to login
      clearSession();
      redirectToLogin();
      throw new Error('Authentication failed');

    case 403:
      // User doesn't have access
      throw new Error('Access denied');

    case 422:
      // Validation errors
      const errors = await response.json();
      throw new ValidationError(errors);

    case 429:
      // Rate limited
      const retryAfter = response.headers.get('Retry-After');
      throw new RateLimitError(retryAfter);

    default:
      throw new Error('Unexpected error');
  }
}
```

### 2. Implement Retry Logic

```typescript
async function fetchWithRetry(
  url: string,
  options: RequestInit,
  maxRetries = 3
): Promise<Response> {
  let lastError: Error | null = null;

  for (let attempt = 0; attempt < maxRetries; attempt++) {
    try {
      const response = await fetch(url, options);

      // Don't retry client errors (4xx except 429)
      if (response.status >= 400 && response.status < 500 && response.status !== 429) {
        return response;
      }

      // Retry on 429 or 5xx
      if (response.status === 429 || response.status >= 500) {
        const delay = Math.pow(2, attempt) * 1000; // Exponential backoff
        await sleep(delay);
        continue;
      }

      return response;
    } catch (error) {
      lastError = error as Error;
      const delay = Math.pow(2, attempt) * 1000;
      await sleep(delay);
    }
  }

  throw lastError || new Error('Request failed after retries');
}
```

### 3. Log Errors Properly

```typescript
function logSSOError(error: Error, context: object) {
  console.error('SSO Error:', {
    message: error.message,
    timestamp: new Date().toISOString(),
    ...context,
  });

  // Send to your error tracking service
  errorTracker.capture(error, context);
}
```

### 4. User-Friendly Error Messages

Map technical errors to user-friendly messages:

```typescript
const ERROR_MESSAGES: Record<string, string> = {
  'Invalid credentials.': 'Email or password is incorrect.',
  'Invalid token.': 'Your session has expired. Please log in again.',
  'Invalid or inactive employee.': 'Your account has been deactivated. Contact support.',
  'Missing client credentials.': 'Application configuration error. Contact support.',
  'Invalid client credentials.': 'Application configuration error. Contact support.',
  'Employee does not have access to this application.': 'You do not have permission to access this application.',
  'Too many requests. Please try again later.': 'Too many requests. Please wait a moment and try again.',
};

function getUserFriendlyMessage(errorMessage: string): string {
  return ERROR_MESSAGES[errorMessage] || 'An unexpected error occurred. Please try again.';
}
```

---

## Debugging Tips

### 1. Check Request Headers

Ensure all required headers are present:

```bash
# For SSO endpoints
curl -v -X POST "{BASE_URL}/api/v1/sso/validate" \
  -H "Content-Type: application/json" \
  -H "X-Client-ID: your-client-id" \
  -H "X-Client-Secret: your-client-secret" \
  -d '{"token": "..."}'
```

### 2. Validate JWT Token Format

JWT tokens have three parts separated by dots:

```
eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.dozjgNryP4J3jVmNHl0w5N_XgL0n3I9PlFUP0THsR8U
│────────────────────────────────────│─────────────────────────────│──────────────────────────────────────────────────│
          Header (Base64)                    Payload (Base64)                      Signature (Base64)
```

### 3. Test Token Manually

```bash
# Decode JWT payload (without verification)
echo "eyJzdWIiOiIxMjM0NTY3ODkwIn0" | base64 -d
```

### 4. Check Token Revocation

If a token suddenly stops working:
- The employee may have logged out from all sessions
- The token may have been refreshed (old one revoked)
- The employee account may have been deactivated

### 5. Verify Environment Variables

```bash
# Check if variables are set
echo $LGU_SSO_BASE_URL
echo $LGU_SSO_CLIENT_ID
echo $LGU_SSO_CLIENT_SECRET
```

### 6. Test with cURL First

Before implementing in code, test with cURL to isolate issues:

```bash
# Test login
curl -X POST "{BASE_URL}/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email": "test@lgu.gov.ph", "password": "password123"}'

# Test token validation
curl -X POST "{BASE_URL}/api/v1/sso/validate" \
  -H "Content-Type: application/json" \
  -H "X-Client-ID: your-client-id" \
  -H "X-Client-Secret: your-client-secret" \
  -d '{"token": "your-jwt-token"}'
```

---

## Getting Help

If you're still experiencing issues:

1. **Check the API Reference** - [api-reference.md](./api-reference.md)
2. **Review Authentication Guide** - [authentication.md](./authentication.md)
3. **Contact SSO Administrator** - For credential issues or account problems
4. **Check Server Status** - Verify the SSO server is operational
