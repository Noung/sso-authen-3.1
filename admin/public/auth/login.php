<?php

/**
 * OIDC Login Handler for Admin Panel
 */

// Start session
session_start();

// Include autoloader
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../admin/vendor/autoload.php';

// Include the AuthController directly since autoloading might not work in this context
require_once __DIR__ . '/../../src/Controllers/AuthController.php';

use SsoAdmin\Controllers\AuthController;

try {
    // Create AuthController instance
    $authController = new AuthController();

    // Create mock request and response objects
    $request = new \stdClass();
    $response = new \stdClass();

    // Handle login
    $authController->login($request, $response);
} catch (Exception $e) {
    error_log('OIDC Login Error: ' . $e->getMessage());
    // Redirect to login with error
    header('Location: ../login.php?error=auth_failed');
    exit;
}
