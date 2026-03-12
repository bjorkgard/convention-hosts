# Project State

**Project:** Convention Hosts
**Initialized:** 2026-03-12
**Current focus:** Phase 3 execution for the authenticated prompt experience
**Current phase:** Phase 3 - Authenticated Prompt Experience
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
- Phase 3 plan `01-authenticated-consent-write-endpoint` is complete: authenticated users can now record accepted or declined consent through a server-owned POST route backed by `RecordUserConsentAction`, and Wayfinder exports the generated consent action for the shell prompt.
- Targeted Phase 3 backend verification passed for the consent write endpoint, including accepted writes, declined writes, guest protection, invalid-state validation, and refreshed Inertia consent props after redirect.
- Phase 3 plan `02-authenticated-shell-consent-prompt` is complete: the shared authenticated shell now mounts a server-backed consent prompt for undecided users, uses the generated consent action for `Accept all` and `Decline`, and demotes the legacy localStorage consent banner to compatibility-only status.
- Targeted Phase 3 frontend verification passed for the authenticated prompt and compatibility wrapper; `npm run lint` still reports a pre-existing warning in `resources/js/components/install-prompt.tsx` outside the prompt plan write scope.

## References

- Source context: `.planning/PROJECT.md`
- Requirement source: `.planning/REQUIREMENTS.md`
- Research source: `.planning/research/SUMMARY.md`
- Active roadmap: `.planning/ROADMAP.md`

## Ready State

- Roadmap exists and has 100% v1 requirement coverage.
- Traceability in `.planning/REQUIREMENTS.md` is initialized with all v1 requirements set to `Pending`.
- Phases 1 and 2 are complete and verified for `CONS-04`, `STOR-01` through `STOR-04`, and `APPX-03`.
- Remaining work is Phase 4 end-to-end verification coverage for the full authenticated consent flow.

---
*Last updated: 2026-03-12 after Phase 3 plan 02 execution*
