# LGU-SSO Backend Implementation Plan

## Project Overview

API-only SSO backend for managing employee authentication across multiple internal LGU applications.

## Confirmed Requirements

| Area | Decision |
|------|----------|
| Auth Protocol | OAuth 2.0 + JWT (tymon/jwt-auth) |
| Token Storage | Database |
| Token Expiry | Long-lived (until revoked) |
| Role Scope | Per-app roles |
| SLO Behavior | Revoke all sessions |
| Audit Logs | Full audit trail |
| PH Locations | PSGC Database |
| API Versioning | URL path (/api/v1/) |
| User Removal | Soft delete |
| Rate Limiting | Per-app limits |
| API Docs | Scramble |
| App Auth | Client ID + Secret |

---

## Database Schema

### employees
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| uuid | uuid | Unique identifier for API |
| first_name | string | Required |
| middle_name | string | Nullable |
| last_name | string | Required |
| suffix | string | Nullable |
| birthday | date | Required |
| civil_status | enum | single, married, widowed, separated, divorced |
| province_code | string | FK to psgc_provinces |
| city_code | string | FK to psgc_cities |
| barangay_code | string | FK to psgc_barangays |
| residence | string | Street address |
| block_number | string | Nullable |
| building_floor | string | Nullable |
| house_number | string | Nullable |
| nationality | string | Required |
| email | string | Unique, required |
| password | string | Hashed |
| is_active | boolean | Default true |
| timestamps | | created_at, updated_at |
| deleted_at | timestamp | Soft delete |

**Computed Accessors:**
- `initials` - First letter of each name part (e.g., "J.C.D")
- `age` - Calculated from birthday

### applications
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| uuid | uuid | Unique identifier for API |
| name | string | Required |
| description | text | Nullable |
| client_id | string | Unique, auto-generated |
| client_secret | string | Hashed |
| redirect_uris | json | Array of allowed URIs |
| rate_limit_per_minute | int | Default 60 |
| is_active | boolean | Default true |
| timestamps | | |
| deleted_at | timestamp | Soft delete |

### employee_application (pivot)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| employee_id | bigint | FK |
| application_id | bigint | FK |
| role | enum | guest, standard, administrator, super_administrator |
| timestamps | | |

### oauth_tokens
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| uuid | uuid | Unique identifier |
| employee_id | bigint | FK |
| application_id | bigint | FK, nullable |
| access_token | string | Hashed |
| revoked_at | timestamp | Nullable |
| last_used_at | timestamp | Nullable |
| timestamps | | |

### audit_logs
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| employee_id | bigint | FK, nullable |
| application_id | bigint | FK, nullable |
| action | string | login, logout, token_refresh, etc. |
| ip_address | string | |
| user_agent | string | |
| metadata | json | Additional context |
| created_at | timestamp | |

### psgc_provinces
| Column | Type | Notes |
|--------|------|-------|
| code | string | PK (PSGC code) |
| name | string | Province name |
| region_code | string | Region identifier |

### psgc_cities
| Column | Type | Notes |
|--------|------|-------|
| code | string | PK (PSGC code) |
| name | string | City/Municipality name |
| province_code | string | FK |

### psgc_barangays
| Column | Type | Notes |
|--------|------|-------|
| code | string | PK (PSGC code) |
| name | string | Barangay name |
| city_code | string | FK |

---

## API Endpoints

### Authentication `/api/v1/auth`
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /login | Authenticate, return JWT |
| POST | /logout | Revoke current token |
| POST | /logout-all | Revoke ALL tokens (SLO) |
| POST | /refresh | Refresh access token |
| GET | /me | Get authenticated employee |

### Employees `/api/v1/employees`
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | / | List employees (paginated) |
| POST | / | Create employee |
| GET | /{uuid} | Get employee |
| PUT | /{uuid} | Update employee |
| DELETE | /{uuid} | Soft delete |
| GET | /{uuid}/applications | List app access |
| POST | /{uuid}/applications | Grant app access |
| PUT | /{uuid}/applications/{appUuid} | Update role |
| DELETE | /{uuid}/applications/{appUuid} | Revoke access |

