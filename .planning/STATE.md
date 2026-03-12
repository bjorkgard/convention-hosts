# Project State

**Project:** Convention Hosts
**Initialized:** 2026-03-12
**Current focus:** Phase 2 preparation for the cookie consent increment
**Current phase:** Phase 1 - Consent State And Delivery Contract
**Next phase:** Phase 2 - Storage Enforcement And Safe Defaults
**Status:** In progress

## Memory

- This is a brownfield Laravel 12 + Inertia.js + React 19 application with validated existing product capabilities.
- The active increment is limited to authenticated cookie consent, not a broader auth rewrite or CMP rollout.
- The core product rule is that non-essential cookies and browser storage must remain inactive until the user accepts consent.
- Decline must preserve only essential auth/session behavior and must not break login continuity or application security.
- The roadmap derives directly from v1 requirements and maps every requirement to exactly one phase.
- Research indicates the highest-risk area is storage enforcement across existing preference and install-prompt persistence, not the banner UI itself.
- Phase ordering is dependency-driven: consent contract first, enforcement second, prompt integration third, verification last.
- Plan `01-server-consent-contract` is complete: consent persistence now lives on `users` with a server-owned policy version, write seam, and resolver contract.
- Resolver contract currently returns `state`, `version`, `allowOptionalStorage`, `decidedAt`, and `updatedAt`, with invalid or mismatched records collapsing to `undecided`.
- Consent writes preserve `consent_decided_at` only when replacing a valid current-version decision; invalidated or malformed stored records are re-baselined on the next explicit write.
- Plan `02-inertia-consent-delivery` is complete: authenticated Inertia responses now share a top-level server-derived `consent` prop through `HandleInertiaRequests`.
- Password login and two-factor completion both rely on the existing redirect flow and now land on authenticated Inertia responses that include the shared consent contract on first delivery.
- `npm run types:check` currently fails due to pre-existing unrelated TypeScript issues outside the consent plan write scope.

## References

- Source context: `.planning/PROJECT.md`
- Requirement source: `.planning/REQUIREMENTS.md`
- Research source: `.planning/research/SUMMARY.md`
- Active roadmap: `.planning/ROADMAP.md`

## Ready State

- Roadmap exists and has 100% v1 requirement coverage.
- Traceability in `.planning/REQUIREMENTS.md` is initialized with all v1 requirements set to `Pending`.
- Phase 1 plans `01-server-consent-contract` and `02-inertia-consent-delivery` are complete and verified at the PHP feature/unit level.
- Phase 1 is functionally complete for `CONS-04`; remaining repo-wide TypeScript cleanup is outside the consent plan scope.

---
*Last updated: 2026-03-12 during Phase 1 plan `02-inertia-consent-delivery` execution*
