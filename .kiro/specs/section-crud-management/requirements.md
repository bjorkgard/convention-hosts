# Requirements Document

## Introduction

This feature adds full Section CRUD (Create, Read, Edit, Delete) management to the FloorsIndex page. Users with appropriate roles can create sections via a modal dialog with a floor selector, edit existing sections inline, and delete sections with a confirmation prompt. All operations are scoped by the user's role-based access within the convention.

## Glossary

- **FloorsIndex_Page**: The existing page at `resources/js/pages/floors/index.tsx` that lists all floors and their sections for a convention.
- **Section_Modal**: A dialog component used for creating or editing a section, containing form fields for all section attributes and a floor selector dropdown.
- **Floor_Selector**: A dropdown within the Section_Modal that lists only the floors visible to the current user based on their role.
- **Section_Form**: The form within the Section_Modal containing fields: name, number_of_seats, elder_friendly, handicap_friendly, and information.
- **Confirmation_Dialog**: A dialog that asks the user to confirm before a destructive action (section deletion) is executed.
- **Convention_System**: The overall Convention Management System backend and frontend.
- **SectionController**: The Laravel controller at `app/Http/Controllers/SectionController.php` that handles section CRUD operations.
- **SectionPolicy**: The Laravel policy at `app/Policies/SectionPolicy.php` that enforces role-based authorization for section operations.

## Requirements

### Requirement 1: Create Section Button Visibility

**User Story:** As a convention manager, I want to see an "Add Section" button on the FloorsIndex page, so that I can create new sections for the convention.

#### Acceptance Criteria

1. WHILE the user has the Owner role, THE FloorsIndex_Page SHALL display an "Add Section" button in the page header area.
2. WHILE the user has the ConventionUser role, THE FloorsIndex_Page SHALL display an "Add Section" button in the page header area.
3. WHILE the user has the FloorUser role, THE FloorsIndex_Page SHALL display an "Add Section" button in the page header area.
4. WHILE the user has only the SectionUser role, THE FloorsIndex_Page SHALL hide the "Add Section" button.

### Requirement 2: Create Section Modal

**User Story:** As a convention manager, I want to fill in section details in a modal dialog, so that I can create a new section without leaving the FloorsIndex page.

#### Acceptance Criteria

1. WHEN the user clicks the "Add Section" button, THE FloorsIndex_Page SHALL open the Section_Modal with empty form fields.
2. THE Section_Modal SHALL display a form with the following fields: name (text input, required), number_of_seats (numeric input, required, minimum 1), elder_friendly (checkbox), handicap_friendly (checkbox), and information (text area, optional).
3. THE Section_Modal SHALL display the Floor_Selector dropdown as a required field.
4. THE Floor_Selector SHALL list only the floors that the current user is authorized to view based on their role.
5. WHILE the user has the Owner or ConventionUser role, THE Floor_Selector SHALL list all floors of the convention.
6. WHILE the user has the FloorUser role, THE Floor_Selector SHALL list only the floors assigned to that user.
7. WHEN the Floor_Selector contains exactly one floor, THE Section_Modal SHALL pre-select that floor automatically.

### Requirement 3: Create Section Submission

**User Story:** As a convention manager, I want to submit the section creation form, so that the new section is persisted and visible on the FloorsIndex page.

#### Acceptance Criteria

1. WHEN the user submits the Section_Modal with valid data, THE Convention_System SHALL create a new section associated with the selected floor.
2. WHEN the section is created successfully, THE Section_Modal SHALL close and THE FloorsIndex_Page SHALL display the new section under its parent floor.
3. IF the user submits the Section_Modal with invalid data, THEN THE Section_Modal SHALL display validation error messages next to the corresponding fields.
4. WHILE the form is being submitted, THE Section_Modal SHALL disable the submit button and display a loading indicator.
5. THE SectionController SHALL authorize the create action using the SectionPolicy, verifying the user has permission to create sections on the selected floor.

### Requirement 4: Edit Section

**User Story:** As a convention manager, I want to edit an existing section from the FloorsIndex page, so that I can update section details without navigating away.

#### Acceptance Criteria

1. WHILE the user has the Owner, ConventionUser, or FloorUser role, THE FloorsIndex_Page SHALL display an edit button for each section the user is authorized to update.
2. WHEN the user clicks the edit button on a section, THE FloorsIndex_Page SHALL open the Section_Modal pre-filled with the section's current data.
3. WHEN the user submits the edit form with valid data, THE Convention_System SHALL update the section attributes.
4. WHEN the section is updated successfully, THE Section_Modal SHALL close and THE FloorsIndex_Page SHALL reflect the updated section data.
5. IF the user submits the edit form with invalid data, THEN THE Section_Modal SHALL display validation error messages next to the corresponding fields.
6. THE SectionController SHALL authorize the update action using the SectionPolicy, verifying the user has permission to update the section.
7. THE Section_Modal in edit mode SHALL display the Floor_Selector with the section's current floor pre-selected.

### Requirement 5: Delete Section

**User Story:** As a convention manager, I want to delete a section from the FloorsIndex page, so that I can remove sections that are no longer needed.

#### Acceptance Criteria

1. WHILE the user has the Owner, ConventionUser, or FloorUser role, THE FloorsIndex_Page SHALL display a delete button for each section the user is authorized to delete.
2. WHEN the user clicks the delete button on a section, THE FloorsIndex_Page SHALL open the Confirmation_Dialog with a message identifying the section being deleted.
3. WHEN the user confirms the deletion in the Confirmation_Dialog, THE Convention_System SHALL permanently remove the section and its associated data.
4. WHEN the section is deleted successfully, THE Confirmation_Dialog SHALL close and THE FloorsIndex_Page SHALL remove the section from the floor listing.
5. WHEN the user cancels the deletion in the Confirmation_Dialog, THE Confirmation_Dialog SHALL close without deleting the section.
6. THE SectionController SHALL authorize the delete action using the SectionPolicy, verifying the user has permission to delete the section.

### Requirement 6: Section Display in Floor Rows

**User Story:** As a user, I want to see section action buttons inline within the floor row's expanded section list, so that I can manage sections contextually.

#### Acceptance Criteria

1. WHEN a floor row is expanded on the FloorsIndex_Page, THE FloorsIndex_Page SHALL display each section with its name, occupancy indicator, and available seats count.
2. WHILE the user has permission to edit a section, THE FloorsIndex_Page SHALL display an edit icon button next to that section in the expanded floor row.
3. WHILE the user has permission to delete a section, THE FloorsIndex_Page SHALL display a delete icon button next to that section in the expanded floor row.
4. WHILE the user has only the SectionUser role, THE FloorsIndex_Page SHALL hide edit and delete buttons for sections the user is not assigned to.

### Requirement 7: Server-Side Validation

**User Story:** As a system administrator, I want all section data to be validated server-side, so that data integrity is maintained regardless of client-side behavior.

#### Acceptance Criteria

1. THE SectionController SHALL validate that the name field is a non-empty string with a maximum length of 255 characters.
2. THE SectionController SHALL validate that the number_of_seats field is a positive integer with a minimum value of 1.
3. THE SectionController SHALL validate that the elder_friendly field is a boolean value when provided.
4. THE SectionController SHALL validate that the handicap_friendly field is a boolean value when provided.
5. THE SectionController SHALL validate that the information field is a string when provided.
6. IF any validation rule fails, THEN THE SectionController SHALL return the validation errors to the client without creating or updating the section.
