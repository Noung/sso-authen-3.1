<?php
/**
 * Simple test script to verify admin panel routing
 */

// Test URL detection
echo "<h2>Admin Panel URL Routing Test</h2>";

echo "<h3>Server Variables:</h3>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "<br>";

$basePath = dirname($_SERVER['SCRIPT_NAME']);
echo "Detected Base Path: " . $basePath . "<br>";

echo "<h3>Test Links:</h3>";
echo '<a href="' . $basePath . '">Dashboard</a><br>';
echo '<a href="' . $basePath . '/clients">Clients</a><br>';
echo '<a href="' . $basePath . '/auth/login">Login</a><br>';
echo '<a href="' . $basePath . '/auth/logout">Logout</a><br>';

echo "<h3>Admin Panel Access:</h3>";
echo '<a href="' . $basePath . '/index.php" class="btn btn-primary">Access Admin Panel</a>';
?>