<?php
/**
 * Debug login access - Test what happens when accessing /auth/login
 */

// Start session
session_start();

echo "<h2>Login Route Debug</h2>";

echo "<h3>URL Information:</h3>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "<br>";

$basePath = dirname($_SERVER['SCRIPT_NAME']);
echo "Base Path: " . $basePath . "<br>";

$requestUri = $_SERVER['REQUEST_URI'];
$path = str_replace($basePath, '', $requestUri);
$path = parse_url($path, PHP_URL_PATH);
$path = '/' . trim($path, '/');

echo "Extracted Path: " . $path . "<br>";

echo "<h3>Session Information:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Admin logged in: " . (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] ? 'YES' : 'NO') . "<br>";

echo "<h3>Path Matching Test:</h3>";
echo "Does path match '/auth/login'? " . ($path === '/auth/login' ? 'YES' : 'NO') . "<br>";
echo "Does path match '/auth/login.php'? " . ($path === '/auth/login.php' ? 'YES' : 'NO') . "<br>";

echo "<h3>Test Links:</h3>";
echo '<a href="' . $basePath . '/auth/login">Test Login URL</a><br>';
echo '<a href="' . $basePath . '/">Back to Admin Panel</a><br>';
echo '<a href="' . $basePath . '/simple_admin.php">Simple Admin (Working)</a><br>';

echo "<h3>Manual Login Form Test:</h3>";
?>

<form method="POST" action="<?php echo $basePath; ?>/">
    <input type="hidden" name="action" value="dev_login">
    <button type="submit" class="btn btn-primary">Manual Dev Login</button>
</form>

<h3>Manual Login via JavaScript:</h3>
<button onclick="testLogin()">Test JavaScript Login</button>

<script>
function testLogin() {
    fetch("<?php echo $basePath; ?>/", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            action: "dev_login",
            email: "admin@psu.ac.th",
            name: "System Administrator"
        })
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Login successful! Redirecting...");
            window.location.href = "<?php echo $basePath; ?>/";
        } else {
            alert("Login failed!");
        }
    }).catch(error => {
        console.error("Error:", error);
        alert("Login error!");
    });
}
</script>