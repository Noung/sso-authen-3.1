<?php
// admin/public/api/user_handler.php
header('Content-Type: application/json');

try {
    // Load configuration
    require_once __DIR__ . '/../../config/admin_config.php';
    $config = require __DIR__ . '/../../config/admin_config.php';

    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    $normalizedUser = $input['normalizedUser'] ?? [];
    $ssoUserInfo = $input['ssoUserInfo'] ?? (object)[];

    if (empty($normalizedUser)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input data']);
        exit;
    }

    // Connect to database
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['database']['host'],
        $config['database']['port'],
        $config['database']['database'],
        $config['database']['charset']
    );

    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], $config['database']['options']);

    // Check if user exists in admin_users table
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email = ? AND status = 'active'");
    $stmt->execute([$normalizedUser['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // User found, update user information with latest data from SSO claims
        $updateStmt = $pdo->prepare(
            "UPDATE admin_users SET name = ?, position = ?, campus = ?, office_name = ?, faculty_id = ?, department_id = ?, campus_id = ?, groups = ?, provider = ?, last_login_at = NOW(), updated_at = NOW() WHERE id = ?"
        );

        // Determine provider from the OIDC configuration
        $provider = 'unknown';
        if (!empty($config['auth']['oidc']['provider_url'])) {
            $providerUrl = $config['auth']['oidc']['provider_url'];
            if (strpos($providerUrl, 'psu.ac.th') !== false) {
                $provider = 'psu';
            } elseif (strpos($providerUrl, 'google') !== false) {
                $provider = 'google';
            } elseif (strpos($providerUrl, 'microsoft') !== false) {
                $provider = 'microsoft';
            } elseif (strpos($providerUrl, 'auth0') !== false) {
                $provider = 'auth0';
            }
        }

        $updateStmt->execute([
            $normalizedUser['name'] ?? $normalizedUser['email'],
            $normalizedUser['position'] ?? null,
            $normalizedUser['campus'] ?? null,
            $normalizedUser['officeName'] ?? null,
            $normalizedUser['facultyId'] ?? null,
            $normalizedUser['departmentId'] ?? null,
            $normalizedUser['campusId'] ?? null,
            isset($normalizedUser['groups']) ? json_encode($normalizedUser['groups']) : null,
            $provider,
            $user['id']
        ]);

        // Return updated user data
        echo json_encode([
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role']
        ]);
    } else {
        // User not authorized
        http_response_code(403);
        echo json_encode(['error' => 'User not authorized to access admin panel']);
    }
} catch (Exception $e) {
    error_log('User handler error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
