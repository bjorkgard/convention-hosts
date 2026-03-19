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

You are automatically assigned as **Owner** and **Administrator**, giving you full control over the convention.

## Managing the Venue

Conventions are organized into a hierarchy: **Convention → Floors → Sections**.

### Adding Floors

1. Open your convention
2. Click **Add Floor**
3. Enter a floor name

Only Owner and Administrator roles can add or delete floors.

### Adding Sections

Sections can be managed directly from the Floors page:

1. Navigate to **Administration** within your convention
2. Click **Add Section** in the page header
3. Select a floor from the dropdown (auto-selected if you only have access to one floor)
4. Fill in:
   - Section name
   - Number of seats (capacity)
   - Optionally: elder-friendly, handicap-friendly, additional information
5. Click **Add Section**

To edit a section, expand a floor row and click the pencil icon next to the section. To delete, click the trash icon and confirm.

Sections start with 0% occupancy and 0 available seats.

The floor dropdown shows only floors you have access to based on your role. Owner and Administrator see all floors. URL session users cannot create sections.

## Section Detail View

Clicking a section name (from the floors page or search results) opens the section detail page. This page shows:

- Section name, parent floor, and convention context
- Occupancy gauge with current percentage
- Seat capacity and accessibility badges (elder-friendly, handicap-friendly)
- Additional information text, if any
- All three occupancy update controls (dropdown, FULL button, available seats input)
- A help note explaining that occupancy resets automatically every night
- Last update footer showing who updated occupancy and when

If an attendance report is active, an attendance card appears below the section details where you can enter or update the attendance count for the current period.

Owner and Administrator roles see a **Delete** button in the header to permanently remove the section and all its data.

## Tracking Occupancy

Each section has three ways to update its occupancy:

### Percentage Dropdown
Select from predefined values: 0%, 10%, 25%, 50%, 75%, 100%. The value saves automatically when you select it.

### FULL Button
A single-tap panic button that immediately sets occupancy to 100%. Use this when a section fills up quickly.

### Available Seats Input
Enter the exact number of available seats and tap **Send**. The system calculates a raw occupancy percentage, then snaps it to the closest dropdown value (0%, 10%, 25%, 50%, 75%, or 100%) so the occupancy gauge always matches one of the predefined levels.

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

1. An Owner or Administrator clicks **Start Attendance Report** on the convention page
2. The system determines the current period (morning before 12:00, afternoon after)
3. A banner appears showing the active report with a counter: "X of Y sections reported"

You can start a maximum of 2 reports per day (one morning, one afternoon).

### Reporting Attendance

1. Navigate to your assigned section
2. Enter the attendance count
3. The system records your count along with the timestamp

Any user with section permissions (authenticated or via URL) can create or update attendance reports.

### Stopping a Report

1. The Owner or Administrator clicks **Stop Attendance Report**
2. If not all sections have reported, a confirmation warning appears
3. Once stopped, the period is locked permanently — no further changes are possible
4. Locked period data appears on the convention page as historical records

## Managing Users

### Inviting Users

1. Go to **Users** within your convention
2. Click **Add User**
3. Fill in: first name, last name, email, mobile
4. Select a role:
   - **Owner** — full control
   - **Administrator** — convention-wide access (manage floors, sections, users, attendance)
5. Click **Invite**

The system sends an invitation email with a secure link. If the email already belongs to an existing user, that user is connected to your convention instead of creating a duplicate account.

For floor and section volunteer access, share the Floor Access URL or Section Access URL from the convention page instead of creating user accounts.

### Email Confirmation Status

- Green checkmark: email is confirmed
- Warning icon: email is not yet confirmed

Any user with convention access can click **Resend Invitation** to send a new activation link (the button is disabled once the email is confirmed). This is rate-limited to 3 resends per hour.

### Editing Users

Click a user to edit their details or change their roles. Role changes take effect immediately — floor and section assignments are synced automatically.

### Removing Users

Click **Delete** on a user to remove them from the convention. All their role and assignment records for this convention are cleaned up. If the user has no other conventions, their account is deleted entirely.

## Roles and Permissions

Your role determines what you can see and do:

| Capability | Owner | Administrator | Floor URL | Section URL |
|-----------|-------|---------------|-----------|-------------|
| View convention | Yes | Yes | Yes | Yes |
| Edit convention | Yes | Yes | No | No |
| Delete convention | Yes | No | No | No |
| Export data | Yes | No | No | No |
| Manage floors | Yes | Yes | No | No |
| View floors | Yes | Yes | Yes | No |
| Manage sections | Yes | Yes | No | No |
| View sections | Yes | Yes | Yes | Yes |
| Update occupancy | Yes | Yes | Yes | Yes |
| Manage users | Yes | Yes | No | No |
| Start/stop attendance | Yes | Yes | No | No |
| Report attendance | Yes | Yes | Yes | Yes |
| Search sections | Yes | Yes | Yes | Yes |

