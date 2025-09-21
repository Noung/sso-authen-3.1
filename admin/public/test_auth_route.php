<?php
/**
 * Specific test for /auth/login route
 */

// Start session
session_start();

echo "<h2>Auth Login Route Test</h2>";

// Simulate the exact routing logic from index.php
$requestUri = '/sso-authen-3/admin/public/auth/login';  // This is what should happen
$scriptName = '/sso-authen-3/admin/public/index.php';

$basePath = dirname($scriptName);
echo "Base Path: " . $basePath . "<br>";

$path = str_replace($basePath, '', $requestUri);
$path = parse_url($path, PHP_URL_PATH);
$path = '/' . trim($path, '/');

echo "Extracted Path from '/sso-authen-3/admin/public/auth/login': " . $path . "<br>";

echo "<h3>Route Matching Test:</h3>";
switch ($path) {
    case '/':
    case '/index.php':
        echo "Matches: Dashboard route<br>";
        break;
        
    case '/auth/login':
    case '/auth/login.php':
        echo "✅ Matches: Login route<br>";
        break;
        
    case '/auth/logout':
    case '/auth/logout.php':
        echo "Matches: Logout route<br>";
        break;
        
    default:
        echo "❌ No route match found - would show 404<br>";
        break;
}

echo "<h3>Direct Access Test:</h3>";
echo '<a href="' . $basePath . '/auth/login">Direct Login Link</a><br>';
echo '<a href="' . $basePath . '/">Back to Dashboard</a><br>';

// Test if the issue is URL rewriting
echo "<h3>URL Rewriting Test:</h3>";
echo "Current REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "If this page was accessed via /auth/login, the routing should work.<br>";

?>

<script>
// Test what happens when we navigate to auth/login
function testAuthLogin() {
    window.location.href = '/sso-authen-3/admin/public/auth/login';
}
</script>

<button onclick="testAuthLogin()">Navigate to Auth/Login</button>