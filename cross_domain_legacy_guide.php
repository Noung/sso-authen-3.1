<?php
/**
 * Legacy App Migration Guide
 * How to convert local user_handler.php to HTTP endpoint for cross-domain use
 */

echo "<h1>Legacy Mode Cross-Domain Migration Guide</h1>";

echo "<h2>Current Limitation</h2>";
echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>❌ Won't Work:</strong> Different domains with local file paths<br>";
echo "sso-authen.com cannot access files on legacy-app.com via require_once<br>";
echo "</div>";

echo "<h2>✅ Solution: Convert to HTTP Endpoint</h2>";

echo "<h3>Step 1: Backup Original File</h3>";
echo "<pre>";
echo "# In your legacy app domain
cp api/user_handler.php api/user_handler_original.php
";
echo "</pre>";

echo "<h3>Step 2: Create HTTP Wrapper</h3>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
echo "<strong>Create new api/user_handler.php:</strong><br>";
echo "<code>";
echo htmlspecialchars("<?php
// Handle HTTP requests
if (\$_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get data from sso-authen
    \$input = file_get_contents('php://input');
    \$data = json_decode(\$input, true);
    
    // Include original logic
    require_once __DIR__ . '/user_handler_original.php';
    
    // Call existing function
    \$result = findOrCreateUser(\$data['normalizedUser'], \$data['ssoUserInfo']);
    
    // Return JSON
    header('Content-Type: application/json');
    echo json_encode(\$result);
}");
echo "</code>";
echo "</div>";

echo "<h3>Step 3: Update SSO Configuration</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<strong>In sso-authen admin panel, set:</strong><br>";
echo "<code>user_handler_endpoint: http://legacy-app.com/api/user_handler.php</code><br>";
echo "<code>api_secret_key: YOUR_SECRET_KEY</code>";
echo "</div>";

echo "<h3>Step 4: Test the Integration</h3>";
echo "<pre>";
echo "# Test the endpoint manually:
curl -X POST http://legacy-app.com/api/user_handler.php \\
  -H 'Content-Type: application/json' \\
  -H 'X-API-SECRET: YOUR_SECRET_KEY' \\
  -d '{\"normalizedUser\": {\"id\":\"test\", \"email\":\"test@example.com\", \"name\":\"Test User\"}, \"ssoUserInfo\": {}}'
";
echo "</pre>";

echo "<h2>🎯 Benefits of HTTP Endpoint Approach</h2>";
echo "<ul>";
echo "<li>✅ Works across different domains</li>";
echo "<li>✅ Maintains existing user handler logic</li>";
echo "<li>✅ Adds API security with secret key</li>";
echo "<li>✅ No need to modify sso-authen core</li>";
echo "<li>✅ Can still create sessions locally in legacy app</li>";
echo "</ul>";

echo "<h2>⚠️ Alternative: Same-Domain Deployment</h2>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
echo "<strong>If you want true Legacy Mode:</strong><br>";
echo "Deploy both sso-authen and legacy app on the same domain/server<br>";
echo "Example: <code>mycompany.com/sso/</code> and <code>mycompany.com/legacy-app/</code>";
echo "</div>";
?>