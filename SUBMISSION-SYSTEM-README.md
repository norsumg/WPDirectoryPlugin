# Business Submission System

## Overview
The Local Business Directory plugin now includes a comprehensive business submission system that allows businesses to submit new listings or claim existing ones.

## Features

### 1. New Business Submissions
- Public-facing form for businesses to submit new listings
- Comprehensive form with all business fields
- Admin approval workflow
- Email notifications
- Spam protection (honeypot fields, rate limiting)

### 2. Business Claims
- Allow businesses to claim existing listings
- Verification process for ownership
- Admin approval workflow
- Email notifications

### 3. Admin Management
- Dedicated admin interface for managing submissions
- Approve/reject functionality
- Automatic business creation from approved submissions
- Email notifications to business owners

## Shortcodes

### Submit Business Form
```
[submit_business_form title="Submit Your Business" description="Add your business to our directory"]
```

### Claim Business Form
```
[claim_business_form title="Claim Your Business" description="Is your business already listed? Claim ownership"]
```

### Claim Business Button
```
[claim_business_button business_id="123" text="Claim This Business"]
```

## Admin Interface

### Business Submissions Menu
- Located under the main Business menu in WordPress admin
- Shows all submissions with status, type, and business owner info
- Sortable columns for easy management

### Submission Details
Each submission includes:
- **Submission Details**: Status, type, dates, reviewer notes
- **Business Owner Information**: Name, email, phone
- **Submission Actions**: Approve/reject buttons with AJAX functionality

## Workflow

### New Business Submission
1. Business owner fills out submission form
2. Form data is validated and stored as `business_submission` post
3. Admin receives email notification
4. Admin reviews submission in WordPress admin
5. Admin approves → creates new `business` post
6. Business owner receives approval email

### Business Claim
1. Business owner selects existing business and fills claim form
2. Claim is validated and stored as `business_submission` post
3. Admin receives email notification
4. Admin reviews claim and verifies ownership
5. Admin approves → updates business with owner info
6. Business owner receives approval email

## Database Structure

### business_submission Post Type
- **Status**: pending, approved, rejected
- **Type**: new_business, claim_business
- **Business Owner**: name, email, phone
- **Tracking**: submission_date, reviewed_date, reviewer_id, reviewer_notes
- **Claim Specific**: claimed_business_id
- **Original Data**: JSON string of all submission data

### Business Post Updates
- **Owner Information**: lbd_owner_name, lbd_owner_email, lbd_owner_phone
- **Claim Status**: lbd_claimed, lbd_claimed_date

## Security Features

### Form Protection
- Nonce verification
- Honeypot fields
- Rate limiting (5 minutes between submissions)
- Input sanitization and validation
- Duplicate submission prevention

### Admin Security
- Permission checks for all admin actions
- Nonce verification for AJAX requests
- Secure email notifications

## Email Notifications

### Admin Notifications
- New business submission received
- New business claim received
- Includes business details and admin links

### Business Owner Notifications
- Submission received confirmation
- Approval notification with business URL
- Rejection notification with optional reason

## CSS Classes

### Form Styling
- `.lbd-submission-form-container` - Main form container
- `.lbd-claim-form-container` - Claim form container
- `.form-section` - Form sections with blue left border
- `.form-field` - Individual form fields
- `.submit-button` - Submit button styling

### Status Messages
- `.lbd-submission-success` - Success message styling
- `.lbd-submission-error` - Error message styling
- `.business-claimed-notice` - Already claimed notice
- `.business-claim-pending` - Pending claim notice

## Usage Examples

### Add Submission Form to Page
```
[submit_business_form title="Add Your Business" description="Join our local business directory"]
```

### Add Claim Button to Business Page
```
[claim_business_button]
```

### Custom Claim Button
```
[claim_business_button business_id="456" text="I Own This Business" class="custom-claim-btn"]
```

## File Structure

```
includes/submission/
├── class-business-submission.php      # Main submission class
├── submission-handler.php             # Form processing
├── submission-shortcodes.php          # Shortcode definitions
└── submission-actions.php             # AJAX handlers

assets/css/
└── submission-forms.css               # Form styling
```

## Version History

- **1.0.08**: Initial implementation of business submission system
  - New business submission forms
  - Business claim functionality
  - Admin approval workflow
  - Email notifications
  - Security features

## Future Enhancements

### Phase 2 (Planned)
- Business owner portal
- Premium listing features
- Advanced verification methods
- Bulk submission handling

### Phase 3 (Planned)
- Enhanced email templates
- SMS notifications
- Integration with business directories
- Analytics and reporting 