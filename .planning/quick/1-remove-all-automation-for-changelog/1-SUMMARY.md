# Quick Task 1 Summary: Remove all automation for changelog

## Outcome

Removed the release workflow automation that updated `docs/CHANGELOG.md`, committed changelog changes during releases, and extracted GitHub release notes from the changelog. Releases now keep the version bump and tag flow, but the changelog is left fully manual.

## Files Changed

- `.github/workflows/release.yml`

## Verification

- `rg -n "CHANGELOG|release_notes|body_path|docs/CHANGELOG.md" .github/workflows/release.yml` -> no matches
- Manual workflow diff review confirmed the release job now stages only `VERSION` and uses a static release body

## Notes

- `docs/CHANGELOG.md` still exists as documentation, but the repository no longer automates its maintenance.
- The rest of the release workflow remains intact, including version calculation, tagging, GitHub release creation, and deploy triggering.
