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
    
    // Check the admin-panel client configuration
    $stmt = $pdo->prepare("
        SELECT client_id, user_handler_endpoint, app_redirect_uri 
        FROM clients 
        WHERE client_id = 'admin-panel'
    ");
    
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "Client Configuration:\n";
    print_r($result);
    
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}
?>