<?php

namespace SsoAdmin\Controllers;

use SsoAuthen\SsoHandler;
use PDO;

/**
 * Authentication Controller
 * Compatible with PHP 7.4.33
 */
class AuthController
{
    /**
     * Handle OIDC login
     */
    public function login()
    {
        try {
            // Load configuration
            $adminConfig = require __DIR__ . '/../../config/admin_config.php';
            $mainConfig = require __DIR__ . '/../../../config/config.php';
            
            // Get OIDC configuration
            $oidcConfig = $adminConfig['auth']['oidc'];
            
            // Create provider config for SsoHandler
            $providerConfig = [
                'clientID' => $oidcConfig['client_id'],
                'clientSecret' => $oidcConfig['client_secret'],
                'providerURL' => $oidcConfig['provider_url'],
                'redirectUri' => $oidcConfig['redirect_uri'],
                'scopes' => $oidcConfig['scopes'],
                // Use PSU claim mapping as default
                'claim_mapping' => [
                    'id' => 'psu_id',
                    'username' => 'preferred_username',
                    'name' => 'display_name_th',
                    'firstName' => 'first_name_th',
                    'lastName' => 'last_name_th',
                    'email' => 'email',
                    'department' => 'department_th',
                    'position' => 'position_th',
                    'campus' => 'campus_th',
                    'officeName' => 'office_name_th',
                    'facultyId' => 'faculty_id',
                    'departmentId' => 'department_id',
                    'campusId' => 'campus_id',
                    'groups' => 'groups'
                ]
            ];
            
            // Create SsoHandler instance
            $handler = new SsoHandler($providerConfig);
            $handler->login();
        } catch (\Exception $e) {
            error_log('OIDC Login Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle OIDC callback
     */
    public function callback()
    {
        try {
            // Load configuration
            $adminConfig = require __DIR__ . '/../../config/admin_config.php';
            $mainConfig = require __DIR__ . '/../../../config/config.php';
            
            // Get OIDC configuration
            $oidcConfig = $adminConfig['auth']['oidc'];
            
            // Create provider config for SsoHandler
            $providerConfig = [
                'clientID' => $oidcConfig['client_id'],
                'clientSecret' => $oidcConfig['client_secret'],
                'providerURL' => $oidcConfig['provider_url'],
                'redirectUri' => $oidcConfig['redirect_uri'],
                'scopes' => $oidcConfig['scopes'],
                // Use PSU claim mapping as default
                'claim_mapping' => [
                    'id' => 'psu_id',
                    'username' => 'preferred_username',
                    'name' => 'display_name_th',
                    'firstName' => 'first_name_th',
                    'lastName' => 'last_name_th',
                    'email' => 'email',
                    'department' => 'department_th',
                    'position' => 'position_th',
                    'campus' => 'campus_th',
                    'officeName' => 'office_name_th',
                    'facultyId' => 'faculty_id',
                    'departmentId' => 'department_id',
                    'campusId' => 'campus_id',
                    'groups' => 'groups'
                ]
            ];
            
            // Create SsoHandler instance
            $handler = new SsoHandler($providerConfig);
            
            // Get the client configuration from database to determine auth mode
            $clientConfig = $this->getClientConfig('admin-panel');
            
            // Handle callback and get user info
            $userInfo = $handler->handleCallback($clientConfig);
            
            // Validate that the user is authorized as admin
            if ($this->isAdminUser($userInfo['email'])) {
                // Get user role from database
                $adminUserData = $this->getAdminUserData($userInfo['email']);
                
                // Set admin session
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_email'] = $userInfo['email'];
                $_SESSION['admin_name'] = $userInfo['name'] ?? $userInfo['email'];
                $_SESSION['admin_user_info'] = $userInfo;
                $_SESSION['admin_role'] = $adminUserData['role'] ?? 'viewer';
                
                // Update last login time
                $this->updateLastLogin($userInfo['email']);
                
                // Log the successful login
                $this->logAdminAction($userInfo['email'], 'admin_login', 'authentication', 'admin_panel', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
                
                // Redirect to dashboard
                header('Location: ../index.php');
                exit;
            } else {
                // User is not authorized as admin
                throw new \Exception('User is not authorized to access the admin panel');
            }
        } catch (\Exception $e) {
            error_log('OIDC Callback Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle logout
     */
    public function logout()
    {
        // Log the logout action if user was logged in
        if (isset($_SESSION['admin_email'])) {
            $this->logAdminAction(
                $_SESSION['admin_email'], 
                'admin_logout', 
                'authentication', 
                'admin_panel', 
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', 
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            );
        }
        
        // Clear session
        $_SESSION = [];
        session_destroy();
        
        // Redirect to login page
        header('Location: ../login.php');
        exit;
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated()
    {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }

    /**
     * Get current admin user info
     */
    public function getCurrentAdmin()
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return [
            'email' => $_SESSION['admin_email'] ?? 'unknown',
            'name' => $_SESSION['admin_name'] ?? 'Unknown User',
            'info' => $_SESSION['admin_user_info'] ?? []
        ];
    }
    
    /**
     * Get client configuration from database
     */
    private function getClientConfig($clientId)
    {
        try {
            $adminConfig = require __DIR__ . '/../../config/admin_config.php';
            
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $adminConfig['database']['host'],
                $adminConfig['database']['port'],
                $adminConfig['database']['database'],
                $adminConfig['database']['charset']
            );

            $pdo = new PDO($dsn, $adminConfig['database']['username'], $adminConfig['database']['password'], $adminConfig['database']['options']);
            
            $stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$clientId]);
            
            $client = $stmt->fetch();
            
            if (!$client) {
                throw new \Exception("Client configuration not found for client_id: " . $clientId);
            }
            
            // Return client configuration in the format expected by SsoHandler
            return [
                'user_handler_endpoint' => $client['user_handler_endpoint'],
                'api_secret_key' => $client['api_secret_key']
            ];
        } catch (\Exception $e) {
            error_log('Client config error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Check if email is authorized as admin
     */
    private function isAdminUser($email)
    {
        try {
            $adminConfig = require __DIR__ . '/../../config/admin_config.php';
            
            // First check if development mode is enabled and it's the dev user
            if ($adminConfig['auth']['development']['enabled'] && 
                $email === $adminConfig['auth']['development']['admin_email']) {
                return true;
            }
            
            // Connect to database to check admin_users table
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $adminConfig['database']['host'],
                $adminConfig['database']['port'],
                $adminConfig['database']['database'],
                $adminConfig['database']['charset']
            );

            $pdo = new PDO($dsn, $adminConfig['database']['username'], $adminConfig['database']['password'], $adminConfig['database']['options']);
            
            $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE email = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$email]);
            
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            error_log('Admin user check error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update last login time for admin user
     */
    private function updateLastLogin($email)
    {
        try {
            $adminConfig = require __DIR__ . '/../../config/admin_config.php';
            
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $adminConfig['database']['host'],
                $adminConfig['database']['port'],
                $adminConfig['database']['database'],
                $adminConfig['database']['charset']
            );

            $pdo = new PDO($dsn, $adminConfig['database']['username'], $adminConfig['database']['password'], $adminConfig['database']['options']);
            
            $stmt = $pdo->prepare("UPDATE admin_users SET last_login_at = NOW() WHERE email = ?");
            $stmt->execute([$email]);
        } catch (\Exception $e) {
            error_log('Update last login error: ' . $e->getMessage());
        }
    }
    
    /**
     * Log admin action to audit log
     */
    private function logAdminAction($adminEmail, $action, $resourceType, $resourceId, $ipAddress, $userAgent)
    {
        try {
            $adminConfig = require __DIR__ . '/../../config/admin_config.php';
            
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $adminConfig['database']['host'],
                $adminConfig['database']['port'],
                $adminConfig['database']['database'],
                $adminConfig['database']['charset']
            );

            $pdo = new PDO($dsn, $adminConfig['database']['username'], $adminConfig['database']['password'], $adminConfig['database']['options']);
            
            $stmt = $pdo->prepare("
                INSERT INTO audit_logs (admin_email, action, resource_type, resource_id, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $adminEmail,
                $action,
                $resourceType,
                $resourceId,
                $ipAddress,
                $userAgent
            ]);
        } catch (\Exception $e) {
            error_log('Audit log error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get admin user data including role
     */
    private function getAdminUserData($email)
    {
        try {
            $adminConfig = require __DIR__ . '/../../config/admin_config.php';
            
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $adminConfig['database']['host'],
                $adminConfig['database']['port'],
                $adminConfig['database']['database'],
                $adminConfig['database']['charset']
            );

            $pdo = new PDO($dsn, $adminConfig['database']['username'], $adminConfig['database']['password'], $adminConfig['database']['options']);
            
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$email]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            error_log('Admin user data fetch error: ' . $e->getMessage());
            return [];
        }
    }
}