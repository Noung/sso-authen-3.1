<?php

/**
 * database/install.php
 * Database installation script
 * Sets up database and initial data for SSO-Authen Admin Panel
 */

// Load admin configuration
$adminConfig = require __DIR__ . '/../admin/config/admin_config.php';

try {
    // Connect to MySQL server (without specifying database)
    $dsn = sprintf(
        'mysql:host=%s;charset=%s',
        $adminConfig['database']['host'],
        $adminConfig['database']['charset']
    );
    
    $pdo = new PDO(
        $dsn,
        $adminConfig['database']['username'],
        $adminConfig['database']['password'],
        $adminConfig['database']['options']
    );
    
    echo "Connected to MySQL server successfully.\n";
    
    // Create database if it doesn't exist
    $dbName = $adminConfig['database']['database'];
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '$dbName' created or already exists.\n";
    
    // Switch to the database
    $pdo->exec("USE `$dbName`");
    echo "Switched to database '$dbName'.\n";
    
    // Read and execute schema
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    $schema = file_get_contents($schemaFile);
    
    // Execute schema directly (MySQL can handle multiple statements)
    try {
        $pdo->exec($schema);
        echo "Database schema installed successfully.\n";
    } catch (PDOException $e) {
        // If exec fails, try splitting by semicolons
        echo "Trying to execute statements individually...\n";
        
        // Remove comments and split statements
        $lines = explode("\n", $schema);
        $statements = [];
        $currentStatement = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines and comments
            if (empty($line) || strpos($line, '--') === 0) {
                continue;
            }
            
            $currentStatement .= ' ' . $line;
            
            // If line ends with semicolon, it's end of statement
            if (substr($line, -1) === ';') {
                $statements[] = trim($currentStatement);
                $currentStatement = '';
            }
        }
        
        // Execute each statement
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    echo "Executed: " . substr($statement, 0, 50) . "...\n";
                } catch (PDOException $e2) {
                    echo "Warning: Failed to execute statement: " . $e2->getMessage() . "\n";
                    echo "Statement: " . substr($statement, 0, 100) . "...\n";
                }
            }
        }
    }
    
    // Insert sample data from existing config.php
    insertSampleClients($pdo);
    
    echo "\n✅ Database installation completed successfully!\n";
    echo "Admin Panel URL: http://localhost/sso-authen-3/admin/public/\n";
    echo "Default admin login: admin@psu.ac.th (Development mode)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Insert sample clients from existing config
 * @param PDO $pdo
 */
function insertSampleClients($pdo) {
    $sampleClients = [
        [
            'client_id' => 'my_react_app',
            'client_secret' => password_hash('secret', PASSWORD_DEFAULT),
            'client_name' => 'React Application',
            'client_description' => 'Sample React application for testing SSO integration',
            'app_redirect_uri' => 'http://localhost:3000/callback',
            'post_logout_redirect_uri' => 'http://localhost:3000/logout-success',
            'user_handler_endpoint' => 'http://localhost:8080/api/sso-user-handler',
            'api_secret_key' => 'VERY_SECRET_KEY_FOR_REACT_APP',
            'allowed_scopes' => 'openid,profile,email',
            'status' => 'active',
            'created_by' => 'installer'
        ],
        [
            'client_id' => 'my_js_app',
            'client_secret' => password_hash('secret', PASSWORD_DEFAULT),
            'client_name' => 'JavaScript Application',
            'client_description' => 'Sample JavaScript application using Live Server',
            'app_redirect_uri' => 'http://localhost:5500/public/callback.html',
            'post_logout_redirect_uri' => 'http://localhost:5500/public/index.html',
            'user_handler_endpoint' => 'http://localhost:8080/sso-user-handler',
            'api_secret_key' => 'VERY_SECRET_KEY_FOR_JS_APP',
            'allowed_scopes' => 'openid,profile,email',
            'status' => 'active',
            'created_by' => 'installer'
        ],
        [
            'client_id' => 'legacy_php_app',
            'client_secret' => password_hash('secret', PASSWORD_DEFAULT),
            'client_name' => 'Legacy PHP Application',
            'client_description' => 'Legacy PHP application using JWT-based authentication',
            'app_redirect_uri' => 'http://my-php-app.test/sso_callback.php',
            'post_logout_redirect_uri' => 'http://my-php-app.test/',
            'user_handler_endpoint' => 'http://my-php-app.test/api/user_handler.php',
            'api_secret_key' => 'ANOTHER_SECRET_KEY_FOR_PHP_APP',
            'allowed_scopes' => 'openid,profile,email',
            'status' => 'active',
            'created_by' => 'installer'
        ],
        [
            'client_id' => 'very_old_php_app',
            'client_secret' => password_hash('secret', PASSWORD_DEFAULT),
            'client_name' => 'Very Old PHP Application',
            'client_description' => 'Legacy PHP application using session-based authentication (V.2 mode)',
            'app_redirect_uri' => 'http://old-app.test/index.php',
            'post_logout_redirect_uri' => 'http://old-app.test/',
            'user_handler_endpoint' => null,
            'api_secret_key' => null,
            'allowed_scopes' => 'openid,profile',
            'status' => 'active',
            'created_by' => 'installer'
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO clients (
            client_id, client_secret, client_name, client_description,
            app_redirect_uri, post_logout_redirect_uri,
            user_handler_endpoint, api_secret_key, allowed_scopes,
            status, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            client_name = VALUES(client_name),
            client_description = VALUES(client_description),
            app_redirect_uri = VALUES(app_redirect_uri),
            post_logout_redirect_uri = VALUES(post_logout_redirect_uri),
            user_handler_endpoint = VALUES(user_handler_endpoint),
            api_secret_key = VALUES(api_secret_key),
            allowed_scopes = VALUES(allowed_scopes),
            updated_at = CURRENT_TIMESTAMP
    ");
    
    foreach ($sampleClients as $client) {
        $stmt->execute([
            $client['client_id'],
            $client['client_secret'],
            $client['client_name'],
            $client['client_description'],
            $client['app_redirect_uri'],
            $client['post_logout_redirect_uri'],
            $client['user_handler_endpoint'],
            $client['api_secret_key'],
            $client['allowed_scopes'],
            $client['status'],
            $client['created_by']
        ]);
    }
    
    echo "Sample clients inserted successfully.\n";
}