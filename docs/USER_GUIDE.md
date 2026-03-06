# User Guide

This guide walks through the main features of the Convention Management System from an end-user perspective.

## Getting Started

After logging in, you land on the conventions list. From there, navigate to a convention to manage it or create a new one.

If you received an invitation email or a guest convention verification email, click the link to set your password and activate your account. The link expires after 24 hours — ask your convention manager to resend it if needed.

## Creating a Convention

1. Go to **Conventions** and click **Create Convention**
2. Fill in the required fields:
   - Name
   - City and Country
   - Start date and End date
   - Optionally: address and additional information
3. Click **Create**

The system checks for date conflicts — you cannot create a convention that overlaps with an existing one in the same city and country.

You are automatically assigned as **Owner** and **ConventionUser**, giving you full control over the convention.

## Managing the Venue

Conventions are organized into a hierarchy: **Convention → Floors → Sections**.

### Adding Floors

1. Open your convention
2. Click **Add Floor**
3. Enter a floor name

Only Owner and ConventionUser roles can add or delete floors. FloorUsers can rename their assigned floors but cannot add new ones.

### Adding Sections

Sections can be managed directly from the Floors page:

1. Navigate to **Floors** within your convention
2. Click **Add Section** in the page header
3. Select a floor from the dropdown (auto-selected if you only have access to one floor)
4. Fill in:
   - Section name
   - Number of seats (capacity)
   - Optionally: elder-friendly, handicap-friendly, additional information
5. Click **Add Section**

To edit a section, expand a floor row and click the pencil icon next to the section. To delete, click the trash icon and confirm.

Sections start with 0% occupancy and 0 available seats.

The floor dropdown shows only floors you have access to based on your role. Owner and ConventionUser see all floors; FloorUser sees only assigned floors. SectionUser cannot create sections.

## Tracking Occupancy

Each section has three ways to update its occupancy:

### Percentage Dropdown
Select from predefined values: 0%, 10%, 25%, 50%, 75%, 100%. The value saves automatically when you select it.

### FULL Button
A single-tap panic button that immediately sets occupancy to 100%. Use this when a section fills up quickly.

### Available Seats Input
Enter the exact number of available seats and tap **Send**. The system calculates the occupancy percentage automatically using the formula:

```
occupancy = 100 - ((available_seats / total_seats) * 100)
```

All updates record who made the change and when. This information appears in the section detail footer.

### Color Coding

Occupancy levels are color-coded across all views:

| Occupancy | Color | Meaning |
|-----------|-------|---------|
| 0–25% | Green | Plenty of space |
| 26–50% | Dark green | Comfortable |
| 51–75% | Yellow | Filling up |
| 76–90% | Orange | Nearly full |
| 91–100% | Red | Full |

### Daily Reset

All occupancy data resets automatically at 6:00 AM each day — occupancy goes to 0% and available seats resets to the section's total capacity — so each convention day starts fresh.

## Attendance Reporting

Attendance reporting collects headcounts from each section during morning and afternoon periods.

### Starting a Report

1. A ConventionUser or Owner clicks **Start Attendance Report** on the convention page
2. The system determines the current period (morning before 12:00, afternoon after)
3. A banner appears showing the active report with a counter: "X of Y sections reported"

You can start a maximum of 2 reports per day (one morning, one afternoon).

### Reporting Attendance

1. Navigate to your assigned section
2. Enter the attendance count
3. The system records your count along with your name and the timestamp

Only the person who originally reported can update the count for that section (until the period is locked).

### Stopping a Report

1. The ConventionUser or Owner clicks **Stop Attendance Report**
2. If not all sections have reported, a confirmation warning appears
3. Once stopped, the period is locked permanently — no further changes are possible
4. Locked period data appears on the convention page as historical records

## Managing Users

### Inviting Users

1. Go to **Users** within your convention
2. Click **Add User**
3. Fill in: first name, last name, email, mobile
4. Select one or more roles:
   - **Owner** — full control
   - **ConventionUser** — convention-wide access
   - **FloorUser** — access to specific floors (select which floors)
   - **SectionUser** — access to specific sections (select which sections)
5. Click **Invite**

The system sends an invitation email with a secure link. If the email already belongs to an existing user, that user is connected to your convention instead of creating a duplicate account.

