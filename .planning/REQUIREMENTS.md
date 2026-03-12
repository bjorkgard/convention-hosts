# Requirements: Convention Hosts

**Defined:** 2026-03-12
**Core Value:** Convention staff can manage their events securely and reliably without the application storing non-essential cookies unless the user has explicitly accepted them.

## v1 Requirements

Requirements for the cookie consent increment. Each maps to roadmap phases.

### Consent

- [ ] **CONS-01**: Authenticated user who has not made a cookie choice is prompted for consent immediately after login
- [ ] **CONS-02**: Authenticated user can choose `Accept all` from the consent prompt
- [ ] **CONS-03**: Authenticated user can choose `Decline` from the consent prompt
- [ ] **CONS-04**: User's cookie decision persists across future authenticated sessions until the consent version is reset or invalidated

### Storage Policy

- [ ] **STOR-01**: When consent is declined, the application stores only essential auth/session cookies required for sign-in and security
- [ ] **STOR-02**: Non-essential cookies are not created before the user accepts consent
- [ ] **STOR-03**: Existing non-essential cookies and browser storage created before decline are cleared or ignored after the user declines
- [ ] **STOR-04**: Browser preference writes in the authenticated app obey one centralized consent policy instead of bypassing it

### App Experience

- [ ] **APPX-01**: The consent prompt is mounted in the shared authenticated app experience so the user sees it on the first post-login destination
- [ ] **APPX-02**: The consent prompt presents `Accept all` and `Decline` with equal prominence and no granular preference center in v1
- [ ] **APPX-03**: The authenticated app remains usable with safe defaults when non-essential preference storage is unavailable

### Verification

- [ ] **VERI-01**: Automated tests cover the immediate post-login consent prompt for undecided authenticated users
- [ ] **VERI-02**: Automated tests verify that declined consent prevents non-essential cookies or browser storage from being treated as active state
- [ ] **VERI-03**: Automated tests verify that essential auth/session behavior continues to work when consent is declined

## v2 Requirements

Deferred to a future release. Tracked but not in the current roadmap.

### Consent Management

- **MGMT-01**: User can review and change cookie consent later from a settings surface
- **MGMT-02**: User can manage cookie categories beyond a simple binary accept-or-decline choice

### Anonymous Experience

- **ANON-01**: Anonymous visitor is prompted for cookie consent before authentication
- **ANON-02**: Anonymous consent decisions carry forward appropriately into authenticated sessions

## Out of Scope

Explicitly excluded. Documented to prevent scope creep.

| Feature | Reason |
|---------|--------|
| Granular cookie categories in v1 | The agreed scope is a simple binary consent flow |
| Anonymous pre-login consent banner | The current increment is explicitly tied to authenticated login |
| Retaining theme, appearance, or install-dismissal persistence after decline | Decline should allow only essential auth/session cookies |
| Third-party CMP integration | The existing first-party Laravel + Inertia stack is sufficient for this increment |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| CONS-01 | Unmapped | Pending |
| CONS-02 | Unmapped | Pending |
| CONS-03 | Unmapped | Pending |
| CONS-04 | Unmapped | Pending |
| STOR-01 | Unmapped | Pending |
| STOR-02 | Unmapped | Pending |
| STOR-03 | Unmapped | Pending |
| STOR-04 | Unmapped | Pending |
| APPX-01 | Unmapped | Pending |
| APPX-02 | Unmapped | Pending |
| APPX-03 | Unmapped | Pending |
| VERI-01 | Unmapped | Pending |
| VERI-02 | Unmapped | Pending |
| VERI-03 | Unmapped | Pending |

**Coverage:**
- v1 requirements: 14 total
- Mapped to phases: 0
- Unmapped: 14 ⚠️

---
*Requirements defined: 2026-03-12*
*Last updated: 2026-03-12 after initial definition*
