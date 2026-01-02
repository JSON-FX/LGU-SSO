# LGU-SSO API Reference

Base URL: `{LGU_SSO_BASE_URL}/api/v1/`

---

## Authentication Endpoints

### POST /auth/login

Authenticate an employee and receive a JWT token.

**Authentication Required:** None

**Request:**
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "employee@lgu.gov.ph",
  "password": "password123"
}
```

**Response (200 OK):**
```json
{
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "bearer",
  "employee": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "first_name": "Juan",
    "middle_name": "Santos",
    "last_name": "Dela Cruz",
    "suffix": null,
    "full_name": "Juan Santos Dela Cruz",
    "initials": "J.S.D",
    "birthday": "1990-05-15",
    "age": 35,
    "civil_status": "married",
    "email": "employee@lgu.gov.ph",
    "is_active": true,
    "nationality": "Filipino",
    "residence": "123 Main Street",
    "block_number": null,
    "building_floor": null,
    "house_number": "123",
    "province": { "code": "0300000000", "name": "Central Luzon" },
    "city": { "code": "0306900000", "name": "San Fernando" },
    "barangay": { "code": "0306901001", "name": "Alasas" },
    "office": {
      "id": 1,
      "name": "Municipal Budget Office",
      "abbreviation": "MBO"
    },
    "position": "Budget Analyst",
    "date_employed": "2020-03-15",
    "date_terminated": null,
    "created_at": "2024-01-15T08:30:00+00:00",
    "updated_at": "2024-06-20T14:45:00+00:00"
  }
}
```

**Response (401 Unauthorized):**
```json
{
  "message": "Invalid credentials."
}
```

**cURL Example:**
```bash
curl -X POST "{BASE_URL}/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email": "employee@lgu.gov.ph", "password": "password123"}'
```

---

### POST /auth/logout

Revoke the current token.

**Authentication Required:** JWT Bearer Token

**Request:**
```http
POST /api/v1/auth/logout
Authorization: Bearer {jwt_token}
```

**Response (200 OK):**
```json
{
  "message": "Successfully logged out."
}
```

**cURL Example:**
```bash
curl -X POST "{BASE_URL}/api/v1/auth/logout" \
  -H "Authorization: Bearer {jwt_token}"
```

---

### POST /auth/logout-all

Revoke ALL tokens for the authenticated employee (Single Logout).

**Authentication Required:** JWT Bearer Token

**Request:**
```http
POST /api/v1/auth/logout-all
Authorization: Bearer {jwt_token}
```

**Response (200 OK):**
```json
{
  "message": "Successfully logged out from all sessions."
}
```

**cURL Example:**
```bash
curl -X POST "{BASE_URL}/api/v1/auth/logout-all" \
  -H "Authorization: Bearer {jwt_token}"
```

---

### POST /auth/refresh

Refresh the current token and get a new one.

**Authentication Required:** JWT Bearer Token

**Request:**
```http
POST /api/v1/auth/refresh
Authorization: Bearer {jwt_token}
```

**Response (200 OK):**
```json
{
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "bearer"
}
```

**cURL Example:**
```bash
curl -X POST "{BASE_URL}/api/v1/auth/refresh" \
  -H "Authorization: Bearer {jwt_token}"
```

---

### GET /auth/me

Get the authenticated employee's profile.

**Authentication Required:** JWT Bearer Token

**Request:**
```http
GET /api/v1/auth/me
Authorization: Bearer {jwt_token}
```

**Response (200 OK):**
```json
{
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "first_name": "Juan",
    "middle_name": "Santos",
    "last_name": "Dela Cruz",
    "suffix": null,
    "full_name": "Juan Santos Dela Cruz",
    "initials": "J.S.D",
    "birthday": "1990-05-15",
    "age": 35,
    "civil_status": "married",
    "email": "employee@lgu.gov.ph",
    "is_active": true,
    "nationality": "Filipino",
    "residence": "123 Main Street",
    "province": { "code": "0300000000", "name": "Central Luzon" },
    "city": { "code": "0306900000", "name": "San Fernando" },
    "barangay": { "code": "0306901001", "name": "Alasas" },
    "office": {
      "id": 1,
      "name": "Municipal Budget Office",
      "abbreviation": "MBO"
    },
    "position": "Budget Analyst",
    "date_employed": "2020-03-15",
    "date_terminated": null,
    "applications": [
      {
        "uuid": "app-uuid-1",
        "name": "HR Management System",
        "role": "administrator"
      }
    ],
    "created_at": "2024-01-15T08:30:00+00:00",
    "updated_at": "2024-06-20T14:45:00+00:00"
  }
}
```

**cURL Example:**
```bash
curl -X GET "{BASE_URL}/api/v1/auth/me" \
  -H "Authorization: Bearer {jwt_token}"
