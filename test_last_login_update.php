<?php
require_once __DIR__ . '/admin/config/admin_config.php';
require_once __DIR__ . '/admin/src/Controllers/AuthController.php';

use SsoAdmin\Controllers\AuthController;

$config = require __DIR__ . '/admin/config/admin_config.php';

// Check current last_login_at value for admin@psu.ac.th
$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $config['database']['host'],
    $config['database']['port'],
    $config['database']['database'],
    $config['database']['charset']
);

try {
    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], $config['database']['options']);

    // Get current last_login_at value
    $stmt = $pdo->prepare("SELECT email, last_login_at FROM admin_users WHERE email = ? LIMIT 1");
    $stmt->execute(['admin@psu.ac.th']);

    $user = $stmt->fetch();

    if ($user) {
        echo "Before update:\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Last login at: " . ($user['last_login_at'] ?? 'NULL') . "\n";

        // Create AuthController instance and call updateLastLogin
        $authController = new AuthController();
        // Use reflection to call the private method
        $reflection = new ReflectionClass($authController);
        $method = $reflection->getMethod('updateLastLogin');
        $method->setAccessible(true);
        $method->invoke($authController, 'admin@psu.ac.th');

        // Check updated last_login_at value
        $stmt = $pdo->prepare("SELECT email, last_login_at FROM admin_users WHERE email = ? LIMIT 1");
        $stmt->execute(['admin@psu.ac.th']);

        $user = $stmt->fetch();

        echo "\nAfter update:\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Last login at: " . ($user['last_login_at'] ?? 'NULL') . "\n";
    } else {
        echo "User admin@psu.ac.th not found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
