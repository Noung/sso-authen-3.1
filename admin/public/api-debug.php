<?php
// API Debug Script
session_start();

// Force login for testing
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_email'] = 'admin@psu.ac.th';
$_SESSION['admin_name'] = 'System Administrator';

echo "<h1>API Debug - Client Management</h1>";

// Load configuration and setup
require_once __DIR__ . '/../src/Database/Connection.php';
require_once __DIR__ . '/../src/Models/Client.php';
require_once __DIR__ . '/../src/Controllers/ClientController.php';
$config = require __DIR__ . '/../config/admin_config.php';

try {
    // Test database connection
    echo "<h2>1. Database Connection Test</h2>";
    \SsoAdmin\Database\Connection::init($config['database']);
    $pdo = \SsoAdmin\Database\Connection::getPdo();
    echo "<p>✓ Database connection successful</p>";
    
    // Test clients table
    echo "<h2>2. Clients Table Test</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM clients");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>✓ Clients table exists with " . $count['count'] . " records</p>";
    
    // Test sample query
    $stmt = $pdo->query("SELECT * FROM clients LIMIT 3");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Sample clients:</h3><pre>" . print_r($clients, true) . "</pre>";
    
    // Test Client Controller
    echo "<h2>3. Client Controller Test</h2>";
    $controller = new \SsoAdmin\Controllers\ClientController();
    
    // Mock request and response objects
    $mockRequest = new class {
        public function getQueryParams() {
            return ['page' => 1, 'per_page' => 10, 'search' => '', 'status' => ''];
        }
    };
    
    $mockResponse = new class {
        public $content = '';
        public function getBody() { return $this; }
        public function write($content) { $this->content = $content; }
        public function withHeader($name, $value) {
            header("$name: $value");
            return $this;
        }
    };
    
    $controller->getAll($mockRequest, $mockResponse);
    
    echo "<h3>API Response:</h3>";
    echo "<pre>" . $mockResponse->content . "</pre>";
    
    // Test statistics
    echo "<h2>4. Statistics Test</h2>";
    $mockResponse2 = new class {
        public $content = '';
        public function getBody() { return $this; }
        public function write($content) { $this->content = $content; }
        public function withHeader($name, $value) {
            header("$name: $value");
            return $this;
        }
        public function withStatus($code) {
            http_response_code($code);
            return $this;
        }
    };
    
    $controller->getStatistics(new stdClass(), $mockResponse2);
    echo "<h3>Statistics Response:</h3>";
    echo "<pre>" . $mockResponse2->content . "</pre>";
    
    echo "<h2>✅ All Tests Completed!</h2>";
    echo "<p><a href='clients' class='btn btn-primary'>Go to Client Management</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .btn { 
        display: inline-block; 
        padding: 10px 20px; 
        background: #007bff; 
        color: white; 
        text-decoration: none; 
        border-radius: 4px; 
        margin-top: 10px;
    }
    pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
    h1, h2, h3 { color: #333; }
</style>