# Changelog

## 1.3 — 2026-02-27

### Added
- **Business Owner Dashboard** — verified owners can log in and manage their listing from a frontend dashboard (`/my-business/`).
- **Business Revision System** — owners can submit changes to their listing via an edit form; changes go through admin review before going live.
- **Automatic account creation** — a WordPress user account with `business_owner` role is created when a claim is approved; the owner receives a "set your password" email.
- **Admin diff view** — when reviewing a revision submission, admins see a side-by-side comparison of current vs proposed values.
- Auto-created "My Business" and "Edit My Business" pages on plugin activation.
- Frontend login form embedded in the dashboard page (no redirect to wp-login.php).

### Changed
- Business owners are redirected to the frontend dashboard instead of wp-admin on login.
- Admin bar is hidden for business owners.
- Approval emails now include a link to the owner dashboard.
- `revision` submission type added to admin dropdowns and column labels.

## 1.2 — 2026-02-27

### Added
- **Claim Your Business — hybrid verification flow**
  - Email verification path: businesses with a listed email receive a verification link; clicking it auto-approves the claim instantly.
  - Manual review path: businesses without an email (or when the claimant can't access it) fall back to a proof-of-ownership form reviewed by an admin.
  - "Can't access this email?" toggle lets claimants switch between the two paths.
- Auto-created "Claim Your Business" page with the `[claim_business_form]` shortcode (created on activation or first load).
- New `email_pending` submission status visible in the admin submissions list, with the option for admins to manually approve.
- Email notifications to admin when a verification email is sent.
- Verification tokens stored as WordPress transients with 24-hour expiry.

### Changed
- "Claim This Business" button now links to the dedicated claim page instead of appending an unhandled query parameter to the business URL.
- Claim form pre-selects the business and hides the business dropdown when accessed from a specific listing.

### Fixed
- Clicking "Claim This Business" on a business profile now actually does something.
