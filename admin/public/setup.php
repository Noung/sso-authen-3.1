<?php
/**
 * Quick Database Setup for Admin Panel
 * Run this script to set up the database for the admin panel
 */

echo "<h1>SSO-Authen Admin Panel - Database Setup</h1>\n";

// Include the installation script
try {
    echo "<h2>Running Database Installation...</h2>\n";
    echo "<pre>\n";
    
    // Capture output from install script
    ob_start();
    include __DIR__ . '/../../database/install.php';
    $output = ob_get_clean();
    
    echo htmlspecialchars($output);
    echo "</pre>\n";
    
    echo "<h2>✅ Installation Complete!</h2>\n";
    echo "<p><a href='login.php' class='btn btn-primary'>Go to Admin Panel</a></p>\n";
    echo "<p><strong>Default Test Credentials:</strong><br>";
    echo "- Client ID: my_react_app<br>";
    echo "- Client Secret: secret<br>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Installation Failed</h2>\n";
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Please check your database configuration in: <code>admin/config/admin_config.php</code></p>\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>SSO Admin - Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .btn { 
            display: inline-block; 
            padding: 10px 20px; 
            background: #007bff; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
        }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; }
        h1 { color: #333; }
        h2 { color: #666; }
    </style>
</head>
<body>
<!-- Content is generated above -->
</body>
</html>