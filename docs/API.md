# API Reference

All routes are Inertia-based (server-side rendered). State-changing requests use standard HTML form submissions via Inertia and require CSRF tokens (handled automatically by Inertia). Responses are either Inertia page renders or redirects with flash messages.

## Authentication

All endpoints except the landing page and invitation routes require authentication via Laravel Fortify session-based auth. The `auth` and `verified` middleware are applied to all protected routes.

### Login

```
POST /login
```

| Field | Type | Rules |
|-------|------|-------|
| email | string | required, email |
| password | string | required |
| remember | boolean | optional |

Rate limited: 5 attempts per minute per IP.

On success: redirects to `/conventions`.
On failure: redirects back with `email` validation error.

### Register

```
POST /register
```

| Field | Type | Rules |
|-------|------|-------|
| first_name | string | required, max:255 |
| last_name | string | required, max:255 |
| email | string | required, email, unique |
| password | string | required, min:8, mixed case, number, symbol, confirmed |
| password_confirmation | string | required |

On success: authenticates and redirects to `/conventions`.

### Logout

```
POST /logout
```

Destroys session and redirects to `/`.

---

## Conventions

### List Conventions

```
GET /conventions
```

Middleware: `auth`, `verified`

Returns Inertia page `conventions/index` with:

| Prop | Type | Description |
|------|------|-------------|
| conventions | Convention[] | User's conventions ordered by start_date desc |
| canCreateConvention | boolean | Whether the user has Owner role on any convention (controls "Create Convention" button visibility) |

### Create Convention Form

```
GET /conventions/create
```

Returns Inertia page `conventions/create`.

### Store Convention

```
POST /conventions
```

| Field | Type | Rules |
|-------|------|-------|
| name | string | required, max:255 |
| city | string | required, max:255 |
| country | string | required, max:255 |
| address | string | nullable |
| start_date | date | required, after_or_equal:today |
| end_date | date | required, after_or_equal:start_date |
| other_info | string | nullable |

Custom validation: rejects if an overlapping convention exists in the same city/country.

On success: redirects to `conventions.show`. Creator is assigned Owner and ConventionUser roles.

Error example:
```json
{ "start_date": "A convention already exists in this location during these dates." }
```

### Show Convention

```
GET /conventions/{convention}
```

Middleware: `auth`, `EnsureConventionAccess`, `ScopeByRole`

Returns Inertia page `conventions/show` with:

| Prop | Type | Description |
|------|------|-------------|
| convention | Convention | Convention details |
| floors | Floor[] | Role-scoped floors with nested sections |
| attendancePeriods | AttendancePeriod[] | All periods with reports, ordered by date desc |
| users | User[] | Convention users with roles |
| userRoles | string[] | Current user's roles for this convention |
| userFloorIds | number[] | Floor IDs the current user is assigned to |
| userSectionIds | number[] | Section IDs the current user is assigned to |

### Update Convention

```
PUT /conventions/{convention}
```

Middleware: `auth`, `EnsureConventionAccess`, `ScopeByRole`

Same fields as Store Convention. Excludes current convention from overlap check.

### Delete Convention

```
DELETE /conventions/{convention}
```

Middleware: `auth`, `EnsureConventionAccess`, `EnsureOwnerRole`
Authorization: ConventionPolicy `delete` (Owner only)

On success: redirects to `conventions.index`.

### Export Convention

```
GET /conventions/{convention}/export?format={format}
```

Middleware: `auth`, `EnsureConventionAccess`, `EnsureOwnerRole`
Authorization: ConventionPolicy `export` (Owner only)

| Parameter | Type | Values |
|-----------|------|--------|
| format | string | `xlsx`, `docx`, `md` |

Returns: binary file download. File is deleted from server after sending.

---

## Floors

### List Floors

```
GET /conventions/{convention}/floors
```

Middleware: `auth`, `EnsureConventionAccess`, `ScopeByRole`

Returns Inertia page `floors/index` with:

| Prop | Type | Description |
|------|------|-------------|
| convention | Convention | Parent convention |
| floors | Floor[] | Role-scoped floors with sections |
| userRoles | string[] | Current user's roles |
| userFloorIds | number[] | Assigned floor IDs |
| userSectionIds | number[] | Assigned section IDs |

