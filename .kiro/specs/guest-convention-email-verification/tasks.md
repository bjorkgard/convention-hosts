# Implementation Plan: Guest Convention Email Verification

## Overview

Modify the guest convention creation flow to require email verification for new users. Existing users retain auto-login behavior. New users receive a verification email with a signed URL to set their password before accessing the system. Implementation follows existing invitation flow patterns.

## Tasks

- [x] 1. Modify GuestConventionController to fork new vs existing user flows
  - [x] 1.1 Update `store()` method to detect new vs existing user and branch behavior
    - After user lookup, if new user: create user with random password and `email_confirmed=false`, create convention, generate signed URL, send verification email, render confirmation page via Inertia
    - If existing user: preserve current auto-login + redirect behavior
    - Import `Mail`, `URL`, `Inertia`, and the new `GuestConventionVerification` mailable
    - _Requirements: 1.1, 1.2, 7.1, 7.2, 7.3_

  - [x] 1.2 Write property test: Existing user auto-login preserved
    - **Property 1: Existing user auto-login preserved**
    - Use Pest with faker to generate existing users and verify auto-login + redirect to convention show
    - Run 100+ iterations
    - **Validates: Requirements 1.1**

  - [x] 1.3 Write property test: New user creation without login
    - **Property 2: New user creation without login**
    - Use Pest with faker to generate new email addresses and verify user created with `email_confirmed=false`, confirmation page rendered, user NOT authenticated
    - Run 100+ iterations
    - **Validates: Requirements 1.2, 7.2**

  - [x] 1.4 Write property test: New user role assignment
    - **Property 8: New user role assignment**
    - Verify new users get Owner and ConventionUser roles on the created convention
    - Run 100+ iterations
    - **Validates: Requirements 7.3**

- [x] 2. Create GuestConventionVerification mailable and email template
  - [x] 2.1 Create `GuestConventionVerification` mailable class
    - File: `app/Mail/GuestConventionVerification.php`
    - Accept `User`, `Convention`, and `verificationUrl` in constructor
    - Set subject to include convention name
    - Use markdown template `emails.guest-convention-verification`
    - Pass `userName`, `conventionName`, `verificationUrl`, `expiresAt` to template
    - Follow `UserInvitation` mailable pattern
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

  - [x] 2.2 Create email Blade template
    - File: `resources/views/emails/guest-convention-verification.blade.php`
    - Markdown email template with user's first name, convention name, verification button/link, and 24-hour expiration notice
    - Follow `user-invitation.blade.php` pattern with guest-specific copy
    - _Requirements: 3.2, 3.4_

  - [x] 2.3 Write property test: Verification email contains signed URL and user context
    - **Property 4: Verification email contains signed URL and user context**
    - Use Mail::fake(), create guest convention with new user, assert email sent with correct recipient, signed URL, user first name, and convention name
    - Run 100+ iterations
    - **Validates: Requirements 3.1, 3.2, 3.4**

