# Project State

**Project:** Convention Hosts
**Initialized:** 2026-03-12
**Current focus:** Phase 3 planning for the authenticated prompt experience
**Current phase:** Phase 2 - Storage Enforcement And Safe Defaults
**Next phase:** Phase 3 - Authenticated Prompt Experience
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
- Phase 2 plan `01-optional-storage-policy-foundation` is complete: one PHP registry and one TypeScript optional-storage policy module now own the known optional cookie/localStorage allowlist and safe defaults.
- Phase 2 plan `02-server-cookie-trust-and-safe-defaults` is complete: Laravel now ignores or forgets `appearance`, `theme`, and `sidebar_state` when optional storage is not allowed, and server-rendered HTML no longer revives disallowed theme state from `localStorage`.
- Phase 2 plan `03-client-storage-gating-and-cleanup` is complete: appearance, theme, sidebar persistence, and install-prompt dismissal are consent-aware and clean up known optional keys when storage becomes disallowed.
- Full targeted verification for Phase 2 passed across Pest and Vitest, including `npm test -- --run`; remaining suite warnings are pre-existing and unrelated to consent enforcement.

## References

- Source context: `.planning/PROJECT.md`
- Requirement source: `.planning/REQUIREMENTS.md`
- Research source: `.planning/research/SUMMARY.md`
- Active roadmap: `.planning/ROADMAP.md`

## Ready State

- Roadmap exists and has 100% v1 requirement coverage.
- Traceability in `.planning/REQUIREMENTS.md` is initialized with all v1 requirements set to `Pending`.
- Phases 1 and 2 are complete and verified for `CONS-04`, `STOR-01` through `STOR-04`, and `APPX-03`.
- Remaining work is Phase 3 prompt integration plus Phase 4 end-to-end verification coverage.

---
*Last updated: 2026-03-12 after Phase 2 execution*
