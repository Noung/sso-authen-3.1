<?php
$config = require_once 'admin/config/admin_config.php';
require_once 'admin/src/Database/Connection.php';

try {
    SsoAdmin\Database\Connection::init($config['database']);
    
    // Check for admin@psu.ac.th
    $stmt = SsoAdmin\Database\Connection::getPdo()->query("SELECT * FROM admin_users WHERE email = 'admin@psu.ac.th'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Admin user data (admin@psu.ac.th):\n";
    var_dump($result);
    
    if ($result) {
        echo "User role: " . $result['role'] . "\n";
    } else {
        echo "No admin user found with email 'admin@psu.ac.th'\n";
    }
    
    echo "\n";
    
    // Check for kittisak.k@psu.ac.th
    $stmt2 = SsoAdmin\Database\Connection::getPdo()->query("SELECT * FROM admin_users WHERE email = 'kittisak.k@psu.ac.th'");
    $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    echo "Admin user data (kittisak.k@psu.ac.th):\n";
    var_dump($result2);
    
    if ($result2) {
        echo "User role: " . $result2['role'] . "\n";
    } else {
        echo "No admin user found with email 'kittisak.k@psu.ac.th'\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>