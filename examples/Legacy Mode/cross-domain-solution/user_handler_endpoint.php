<?php
/**
 * Cross-domain Legacy Mode Solution
 * Convert local user_handler.php to HTTP endpoint
 * 
 * Place this file in your legacy app domain (e.g., http://legacy-app.com/api/user_handler.php)
 */

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Verify API secret (optional but recommended)
$expectedSecret = 'YOUR_LEGACY_APP_SECRET_KEY'; // Set this in sso-authen client config
$providedSecret = $_SERVER['HTTP_X_API_SECRET'] ?? '';

if ($providedSecret !== $expectedSecret) {
    http_response_code(401);
    exit('Unauthorized');
}

// Get JSON payload
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['normalizedUser'])) {
    http_response_code(400);
    exit('Invalid payload');
}

$normalizedUser = $data['normalizedUser'];
$ssoUserInfo = $data['ssoUserInfo'];

// Include your existing legacy user handler logic
require_once __DIR__ . '/original_user_handler.php';

// Call the existing function
$internalUser = findOrCreateUser($normalizedUser, $ssoUserInfo);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($internalUser);
?>