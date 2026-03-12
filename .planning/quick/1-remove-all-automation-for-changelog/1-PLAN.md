# Quick Task 1 Plan: Remove all automation for changelog

## Goal

Stop the repository from automatically updating `docs/CHANGELOG.md` or deriving GitHub release content from it while keeping the manual release workflow otherwise intact.

## Tasks

### Task 1

- files: `.github/workflows/release.yml`
- action: Remove the workflow steps that mutate `docs/CHANGELOG.md`, stop committing the changelog file during releases, and stop extracting release notes from the changelog.
- verify: Inspect the workflow to confirm changelog update, changelog commit, and changelog-based release note extraction are gone.
- done: Release workflow no longer automates changelog maintenance.

### Task 2

- files: `.planning/quick/1-remove-all-automation-for-changelog/1-SUMMARY.md`, `.planning/STATE.md`
- action: Record the quick-task outcome and add the quick-task completion entry to project state.
- verify: Quick-task summary exists and STATE.md includes the new quick-task row.
- done: Planning artifacts and project state reflect the completed task.