### Email Confirmation Status

- Green checkmark: email is confirmed
- Warning icon: email is not yet confirmed

You can click **Resend Invitation** to send a new activation link. This is rate-limited to 3 resends per hour.

### Editing Users

Click a user to edit their details or change their roles. Role changes take effect immediately — floor and section assignments are synced automatically.

### Removing Users

Click **Delete** on a user to remove them from the convention. All their role and assignment records for this convention are cleaned up. If the user has no other conventions, their account is deleted entirely.

## Roles and Permissions

Your role determines what you can see and do:

| Capability | Owner | ConventionUser | FloorUser | SectionUser |
|-----------|-------|----------------|-----------|-------------|
| View convention | Yes | Yes | Yes | Yes |
| Edit convention | Yes | Yes | No | No |
| Delete convention | Yes | No | No | No |
| Export data | Yes | No | No | No |
| Manage all floors | Yes | Yes | No | No |
| Edit assigned floors | Yes | Yes | Yes | No |
| Manage all sections | Yes | Yes | No | No |
| Edit assigned sections | Yes | Yes | Yes | Yes |
| Manage all users | Yes | Yes | No | No |
| Start/stop attendance | Yes | Yes | No | No |
| Report attendance | Yes | Yes | Yes (assigned) | Yes (assigned) |
| Search sections | Yes | Yes | Yes | Yes |

FloorUsers see only their assigned floors and the sections within them. SectionUsers see only their assigned sections. The navigation and data tables automatically adjust to show only what you have access to.

## Searching for Available Sections

The Search page is available to all authenticated users regardless of role.

1. Go to **Search** within a convention
2. Optionally filter by:
   - Floor (dropdown)
   - Elder-friendly sections (checkbox)
   - Handicap-friendly sections (checkbox)
3. Results show only sections with less than 90% occupancy
4. Results are sorted by occupancy (lowest first) so the most available sections appear at the top
5. Click a result to navigate to the section detail

## Exporting Convention Data

Owners can export the complete convention dataset:

1. On the convention page, click the **Export** dropdown
2. Choose a format:
   - **.xlsx** — Excel workbook with multiple sheets (convention details, floors/sections, attendance history, users)
   - **.docx** — Word document with formatted tables
   - **Markdown** — Plain text Markdown file
3. The file downloads automatically

The export includes all floors, sections (with capacity and occupancy), the full attendance history, and all users with their roles.

## Installing as a Mobile App (PWA)

The application can be installed on your phone's home screen for a native-like experience.

### Android (Chrome)
1. Open the app in Chrome
2. Tap the menu (three dots) in the top right
3. Tap **Add to Home screen** or **Install app**
4. Confirm the installation

### iOS (Safari)
1. Open the app in Safari
2. Tap the Share button (square with arrow)
3. Scroll down and tap **Add to Home Screen**
4. Tap **Add**

Once installed, the app opens in full-screen mode without the browser address bar, and loads quickly thanks to cached assets.

## Update Notifications

When a new version of the application is released, a notification modal appears automatically. It shows your current version, the new version number, release name, and release notes. You can click **Reload Now** to update immediately, or dismiss the modal to continue working — it won't reappear until the next check cycle (every 5 minutes).

## Account Settings

Access your settings from the user menu:

- **Profile** — Update your name and email. Changing your email triggers a confirmation email to the new address.
- **Password** — Change your password. Must meet the requirements: minimum 8 characters, mixed case, number, and symbol.
- **Two-Factor Authentication** — Enable TOTP-based 2FA for extra security. Scan the QR code with an authenticator app and save your recovery codes.
- **Appearance** — Switch between light and dark mode.

## Troubleshooting

**Invitation link expired:** Ask your convention manager to resend the invitation from the Users page.

**Can't see floors or sections:** Your role may not have access. Check with your convention manager about your assigned role and scope.

**Occupancy not updating:** Make sure you have edit permission for the section. FloorUsers can only update sections on their assigned floors; SectionUsers can only update their assigned sections.

**Attendance report won't start:** The maximum is 2 reports per day. If both morning and afternoon periods have already been created, you'll need to wait until the next day.

**Rate limit error on resend:** Invitation resends are limited to 3 per hour. Wait and try again later.
