<?php

/**
 * OIDC Login Handler for Admin Panel
 */

// Start session
session_start();

try {
    // Include autoloader
    require_once __DIR__ . '/../../../vendor/autoload.php';
    require_once __DIR__ . '/../../../admin/vendor/autoload.php';

    // Include the AuthController directly since autoloading might not work in this context
    require_once __DIR__ . '/../../src/Controllers/AuthController.php';

    // Create AuthController instance
    $authController = new \SsoAdmin\Controllers\AuthController();

    // Handle login
    $authController->login();
} catch (Exception $e) {
    error_log('OIDC Login Error: ' . $e->getMessage());
    
    // Parse and format the error message for better user experience
    $errorMessage = $e->getMessage();
    
    // Extract JSON error message if present
    if (preg_match('/Response: (\{.*\})/', $errorMessage, $matches)) {
        $jsonResponse = json_decode($matches[1], true);
        if (isset($jsonResponse['error'])) {
            $errorMessage = $jsonResponse['error'];
        }
    }
    
    // Redirect to login with clean error message
    header('Location: ../login.php?error=auth_failed&message=' . urlencode($errorMessage));
    exit;
}