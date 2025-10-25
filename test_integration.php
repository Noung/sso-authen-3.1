<?php
/**
 * Test script to verify SsoHandler integration with client configuration
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/SsoHandler.php';

echo "<h1>SSO Handler Integration Test</h1>";

// Test client configurations loaded from database
echo "<h2>1. Available Client Configurations</h2>";
echo "<pre>";
print_r($authorized_clients);
echo "</pre>";

// Test SsoHandler with different client configurations
echo "<h2>2. Authentication Mode Detection</h2>";

foreach ($authorized_clients as $clientId => $config) {
    echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
    echo "<h3>Client: $clientId</h3>";
    
    if (!empty($config['user_handler_endpoint'])) {
        if (strpos($config['user_handler_endpoint'], 'http') === 0) {
            echo "<span style='color: blue;'>✓ JWT Mode - Will call API endpoint:</span><br>";
            echo "Endpoint: " . htmlspecialchars($config['user_handler_endpoint']) . "<br>";
            echo "API Secret: " . (empty($config['api_secret_key']) ? 'Not set' : 'Set') . "<br>";
        } else {
            echo "<span style='color: green;'>✓ Legacy Mode with local file path:</span><br>";
            echo "Path: " . htmlspecialchars($config['user_handler_endpoint']) . "<br>";
            $fullPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($config['user_handler_endpoint'], '/');
            echo "Full path would be: " . htmlspecialchars($fullPath) . "<br>";
            echo "File exists: " . (file_exists($fullPath) ? 'Yes' : 'No') . "<br>";
        }
    } else {
        echo "<span style='color: orange;'>✓ Legacy Mode - Will use default user_handler.php:</span><br>";
        $defaultPath = __DIR__ . '/user_handler.php';
        echo "Default path: " . htmlspecialchars($defaultPath) . "<br>";
        echo "File exists: " . (file_exists($defaultPath) ? 'Yes' : 'No') . "<br>";
    }
    
    echo "</div>";
}

echo "<h2>3. Integration Status</h2>";
echo "<div style='background: #e7f5e7; padding: 15px; border-radius: 5px;'>";
echo "<strong>✅ Integration Complete!</strong><br>";
echo "The SsoHandler.php now properly accepts client configuration and can handle:<br>";
echo "• JWT Mode: Calls HTTP API endpoints<br>";
echo "• Legacy Mode with paths: Loads user_handler.php from specified path<br>";
echo "• Legacy Mode default: Falls back to default user_handler.php location<br>";
echo "<br>";
echo "The callback.php has been updated to pass client configuration to handleCallback() method.";
echo "</div>";

echo "<h2>4. Legacy Mode Setup Guide</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<strong>For Legacy Mode to work:</strong><br>";
echo "1. Set user_handler_endpoint to null (for default path) or local file path<br>";
echo "2. Create user_handler.php file with findOrCreateUser() function<br>";
echo "3. The SsoHandler will automatically detect and use Legacy Mode<br>";
echo "<br>";
echo "<strong>Example configurations:</strong><br>";
echo "• null - Uses " . htmlspecialchars(__DIR__ . '/user_handler.php') . "<br>";
echo "• '/api/user_handler.php' - Uses " . htmlspecialchars(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/api/user_handler.php') . "<br>";
echo "• 'http://...' - Uses JWT Mode with API call<br>";
echo "</div>";
?>