<?php
// Simple database check script
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
    
    // Check if tables exist
    $tables = ['clients', 'admin_users', 'audit_logs'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' exists\n";
            
            if ($table === 'clients') {
                $countStmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $count = $countStmt->fetch(PDO::FETCH_ASSOC);
                echo "  - Records in $table: " . $count['count'] . "\n";
            }
        } else {
            echo "✗ Table '$table' missing\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    echo "You need to run the database installation.\n";
}
?>