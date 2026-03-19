# Implementation Plan: Hearing Loop Section

## Overview

Add the `hearing_loop` boolean accessibility flag to sections across the full stack, mirroring the existing `handicap_friendly` pattern. The implementation follows a bottom-up approach: database → model → validation → frontend type → UI components → exports → factory/tests.

## Tasks

- [x] 1. Database migration and Section model
  - [x] 1.1 Create migration to add `hearing_loop` column to sections table
    - Create migration `add_hearing_loop_to_sections_table`
    - Add `boolean('hearing_loop')->default(false)->after('handicap_friendly')`
    - Drop existing `idx_sections_accessibility` index and recreate with `['elder_friendly', 'handicap_friendly', 'hearing_loop']`
    - Add standalone `idx_sections_hearing_loop` index on `hearing_loop` for efficient standalone filtering (B-tree left-prefix requirement)
    - _Requirements: 1.1_

  - [x] 1.2 Update Section model fillable and casts
    - Add `'hearing_loop'` to `$fillable` array after `'handicap_friendly'` in `app/Models/Section.php`
    - Add `'hearing_loop' => 'boolean'` to `casts()` after `'handicap_friendly'`
    - _Requirements: 2.1, 2.2_

  - [x] 1.3 Write property test for hearing_loop round-trip persistence
    - **Property 1: Section hearing_loop round-trip**
    - Create sections with random `hearing_loop` values, verify persistence and boolean cast; verify default is `false` when omitted
    - **Validates: Requirements 1.1, 2.1, 2.2**

- [x] 2. Backend validation and search filter
  - [x] 2.1 Add `hearing_loop` validation rule to form requests
    - Add `'hearing_loop' => ['nullable', 'boolean']` to `StoreSectionRequest` after `handicap_friendly` rule
    - Add `'hearing_loop' => ['nullable', 'boolean']` to `UpdateSectionRequest` after `handicap_friendly` rule
    - Add `'hearing_loop' => ['nullable', 'boolean']` to `SearchRequest` after `handicap_friendly` rule
    - _Requirements: 3.1, 3.2, 5.4_

  - [x] 2.2 Write property test for hearing_loop validation
    - **Property 2: Validation accepts valid booleans and rejects invalid values**
    - Generate valid and invalid `hearing_loop` inputs, verify `StoreSectionRequest`, `UpdateSectionRequest`, and `SearchRequest` accept/reject correctly
    - **Validates: Requirements 3.1, 3.2, 5.4**

  - [x] 2.3 Add `hearing_loop` filter to SearchController
    - Add `hearing_loop` filter block after `handicap_friendly` in `app/Http/Controllers/SearchController.php`: `if ($request->boolean('hearing_loop')) { $query->where('hearing_loop', true); }`
    - Add `'hearing_loop'` to the `$request->only([...])` filters array
    - _Requirements: 5.5, 5.6_

  - [x] 2.4 Write property test for search filter
    - **Property 3: Search filter returns only hearing_loop sections**
    - Create sections with mixed `hearing_loop` values, search with filter active, verify all results have `hearing_loop = true` and filters object includes the value
    - **Validates: Requirements 5.5, 5.6**

- [x] 3. Checkpoint - Ensure all backend tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Frontend type and UI components
  - [x] 4.1 Add `hearing_loop` to TypeScript Section type
    - Add `hearing_loop: boolean` to `Section` interface in `resources/js/types/convention.ts` after `handicap_friendly`
    - _Requirements: 6.1_

  - [x] 4.2 Add "Hearing loop" checkbox to section modal
    - Add `hearing_loop: false` to `useForm` initial data in `resources/js/components/conventions/section-modal.tsx`
    - Add `hearing_loop` to `setData` in the edit mode effect
    - Add "Hearing loop" checkbox after "Handicap friendly" checkbox using the same markup pattern
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [x] 4.3 Add "Hearing loop" filter to search page
    - Add `hearing_loop?: string` to `SearchFilters` interface in `resources/js/pages/search/index.tsx`
    - Add `hearing_loop` to `applyFilters` query building logic (same pattern as `handicap_friendly`)
    - Add `handleHearingLoopChange` handler
    - Add "Hearing loop" checkbox filter after "Handicap-friendly" checkbox
    - _Requirements: 5.1, 5.2, 5.3_

- [x] 5. Data exports
  - [x] 5.1 Add `hearing_loop` to all export formats
    - `FloorsAndSectionsSheet`: add `'Hearing Loop'` heading and `$section->hearing_loop ? 'Yes' : 'No'` data column after Handicap Friendly
    - `ConventionWordExport::addFloorsAndSections()`: add `'Hearing Loop'` header cell and data cell after Handicap Friendly
    - `ConventionMarkdownExport::generateContent()`: add `Hearing Loop` table column header and data cell after Handicap Friendly
    - _Requirements: 7.1_

  - [x] 5.2 Write property test for export includes hearing_loop
    - **Property 4: Export includes hearing_loop for all sections**
    - Create sections with random `hearing_loop` values, generate all three export formats, verify `hearing_loop` appears as "Yes"/"No" in output
    - **Validates: Requirements 7.1**

- [x] 6. Factory and daily reset verification
  - [x] 6.1 Update SectionFactory with `hearing_loop`
    - Add `'hearing_loop' => $this->faker->boolean(20)` to `definition()` in `database/factories/SectionFactory.php`
    - Add `hearingLoop()` state method returning `['hearing_loop' => true]`
    - _Requirements: 2.1_

  - [x] 6.2 Write property test for daily reset preserves hearing_loop
    - **Property 5: Daily reset preserves hearing_loop**
    - Create sections with random `hearing_loop` values, run `ResetDailyOccupancy` command, verify `hearing_loop` values remain unchanged
    - **Validates: Requirements 8.1**

- [x] 7. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- The implementation mirrors the existing `handicap_friendly` pattern throughout
- Property tests validate universal correctness properties from the design document
- No new routes, controllers, middleware, or policies are needed
