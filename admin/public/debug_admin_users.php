<?php
// Debug script for admin users functionality
require_once __DIR__ . '/../vendor/autoload.php';

// Load main project autoloader (conditional)
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} else {
    // For now, manually include required files
    require_once __DIR__ . '/../src/Database/Connection.php';
    require_once __DIR__ . '/../src/Models/AdminUser.php';
    require_once __DIR__ . '/../src/Controllers/AdminUserController.php';
}

use SsoAdmin\Database\Connection;
use SsoAdmin\Models\AdminUser;
use SsoAdmin\Controllers\AdminUserController;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Load admin configuration
$config = require __DIR__ . '/../config/admin_config.php';

echo "<h1>Admin Users Debug Script</h1>\n";

try {
    echo "<h2>1. Testing Database Connection</h2>\n";
    
    // Initialize database connection
    Connection::init($config['database']);
    
    // Test connection
    if (Connection::testConnection()) {
        echo "<p style='color: green;'>✓ Database connection successful</p>\n";
    } else {
        echo "<p style='color: red;'>✗ Database connection failed</p>\n";
    }
    
    echo "<h2>2. Testing AdminUser Model</h2>\n";
    
    // Test getting all admin users
    try {
        $users = AdminUser::getAll();
        echo "<p style='color: green;'>✓ AdminUser::getAll() successful</p>\n";
        echo "<p>Found " . count($users) . " admin users</p>\n";
        
        if (!empty($users)) {
            echo "<h3>Sample users:</h3>\n";
            echo "<ul>\n";
            foreach (array_slice($users, 0, 3) as $user) {
                echo "<li>ID: " . htmlspecialchars($user['id']) . ", Email: " . htmlspecialchars($user['email']) . ", Name: " . htmlspecialchars($user['name']) . "</li>\n";
            }
            echo "</ul>\n";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ AdminUser::getAll() failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
    }
    
    echo "<h2>3. Testing AdminUserController</h2>\n";
    
    // Test controller
    try {
        $controller = new AdminUserController();
        echo "<p style='color: green;'>✓ AdminUserController instantiation successful</p>\n";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ AdminUserController instantiation failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ General error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}

echo "<h2>4. Configuration Details</h2>\n";
echo "<p>Host: " . htmlspecialchars($config['database']['host']) . "</p>\n";
echo "<p>Database: " . htmlspecialchars($config['database']['database']) . "</p>\n";
echo "<p>Username: " . htmlspecialchars($config['database']['username']) . "</p>\n";
echo "<p>Password: " . (empty($config['database']['password']) ? '[EMPTY]' : '[SET]') . "</p>\n";
?>