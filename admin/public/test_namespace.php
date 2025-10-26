<?php

// Test script to check namespace and class loading

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing namespace and class loading...\n";

// Try to manually include the Connection class
$connectionFile = __DIR__ . '/../src/Database/Connection.php';
echo "Looking for Connection.php at: $connectionFile\n";

if (file_exists($connectionFile)) {
    echo "Connection.php file exists\n";
    require_once $connectionFile;
    
    echo "After including Connection.php\n";
    
    // Check if class exists with full namespace
    echo "Checking if SsoAdmin\Database\Connection class exists...\n";
    if (class_exists('\\SsoAdmin\\Database\\Connection')) {
        echo "SUCCESS: \\SsoAdmin\\Database\\Connection class exists\n";
    } else {
        echo "FAILED: \\SsoAdmin\\Database\\Connection class does NOT exist\n";
    }
    
    // Check if class exists without leading backslash
    echo "Checking if SsoAdmin\Database\Connection class exists...\n";
    if (class_exists('SsoAdmin\Database\Connection')) {
        echo "SUCCESS: SsoAdmin\Database\Connection class exists\n";
    } else {
        echo "FAILED: SsoAdmin\Database\Connection class does NOT exist\n";
    }
    
    // List all declared classes that might be related
    echo "Looking for classes with 'Connection' in the name:\n";
    $classes = get_declared_classes();
    foreach ($classes as $class) {
        if (stripos($class, 'Connection') !== false) {
            echo "  Found: $class\n";
        }
    }
    
    // List all declared classes that might be related to SsoAdmin
    echo "Looking for classes with 'SsoAdmin' in the name:\n";
    foreach ($classes as $class) {
        if (stripos($class, 'SsoAdmin') !== false) {
            echo "  Found: $class\n";
        }
    }
} else {
    echo "Connection.php file does NOT exist\n";
}

echo "Test completed.\n";