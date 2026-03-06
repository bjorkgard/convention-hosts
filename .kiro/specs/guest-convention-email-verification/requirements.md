# Requirements Document

## Introduction

This feature changes the guest convention creation flow for new users. Currently, when an unauthenticated user creates a convention and a new account is created, the system auto-logs them in with a random password and redirects to the convention page. The new behavior requires new users to verify their email and set a personal password before gaining access. Existing users who create a guest convention retain the current auto-login behavior.

## Glossary

- **Guest_Convention_Controller**: The controller handling convention creation by unauthenticated users (`GuestConventionController`)
- **Confirmation_Page**: A new Inertia page displayed after a new user creates a convention, informing them to check their email
- **Set_Password_Page**: An Inertia page where the new user sets a personal password after clicking the signed email link (reuses the existing invitation page pattern)
- **Verification_Email**: A mailable sent to new guest users containing a signed URL to set their password
- **Signed_URL**: A Laravel signed URL with a 24-hour expiration that authenticates the email link
- **New_User**: A user whose email does not exist in the system at the time of guest convention creation
- **Existing_User**: A user whose email already exists in the system at the time of guest convention creation

## Requirements

### Requirement 1: Divergent Flow for New vs Existing Users

**User Story:** As a system administrator, I want the guest convention creation flow to distinguish between new and existing users, so that new users verify their email before accessing the system.

#### Acceptance Criteria

1. WHEN an existing user's email is provided during guest convention creation, THE Guest_Convention_Controller SHALL log the user in and redirect to the convention show page (current behavior preserved)
2. WHEN a new user's email is provided during guest convention creation, THE Guest_Convention_Controller SHALL create the user account, create the convention, and redirect to the Confirmation_Page without logging the user in

### Requirement 2: Confirmation Page After Convention Creation

**User Story:** As a new guest user, I want to see a confirmation page after creating a convention, so that I know the convention was created and I need to check my email.

#### Acceptance Criteria

1. WHEN a new user is redirected after guest convention creation, THE Confirmation_Page SHALL display the convention name and the user's email address
2. THE Confirmation_Page SHALL inform the user that a verification email has been sent to the provided email address
3. THE Confirmation_Page SHALL instruct the user to click the link in the email to set a password and activate the account
4. THE Confirmation_Page SHALL be accessible without authentication

### Requirement 3: Verification Email Delivery

**User Story:** As a new guest user, I want to receive an email with a verification link, so that I can set my password and access my convention.

#### Acceptance Criteria

1. WHEN a new user account is created during guest convention creation, THE Guest_Convention_Controller SHALL send a Verification_Email to the user's email address
2. THE Verification_Email SHALL contain a Signed_URL linking to the Set_Password_Page
3. THE Signed_URL SHALL expire after 24 hours
4. THE Verification_Email SHALL include the user's first name and the convention name

### Requirement 4: Set Password Page via Signed URL

**User Story:** As a new guest user, I want to set a personal password after clicking the email link, so that I can securely access my account.

#### Acceptance Criteria

1. WHEN a user navigates to a valid Signed_URL for guest convention verification, THE Set_Password_Page SHALL display a password form with the user's email shown as read-only
2. THE Set_Password_Page SHALL require a password and password confirmation
3. THE Set_Password_Page SHALL enforce password rules: minimum 8 characters, at least one lowercase letter, one uppercase letter, one number, and one symbol (@$!%*#?&)
4. THE Set_Password_Page SHALL display real-time password criteria feedback as the user types
5. THE Set_Password_Page SHALL be accessible without authentication

### Requirement 5: Account Activation on Password Submission

**User Story:** As a new guest user, I want my account to be fully activated after setting my password, so that I can immediately use the system.

#### Acceptance Criteria

1. WHEN a valid password is submitted on the Set_Password_Page, THE System SHALL save the hashed password to the user record
2. WHEN a valid password is submitted on the Set_Password_Page, THE System SHALL set the user's email_confirmed field to true
3. WHEN a valid password is submitted on the Set_Password_Page, THE System SHALL log the user in
4. WHEN a valid password is submitted on the Set_Password_Page, THE System SHALL redirect the user to the convention show page

### Requirement 6: Invalid or Expired Signed URL Handling

**User Story:** As a new guest user, I want to see a clear error message if my verification link is invalid or expired, so that I know what went wrong and what to do next.

#### Acceptance Criteria

1. IF a user navigates to an expired Signed_URL for guest convention verification, THEN THE System SHALL display an error page indicating the link has expired
2. IF a user navigates to a tampered or invalid Signed_URL for guest convention verification, THEN THE System SHALL display an error page indicating the link is invalid
3. THE error page SHALL provide a link to the home page

### Requirement 7: New User Record Creation Without Login Credentials

**User Story:** As a system administrator, I want new guest users to be created with a random password and unconfirmed email, so that the account exists but cannot be used until the user verifies.

#### Acceptance Criteria

1. WHEN a new user is created during guest convention creation, THE Guest_Convention_Controller SHALL create the user with a random password
2. WHEN a new user is created during guest convention creation, THE Guest_Convention_Controller SHALL set email_confirmed to false on the user record
3. WHEN a new user is created during guest convention creation, THE Guest_Convention_Controller SHALL assign the Owner and ConventionUser roles to the user for the created convention
