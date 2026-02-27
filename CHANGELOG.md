# Changelog

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
