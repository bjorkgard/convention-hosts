# Requirements Document

## Introduction

Add a `hearing_loop` boolean accessibility flag to sections, following the same pattern as the existing `elder_friendly` and `handicap_friendly` booleans. The field must be supported across the full stack: database, model, validation, section create/edit modal, search filters, and data exports.

## Glossary

- **Section**: A seating area within a floor that tracks capacity, occupancy, and accessibility features.
- **Section_Modal**: The dialog component used to create and edit sections from the floors index page.
- **Search_Page**: The page where users search for available sections with optional accessibility filters.
- **StoreSectionRequest**: The Laravel Form Request that validates input when creating a section.
- **UpdateSectionRequest**: The Laravel Form Request that validates input when updating a section.
- **SearchRequest**: The Laravel Form Request that validates search filter parameters.
- **SearchController**: The controller that handles section search queries with accessibility filters.
- **Section_Model**: The Eloquent model representing a section, including fillable fields and casts.
- **Section_Type**: The TypeScript interface defining the Section shape on the frontend.
- **Export_System**: The set of exporters (Excel, Word, Markdown) that output convention data including section accessibility details.

## Requirements

### Requirement 1: Database Schema

**User Story:** As a developer, I want the sections table to include a `hearing_loop` boolean column, so that the system can persist hearing loop availability per section.

#### Acceptance Criteria

1. THE Database SHALL include a `hearing_loop` boolean column on the sections table, positioned after the `handicap_friendly` column, defaulting to false.

### Requirement 2: Section Model

**User Story:** As a developer, I want the Section model to support the `hearing_loop` attribute, so that it is mass-assignable and correctly cast to a boolean.

#### Acceptance Criteria

1. THE Section_Model SHALL include `hearing_loop` in the fillable attributes array.
2. THE Section_Model SHALL cast the `hearing_loop` attribute to boolean.

### Requirement 3: Section Validation

**User Story:** As a developer, I want the section form requests to accept and validate the `hearing_loop` field, so that it can be submitted during create and edit operations.

#### Acceptance Criteria

1. THE StoreSectionRequest SHALL accept `hearing_loop` as a nullable boolean field.
2. THE UpdateSectionRequest SHALL accept `hearing_loop` as a nullable boolean field.

### Requirement 4: Section Create and Edit Modal

**User Story:** As an administrator, I want to check a "Hearing loop" checkbox when creating or editing a section, so that I can indicate whether a section has a hearing loop.

#### Acceptance Criteria

1. WHEN the Section_Modal is rendered, THE Section_Modal SHALL display a "Hearing loop" checkbox after the "Handicap friendly" checkbox.
2. WHEN creating a section, THE Section_Modal SHALL include the `hearing_loop` value in the form submission.
3. WHEN editing a section, THE Section_Modal SHALL pre-populate the "Hearing loop" checkbox with the current `hearing_loop` value.
4. WHEN the form is submitted with `hearing_loop` checked, THE Section_Modal SHALL send `hearing_loop` as true to the backend.
5. WHEN the form is submitted with `hearing_loop` unchecked, THE Section_Modal SHALL send `hearing_loop` as false to the backend.

### Requirement 5: Search Filter

**User Story:** As a user, I want to filter sections by hearing loop availability on the search page, so that I can find sections equipped with a hearing loop.

#### Acceptance Criteria

1. THE Search_Page SHALL display a "Hearing loop" checkbox filter after the "Handicap-friendly" checkbox filter.
2. WHEN the "Hearing loop" checkbox is checked, THE Search_Page SHALL include `hearing_loop=1` in the search query parameters.
3. WHEN the "Hearing loop" checkbox is unchecked, THE Search_Page SHALL omit `hearing_loop` from the search query parameters.
4. THE SearchRequest SHALL accept `hearing_loop` as a nullable boolean filter parameter.
5. WHEN the `hearing_loop` filter is active, THE SearchController SHALL return only sections where `hearing_loop` is true.
6. THE SearchController SHALL pass the `hearing_loop` filter value to the frontend in the filters object.

### Requirement 6: TypeScript Type

**User Story:** As a developer, I want the frontend Section type to include `hearing_loop`, so that TypeScript enforces correct usage across all components.

#### Acceptance Criteria

1. THE Section_Type SHALL include a `hearing_loop` property of type boolean, positioned after `handicap_friendly`.

### Requirement 7: Data Export

**User Story:** As an owner, I want exported convention data to include the hearing loop status of each section, so that exported reports reflect all accessibility features.

#### Acceptance Criteria

1. WHEN exporting convention data, THE Export_System SHALL include the `hearing_loop` value for each section in all export formats (Excel, Word, Markdown).

### Requirement 8: Daily Occupancy Reset Preservation

**User Story:** As a developer, I want the daily occupancy reset to leave the `hearing_loop` field unchanged, so that accessibility metadata is not lost during scheduled resets.

#### Acceptance Criteria

1. WHEN the daily occupancy reset runs, THE Section_Model SHALL retain the existing `hearing_loop` value for each section.
