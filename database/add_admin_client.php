<?php
// Script to add admin panel as a client in the database
$config = require __DIR__ . '/../admin/config/admin_config.php';

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['database']['host'],
        $config['database']['port'],
        $config['database']['database'],
        $config['database']['charset']
    );

    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], $config['database']['options']);
    echo "✓ Database connection successful\n";

    // Insert admin panel as a client
    $stmt = $pdo->prepare("
        INSERT INTO clients 
        (client_id, client_name, client_description, app_redirect_uri, post_logout_redirect_uri, user_handler_endpoint, api_secret_key, allowed_scopes, status, created_by) 
        VALUES 
        (:client_id, :client_name, :client_description, :app_redirect_uri, :post_logout_redirect_uri, :user_handler_endpoint, :api_secret_key, :allowed_scopes, :status, :created_by)
        ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP
    ");

    $result = $stmt->execute([
        'client_id' => 'admin-panel',
        'client_name' => 'SSO-Authen Admin Panel',
        'client_description' => 'Admin panel for managing the SSO system',
        'app_redirect_uri' => 'http://localhost:8080/sso-authen-3/admin/public/auth/callback.php',
        'post_logout_redirect_uri' => 'http://localhost:8080/sso-authen-3/admin/public/login.php',
        'user_handler_endpoint' => null,
        'api_secret_key' => 'admin-panel-secret-key',
        'allowed_scopes' => 'openid,profile,email',
        'status' => 'active',
        'created_by' => 'system'
    ]);

    if ($result) {
        echo "✓ Successfully added/updated admin panel as a client\n";
    } else {
        echo "✗ Failed to add admin panel as a client\n";
    }
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}
