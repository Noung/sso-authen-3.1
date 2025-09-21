<?php
/**
 * Simple test to verify PHP is working
 */

echo "<h1>PHP Test</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script Name: " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>";

echo "<h2>Test Navigation</h2>";
$basePath = dirname($_SERVER['SCRIPT_NAME']);
echo '<p><a href="' . $basePath . '/debug.php">Debug Information</a></p>';
echo '<p><a href="' . $basePath . '/index.php">Admin Panel</a></p>';
echo '<p><a href="' . $basePath . '/test_routing.php">Routing Test</a></p>';

echo "<h2>File System Check</h2>";
echo "<p>Index.php exists: " . (file_exists(__DIR__ . '/index.php') ? 'YES' : 'NO') . "</p>";
echo "<p>Config directory exists: " . (is_dir(__DIR__ . '/../config') ? 'YES' : 'NO') . "</p>";
echo "<p>Controllers directory exists: " . (is_dir(__DIR__ . '/../src/Controllers') ? 'YES' : 'NO') . "</p>";

phpinfo();
?>