URL session users access the convention via a shareable link and see a simplified interface without user account options.

## URL-Based Volunteer Access

Instead of creating user accounts for every volunteer, convention organizers can share access URLs.

### Sharing Access URLs

1. Open your convention page as an Owner or Administrator
2. Find the "Floor Access URL" and "Section Access URL" displayed on the page
3. Click the copy button next to the URL you want to share
4. Send the URL to your volunteers via messaging app, email, or printed QR code

### Floor Access URL

Volunteers who open this link can:
- View all floors and sections
- Update occupancy for any section
- Report attendance for any section

They cannot create/edit/delete floors or sections, manage users, or start/stop attendance reports.

### Section Access URL

Volunteers who open this link can:
- View all sections
- Update occupancy for any section
- Report attendance for any section

They cannot view floors, manage users, or start/stop attendance reports.

### URL Session Behavior

- No login is required — the link opens directly into the convention
- The interface is simplified: no user menu, profile, or logout options
- Breadcrumb navigation omits the "Conventions" link since URL session users only have access to a single convention
- The Apple theme is applied automatically
- If the browser session expires, the volunteer simply re-opens the link

## Searching for Available Sections

The Search page is available to all authenticated users regardless of role.

1. Go to **Availability** within a convention
2. Optionally filter by:
   - Floor (dropdown)
   - Elder-friendly sections (checkbox)
   - Handicap-friendly sections (checkbox)
3. Results show only sections with less than 90% occupancy
4. Results are sorted by occupancy (lowest first) so the most available sections appear at the top
5. Click a result to navigate to the section detail page

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

On your first visit from a mobile browser, an install dialog appears automatically to guide you through the process. If you dismiss it, the dialog won't appear again — you can still install later using the **Install App** button in the sidebar.

### Android (Chrome)
1. Open the app in Chrome
2. Tap **Install App** when the dialog appears (or use the sidebar button)
3. Confirm the installation

### iOS (Safari)
1. Open the app in Safari
2. When the install dialog appears, follow the instructions:
   - Tap the Share button (square with arrow)
   - Scroll down and tap **Add to Home Screen**
   - Tap **Add**

Once installed, the app opens in full-screen mode without the browser address bar, and loads quickly thanks to cached assets.

## Update Notifications

When a new version of the application is released, a notification modal appears automatically. It shows your current version, the new version number, release name, and release notes. You can click **Reload Now** to update immediately — this clears cached assets and service workers to ensure you get the latest version cleanly. Alternatively, dismiss the modal to continue working — it won't reappear until the next check cycle (every 5 minutes).

## Cookie Consent

When you first visit the application, a consent banner appears at the bottom of the screen asking whether you accept optional cookies (used for theme and appearance preferences).

- **Accept** — preference cookies are stored normally in your browser
- **Decline** — only essential cookies (session, CSRF) are used; theme preferences fall back to localStorage

Your choice is remembered per device. If the application introduces new cookie functionality, the banner reappears so you can review and decide again.

Authenticated users have their consent decision saved to their account (synced across devices). Anonymous users accessing via a shared URL have their choice stored in the browser session only.

## Account Settings

Access your settings from the user menu:

- **Profile** — Update your name and email. Changing your email triggers a confirmation email to the new address.
- **Password** — Change your password. Must meet the requirements: minimum 8 characters, mixed case, number, and symbol.
- **Two-Factor Authentication** — Enable TOTP-based 2FA for extra security. Scan the QR code with an authenticator app and save your recovery codes.
- **Appearance** — Switch between light and dark mode.

## Troubleshooting

**Invitation link expired:** Ask your convention manager to resend the invitation from the Users page.

**Can't see floors or sections:** Your role may not have access. Check with your convention manager about your assigned role and scope.

**Occupancy not updating:** Make sure you have permission for the section. Authenticated users with Owner or Administrator roles can update any section. URL session users can update occupancy for sections within their access scope. If you are logged in and also opened a URL access link, the URL session grants you occupancy update permissions regardless of your account's role on the convention.

**Attendance report won't start:** The maximum is 2 reports per day. If both morning and afternoon periods have already been created, you'll need to wait until the next day.

**Rate limit error on resend:** Invitation resends are limited to 3 per hour. Wait and try again later.