- [x] 3. Create GuestConventionVerificationController
  - [x] 3.1 Create controller with `show` and `store` methods
    - File: `app/Http/Controllers/Auth/GuestConventionVerificationController.php`
    - `show(Request $request, User $user, Convention $convention)`: Render `auth/guest-convention-set-password` with user and convention props
    - `store(SetPasswordRequest $request, User $user, Convention $convention)`: Update password, set `email_confirmed=true`, login user, redirect to convention show page
    - Reuse existing `SetPasswordRequest` for password validation
    - _Requirements: 4.1, 5.1, 5.2, 5.3, 5.4_

  - [x] 3.2 Register routes in `routes/web.php`
    - Add GET and POST routes for `guest-verification/{user}/{convention}` outside auth middleware
    - Apply `signed` middleware to GET route
    - Name routes `guest-verification.show` and `guest-verification.store`
    - _Requirements: 4.5, 5.4_

  - [x] 3.3 Add signed URL exception handling for guest verification routes
    - Handle `InvalidSignatureException` for `guest-verification.*` routes in `bootstrap/app.php` or exception handler
    - Render error page with `reason='expired'` or `reason='invalid'` as appropriate
    - Follow existing invitation invalid URL handling pattern
    - _Requirements: 6.1, 6.2_

  - [x] 3.4 Write property test: Set password page displays user email
    - **Property 5: Set password page displays user email**
    - Generate valid signed URLs and verify the rendered page contains user email as prop
    - Run 100+ iterations
    - **Validates: Requirements 4.1**

  - [x] 3.5 Write property test: Password validation enforcement
    - **Property 6: Password validation enforcement**
    - Generate strings that violate password criteria and verify rejection
    - Run 100+ iterations
    - **Validates: Requirements 4.3**

  - [x] 3.6 Write property test: Account activation round trip
    - **Property 7: Account activation round trip**
    - Submit valid passwords and verify: hashed password saved, `email_confirmed=true`, user logged in, redirect to convention show
    - Run 100+ iterations
    - **Validates: Requirements 5.1, 5.2, 5.3, 5.4**

- [x] 4. Checkpoint - Backend verification
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Create frontend confirmation page
  - [x] 5.1 Create `guest-convention-confirmation.tsx` page component
    - File: `resources/js/pages/auth/guest-convention-confirmation.tsx`
    - Accept `conventionName` and `email` as Inertia page props
    - Display convention name and user email
    - Show instructional text: verification email sent, click link to set password
    - Use `AuthLayout` for consistent styling
    - No authentication required
    - _Requirements: 2.1, 2.2, 2.3, 2.4_

  - [x] 5.2 Write property test: Confirmation page displays convention and email
    - **Property 3: Confirmation page displays convention and email**
    - Verify the Inertia response from guest convention creation for new users contains `conventionName` and `email` props
    - Run 100+ iterations
    - **Validates: Requirements 2.1**

- [x] 6. Create frontend set password page
  - [x] 6.1 Create `guest-convention-set-password.tsx` page component
    - File: `resources/js/pages/auth/guest-convention-set-password.tsx`
    - Accept `user` and `convention` as Inertia page props
    - Display email as read-only field
    - Password and password confirmation inputs
    - Real-time password criteria feedback (min 8 chars, lowercase, uppercase, number, symbol)
    - Form submits POST to `guest-verification.store` route
    - Follow `invitation.tsx` pattern with guest-specific copy
    - Use `AuthLayout` for consistent styling
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [x] 7. Create frontend error page for invalid/expired signed URLs
  - [x] 7.1 Create `guest-convention-invalid.tsx` page component or reuse existing pattern
    - File: `resources/js/pages/auth/guest-convention-invalid.tsx`
    - Accept `reason` prop (`'expired'` or `'invalid'`)
    - Display appropriate error message based on reason
    - Include link to home page
    - Guest-appropriate copy (not invitation language)
    - _Requirements: 6.1, 6.2, 6.3_

- [x] 8. Write backend unit tests for edge cases and error conditions
  - [x] 8.1 Write unit tests for confirmation page
    - Test confirmation page renders without authentication
    - Test confirmation page contains instructional text about checking email
    - _Requirements: 2.2, 2.3, 2.4_

  - [x] 8.2 Write unit tests for set password page
    - Test set password page renders without authentication with valid signed URL
    - Test validation rejects missing password or confirmation
    - _Requirements: 4.2, 4.5_

  - [x] 8.3 Write unit tests for signed URL error handling
    - Test expired URL (>24h) renders error page with `reason='expired'`
    - Test tampered URL renders error page with `reason='invalid'`
    - Test error page contains link to home page
    - _Requirements: 6.1, 6.2, 6.3_

- [x] 9. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Property tests use Pest PHP with faker for 100+ iterations per property
- The implementation follows existing invitation flow patterns (`InvitationController`, `UserInvitation` mailable) with guest-specific adaptations
- Frontend pages use `AuthLayout` and follow existing auth page conventions
- No database schema changes required
