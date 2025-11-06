<?php
/**
 * admin/public/auth/user_handler.php
 * User handler for the admin panel - Legacy Mode
 */

/**
 * Find or create user in the admin_users table
 *
 * @param array $normalizedUser Normalized user data from SSO
 * @param object $ssoUserInfo Raw user data from SSO provider
 * @return array User data from the application's database (including role)
 */
function findOrCreateUser(array $normalizedUser, object $ssoUserInfo): array
{
    // Load admin configuration
    $config = require __DIR__ . '/../../config/admin_config.php';
    
    // Set up PDO connection using admin config
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['database']['host'],
        $config['database']['port'],
        $config['database']['database'],
        $config['database']['charset']
    );
    
    $options = $config['database']['options'];
    
    try {
        // Connect to database
        $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], $options);
        
        // Search for user by email in admin_users table
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email = ? AND status = 'active'");
        $stmt->execute([$normalizedUser['email']]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Case 1: User found (existing admin)
            // Update user information with latest data
            $updateStmt = $pdo->prepare(
                "UPDATE admin_users SET name = ?, updated_at = NOW() WHERE id = ?"
            );
            $updateStmt->execute([
                $normalizedUser['name'] ?? $normalizedUser['email'],
                $user['id']
            ]);
            
            // Return user data from our database
            return [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $user['role'],
                'status' => $user['status']
            ];
        } else {
            // Case 2: User not found - Check if we should auto-create admin users
            // For security, we don't automatically create admin users
            // Only users already in the admin_users table can access the admin panel
            throw new Exception("User not authorized to access admin panel");
        }
    } catch (PDOException $e) {
        // Handle database errors
        error_log("Database error in user handler: " . $e->getMessage());
        throw new Exception("Authentication system error");
    } catch (Exception $e) {
        // Handle other errors
        error_log("User handler error: " . $e->getMessage());
        throw $e;
    }
}