```

---

## SSO Endpoints (Client Applications)

These endpoints are used by client applications to validate tokens and check authorization.

**Required Headers for all SSO endpoints:**
```
X-Client-ID: {your_client_id}
X-Client-Secret: {your_client_secret}
```

---

### POST /sso/validate

Validate a JWT token and get employee data.

**Authentication Required:** Client Credentials (headers)

**Request:**
```http
POST /api/v1/sso/validate
X-Client-ID: {client_id}
X-Client-Secret: {client_secret}
Content-Type: application/json

{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Response (200 OK - Valid Token):**
```json
{
  "valid": true,
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "first_name": "Juan",
    "middle_name": "Santos",
    "last_name": "Dela Cruz",
    "full_name": "Juan Santos Dela Cruz",
    "email": "employee@lgu.gov.ph",
    "is_active": true,
    "province": { "code": "0300000000", "name": "Central Luzon" },
    "city": { "code": "0306900000", "name": "San Fernando" },
    "barangay": { "code": "0306901001", "name": "Alasas" }
  }
}
```

**Response (401 Unauthorized - Invalid Token):**
```json
{
  "valid": false,
  "message": "Invalid token."
}
```

**Response (401 Unauthorized - Inactive Employee):**
```json
{
  "valid": false,
  "message": "Invalid or inactive employee."
}
```

**Response (400 Bad Request - Missing Token):**
```json
{
  "valid": false,
  "message": "Token is required."
}
```

**cURL Example:**
```bash
curl -X POST "{BASE_URL}/api/v1/sso/validate" \
  -H "Content-Type: application/json" \
  -H "X-Client-ID: your-client-id" \
  -H "X-Client-Secret: your-client-secret" \
  -d '{"token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."}'
```

---

### POST /sso/authorize

Check if an employee has access to your application and get their role.

**Authentication Required:** Client Credentials (headers)

**Request:**
```http
POST /api/v1/sso/authorize
X-Client-ID: {client_id}
X-Client-Secret: {client_secret}
Content-Type: application/json

{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Response (200 OK - Authorized):**
```json
{
  "authorized": true,
  "role": "standard",
  "employee": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "full_name": "Juan Santos Dela Cruz",
    "email": "employee@lgu.gov.ph"
  }
}
```

**Response (403 Forbidden - No Access):**
```json
{
  "authorized": false,
  "message": "Employee does not have access to this application."
}
```

**Response (401 Unauthorized - Invalid Token):**
```json
{
  "authorized": false,
  "message": "Invalid token."
}
```

**cURL Example:**
```bash
curl -X POST "{BASE_URL}/api/v1/sso/authorize" \
  -H "Content-Type: application/json" \
  -H "X-Client-ID: your-client-id" \
  -H "X-Client-Secret: your-client-secret" \
  -d '{"token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."}'
```

---

### GET /sso/employee

Get the authenticated employee's profile with their role for your application.

**Authentication Required:** Client Credentials (headers) + JWT Bearer Token

**Request:**
```http
GET /api/v1/sso/employee
X-Client-ID: {client_id}
X-Client-Secret: {client_secret}
Authorization: Bearer {jwt_token}
```

**Response (200 OK):**
```json
{
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "first_name": "Juan",
    "last_name": "Dela Cruz",
    "full_name": "Juan Santos Dela Cruz",
    "email": "employee@lgu.gov.ph",
    "province": { "code": "0300000000", "name": "Central Luzon" },
    "city": { "code": "0306900000", "name": "San Fernando" },
    "barangay": { "code": "0306901001", "name": "Alasas" }
  },
  "role": "administrator"
}
```

**cURL Example:**
```bash
curl -X GET "{BASE_URL}/api/v1/sso/employee" \
  -H "X-Client-ID: your-client-id" \
  -H "X-Client-Secret: your-client-secret" \
  -H "Authorization: Bearer {jwt_token}"
