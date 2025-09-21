<?php

namespace SsoAdmin\Controllers;

/**
 * Authentication Controller
 * Compatible with PHP 7.4.33
 */
class AuthController
{
    /**
     * Handle OIDC login
     */
    public function login($request, $response)
    {
        // TODO: Implement OIDC login
        $body = $response->getBody();
        $body->write(json_encode([
            'success' => false,
            'message' => 'OIDC authentication not implemented yet'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Handle OIDC callback
     */
    public function callback($request, $response)
    {
        // TODO: Implement OIDC callback
        $body = $response->getBody();
        $body->write(json_encode([
            'success' => false,
            'message' => 'OIDC callback not implemented yet'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Handle logout
     */
    public function logout($request, $response)
    {
        // Clear session
        $_SESSION = [];
        session_destroy();

        $body = $response->getBody();
        $body->write(json_encode([
            'success' => true,
            'message' => 'Logged out successfully'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
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
        ];
    }
}