### Applications `/api/v1/applications`
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | / | List applications |
| POST | / | Register application |
| GET | /{uuid} | Get application |
| PUT | /{uuid} | Update application |
| DELETE | /{uuid} | Soft delete |
| POST | /{uuid}/regenerate-secret | New client_secret |

### SSO `/api/v1/sso`
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /validate | Validate token, return employee |
| POST | /authorize | Check app access + role |
| GET | /employee | Get employee for current app |

### Locations `/api/v1/locations`
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /provinces | List provinces |
| GET | /provinces/{code}/cities | Cities in province |
| GET | /cities/{code}/barangays | Barangays in city |

### Audit `/api/v1/audit`
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /logs | List logs (filtered) |
| GET | /employees/{uuid}/logs | Employee logs |
| GET | /applications/{uuid}/logs | App logs |

---

## Implementation Phases

### Phase 1: Foundation
1. Install packages (tymon/jwt-auth, dedoc/scramble)
2. Create PSGC migrations and seeder
3. Create core migrations (employees, applications, tokens, audit)

### Phase 2: Models
1. Create Employee model with accessors (initials, age)
2. Create Application model with credential generation
3. Create OAuthToken model
4. Create AuditLog model
5. Create PSGC models (Province, City, Barangay)
6. Define relationships

### Phase 3: Middleware
1. JWT validation middleware
2. App credentials validation middleware
3. Per-app rate limiting middleware
4. Audit logging middleware

### Phase 4: Request Validation
1. LoginRequest
2. StoreEmployeeRequest / UpdateEmployeeRequest
3. StoreApplicationRequest / UpdateApplicationRequest
4. GrantAppAccessRequest

### Phase 5: API Resources
1. EmployeeResource
2. ApplicationResource
3. TokenResource
4. AuditLogResource
5. LocationResource

### Phase 6: Controllers
1. AuthController
2. EmployeeController
3. ApplicationController
4. SsoController
5. LocationController
6. AuditController

### Phase 7: Routes & Config
1. Create routes/api.php with versioned routes
2. Configure bootstrap/app.php for API routing
3. Configure JWT settings
4. Configure Scramble for API docs

### Phase 8: Seeders & Testing
1. PSGC data seeder
2. Employee seeder
3. Application seeder
4. Feature tests for all endpoints

---

## File Structure

```
app/
├── Models/
│   ├── Employee.php
│   ├── Application.php
│   ├── OAuthToken.php
│   ├── AuditLog.php
│   ├── Province.php
│   ├── City.php
│   └── Barangay.php
├── Http/
│   ├── Controllers/Api/V1/
│   │   ├── AuthController.php
│   │   ├── EmployeeController.php
│   │   ├── ApplicationController.php
│   │   ├── SsoController.php
│   │   ├── LocationController.php
│   │   └── AuditController.php
│   ├── Middleware/
│   │   ├── ValidateJwtToken.php
│   │   ├── ValidateAppCredentials.php
│   │   ├── PerAppRateLimit.php
│   │   └── AuditLogger.php
│   ├── Requests/
│   │   ├── Auth/
│   │   ├── Employee/
│   │   └── Application/
│   └── Resources/
│       ├── EmployeeResource.php
│       ├── ApplicationResource.php
│       └── ...
├── Enums/
│   ├── CivilStatus.php
│   └── AppRole.php
database/
├── migrations/
│   └── (all migration files)
├── seeders/
│   ├── PsgcSeeder.php
│   ├── EmployeeSeeder.php
│   └── ApplicationSeeder.php
routes/
└── api.php
```

---

## Notes

- Code will have minimal comments (only for complex logic)
- All routes use UUID for public identifiers
- Soft deletes preserve data for audit compliance
- Rate limiting configurable per application
- PSGC data imported from official source
