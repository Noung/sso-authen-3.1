<?php
// Test client creation with updated created_by field
require_once __DIR__ . '/src/Database/Connection.php';
require_once __DIR__ . '/src/Models/Client.php';

// Load admin configuration
$config = require __DIR__ . '/config/admin_config.php';

// Initialize database connection
\SsoAdmin\Database\Connection::init($config['database']);

// Start session and set admin email
session_start();
$_SESSION['admin_email'] = 'test-admin@psu.ac.th';

try {
    echo "Testing client creation with admin email...\n";
    
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
    echo "Creating client...\n";
    $createdClient = \SsoAdmin\Models\Client::create($testClientData);
    
    echo "✓ Client created successfully\n";
    echo "Client ID: " . $createdClient['client_id'] . "\n";
    
    // Verify the created_by field
    $clientId = $createdClient['id'];
    $pdo = \SsoAdmin\Database\Connection::getPdo();
    $stmt = $pdo->prepare("SELECT created_by FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "Created by: " . $result['created_by'] . "\n";
        if ($result['created_by'] === 'test-admin@psu.ac.th') {
            echo "✓ SUCCESS: created_by field correctly populated with admin email\n";
        } else {
            echo "✗ FAILED: created_by field not populated correctly\n";
        }
    } else {
        echo "✗ FAILED: Could not retrieve client from database\n";
    }
    
    // Test updating client
    echo "\nTesting client update with admin email...\n";
    $updateData = [
        'client_name' => 'Updated Test Client Application',
        'client_description' => 'Updated description for testing updated_by field'
    ];
    
    // Change admin email for update test
    $_SESSION['admin_email'] = 'update-admin@psu.ac.th';
    
    $updatedClient = \SsoAdmin\Models\Client::update($clientId, $updateData);
    echo "✓ Client updated successfully\n";
    
    // Verify the updated_by field
    $stmt = $pdo->prepare("SELECT updated_by FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "Updated by: " . $result['updated_by'] . "\n";
        if ($result['updated_by'] === 'update-admin@psu.ac.th') {
            echo "✓ SUCCESS: updated_by field correctly populated with admin email\n";
        } else {
            echo "✗ FAILED: updated_by field not populated correctly\n";
        }
    } else {
        echo "✗ FAILED: Could not retrieve client from database\n";
    }
    
    // Clean up - delete the test client
    echo "\nCleaning up test client...\n";
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    echo "✓ Test client deleted\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Error in file: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}

echo "\nTest completed.\n";
?>