### Store Floor

```
POST /conventions/{convention}/floors
```

Middleware: `auth`, `EnsureConventionAccess`, `ScopeByRole`
Authorization: FloorPolicy `create` (Owner or ConventionUser only)

| Field | Type | Rules |
|-------|------|-------|
| name | string | required, max:255 |

On success: redirects to `conventions.show`.

### Update Floor

```
PUT /floors/{floor}
```

Authorization: FloorPolicy `update` (Owner, ConventionUser, or assigned FloorUser)

| Field | Type | Rules |
|-------|------|-------|
| name | string | required, max:255 |

### Delete Floor

```
DELETE /floors/{floor}
```

Authorization: FloorPolicy `delete` (Owner or ConventionUser only)

Cascades: deletes all sections on the floor via database foreign key.

---

## Sections

### List Sections

```
GET /conventions/{convention}/floors/{floor}/sections
```

Middleware: `auth`, `EnsureConventionAccess`, `ScopeByRole`

Returns Inertia page `sections/index` with role-scoped sections.

### Show Section

```
GET /sections/{section}
```

Returns Inertia page `sections/show` with:

| Prop | Type | Description |
|------|------|-------------|
| section | Section | Section with floor, convention, lastUpdatedBy |
| floor | Floor | Parent floor |
| convention | Convention | Parent convention |
| userRoles | string[] | Current user's roles |
| activePeriod | AttendancePeriod\|null | Current unlocked period, if any |

### Store Section

```
POST /conventions/{convention}/floors/{floor}/sections
```

Authorization: SectionPolicy `create`

| Field | Type | Rules |
|-------|------|-------|
| name | string | required, max:255 |
| number_of_seats | integer | required, min:1 |
| elder_friendly | boolean | nullable |
| handicap_friendly | boolean | nullable |
| information | string | nullable |

Defaults: `occupancy` = 0, `available_seats` = 0.

### Update Section

```
PUT /sections/{section}
```

Authorization: SectionPolicy `update`

Same fields as Store Section.

### Update Occupancy

```
PATCH /sections/{section}/occupancy
```

Authorization: SectionPolicy `update`

| Field | Type | Rules |
|-------|------|-------|
| occupancy | integer | nullable, one of: 0, 10, 25, 50, 75, 100 |
| available_seats | integer | nullable, min:0 |

At least one of `occupancy` or `available_seats` must be provided. When `available_seats` is given, occupancy is calculated as `100 - ((available_seats / number_of_seats) * 100)`. Records the updating user and timestamp.

### Set Full

```
POST /sections/{section}/full
```

Authorization: SectionPolicy `update`

No request body. Sets occupancy to 100% and records the updating user and timestamp.

### Delete Section

```
DELETE /sections/{section}
```

Authorization: SectionPolicy `delete`

---

## Users

### List Users

```
GET /conventions/{convention}/users
```

Middleware: `auth`, `EnsureConventionAccess`, `ScopeByRole`

Returns Inertia page `users/index` with:

| Prop | Type | Description |
|------|------|-------------|
| convention | Convention | Parent convention |
| users | User[] | Role-scoped users with roles, floor_ids, section_ids |
| floors | Floor[] | Convention floors with sections (for add/edit form) |
| userRoles | string[] | Current user's roles |

### Store User (Invite)

```
POST /conventions/{convention}/users
```

| Field | Type | Rules |
|-------|------|-------|
| first_name | string | required, max:255 |
| last_name | string | required, max:255 |
| email | string | required, email, unique, must not contain "jwpub.org" |
| mobile | string | required, max:255 |
| roles | string[] | required, min:1, each one of: Owner, ConventionUser, FloorUser, SectionUser |
| floor_ids | integer[] | required if FloorUser role, each must exist in floors table |
| section_ids | integer[] | required if SectionUser role, each must exist in sections table |

If the email already exists, the existing user is attached to the convention instead of creating a duplicate. Sends an invitation email with a signed URL (24h expiry).

### Update User

```
PUT /conventions/{convention}/users/{user}
```

Authorization: UserPolicy `update`

Same fields as Store User. Email uniqueness check excludes the current user. Syncs roles, floor assignments, and section assignments within a transaction.

### Delete User

