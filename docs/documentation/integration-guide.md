# LGU-SSO Integration Guide

This guide provides practical integration patterns and code examples for various tech stacks.

---

## Getting Started Checklist

Before integrating with LGU-SSO, ensure you have:

- [ ] **Client credentials** from the SSO administrator
  - `client_id`
  - `client_secret`
- [ ] **Base URL** of the SSO server
- [ ] **Application registered** in the SSO system
- [ ] **Employee accounts** with access to your application

---

## Environment Configuration

### Environment Variables

```env
# Required
LGU_SSO_BASE_URL=https://sso.lgu.gov.ph
LGU_SSO_CLIENT_ID=your-client-id
LGU_SSO_CLIENT_SECRET=your-client-secret

# Optional
LGU_SSO_TIMEOUT=30000
LGU_SSO_CACHE_TTL=300
```

---

## Integration Patterns

### Pattern A: Your App Has Its Own Login Form

Your application provides a login form, collects credentials, and authenticates via SSO.

```
User ──▶ Your Login Form ──▶ Your Backend ──▶ LGU-SSO
                                    │
                              Store Token
                                    │
                              Create Session
```

**Best for:**
- Custom login UI/UX
- Apps that need to handle credentials
- Backend applications

### Pattern B: Backend-Only Token Validation

Your app receives a token (from URL, header, or storage) and validates it.

```
User (with token) ──▶ Your Backend ──▶ LGU-SSO ──▶ Valid/Invalid
```

**Best for:**
- APIs
- Microservices
- Apps receiving tokens from other SSO-integrated apps

---

## Code Examples by Language

### JavaScript/TypeScript

#### SSO Client Class

```typescript
// lib/sso-client.ts
interface SSOConfig {
  baseUrl: string;
  clientId: string;
  clientSecret: string;
}

interface Employee {
  uuid: string;
  first_name: string;
  middle_name: string | null;
  last_name: string;
  full_name: string;
  email: string;
  is_active: boolean;
}

interface LoginResponse {
  access_token: string;
  token_type: string;
  employee: Employee;
}

interface ValidateResponse {
  valid: boolean;
  data?: Employee;
  message?: string;
}

interface AuthorizeResponse {
  authorized: boolean;
  role?: string;
  employee?: {
    uuid: string;
    full_name: string;
    email: string;
  };
  message?: string;
}

export class SSOClient {
  private config: SSOConfig;

  constructor(config: SSOConfig) {
    this.config = config;
  }

  private getClientHeaders(): Record<string, string> {
    return {
      'Content-Type': 'application/json',
      'X-Client-ID': this.config.clientId,
      'X-Client-Secret': this.config.clientSecret,
    };
  }

  async login(email: string, password: string): Promise<LoginResponse> {
    const response = await fetch(`${this.config.baseUrl}/api/v1/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password }),
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Login failed');
    }

    return response.json();
  }

  async validateToken(token: string): Promise<ValidateResponse> {
    const response = await fetch(`${this.config.baseUrl}/api/v1/sso/validate`, {
      method: 'POST',
      headers: this.getClientHeaders(),
      body: JSON.stringify({ token }),
    });

    return response.json();
  }

  async authorizeToken(token: string): Promise<AuthorizeResponse> {
    const response = await fetch(`${this.config.baseUrl}/api/v1/sso/authorize`, {
      method: 'POST',
      headers: this.getClientHeaders(),
      body: JSON.stringify({ token }),
    });

    return response.json();
  }

  async logout(token: string): Promise<void> {
    await fetch(`${this.config.baseUrl}/api/v1/auth/logout`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
      },
    });
  }

  async logoutAll(token: string): Promise<void> {
    await fetch(`${this.config.baseUrl}/api/v1/auth/logout-all`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
      },
    });
  }
}

// Usage
const sso = new SSOClient({
  baseUrl: process.env.LGU_SSO_BASE_URL!,
  clientId: process.env.LGU_SSO_CLIENT_ID!,
  clientSecret: process.env.LGU_SSO_CLIENT_SECRET!,
});
```

#### Next.js API Route Example

```typescript
// app/api/auth/login/route.ts
import { NextRequest, NextResponse } from 'next/server';
import { cookies } from 'next/headers';

const SSO_BASE_URL = process.env.LGU_SSO_BASE_URL;

