<?php
// Debug script to test database connection and API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug SSO-Authen Admin Panel</h1>";

// Test 1: Check if files exist
echo "<h2>1. File Existence Check</h2>";
$files = [
    __DIR__ . '/../src/Database/Connection.php',
    __DIR__ . '/../src/Models/Client.php',
    __DIR__ . '/../src/Controllers/ClientController.php',
    __DIR__ . '/../config/admin_config.php'
];

foreach ($files as $file) {
    echo "<p>" . basename($file) . ": " . (file_exists($file) ? "✓ EXISTS" : "✗ MISSING") . "</p>";
}

// Test 2: Load configuration
echo "<h2>2. Configuration Check</h2>";
try {
    $config = require __DIR__ . '/../config/admin_config.php';
    echo "<p>✓ Config loaded successfully</p>";
    echo "<pre>Database config: " . print_r($config['database'], true) . "</pre>";
} catch (Exception $e) {
    echo "<p>✗ Config error: " . $e->getMessage() . "</p>";
}

// Test 3: Database connection
echo "<h2>3. Database Connection Test</h2>";
try {
    require_once __DIR__ . '/../src/Database/Connection.php';
    \SsoAdmin\Database\Connection::init($config['database']);
    $pdo = \SsoAdmin\Database\Connection::getPdo();
    echo "<p>✓ Database connection successful</p>";
    
    // Check if oauth_clients table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'oauth_clients'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✓ oauth_clients table exists</p>";
        
        // Get table structure
        $stmt = $pdo->query('DESCRIBE oauth_clients');
        echo "<h3>Table Structure:</h3><pre>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " (" . $row['Type'] . ")\n";
        }
        echo "</pre>";
        
        // Count records
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM oauth_clients');
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Records count: " . $count['count'] . "</p>";
        
    } else {
        echo "<p>✗ oauth_clients table does not exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p>✗ Database error: " . $e->getMessage() . "</p>";
}

// Test 4: Client Model
echo "<h2>4. Client Model Test</h2>";
try {
    require_once __DIR__ . '/../src/Models/Client.php';
    echo "<p>✓ Client model loaded successfully</p>";
    
    // Test getting all clients
    $result = \SsoAdmin\Models\Client::getAll(1, 10, '', '');
    echo "<p>✓ Client::getAll() method works</p>";
    echo "<pre>Result: " . print_r($result, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<p>✗ Client model error: " . $e->getMessage() . "</p>";
    echo "<pre>Stack trace: " . $e->getTraceAsString() . "</pre>";
}

// Test 5: Session check
echo "<h2>5. Session Check</h2>";
session_start();
echo "<p>Session status: " . session_status() . "</p>";
echo "<p>Admin logged in: " . (isset($_SESSION['admin_logged_in']) ? 'YES' : 'NO') . "</p>";
if (isset($_SESSION['admin_logged_in'])) {
    echo "<pre>Session data: " . print_r($_SESSION, true) . "</pre>";
}