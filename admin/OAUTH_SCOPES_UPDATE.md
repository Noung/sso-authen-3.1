# OAuth Scopes Update - Documentation

## Overview
This document describes the changes made to the SSO-Authen Admin Panel regarding OAuth/OIDC scopes configuration for client applications.

## Background

### The Issue
The Admin Panel previously allowed administrators to select OAuth scopes (`openid`, `profile`, `email`, `phone`, `address`) when creating or editing client applications. However, PSU SSO (the OIDC provider) has a fixed claim mapping configuration that:
- **Supports**: User ID, username, Thai/English names, email, and department information
- **Does NOT support**: Phone numbers or physical addresses

This created a mismatch where administrators could select `phone` and `address` scopes in the UI, but these would never return any data from PSU SSO.

### Understanding Scopes vs Claims

**Scopes** are permission requests - what you *ask for*:
- Sent in the OAuth/OIDC authentication request
- Examples: `openid`, `profile`, `email`, `phone`, `address`
- Defined by OAuth 2.0 / OIDC standards

**Claims** are actual data - what you *get back*:
- Specific user information fields returned by the provider
- Examples: `sub`, `name`, `email`, `psu_id`, `department_th`
- Provider-specific (determined by PSU SSO's configuration)

**The key insight**: Requesting a scope doesn't guarantee you'll receive the corresponding claims - it depends on what the provider actually supports.

## Changes Made

### 1. UI Changes (`admin/views/clients.php`)

**Before:**
- 5 scope checkboxes in a 2-column layout
- All scopes were user-selectable (except openid was required)
- Phone and address scopes were available but misleading

**After:**
- 3 scope checkboxes in a single column
- All checkboxes are **checked and disabled** (visual only, not editable)
- Only `openid`, `profile`, and `email` are shown
- `phone` and `address` options removed entirely
- Added info alert explaining these are PSU SSO's supported scopes
- Updated help text to clarify scopes are fixed by provider configuration

### 2. JavaScript Changes (`admin/public/js/client-management.js`)

**Updated Functions:**
- `showAddClientModal()`: Removed references to `scope-phone` and `scope-address`
- Event listener for scope checkboxes: Simplified to prevent any changes (forces openid, profile, email only)

### 3. Documentation Updates

#### A. Client Management Documentation (`admin/CLIENT_MANAGEMENT.md`)
- Updated "Creating a New Client" section to mention fixed scopes
- Added new "Understanding OAuth Scopes" subsection explaining:
  - Difference between scopes and claims
  - Why phone/address are not supported
  - That scopes are configured by PSU SSO provider

#### B. API Documentation (`admin/public/api-docs-v3.html`)
- Updated "Adding a New Client" section to list allowed scopes as fixed
- **Added comprehensive new section: "OAuth 2.0 / OIDC Scopes"** covering:
  - Scopes vs Claims explanation with visual cards
  - Table of supported scopes with status badges
  - Available claims from PSU SSO with example JSON
  - Claim mapping table (normalized fields ↔ PSU SSO claims)
  - Technical explanation of why some scopes aren't supported
  - Mermaid sequence diagram showing scope/claim flow
  - Best practices for developers
- Added navigation link to new "OAuth Scopes" section in sidebar

## Technical Details

### PSU SSO Claim Mapping
Based on `config/providers/psu.php`:

```php
'claim_mapping' => [
    'id'        => 'psu_id',
    'username'  => 'preferred_username',
    'name'      => 'display_name_th',
    'firstName' => 'first_name_th',
    'lastName'  => 'last_name_th',
    'email'     => 'email',
    'department' => 'department_th'
]
```

PSU SSO **does NOT** provide:
- `phone_number` or `phone_number_verified` claims
- `address` claim (structured address object)

Therefore, requesting `phone` or `address` scopes would be futile.

### Fixed Scopes Configuration
All clients now use: `openid,profile,email`

This is:
- Stored in the hidden field: `<input type="hidden" id="allowedScopes" value="openid,profile,email">`
- Sent to the backend when creating/updating clients
- Used in actual OAuth authentication requests to PSU SSO

## Impact Analysis

### Breaking Changes
**None** - This is a UI-only change with no breaking changes:
- Existing clients maintain their scope settings
- The backend already defaulted to `openid,profile,email` for new clients
- Authentication flow remains unchanged
- API responses remain unchanged

### User Experience Improvements
✅ **Eliminates confusion** - Admins can no longer select scopes that won't work
✅ **Sets correct expectations** - Clear messaging about PSU SSO limitations  
✅ **Educational** - Documentation now explains OAuth scopes vs claims
✅ **Accurate** - UI reflects actual provider capabilities

## Files Modified

1. **`admin/views/clients.php`** (Lines 428-475)
   - Removed phone and address scope checkboxes
   - Made openid, profile, email disabled (checked, required)
   - Updated help text and labels
   - Added info alert about PSU SSO configuration

2. **`admin/public/js/client-management.js`** (Lines 267-272, 1258-1270)
   - Removed scope-phone and scope-address references
   - Simplified scope checkbox event handlers

3. **`admin/CLIENT_MANAGEMENT.md`** (Lines 100-135)
   - Updated "Creating a New Client" section
   - Added "Understanding OAuth Scopes" explanation

4. **`admin/public/api-docs-v3.html`** (Lines 3528, 3777+)
   - Updated client creation documentation
   - Added comprehensive 280+ line "OAuth Scopes" section
   - Added navigation menu item

## Testing Recommendations

### Manual Testing Checklist
- [ ] Open Client Management page
- [ ] Click "Add New Client"
- [ ] Verify only 3 scopes are visible (openid, profile, email)
- [ ] Verify all 3 scopes are checked and disabled
- [ ] Verify info alert is displayed
- [ ] Create a new client - confirm scopes are saved as `openid,profile,email`
- [ ] Edit existing client - verify same scope UI
- [ ] Check API documentation - verify new OAuth Scopes section

### Browser Compatibility
Tested on:
- Chrome/Edge (Chromium)
- Firefox
- Safari (if available)

## Migration Notes

### For Administrators
No action required. The UI change is transparent and doesn't affect existing clients.

### For Developers
If you're integrating a new client application:
- Your application will receive normalized user data via JWT
- Available fields: `id`, `username`, `name`, `firstName`, `lastName`, `email`, `department`
- Do NOT expect: `phone` or `address` fields from PSU SSO
- Implement your User Handler API to map and extend user data as needed

## Future Considerations

### If PSU SSO Adds New Claims
To add support for new scopes/claims:

1. Update `config/providers/psu.php` claim mapping
2. Update UI in `admin/views/clients.php` to add new scope checkboxes
3. Update JavaScript validation in `client-management.js`
4. Update documentation in `CLIENT_MANAGEMENT.md` and `api-docs-v3.html`
5. Update this document

### If Supporting Multiple Providers
Consider:
- Making scopes configurable per provider
- Dynamic UI generation based on provider capabilities
- Provider selection in client configuration

## References

- **OpenID Connect Core Specification**: https://openid.net/specs/openid-connect-core-1_0.html#ScopeClaims
- **OAuth 2.0 RFC 6749**: https://tools.ietf.org/html/rfc6749
- **PSU SSO Configuration**: `config/providers/psu.php`

## Author & Date
- **Updated**: 2025-10-22
- **Version**: SSO-Authen V.3
- **Change Type**: UI Enhancement & Documentation Improvement

---

**Summary**: This update aligns the Admin Panel UI with PSU SSO's actual capabilities, provides better developer education about OAuth scopes vs claims, and prevents configuration mistakes. No breaking changes, improved UX, comprehensive documentation.
