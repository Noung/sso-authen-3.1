<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_email'] = 'admin@psu.ac.th';
$_SESSION['admin_name'] = 'System Administrator';

// Include required files
require_once __DIR__ . '/../src/Database/Connection.php';
require_once __DIR__ . '/../src/Models/AdminUser.php';
require_once __DIR__ . '/../src/Controllers/AdminUserController.php';

// Load admin configuration
$config = require __DIR__ . '/../config/admin_config.php';

// Initialize database connection
\SsoAdmin\Database\Connection::init($config['database']);

// Test the AdminUser model directly
try {
    echo "Testing AdminUser model methods...\n";
    
    // Test getAllWithPagination
    echo "Testing getAllWithPagination...\n";
    $result = \SsoAdmin\Models\AdminUser::getAllWithPagination(1, 10, '', '');
    echo "Result: " . print_r($result, true) . "\n";
    
    echo "Testing successful!\n";
    
} catch (Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "Error caught: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}