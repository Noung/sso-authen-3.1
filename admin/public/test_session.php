<?php
// Session test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Session Test</h1>\n";

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Session Status:</h2>\n";
echo "<p>Session ID: " . session_id() . "</p>\n";
echo "<p>Session Status: " . session_status() . "</p>\n";

echo "<h2>Session Data:</h2>\n";
echo "<pre>" . print_r($_SESSION, true) . "</pre>\n";

echo "<h2>Setting Test Value...</h2>\n";
$_SESSION['test'] = 'test_value';
echo "<p>Set \$_SESSION['test'] = 'test_value'</p>\n";

echo "<h2>Session Data After Setting:</h2>\n";
echo "<pre>" . print_r($_SESSION, true) . "</pre>\n";

echo "<h2>Server Variables:</h2>\n";
echo "<p>REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "</p>\n";
echo "<p>SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "</p>\n";
?>