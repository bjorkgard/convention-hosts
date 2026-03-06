# Implementation Plan: Section CRUD Management

## Overview

Add full Section CRUD (Create, Edit, Delete) capabilities to the FloorsIndex page. This involves backend changes (new form request, controller updates, route adjustments) and frontend changes (new SectionModal component, FloorRow action buttons, FloorsIndex state management). The implementation builds incrementally: backend first, then frontend components, then wiring and integration.

## Tasks

- [x] 1. Backend: Create UpdateSectionRequest and modify StoreSectionRequest
  - [x] 1.1 Create `app/Http/Requests/UpdateSectionRequest.php` with validation rules for name, number_of_seats, elder_friendly, handicap_friendly, and information (no floor_id since sections don't change floors on edit)
    - Use the `SanitizesInput` concern and `richTextFields()` method matching the existing `StoreSectionRequest` pattern
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_
  - [x] 1.2 Modify `app/Http/Requests/StoreSectionRequest.php` to add `floor_id` validation rule (`sometimes|required|exists:floors,id`) for creating sections from the FloorsIndex page
    - _Requirements: 2.3, 3.1_
  - [x] 1.3 Write property test for server-side validation
    - **Property 10: Server-side validation rejects invalid data without state change**
    - **Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 3.3, 4.5**

- [x] 2. Backend: Update SectionController store/update/destroy actions
  - [x] 2.1 Update `SectionController@store` to accept `floor_id` from request body (when provided) and redirect to `floors.index` route instead of `conventions.show`
    - When `floor_id` is present in validated data, resolve the Floor model and use it; otherwise use the route-bound `$floor` parameter
    - _Requirements: 3.1, 3.2, 3.5_
  - [x] 2.2 Update `SectionController@update` to use `UpdateSectionRequest` instead of `StoreSectionRequest` and redirect to `floors.index` route
    - _Requirements: 4.3, 4.4, 4.6_
  - [x] 2.3 Update `SectionController@destroy` to redirect to `floors.index` route instead of `conventions.show`
    - _Requirements: 5.3, 5.4, 5.6_
  - [x] 2.4 Write property test for valid section creation
    - **Property 3: Valid section creation persists correctly**
    - **Validates: Requirements 3.1**
  - [x] 2.5 Write property test for valid section update
    - **Property 4: Valid section update persists correctly**
    - **Validates: Requirements 4.3**
  - [x] 2.6 Write property test for section deletion
    - **Property 5: Section deletion removes the section**
    - **Validates: Requirements 5.3**
  - [x] 2.7 Write property test for cancelling deletion
    - **Property 6: Cancelling deletion preserves the section**
    - **Validates: Requirements 5.5**

- [x] 3. Checkpoint - Backend changes verified
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Backend: Write authorization property tests
  - [x] 4.1 Write property test for CRUD authorization enforcement
    - **Property 7: Section CRUD authorization enforcement**
    - **Validates: Requirements 3.5, 4.6, 5.6**

- [x] 5. Frontend: Create SectionModal component
  - [x] 5.1 Create `resources/js/components/conventions/section-modal.tsx` with the `SectionModalProps` interface from the design
    - Implement form fields: name (text input), number_of_seats (numeric input), elder_friendly (checkbox), handicap_friendly (checkbox), information (textarea), floor_id (select dropdown)
    - Use `useForm` from `@inertiajs/react` for form state and submission
    - In create mode: POST to `SectionController@store` via Wayfinder; in edit mode: PUT to `SectionController@update` via Wayfinder
    - Auto-select floor when only one floor is available
    - Disable submit button and show loading text during `processing` state
    - Display inline validation errors from `form.errors`
    - Follow existing Dialog/DialogContent/DialogFooter patterns from the floor add/edit dialogs in FloorsIndex
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 3.2, 3.3, 3.4, 4.2, 4.4, 4.5, 4.7_

- [x] 6. Frontend: Update FloorRow component with section action buttons
  - [x] 6.1 Add `onEditSection` and `onDeleteSection` callback props to `FloorRowProps` interface
    - Add `userFloorIds` and `userSectionIds` props for role-based button visibility
    - _Requirements: 6.2, 6.3, 6.4_
  - [x] 6.2 Add edit (Pencil) and delete (Trash2) icon buttons next to each section in the expanded section list
    - Edit button visible when user can edit the section (Owner, ConventionUser, FloorUser for that floor)
    - Delete button visible when user can delete the section (same roles as edit)
    - SectionUser sees no action buttons for sections they are not assigned to
    - Preserve the existing Link navigation to section show page
    - _Requirements: 4.1, 5.1, 6.1, 6.2, 6.3, 6.4_

- [x] 7. Frontend: Update FloorsIndex page to wire section CRUD
  - [x] 7.1 Add "Add Section" button in the page header, visible to Owner, ConventionUser, and FloorUser roles (hidden for SectionUser-only users)
    - _Requirements: 1.1, 1.2, 1.3, 1.4_
  - [x] 7.2 Add state management for SectionModal (open/close, create vs edit mode, selected section) and section deletion ConfirmationDialog
    - Filter floors list for the modal based on user role: all floors for Owner/ConventionUser, only assigned floors for FloorUser
    - _Requirements: 2.1, 2.4, 2.5, 2.6, 4.2, 5.2_
  - [x] 7.3 Pass `onEditSection` and `onDeleteSection` callbacks to FloorRow components and wire them to the SectionModal and ConfirmationDialog
    - Pass `userFloorIds` and `userSectionIds` props to FloorRow
    - _Requirements: 4.2, 5.2, 5.4, 5.5_

- [x] 8. Checkpoint - Full feature integration verified
  - Ensure all tests pass, ask the user if questions arise.

- [x] 9. Property tests for frontend behavior
  - [x] 9.1 Write property test for Add Section button visibility by role
    - **Property 1: Add Section button visibility is determined by role**
    - **Validates: Requirements 1.1, 1.2, 1.3, 1.4**
  - [x] 9.2 Write property test for floor selector filtering
    - **Property 2: Floor selector shows exactly the authorized floors**
    - **Validates: Requirements 2.4, 2.5, 2.6**
  - [x] 9.3 Write property test for section action button visibility
    - **Property 8: Section action button visibility matches authorization**
    - **Validates: Requirements 4.1, 5.1, 6.2, 6.3, 6.4**
  - [x] 9.4 Write property test for section display information
    - **Property 9: Section display contains required information**
    - **Validates: Requirements 6.1**

- [x] 10. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- The implementation language is PHP (Laravel) for backend and TypeScript (React) for frontend, matching the existing codebase
- Wayfinder auto-generated route actions will need regeneration after controller changes (`php artisan wayfinder:generate`)