```

---

## Employee Management Endpoints (Admin)

These endpoints require JWT authentication and are typically used by admin applications.

---

### GET /employees

List all employees (paginated).

**Authentication Required:** JWT Bearer Token

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `page` | integer | Page number (default: 1) |
| `per_page` | integer | Items per page (default: 15) |

**Request:**
```http
GET /api/v1/employees?page=1&per_page=15
Authorization: Bearer {jwt_token}
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "first_name": "Juan",
      "last_name": "Dela Cruz",
      "email": "juan@lgu.gov.ph",
      "is_active": true
    }
  ],
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 75
  }
}
```

---

### POST /employees

Create a new employee.

**Authentication Required:** JWT Bearer Token

**Request:**
```http
POST /api/v1/employees
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "first_name": "Maria",
  "middle_name": "Santos",
  "last_name": "Garcia",
  "suffix": null,
  "birthday": "1995-03-20",
  "civil_status": "single",
  "province_code": "0300000000",
  "city_code": "0306900000",
  "barangay_code": "0306901001",
  "residence": "456 Second Street",
  "nationality": "Filipino",
  "email": "maria@lgu.gov.ph",
  "password": "securepassword123",
  "office_id": 1,
  "position": "Budget Analyst",
  "date_employed": "2024-01-15",
  "date_terminated": null
}
```

**Required Fields:**
- `first_name`, `last_name`, `birthday`, `civil_status`, `residence`, `nationality`, `email`, `password`, `position`

**Optional Fields:**
- `middle_name`, `suffix`, `province_code`, `city_code`, `barangay_code`, `block_number`, `building_floor`, `house_number`, `office_id`, `date_employed`, `date_terminated`

**Response (201 Created):**
```json
{
  "message": "Employee created successfully.",
  "data": {
    "uuid": "new-employee-uuid",
    "first_name": "Maria",
    "last_name": "Garcia",
    "email": "maria@lgu.gov.ph",
    "is_active": true,
    "position": "Budget Analyst",
    "office": {
      "id": 1,
      "name": "Municipal Budget Office",
      "abbreviation": "MBO"
    },
    "date_employed": "2024-01-15",
    "date_terminated": null
  }
}
```

---

### GET /employees/{uuid}

Get a specific employee.

**Authentication Required:** JWT Bearer Token

**Request:**
```http
GET /api/v1/employees/550e8400-e29b-41d4-a716-446655440000
Authorization: Bearer {jwt_token}
```

**Response (200 OK):**
```json
{
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "first_name": "Juan",
    "middle_name": "Santos",
    "last_name": "Dela Cruz",
    "full_name": "Juan Santos Dela Cruz",
    "email": "juan@lgu.gov.ph",
    "is_active": true,
    "office": {
      "id": 1,
      "name": "Municipal Budget Office",
      "abbreviation": "MBO"
    },
    "position": "Budget Analyst",
    "date_employed": "2020-03-15",
    "date_terminated": null
  }
}
```

---

### PUT /employees/{uuid}

Update an employee.

**Authentication Required:** JWT Bearer Token

**Request:**
```http
PUT /api/v1/employees/550e8400-e29b-41d4-a716-446655440000
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "first_name": "Juan Carlos",
  "civil_status": "married",
  "office_id": 2,
  "position": "Senior Budget Analyst"
}
```

**Response (200 OK):**
```json
{
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "first_name": "Juan Carlos",
    "civil_status": "married"
  }
}
```

---

### DELETE /employees/{uuid}

Soft delete an employee.

**Authentication Required:** JWT Bearer Token

**Request:**
```http
DELETE /api/v1/employees/550e8400-e29b-41d4-a716-446655440000
Authorization: Bearer {jwt_token}
```

**Response (204 No Content)**

---

### GET /employees/{uuid}/applications

List applications an employee has access to.

**Authentication Required:** JWT Bearer Token

**Request:**
```http
GET /api/v1/employees/550e8400-e29b-41d4-a716-446655440000/applications
Authorization: Bearer {jwt_token}
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "uuid": "app-uuid-1",
      "name": "HR Management System",
      "role": "administrator"
    },
    {
      "uuid": "app-uuid-2",
      "name": "Document Tracking System",
      "role": "standard"
    }
  ]
}
```

---

### POST /employees/{uuid}/applications

Grant an employee access to an application.

**Authentication Required:** JWT Bearer Token

**Request:**
```http
POST /api/v1/employees/550e8400-e29b-41d4-a716-446655440000/applications
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "application_uuid": "app-uuid-1",
  "role": "standard"
}
```

**Roles:** `guest`, `standard`, `administrator`, `super_administrator`

**Response (201 Created):**
```json
{
  "message": "Access granted.",
  "data": {
    "application_uuid": "app-uuid-1",
    "role": "standard"
  }
}
```

---

### PUT /employees/{uuid}/applications/{app_uuid}

Update an employee's role for an application.

**Authentication Required:** JWT Bearer Token

**Request:**
```http
PUT /api/v1/employees/550e8400-e29b-41d4-a716-446655440000/applications/app-uuid-1
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "role": "administrator"
}
```

**Response (200 OK):**
```json
{
  "message": "Access updated.",
  "data": {
    "role": "administrator"
  }
}
```

---

### DELETE /employees/{uuid}/applications/{app_uuid}

Revoke an employee's access to an application.

**Authentication Required:** JWT Bearer Token

**Request:**
```http
DELETE /api/v1/employees/550e8400-e29b-41d4-a716-446655440000/applications/app-uuid-1
Authorization: Bearer {jwt_token}
```

**Response (200 OK):**
```json
{
  "message": "Access revoked."
}
```

---

## Application Management Endpoints (Admin)

---

### GET /applications

List all registered applications.

**Authentication Required:** JWT Bearer Token

**Request:**
```http
GET /api/v1/applications
Authorization: Bearer {jwt_token}
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "uuid": "app-uuid-1",
      "name": "HR Management System",
      "description": "Human Resources application",
      "client_id": "hrms-client-xxxx",
      "redirect_uris": ["http://hrms.lgu.gov.ph/callback"],
      "rate_limit_per_minute": 60,
      "is_active": true
    }
  ]
}
```

---

### POST /applications

Register a new application.

**Authentication Required:** JWT Bearer Token

**Request:**
```http
POST /api/v1/applications
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "name": "New Application",
  "description": "Description of the application",
  "redirect_uris": ["http://newapp.lgu.gov.ph/callback"],
  "rate_limit_per_minute": 60
}
```

**Response (201 Created):**
```json
{
  "data": {
    "uuid": "new-app-uuid",
    "name": "New Application",
    "client_id": "generated-client-id",
    "client_secret": "plain-text-secret-only-shown-once"
  }
}
```

> **Important:** The `client_secret` is only returned in plain text once during creation. Store it securely.

---

### POST /applications/{uuid}/regenerate-secret

Generate a new client secret for an application.

**Authentication Required:** JWT Bearer Token

**Request:**
```http
POST /api/v1/applications/app-uuid-1/regenerate-secret
Authorization: Bearer {jwt_token}
```

**Response (200 OK):**
```json
{
  "client_secret": "new-plain-text-secret"
}
```

---

## Location Endpoints (Public)

These endpoints are public and do not require authentication.

---

### GET /locations/provinces

List all provinces.

**Request:**
```http
GET /api/v1/locations/provinces
```

**Response (200 OK):**
```json
{
  "data": [
    { "code": "0100000000", "name": "Ilocos Region" },
    { "code": "0200000000", "name": "Cagayan Valley" },
    { "code": "0300000000", "name": "Central Luzon" }
  ]
}
```

---

### GET /locations/provinces/{code}/cities

List cities in a province.

**Request:**
```http
GET /api/v1/locations/provinces/0300000000/cities
```

**Response (200 OK):**
```json
{
  "data": [
    { "code": "0306900000", "name": "San Fernando" },
    { "code": "0307000000", "name": "Angeles" }
  ]
}
```

---

### GET /locations/cities/{code}/barangays

List barangays in a city.

**Request:**
```http
GET /api/v1/locations/cities/0306900000/barangays
```

**Response (200 OK):**
```json
{
  "data": [
    { "code": "0306901001", "name": "Alasas" },
    { "code": "0306901002", "name": "Bulaon" }
  ]
}
```

---

## Audit Log Endpoints (Admin)

---

### GET /audit/logs

List audit logs with optional filtering.

**Authentication Required:** JWT Bearer Token

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `action` | string | Filter by action type |
| `employee_uuid` | string | Filter by employee |
| `application_uuid` | string | Filter by application |
| `from` | date | Start date (YYYY-MM-DD) |
| `to` | date | End date (YYYY-MM-DD) |

**Request:**
```http
GET /api/v1/audit/logs?action=login&from=2024-01-01
Authorization: Bearer {jwt_token}
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "action": "login",
      "employee": {
        "uuid": "...",
        "full_name": "Juan Dela Cruz"
      },
      "application": null,
      "ip_address": "192.168.1.1",
      "user_agent": "Mozilla/5.0...",
      "metadata": {},
      "created_at": "2024-01-15T08:30:00+00:00"
    }
  ]
}
```

**Audit Actions:**
- `login` - Employee logged in
- `logout` - Employee logged out (single session)
- `logout_all` - Employee logged out from all sessions
- `token_refresh` - Token was refreshed
- `token_validate` - Token was validated by client app
- `app_authorize` - Employee authorization was checked
