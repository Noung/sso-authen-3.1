<?php
// Test client creation with updated created_by field (Web version)
require_once __DIR__ . '/src/Database/Connection.php';
require_once __DIR__ . '/src/Models/Client.php';

// Load admin configuration
$config = require __DIR__ . '/config/admin_config.php';

// Initialize database connection
\SsoAdmin\Database\Connection::init($config['database']);

// Start session and set admin email
session_start();
$_SESSION['admin_email'] = 'test-admin@psu.ac.th';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Client Creation Test</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>Client Creation Test</h1>
    
    <?php
    try {
        echo "<p class='info'>Testing client creation with admin email...</p>\n";
        
        // Test data for new client
        $testClientData = [
            'client_name' => 'Test Client Application',
            'client_description' => 'Test client for verifying created_by field',
            'app_redirect_uri' => 'http://localhost:3000/callback',
            'post_logout_redirect_uri' => 'http://localhost:3000/logout',
            'user_handler_endpoint' => 'http://localhost:8080/api/user-handler',
            'api_secret_key' => 'TEST_SECRET_KEY',
            'allowed_scopes' => 'openid,profile,email',
            'status' => 'active'
        ];
        
        // Create client
        echo "<p class='info'>Creating client...</p>\n";
        $createdClient = \SsoAdmin\Models\Client::create($testClientData);
        
        echo "<p class='success'>✓ Client created successfully</p>\n";
        echo "<p>Client ID: " . htmlspecialchars($createdClient['client_id']) . "</p>\n";
        
        // Verify the created_by field
        $clientId = $createdClient['id'];
        $pdo = \SsoAdmin\Database\Connection::getPdo();
        $stmt = $pdo->prepare("SELECT created_by FROM clients WHERE id = ?");
        $stmt->execute([$clientId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "<p>Created by: " . htmlspecialchars($result['created_by']) . "</p>\n";
            if ($result['created_by'] === 'test-admin@psu.ac.th') {
                echo "<p class='success'>✓ SUCCESS: created_by field correctly populated with admin email</p>\n";
            } else {
                echo "<p class='error'>✗ FAILED: created_by field not populated correctly</p>\n";
            }
        } else {
            echo "<p class='error'>✗ FAILED: Could not retrieve client from database</p>\n";
        }
        
        // Test updating client
        echo "<h2>Testing Client Update</h2>\n";
        echo "<p class='info'>Testing client update with admin email...</p>\n";
        $updateData = [
            'client_name' => 'Updated Test Client Application',
            'client_description' => 'Updated description for testing updated_by field'
        ];
        
        // Change admin email for update test
        $_SESSION['admin_email'] = 'update-admin@psu.ac.th';
        
        $updatedClient = \SsoAdmin\Models\Client::update($clientId, $updateData);
        echo "<p class='success'>✓ Client updated successfully</p>\n";
        
        // Verify the updated_by field
        $stmt = $pdo->prepare("SELECT updated_by FROM clients WHERE id = ?");
        $stmt->execute([$clientId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "<p>Updated by: " . htmlspecialchars($result['updated_by']) . "</p>\n";
            if ($result['updated_by'] === 'update-admin@psu.ac.th') {
                echo "<p class='success'>✓ SUCCESS: updated_by field correctly populated with admin email</p>\n";
            } else {
                echo "<p class='error'>✗ FAILED: updated_by field not populated correctly</p>\n";
            }
        } else {
            echo "<p class='error'>✗ FAILED: Could not retrieve client from database</p>\n";
        }
        
        // Clean up - delete the test client
        echo "<h2>Cleanup</h2>\n";
        echo "<p class='info'>Cleaning up test client...</p>\n";
        $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
        $stmt->execute([$clientId]);
        echo "<p class='success'>✓ Test client deleted</p>\n";
        
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        echo "<p class='error'>Error in file: " . htmlspecialchars($e->getFile()) . " on line " . htmlspecialchars($e->getLine()) . "</p>\n";
    }
    
    echo "<h2>Test completed.</h2>\n";
    ?>
</body>
</html>