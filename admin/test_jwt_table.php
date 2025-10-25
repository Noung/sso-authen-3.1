<?php
// Test script to verify JWT secret history table creation

require_once __DIR__ . '/../admin/src/Database/Connection.php';

use SsoAdmin\Database\Connection;

// Initialize database connection
$config = require __DIR__ . '/../admin/config/admin_config.php';
Connection::init($config['database']);

try {
    // Check if the jwt_secret_history table exists
    $sql = "SHOW TABLES LIKE 'jwt_secret_history'";
    $result = Connection::fetchOne($sql);
    
    if ($result) {
        echo "✅ jwt_secret_history table exists\n";
        
        // Try to insert a test record
        $sql = "INSERT INTO jwt_secret_history (secret_key, created_by, notes, is_active) VALUES (?, ?, ?, ?)";
        Connection::query($sql, ['test_secret_key_12345', 'test_admin', 'Test entry', true]);
        
        echo "✅ Test record inserted successfully\n";
        
        // Retrieve the test record
        $sql = "SELECT * FROM jwt_secret_history WHERE secret_key = ?";
        $record = Connection::fetchOne($sql, ['test_secret_key_12345']);
        
        if ($record) {
            echo "✅ Test record retrieved successfully\n";
            echo "Record ID: " . $record['id'] . "\n";
            echo "Created by: " . $record['created_by'] . "\n";
            echo "Created at: " . $record['created_at'] . "\n";
            
            // Clean up test record
            $sql = "DELETE FROM jwt_secret_history WHERE id = ?";
            Connection::query($sql, [$record['id']]);
            echo "✅ Test record cleaned up\n";
        } else {
            echo "❌ Failed to retrieve test record\n";
        }
    } else {
        echo "❌ jwt_secret_history table does not exist\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}