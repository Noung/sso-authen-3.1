## Issue Summary

The authentication request logs were not being recorded in the `audit_logs` table when clients initiated authentication requests to the provider.

## Root Cause

The issue was in the database connection initialization in both `public/login.php` and `public/callback.php`. The files were attempting to use the `SsoAdmin\Database\Connection` class without properly initializing it with the database configuration.

In the original code:

```php
require_once __DIR__ . '/../admin/src/Database/Connection.php';
$db = SsoAdmin\Database\Connection::getPdo();
```

The `Connection::getPdo()` method was failing because the database configuration was never initialized, causing the logging code to silently fail and not record any authentication events.

## Fix Applied

Added proper database connection initialization in both files:

1. In `public/login.php`:

```php
require_once __DIR__ . '/../admin/src/Database/Connection.php';
// Initialize database connection properly
$adminConfig = require __DIR__ . '/../admin/config/admin_config.php';
SsoAdmin\Database\Connection::init($adminConfig['database']);
$db = SsoAdmin\Database\Connection::getPdo();
```

2. In `public/callback.php`:

```php
require_once __DIR__ . '/../admin/src/Database/Connection.php';
// Initialize database connection properly
$adminConfig = require __DIR__ . '/../admin/config/admin_config.php';
SsoAdmin\Database\Connection::init($adminConfig['database']);
$db = SsoAdmin\Database\Connection::getPdo();
```

This was added in both the success and error handling sections of `callback.php`.

## Verification

Created and ran multiple test scripts to verify:

1. Database connection functionality
2. Authentication logging functionality
3. Proper error handling
4. Complete authentication flow logging

All tests passed, confirming that authentication requests will now be properly logged in the `audit_logs` table with the appropriate actions:

- `oidc_login_initiated` - When a client initiates an authentication request
- `oidc_auth_success` - When authentication completes successfully
- `oidc_auth_failed` - When authentication fails

## Impact

With this fix, the system will now properly track all authentication requests in the audit logs, enabling better monitoring, security analysis, and usage statistics as intended.