```
DELETE /conventions/{convention}/users/{user}
```

Authorization: UserPolicy `delete`

Removes all role and pivot records for this convention. If the user has no remaining conventions, deletes the user record entirely.

### Resend Invitation

```
POST /conventions/{convention}/users/{user}/resend-invitation
```

Middleware: `auth`, `EnsureConventionAccess`, `throttle:3,60`

Rate limited: 3 requests per 60 minutes. Generates a new signed URL and sends the invitation email.

---

## Attendance

### Start Attendance Report

```
POST /conventions/{convention}/attendance/start
```

Middleware: `auth`, `EnsureConventionAccess`
Authorization: Owner or ConventionUser role required (checked in controller)

Determines the current period (morning if before 12:00, afternoon otherwise). Maximum 2 reports per day per convention.

On success: redirects back with flash `success`.
On failure: redirects back with `attendance` error (e.g., "Maximum 2 attendance reports per day").

### Stop Attendance Report

```
POST /conventions/{convention}/attendance/{attendancePeriod}/stop
```

Middleware: `auth`, `EnsureConventionAccess`
Authorization: Owner or ConventionUser role required

Locks the period permanently. No further attendance updates are allowed.

### Report Section Attendance

```
POST /sections/{section}/attendance/{attendancePeriod}/report
```

| Field | Type | Rules |
|-------|------|-------|
| attendance | integer | required, min:0 |

Creates or updates the attendance report for this section and period. Records `reported_by` and `reported_at`. Before locking, only the original reporter can update. After locking, no updates are allowed.

---

## Search

### Search Available Sections

```
GET /conventions/{convention}/search
```

Middleware: `auth`, `EnsureConventionAccess`

No role-based filtering — accessible to all authenticated users with convention access.

| Parameter | Type | Rules |
|-----------|------|-------|
| floor_id | integer | optional, must exist in floors table |
| elder_friendly | boolean | optional |
| handicap_friendly | boolean | optional |

Always filters to sections with `occupancy < 90%`. Results sorted by occupancy ascending, paginated (20 per page).

Returns Inertia page `search/index` with:

| Prop | Type | Description |
|------|------|-------------|
| convention | Convention | Current convention |
| sections | Paginated\<Section\> | Matching sections with floor relationship |
| floors | Floor[] | All convention floors (for filter dropdown) |
| filters | object | Applied filter values |

---

## Invitation (Unauthenticated)

### Show Invitation Form

```
GET /invitation/{user}/{convention}
```

Middleware: `signed` (verifies URL signature and expiration)

Returns Inertia page `auth/invitation` with user name/email and convention name.

Returns 403 if the signature is invalid or expired.

### Set Password

```
POST /invitation/{user}/{convention}
```

| Field | Type | Rules |
|-------|------|-------|
| password | string | required, min:8, lowercase, uppercase, number, symbol, confirmed |
| password_confirmation | string | required |

Sets the user's password and marks `email_confirmed` as true. Redirects to login.

---

## Email Confirmation (Unauthenticated)

### Confirm Email

```
GET /email/confirm/{user}
```

Middleware: `signed`

Sets `email_confirmed` to true and redirects to home with flash status message.

---

## Error Responses

All validation errors are returned as Inertia redirects with errors in the session. The frontend accesses them via `usePage().props.errors`.

| Status | Meaning | When |
|--------|---------|------|
| 403 | Forbidden | User lacks required role or convention access |
| 404 | Not Found | Resource doesn't exist |
| 419 | CSRF Mismatch | Missing or invalid CSRF token |
| 422 | Validation Error | Form request validation failed (returned as redirect with errors) |
| 429 | Too Many Requests | Rate limit exceeded (login or invitation resend) |

---

## Middleware Reference

| Middleware | Purpose |
|-----------|---------|
| `auth` | Requires authenticated session |
| `verified` | Requires verified email |
| `signed` | Verifies URL signature (invitation links) |
| `EnsureConventionAccess` | Verifies user has any role for the convention |
| `EnsureOwnerRole` | Verifies user has Owner role |
| `ScopeByRole` | Injects `scoped_floor_ids` / `scoped_section_ids` based on role |
| `throttle:3,60` | Rate limits to 3 requests per 60 minutes |
