<?php

/**
 * OIDC Logout Handler for Admin Panel
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

    // Handle logout
    $authController->logout($request, $response);

    // Redirect to login page
    header('Location: ../login.php');
    exit;
} catch (Exception $e) {
    error_log('OIDC Logout Error: ' . $e->getMessage());
    // Redirect to login with error
    header('Location: ../login.php?error=logout_failed');
    exit;
}
