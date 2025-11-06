<?php
require_once 'admin/config/admin_config.php';
$config = require 'admin/config/admin_config.php';

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['database']['host'],
        $config['database']['port'],
        $config['database']['database'],
        $config['database']['charset']
    );

    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], $config['database']['options']);
    
    // Update the admin-panel client to use Legacy Mode
    $stmt = $pdo->prepare("
        UPDATE clients 
        SET user_handler_endpoint = '/admin/public/auth/user_handler.php',
            updated_at = NOW()
        WHERE client_id = 'admin-panel'
    ");
    
    $result = $stmt->execute();
    
    if ($result) {
        echo "✓ Successfully updated admin-panel client to use Legacy Mode\n";
    } else {
        echo "✗ Failed to update admin-panel client\n";
    }
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}
?>