export async function POST(request: NextRequest) {
  const { email, password } = await request.json();

  const response = await fetch(`${SSO_BASE_URL}/api/v1/auth/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password }),
  });

  if (!response.ok) {
    const error = await response.json();
    return NextResponse.json(error, { status: response.status });
  }

  const data = await response.json();

  // Set HTTP-only cookie
  cookies().set('sso_token', data.access_token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'strict',
    maxAge: 30 * 24 * 60 * 60, // 30 days
  });

  return NextResponse.json({
    employee: data.employee,
  });
}
```

#### Next.js Middleware Example

```typescript
// middleware.ts
import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

const SSO_BASE_URL = process.env.LGU_SSO_BASE_URL;
const CLIENT_ID = process.env.LGU_SSO_CLIENT_ID;
const CLIENT_SECRET = process.env.LGU_SSO_CLIENT_SECRET;

export async function middleware(request: NextRequest) {
  const token = request.cookies.get('sso_token')?.value;

  if (!token) {
    return NextResponse.redirect(new URL('/login', request.url));
  }

  // Validate token with SSO
  const response = await fetch(`${SSO_BASE_URL}/api/v1/sso/validate`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Client-ID': CLIENT_ID!,
      'X-Client-Secret': CLIENT_SECRET!,
    },
    body: JSON.stringify({ token }),
  });

  const result = await response.json();

  if (!result.valid) {
    // Clear invalid token and redirect to login
    const res = NextResponse.redirect(new URL('/login', request.url));
    res.cookies.delete('sso_token');
    return res;
  }

  return NextResponse.next();
}

export const config = {
  matcher: ['/dashboard/:path*', '/admin/:path*'],
};
```

---

### Python

#### SSO Client Class

```python
# sso_client.py
import os
import requests
from typing import Optional, Dict, Any
from dataclasses import dataclass

@dataclass
class Employee:
    uuid: str
    first_name: str
    middle_name: Optional[str]
    last_name: str
    full_name: str
    email: str
    is_active: bool

class SSOClient:
    def __init__(
        self,
        base_url: str = None,
        client_id: str = None,
        client_secret: str = None
    ):
        self.base_url = base_url or os.environ['LGU_SSO_BASE_URL']
        self.client_id = client_id or os.environ['LGU_SSO_CLIENT_ID']
        self.client_secret = client_secret or os.environ['LGU_SSO_CLIENT_SECRET']

    def _get_client_headers(self) -> Dict[str, str]:
        return {
            'Content-Type': 'application/json',
            'X-Client-ID': self.client_id,
            'X-Client-Secret': self.client_secret,
        }

    def login(self, email: str, password: str) -> Dict[str, Any]:
        """Authenticate an employee and get a JWT token."""
        response = requests.post(
            f"{self.base_url}/api/v1/auth/login",
            json={'email': email, 'password': password}
        )
        response.raise_for_status()
        return response.json()

    def validate_token(self, token: str) -> Dict[str, Any]:
        """Validate a JWT token and get employee data."""
        response = requests.post(
            f"{self.base_url}/api/v1/sso/validate",
            headers=self._get_client_headers(),
            json={'token': token}
        )
        return response.json()

    def authorize_token(self, token: str) -> Dict[str, Any]:
        """Check if employee has access to this application."""
        response = requests.post(
            f"{self.base_url}/api/v1/sso/authorize",
            headers=self._get_client_headers(),
            json={'token': token}
        )
        return response.json()

    def logout(self, token: str) -> None:
        """Logout from current session."""
        requests.post(
            f"{self.base_url}/api/v1/auth/logout",
            headers={'Authorization': f'Bearer {token}'}
        )

    def logout_all(self, token: str) -> None:
        """Logout from all sessions."""
        requests.post(
            f"{self.base_url}/api/v1/auth/logout-all",
            headers={'Authorization': f'Bearer {token}'}
        )


# Usage
sso = SSOClient()

# Login
result = sso.login('employee@lgu.gov.ph', 'password123')
token = result['access_token']

# Validate token
validation = sso.validate_token(token)
if validation['valid']:
    employee = validation['data']
    print(f"Welcome, {employee['full_name']}")
```

#### Flask Example

```python
# app.py
from flask import Flask, request, jsonify, session, redirect
from functools import wraps
from sso_client import SSOClient

app = Flask(__name__)
app.secret_key = 'your-secret-key'
sso = SSOClient()

def require_auth(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        token = session.get('sso_token')
        if not token:
            return redirect('/login')

        result = sso.validate_token(token)
        if not result['valid']:
            session.pop('sso_token', None)
            return redirect('/login')

        request.employee = result['data']
        return f(*args, **kwargs)
    return decorated

@app.route('/login', methods=['POST'])
def login():
    data = request.get_json()
    try:
        result = sso.login(data['email'], data['password'])
        session['sso_token'] = result['access_token']
        return jsonify({'employee': result['employee']})
    except Exception as e:
        return jsonify({'error': str(e)}), 401

@app.route('/dashboard')
@require_auth
def dashboard():
    return jsonify({
        'message': f"Welcome, {request.employee['full_name']}"
    })

@app.route('/logout', methods=['POST'])
def logout():
    token = session.get('sso_token')
    if token:
        sso.logout(token)
        session.pop('sso_token', None)
    return jsonify({'message': 'Logged out'})
```

#### FastAPI Example

```python
# main.py
from fastapi import FastAPI, Depends, HTTPException, Response
from fastapi.security import HTTPBearer
from pydantic import BaseModel
from sso_client import SSOClient

app = FastAPI()
sso = SSOClient()
security = HTTPBearer()

class LoginRequest(BaseModel):
    email: str
    password: str

async def get_current_employee(credentials = Depends(security)):
    token = credentials.credentials
    result = sso.validate_token(token)

    if not result['valid']:
        raise HTTPException(status_code=401, detail="Invalid token")

    return result['data']

@app.post("/login")
async def login(request: LoginRequest, response: Response):
    try:
        result = sso.login(request.email, request.password)
        return {
            "access_token": result['access_token'],
            "employee": result['employee']
        }
    except Exception as e:
        raise HTTPException(status_code=401, detail=str(e))

@app.get("/me")
async def get_me(employee = Depends(get_current_employee)):
    return {"employee": employee}
```

---

### PHP

#### SSO Client Class

```php
<?php
// SSOClient.php

class SSOClient
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;

    public function __construct(
        ?string $baseUrl = null,
        ?string $clientId = null,
        ?string $clientSecret = null
    ) {
        $this->baseUrl = $baseUrl ?? getenv('LGU_SSO_BASE_URL');
        $this->clientId = $clientId ?? getenv('LGU_SSO_CLIENT_ID');
        $this->clientSecret = $clientSecret ?? getenv('LGU_SSO_CLIENT_SECRET');
    }

    private function getClientHeaders(): array
    {
        return [
            'Content-Type: application/json',
            'X-Client-ID: ' . $this->clientId,
            'X-Client-Secret: ' . $this->clientSecret,
        ];
    }

    public function login(string $email, string $password): array
    {
        $response = $this->request(
            'POST',
            '/api/v1/auth/login',
            ['email' => $email, 'password' => $password]
        );

        return $response;
    }

    public function validateToken(string $token): array
    {
        return $this->request(
            'POST',
            '/api/v1/sso/validate',
            ['token' => $token],
            $this->getClientHeaders()
        );
    }

    public function authorizeToken(string $token): array
    {
        return $this->request(
            'POST',
            '/api/v1/sso/authorize',
            ['token' => $token],
            $this->getClientHeaders()
        );
    }

    public function logout(string $token): void
    {
        $this->request(
            'POST',
            '/api/v1/auth/logout',
            [],
            ['Authorization: Bearer ' . $token]
        );
    }

    private function request(
        string $method,
        string $endpoint,
        array $data = [],
        array $headers = ['Content-Type: application/json']
    ): array {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode >= 400) {
            throw new Exception($result['message'] ?? 'Request failed');
        }

        return $result;
    }
}

// Usage
$sso = new SSOClient();

// Login
$result = $sso->login('employee@lgu.gov.ph', 'password123');
$token = $result['access_token'];

// Validate
$validation = $sso->validateToken($token);
if ($validation['valid']) {
    $employee = $validation['data'];
    echo "Welcome, " . $employee['full_name'];
}
```

#### Laravel Integration

```php
<?php
// app/Services/SSOService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SSOService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.sso.base_url');
        $this->clientId = config('services.sso.client_id');
        $this->clientSecret = config('services.sso.client_secret');
    }

    public function login(string $email, string $password): array
    {
        $response = Http::post("{$this->baseUrl}/api/v1/auth/login", [
            'email' => $email,
            'password' => $password,
        ]);

        return $response->json();
    }

    public function validateToken(string $token): array
    {
        $response = Http::withHeaders([
            'X-Client-ID' => $this->clientId,
            'X-Client-Secret' => $this->clientSecret,
        ])->post("{$this->baseUrl}/api/v1/sso/validate", [
            'token' => $token,
        ]);

        return $response->json();
    }

    public function authorizeToken(string $token): array
    {
        $response = Http::withHeaders([
            'X-Client-ID' => $this->clientId,
            'X-Client-Secret' => $this->clientSecret,
        ])->post("{$this->baseUrl}/api/v1/sso/authorize", [
            'token' => $token,
        ]);

        return $response->json();
    }
}
```

```php
<?php
// app/Http/Middleware/ValidateSSOToken.php

namespace App\Http\Middleware;

use App\Services\SSOService;
use Closure;
use Illuminate\Http\Request;

class ValidateSSOToken
{
    public function __construct(private SSOService $sso) {}

    public function handle(Request $request, Closure $next)
    {
        $token = $request->cookie('sso_token')
            ?? $request->bearerToken();

        if (!$token) {
            return redirect('/login');
        }

        $result = $this->sso->validateToken($token);

        if (!$result['valid']) {
            return redirect('/login')->withCookie(
                cookie()->forget('sso_token')
            );
        }

        $request->merge(['employee' => $result['data']]);

        return $next($request);
    }
}
```

---

### Go

```go
// sso/client.go
package sso

import (
    "bytes"
    "encoding/json"
    "fmt"
    "net/http"
    "os"
)

type Client struct {
    BaseURL      string
    ClientID     string
    ClientSecret string
    HTTPClient   *http.Client
}

type Employee struct {
    UUID      string  `json:"uuid"`
    FirstName string  `json:"first_name"`
    LastName  string  `json:"last_name"`
    FullName  string  `json:"full_name"`
    Email     string  `json:"email"`
    IsActive  bool    `json:"is_active"`
}

type LoginResponse struct {
    AccessToken string   `json:"access_token"`
    TokenType   string   `json:"token_type"`
    Employee    Employee `json:"employee"`
}

type ValidateResponse struct {
    Valid   bool     `json:"valid"`
    Data    Employee `json:"data,omitempty"`
    Message string   `json:"message,omitempty"`
}

func NewClient() *Client {
    return &Client{
        BaseURL:      os.Getenv("LGU_SSO_BASE_URL"),
        ClientID:     os.Getenv("LGU_SSO_CLIENT_ID"),
        ClientSecret: os.Getenv("LGU_SSO_CLIENT_SECRET"),
        HTTPClient:   &http.Client{},
    }
}

func (c *Client) Login(email, password string) (*LoginResponse, error) {
    body, _ := json.Marshal(map[string]string{
        "email":    email,
        "password": password,
    })

    req, _ := http.NewRequest(
        "POST",
        fmt.Sprintf("%s/api/v1/auth/login", c.BaseURL),
        bytes.NewBuffer(body),
    )
    req.Header.Set("Content-Type", "application/json")

    resp, err := c.HTTPClient.Do(req)
    if err != nil {
        return nil, err
    }
    defer resp.Body.Close()

    var result LoginResponse
    json.NewDecoder(resp.Body).Decode(&result)
    return &result, nil
}

func (c *Client) ValidateToken(token string) (*ValidateResponse, error) {
    body, _ := json.Marshal(map[string]string{"token": token})

    req, _ := http.NewRequest(
        "POST",
        fmt.Sprintf("%s/api/v1/sso/validate", c.BaseURL),
        bytes.NewBuffer(body),
    )
    req.Header.Set("Content-Type", "application/json")
    req.Header.Set("X-Client-ID", c.ClientID)
    req.Header.Set("X-Client-Secret", c.ClientSecret)

    resp, err := c.HTTPClient.Do(req)
    if err != nil {
        return nil, err
    }
    defer resp.Body.Close()

    var result ValidateResponse
    json.NewDecoder(resp.Body).Decode(&result)
    return &result, nil
}
```

---

## Caching Token Validation

For performance, you may want to cache validation results. Be careful with cache TTL since tokens can be revoked at any time.

### Recommended Cache Strategy

```typescript
// Pseudo-code
async function validateWithCache(token: string): Promise<ValidateResponse> {
  const cacheKey = `sso:token:${hash(token)}`;

  // Check cache first
  const cached = await cache.get(cacheKey);
  if (cached) {
    return JSON.parse(cached);
  }

  // Validate with SSO
  const result = await sso.validateToken(token);

  // Only cache valid tokens, with short TTL
  if (result.valid) {
    await cache.set(cacheKey, JSON.stringify(result), {
      ttl: 60, // 1 minute max
    });
  }

  return result;
}
```

**Important:** Keep cache TTL very short (1-5 minutes) to handle token revocation.

---

## Testing Integration

### Test Credentials

Contact your SSO administrator for test credentials:
- Test employee accounts
- Test application credentials
- Development/staging SSO URL

### Testing Checklist

- [ ] Login works with valid credentials
- [ ] Login fails with invalid credentials
- [ ] Token validation returns employee data
- [ ] Token validation fails for invalid tokens
- [ ] Authorization check returns correct role
- [ ] Logout invalidates the token
- [ ] Rate limiting is handled gracefully
