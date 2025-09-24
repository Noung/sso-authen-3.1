<?php

/**
 * admin/public/index.php
 * Fixed version - Simple Admin Panel Bootstrap
 * Compatible with PHP 7.4.33
 */

// Load autoloader when available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Load main project autoloader (conditional)
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} else {
    // For now, manually include required files
    require_once __DIR__ . '/../src/Database/Connection.php';
    require_once __DIR__ . '/../src/Models/Client.php';
    require_once __DIR__ . '/../src/Controllers/AuthController.php';
    require_once __DIR__ . '/../src/Controllers/ClientController.php';
    require_once __DIR__ . '/../src/Controllers/DashboardController.php';
}

// Use statements - moved to top of file
use SsoAdmin\Database\Connection;
use SsoAdmin\Controllers\AuthController;
use SsoAdmin\Controllers\ClientController;
use SsoAdmin\Controllers\DashboardController;

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    // Simple .env loader
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[$key] = $value;
        }
    }
}

// Load admin configuration
$config = require __DIR__ . '/../config/admin_config.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize database connection
Connection::init($config['database']);

// Handle development login first
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (isset($data['action']) && $data['action'] === 'dev_login') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = 'admin@psu.ac.th';
        $_SESSION['admin_name'] = 'System Administrator';
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}

// Simple routing - Fixed for correct base path detection
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];

// Get the base path correctly (e.g., /sso-authen-3/admin/public)
$basePath = dirname($scriptName);

// Extract the path relative to the admin public directory
$path = str_replace($basePath, '', $requestUri);
$path = parse_url($path, PHP_URL_PATH);

// Remove query string
if (($pos = strpos($path, '?')) !== false) {
    $path = substr($path, 0, $pos);
}

// Clean up path
$path = '/' . trim($path, '/');

// Store base path for use in redirects
$GLOBALS['admin_base_path'] = $basePath;

try {
    // Enhanced debug logging
    error_log('Admin Panel - Path: ' . $path . ', REQUEST_URI: ' . $_SERVER['REQUEST_URI']);
    error_log('Admin Panel - Method: ' . $_SERVER['REQUEST_METHOD']);
    error_log('Admin Panel - Session logged in: ' . (isset($_SESSION['admin_logged_in']) ? 'YES' : 'NO'));

    // Route handling
    switch ($path) {
        case '/':
        case '/index.php':
            handleDashboard();
            break;

        case '/login':
        case '/login.php':
            // Redirect to standalone login page
            include 'login.php';
            exit;

        case '/auth/login':
        case '/auth/login.php':
            // Clear any existing session
            if (isset($_SESSION['admin_logged_in'])) {
                session_destroy();
                session_start();
            }
            handleAuthLogin();
            break;

        case '/auth/callback':
        case '/auth/callback.php':
            handleAuthCallback();
            break;

        case '/auth/logout':
        case '/auth/logout.php':
            handleAuthLogout();
            break;

        case '/clients':
        case '/clients.php':
            handleClientsPage();
            break;

        case '/statistics':
        case '/statistics.php':
            handleStatisticsPage();
            break;

        // API routes
        case '/api/clients':
            handleApiClients();
            break;

        case '/api/clients/statistics':
            handleApiClientStatistics();
            break;

        case '/api/dashboard/stats':
            handleApiDashboardStats();
            break;

        case '/api/dashboard/recent-activities':
            handleApiRecentActivities();
            break;


        case '/api/jwt-secret':
            handleApiJwtSecret();
            break;

        case '/api/log-jwt-view':
            handleApiLogJwtView();
            break;

        case '/api/usage-statistics':
            handleApiUsageStatistics();
            break;

        case '/api/generate-mock-data':
            handleApiGenerateMockData();
            break;

        // Backup and Restore APIs
        case '/api/backup/create':
            handleApiCreateBackup();
            break;

        case '/api/backup/list':
            handleApiListBackups();
            break;

        case '/api/backup/download':
            handleApiDownloadBackup();
            break;

        case '/api/backup/delete':
            handleApiDeleteBackup();
            break;

        case '/api/backup/restore':
            handleApiRestoreBackup();
            break;


        default:
            // Handle API routes with parameters
            if (preg_match('/^\/api\/clients\/(\d+)$/', $path, $matches)) {
                handleApiClientById($matches[1]);
            } elseif (preg_match('/^\/api\/clients\/(\d+)\/toggle-status$/', $path, $matches)) {
                handleApiToggleStatus($matches[1]);
            } elseif (preg_match('/^\/api\/clients\/(\d+)\/statistics$/', $path, $matches)) {
                handleApiIndividualClientStatistics($matches[1]);
            } else {
                http_response_code(404);
                echo '404 - Page Not Found';
            }
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo 'Server Error: ' . $e->getMessage();
}

// Route handlers
function checkAdminAuth()
{
    global $config;
    $requestUri = $_SERVER['REQUEST_URI'];

    error_log('checkAdminAuth - Request URI: ' . $requestUri);
    error_log('checkAdminAuth - Session data: ' . print_r($_SESSION, true));

    // Skip auth for login and callback routes
    if (strpos($requestUri, '/auth/') !== false || strpos($requestUri, 'login.php') !== false || strpos($requestUri, 'debug') !== false) {
        error_log('checkAdminAuth - Skipping auth for: ' . $requestUri);
        return true;
    }

    // Check if admin is logged in
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        error_log('checkAdminAuth - Not logged in, redirecting to login');
        $basePath = $GLOBALS['admin_base_path'];
        // For API calls, return JSON error instead of redirect
        if (strpos($requestUri, '/api/') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            exit;
        }
        // Redirect to standalone login page
        header('Location: ' . $basePath . '/login.php');
        exit;
    }

    error_log('checkAdminAuth - Authentication successful');
    return true;
}

function handleDashboard()
{
    checkAdminAuth();

    $controller = new DashboardController();

    // Create simple mock objects
    $mockRequest = new stdClass();
    $mockResponse = new class {
        public $content = '';

        public function getBody()
        {
            return $this;
        }

        public function write($content)
        {
            $this->content = $content;
        }

        public function withHeader($name, $value)
        {
            header("$name: $value");
            return $this;
        }

        public function getContent()
        {
            return $this->content;
        }
    };

    $controller->index($mockRequest, $mockResponse);
    echo $mockResponse->getContent();
}

function handleAuthLogin()
{
    // Debug: Check if we're properly handling the login route
    error_log('handleAuthLogin called - REQUEST_URI: ' . $_SERVER['REQUEST_URI']);

    // Ensure we start with a clean session for login
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    echo renderSimpleLoginPage();
}

function handleAuthCallback()
{
    echo 'Auth callback - Under development';
}

function handleAuthLogout()
{
    // Clear all session data
    $_SESSION = array();

    // Destroy the session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    $basePath = $GLOBALS['admin_base_path'];

    // Show logout success message and redirect to standalone login
    echo renderAlert('ออกจากระบบสำเร็จ', 'คุณได้ออกจากระบบเรียบร้อยแล้ว', 'success', $basePath . '/login.php');
}

function handleClientsPage()
{
    checkAdminAuth();

    // Include the clients view
    $viewPath = __DIR__ . '/../views/clients.php';
    if (file_exists($viewPath)) {
        include $viewPath;
    } else {
        echo renderClientsPage();
    }
}

function handleStatisticsPage()
{
    checkAdminAuth();
    echo renderStatisticsPage();
}

function handleApiClients()
{
    checkAdminAuth();
    header('Content-Type: application/json');

    $controller = new ClientController();

    // Handle different methods
    $method = $_SERVER['REQUEST_METHOD'];

    try {
        switch ($method) {
            case 'GET':
                $mockRequest = new class {
                    public function getQueryParams()
                    {
                        return $_GET;
                    }
                };
                $mockResponse = new class {
                    public $content = '';
                    public function getBody()
                    {
                        return $this;
                    }
                    public function write($content)
                    {
                        $this->content = $content;
                    }
                    public function withHeader($name, $value)
                    {
                        header("$name: $value");
                        return $this;
                    }
                };

                $controller->getAll($mockRequest, $mockResponse);
                echo $mockResponse->content;
                break;

            case 'POST':
                $mockRequest = new class {
                    public function getBody()
                    {
                        return new class {
                            public function getContents()
                            {
                                return file_get_contents('php://input');
                            }
                        };
                    }
                };
                $mockResponse = new class {
                    public $content = '';
                    public function getBody()
                    {
                        return $this;
                    }
                    public function write($content)
                    {
                        $this->content = $content;
                    }
                    public function withHeader($name, $value)
                    {
                        header("$name: $value");
                        return $this;
                    }
                    public function withStatus($code)
                    {
                        http_response_code($code);
                        return $this;
                    }
                };

                $controller->create($mockRequest, $mockResponse);
                echo $mockResponse->content;
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Method ' . $method . ' not allowed']);
                http_response_code(405);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleApiDashboardStats()
{
    checkAdminAuth();
    header('Content-Type: application/json');

    $controller = new DashboardController();

    $mockRequest = new stdClass();
    $mockResponse = new class {
        public $content = '';
        public function getBody()
        {
            return $this;
        }
        public function write($content)
        {
            $this->content = $content;
        }
        public function withHeader($name, $value)
        {
            header("$name: $value");
            return $this;
        }
        public function withStatus($code)
        {
            http_response_code($code);
            return $this;
        }
    };

    $controller->getStats($mockRequest, $mockResponse);
    echo $mockResponse->content;
}

function handleApiRecentActivities()
{
    checkAdminAuth();
    header('Content-Type: application/json');

    $controller = new DashboardController();

    $mockRequest = new class {
        public function getQueryParams()
        {
            return $_GET;
        }
    };
    $mockResponse = new class {
        public $content = '';
        public function getBody()
        {
            return $this;
        }
        public function write($content)
        {
            $this->content = $content;
        }
        public function withHeader($name, $value)
        {
            header("$name: $value");
            return $this;
        }
        public function withStatus($code)
        {
            http_response_code($code);
            return $this;
        }
    };

    $controller->getRecentActivities($mockRequest, $mockResponse);
    echo $mockResponse->content;
}

function handleApiClientStatistics()
{
    checkAdminAuth();
    header('Content-Type: application/json');

    $controller = new ClientController();

    $mockRequest = new stdClass();
    $mockResponse = new class {
        public $content = '';
        public function getBody()
        {
            return $this;
        }
        public function write($content)
        {
            $this->content = $content;
        }
        public function withHeader($name, $value)
        {
            header("$name: $value");
            return $this;
        }
        public function withStatus($code)
        {
            http_response_code($code);
            return $this;
        }
    };

    $controller->getStatistics($mockRequest, $mockResponse);
    echo $mockResponse->content;
}

function handleApiClientById($id)
{
    checkAdminAuth();
    header('Content-Type: application/json');

    $controller = new ClientController();
    $method = $_SERVER['REQUEST_METHOD'];

    $mockRequest = new class {
        public function getBody()
        {
            return new class {
                public function getContents()
                {
                    return file_get_contents('php://input');
                }
            };
        }
    };

    $mockResponse = new class {
        public $content = '';
        public function getBody()
        {
            return $this;
        }
        public function write($content)
        {
            $this->content = $content;
        }
        public function withHeader($name, $value)
        {
            header("$name: $value");
            return $this;
        }
        public function withStatus($code)
        {
            http_response_code($code);
            return $this;
        }
    };

    $args = ['id' => $id];

    try {
        switch ($method) {
            case 'GET':
                $controller->getById($mockRequest, $mockResponse, $args);
                break;
            case 'PUT':
                $controller->update($mockRequest, $mockResponse, $args);
                break;
            case 'DELETE':
                $controller->delete($mockRequest, $mockResponse, $args);
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                http_response_code(405);
                return;
        }
        echo $mockResponse->content;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}



function handleApiToggleStatus($id)
{
    checkAdminAuth();
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        http_response_code(405);
        return;
    }

    $controller = new ClientController();

    $mockRequest = new stdClass();
    $mockResponse = new class {
        public $content = '';
        public function getBody()
        {
            return $this;
        }
        public function write($content)
        {
            $this->content = $content;
        }
        public function withHeader($name, $value)
        {
            header("$name: $value");
            return $this;
        }
        public function withStatus($code)
        {
            http_response_code($code);
            return $this;
        }
    };

    $args = ['id' => $id];

    try {
        $controller->toggleStatus($mockRequest, $mockResponse, $args);
        echo $mockResponse->content;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Handle JWT Secret API with enhanced security
 * Requires admin authentication and logs access
 */
function handleApiJwtSecret()
{
    checkAdminAuth();
    header('Content-Type: application/json');

    // Load JWT secret from config
    $configPath = __DIR__ . '/../../config/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;

        $jwtSecret = defined('JWT_SECRET_KEY') ? JWT_SECRET_KEY : null;

        // Debug logging
        error_log("JWT Secret Key Defined: " . ($jwtSecret ? 'Yes' : 'No'));
        error_log("JWT Secret Value: " . ($jwtSecret ? substr($jwtSecret, 0, 10) . '...' : 'N/A'));

        if (!$jwtSecret) {
            echo json_encode([
                'success' => false,
                'message' => 'JWT secret not configured'
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'jwt_secret' => $jwtSecret
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Configuration file not found'
        ]);
    }
}

/**
 * Handle JWT secret view logging
 * Logs when admin views JWT secret in client details
 */
function handleApiLogJwtView()
{
    checkAdminAuth();
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        http_response_code(405);
        return;
    }

    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $clientId = isset($input['client_id']) ? (int)$input['client_id'] : null;

        // Get admin info from session
        $adminEmail = $_SESSION['admin_email'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ipAddress = getClientIpAddress();

        // Log JWT secret view activity
        $sql = 'INSERT INTO audit_logs (admin_email, action, resource_type, resource_id, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())';

        Connection::query($sql, [
            $adminEmail,
            'jwt_secret_viewed',
            'client',
            $clientId,
            $ipAddress,
            $userAgent
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'JWT view logged successfully'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to log JWT view: ' . $e->getMessage()
        ]);
        http_response_code(500);
    }
}

/**
 * Get client IP address safely
 */
function getClientIpAddress()
{
    // Check for shared IP
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        // Take the first IP if multiple
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    // Validate IP address
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = 'unknown';
    }

    return $ip;
}

/**
 * Handle system-wide usage statistics API
 */
function handleApiUsageStatistics()
{
    checkAdminAuth();
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        http_response_code(405);
        return;
    }

    try {
        require_once __DIR__ . '/../src/Models/UsageStatistics.php';

        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        $days = max(1, min(365, $days)); // Limit between 1-365 days

        $stats = new SsoAdmin\Models\UsageStatistics();
        $data = $stats->getSystemStatistics($days);

        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to get usage statistics: ' . $e->getMessage()
        ]);
        http_response_code(500);
    }
}

/**
 * Handle individual client statistics API
 */
function handleApiIndividualClientStatistics($clientId)
{
    checkAdminAuth();
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        http_response_code(405);
        return;
    }

    try {
        require_once __DIR__ . '/../src/Models/UsageStatistics.php';

        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        $days = max(1, min(365, $days)); // Limit between 1-365 days

        $stats = new SsoAdmin\Models\UsageStatistics();
        $data = $stats->getClientStatistics((int)$clientId, $days);

        if (isset($data['error'])) {
            echo json_encode([
                'success' => false,
                'message' => $data['error']
            ]);
            http_response_code(404);
            return;
        }

        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to get client statistics: ' . $e->getMessage()
        ]);
        http_response_code(500);
    }
}

/**
 * Handle backup creation API
 */
function handleApiCreateBackup()
{
    checkAdminAuth();
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }

    try {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $options = [
            'name' => $input['name'] ?? null,
            'description' => $input['description'] ?? 'Manual backup',
            'type' => $input['type'] ?? 'full',
            'created_by' => $_SESSION['admin_email'] ?? 'unknown',
            'exclude_clients' => $input['exclude_clients'] ?? false,
            'exclude_users' => $input['exclude_users'] ?? false,
            'include_audit_logs' => $input['include_audit_logs'] ?? false,
            'audit_days' => $input['audit_days'] ?? 30
        ];

        $backup = new SsoAdmin\Models\BackupManager();
        $result = $backup->createBackup($options);

        echo json_encode([
            'success' => true,
            'message' => 'Backup created successfully',
            'data' => $result
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Backup creation failed: ' . $e->getMessage()
        ]);
    }
}

/**
 * Handle backup list API
 */
function handleApiListBackups()
{
    checkAdminAuth();
    header('Content-Type: application/json');

    try {
        $backup = new SsoAdmin\Models\BackupManager();
        $backups = $backup->getBackupList();

        echo json_encode([
            'success' => true,
            'data' => $backups
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to list backups: ' . $e->getMessage()
        ]);
    }
}

/**
 * Handle backup download API
 */
function handleApiDownloadBackup()
{
    checkAdminAuth();

    $filename = $_GET['file'] ?? null;
    if (!$filename) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Filename required']);
        return;
    }

    try {
        $backupDir = __DIR__ . '/../storage/backups';
        $backupPath = $backupDir . '/' . basename($filename); // Security: only filename

        if (!file_exists($backupPath)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Backup file not found']);
            return;
        }

        // Set headers for file download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . filesize($backupPath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        // Output file
        readfile($backupPath);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Download failed: ' . $e->getMessage()
        ]);
    }
}

/**
 * Handle backup deletion API
 */
function handleApiDeleteBackup()
{
    checkAdminAuth();
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }

    $filename = $_GET['file'] ?? null;
    if (!$filename) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Filename required']);
        return;
    }

    try {
        $backup = new SsoAdmin\Models\BackupManager();
        $backup->deleteBackup($filename);

        echo json_encode([
            'success' => true,
            'message' => 'Backup deleted successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Delete failed: ' . $e->getMessage()
        ]);
    }
}

/**
 * Handle backup restore API
 */
function handleApiRestoreBackup()
{
    checkAdminAuth();
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }

    try {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $filename = $input['filename'] ?? null;
        if (!$filename) {
            throw new Exception('Backup filename required');
        }

        $options = [
            'restored_by' => $_SESSION['admin_email'] ?? 'unknown',
            'client_mode' => $input['client_mode'] ?? 'merge', // merge, replace, skip
            'user_mode' => $input['user_mode'] ?? 'merge',
            'skip_clients' => $input['skip_clients'] ?? false,
            'skip_users' => $input['skip_users'] ?? false,
            'skip_config' => $input['skip_config'] ?? false,
            'skip_audit' => $input['skip_audit'] ?? true // Skip audit logs by default
        ];

        $backup = new SsoAdmin\Models\BackupManager();
        $result = $backup->restoreFromBackup($filename, $options);

        echo json_encode([
            'success' => true,
            'message' => 'Backup restored successfully',
            'data' => $result
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Restore failed: ' . $e->getMessage()
        ]);
    }
}
function handleApiGenerateMockData()
{
    checkAdminAuth();
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        http_response_code(405);
        return;
    }

    try {
        require_once __DIR__ . '/../src/Models/MockDataGenerator.php';

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $days = isset($input['days']) ? (int)$input['days'] : 30;
        $days = max(7, min(90, $days)); // Limit between 7-90 days

        $generator = new SsoAdmin\Models\MockDataGenerator();
        $summary = $generator->generateMockData($days);

        echo json_encode([
            'success' => true,
            'message' => 'Mock data generated successfully',
            'data' => $summary
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to generate mock data: ' . $e->getMessage()
        ]);
        http_response_code(500);
    }
}

function renderSimpleLoginPage()
{
    $basePath = $GLOBALS['admin_base_path'];
    return '<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSO Admin Panel - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow mt-5">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                            <h3>SSO Admin Panel</h3>
                            <p class="text-muted">เข้าสู่ระบบด้วย SSO</p>
                        </div>
                        
                        <div class="d-grid">
                            <a href="#" onclick="devLogin()" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ
                            </a>
                        </div>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">Development Mode - Click to login as admin</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function devLogin() {
            fetch("' . $basePath . '", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    action: "dev_login",
                    email: "admin@psu.ac.th",
                    name: "System Administrator"
                })
            }).then(() => {
                window.location.href = "' . $basePath . '";
            });
        }
    </script>
</body>
</html>';
}

function renderClientsPage()
{
    $basePath = $GLOBALS['admin_base_path'];
    $adminName = $_SESSION['admin_name'] ?? 'Administrator';
    return '<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSO Admin Panel - Clients</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="' . $basePath . '">
                <i class="fas fa-shield-alt me-2"></i>SSO Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i>' . $adminName . '
                </span>
                <a class="nav-link" href="' . $basePath . '/auth/logout">
                    <i class="fas fa-sign-out-alt me-1"></i>ออกจากระบบ
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="' . $basePath . '">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="' . $basePath . '/clients">
                                <i class="fas fa-users me-2"></i>Client Applications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="' . $basePath . '/statistics">
                                <i class="fas fa-chart-bar me-2"></i>Usage Statistics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="backup-restore.html" target="_blank">
                                <i class="fas fa-database me-2"></i>Backup & Restore
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="api-docs.html" target="_blank">
                                <i class="fas fa-book me-2"></i>API Documentation
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Client Applications</h1>
                    <button class="btn btn-primary" onclick="showAddClientModal()">
                        <i class="fas fa-plus me-1"></i>Add Client
                    </button>
                </div>

                <div class="card shadow">
                    <div class="card-body">
                        <div id="clients-table" class="table-responsive">
                            <p class="text-center">Loading...</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            loadClients();
        });

        function loadClients() {
            fetch("' . $basePath . '/api/clients")
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderClientsTable(data.data.data);
                    } else {
                        Swal.fire("Error", data.message, "error");
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    Swal.fire("Error", "Failed to load clients", "error");
                });
        }

        function renderClientsTable(clients) {
            const container = document.getElementById("clients-table");
            
            if (clients.length === 0) {
                container.innerHTML = "<p class=\\"text-center text-muted\\">ไม่มีข้อมูล Client Applications</p>";
                return;
            }

            let html = `<table class="table table-striped">
                <thead>
                    <tr>
                        <th>Client ID</th>
                        <th>Name</th>
                        <th>Redirect URI</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>`;
            
            clients.forEach(client => {
                const createdDate = new Date(client.created_at).toLocaleDateString("th-TH");
                const statusBadge = client.status === "active" ? 
                    `<span class="badge bg-success">${client.status}</span>` :
                    `<span class="badge bg-secondary">${client.status}</span>`;
                    
                html += `<tr>
                    <td><code>${client.client_id}</code></td>
                    <td>${client.client_name}</td>
                    <td><small>${client.app_redirect_uri}</small></td>
                    <td>${statusBadge}</td>
                    <td>${createdDate}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editClient(${client.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteClient(${client.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
            });
            
            html += "</tbody></table>";
            container.innerHTML = html;
        }

        function showAddClientModal() {
            Swal.fire({
                title: "Add Client Application",
                text: "ฟีเจอร์นี้จะพร้อมใช้งานในเร็วๆ นี้",
                icon: "info"
            });
        }

        function editClient(id) {
            Swal.fire({
                title: "Edit Client",
                text: "ฟีเจอร์นี้จะพร้อมใช้งานในเร็วๆ นี้",
                icon: "info"
            });
        }

        function deleteClient(id) {
            Swal.fire({
                title: "Delete Client",
                text: "ฟีเจอร์นี้จะพร้อมใช้งานในเร็วๆ นี้",
                icon: "info"
            });
        }
    </script>
</body>
</html>';
}

function renderAlert($title, $message, $icon, $redirectUrl)
{
    return '<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container mt-5">
        <div class="text-center">
            <h3>กรุณารอสักครู่...</h3>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "' . addslashes($title) . '",
                text: "' . addslashes($message) . '",
                icon: "' . addslashes($icon) . '",
                confirmButtonText: "ตกลง"
            }).then((result) => {
                window.location.href = "' . addslashes($redirectUrl) . '";
            });
        });
    </script>
</body>
</html>';
}

function renderStatisticsPage()
{
    $basePath = $GLOBALS['admin_base_path'];
    $adminName = $_SESSION['admin_name'] ?? 'Administrator';

    return '<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSO Admin Panel - Usage Statistics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="' . $basePath . '">
                <i class="fas fa-shield-alt me-2"></i>SSO Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i>' . $adminName . '
                </span>
                <a class="nav-link" href="' . $basePath . '/auth/logout">
                    <i class="fas fa-sign-out-alt me-1"></i>ออกจากระบบ
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="' . $basePath . '">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="' . $basePath . '/clients">
                                <i class="fas fa-users me-2"></i>Client Applications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="' . $basePath . '/statistics">
                                <i class="fas fa-chart-bar me-2"></i>Usage Statistics
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Usage Statistics <small class="text-muted fs-6" id="demo-indicator" style="display:none"><i class="fas fa-flask me-1"></i>Demo Data Active</small></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <select class="form-select" id="periodSelect" onchange="loadStatistics()">
                                <option value="7">Last 7 days</option>
                                <option value="30" selected>Last 30 days</option>
                                <option value="90">Last 90 days</option>
                            </select>
                        </div>
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-success" onclick="generateMockData()">
                                <i class="fas fa-magic me-1"></i>Generate Demo Data
                            </button>
                            <a href="oidc-explanation.html" class="btn btn-outline-info" target="_blank">
                                <i class="fas fa-question-circle me-1"></i>What is OIDC Action?
                            </a>
                        </div>
                        <button type="button" class="btn btn-outline-secondary" onclick="loadStatistics()">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                    </div>
                </div>

                <!-- System Statistics Overview -->
                <div class="row mb-4" id="system-stats">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-globe me-2"></i>System Overview</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2">Loading system statistics...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Client Statistics -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-users me-2"></i>Client Activity Summary</h5>
                            </div>
                            <div class="card-body" id="client-stats">
                                <div class="text-center">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2">Loading client statistics...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const basePath = "' . $basePath . '";
        
        document.addEventListener("DOMContentLoaded", function() {
            loadStatistics();
        });
        
        function loadStatistics() {
            const days = document.getElementById("periodSelect").value;
            
            // Load system statistics
            fetch(`${basePath}/api/usage-statistics?days=${days}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderSystemStats(data.data);
                    } else {
                        Swal.fire("Error", data.message, "error");
                    }
                })
                .catch(error => {
                    console.error("Error loading statistics:", error);
                    Swal.fire("Error", "Failed to load statistics", "error");
                });
        }
        
        function renderSystemStats(stats) {
            const systemStatsDiv = document.getElementById("system-stats");
            const clientStatsDiv = document.getElementById("client-stats");
            
            // Render system overview
            systemStatsDiv.innerHTML = `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-globe me-2"></i>System Overview (${stats.period_days} days)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h3 class="text-primary">${stats.total_clients}</h3>
                                        <p class="mb-0">Total Clients</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h3 class="text-success">${stats.active_clients}</h3>
                                        <p class="mb-0">Active Clients</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h3 class="text-info">${stats.total_activities}</h3>
                                        <p class="mb-0">Total Activities</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h3 class="text-warning">${stats.admin_activity.length}</h3>
                                        <p class="mb-0">Active Admins</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Render client activity summary
            let clientActivityHtml = `
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Client Name</th>
                                <th>Status</th>
                                <th>Total Activities</th>
                                <th>Unique Actions</th>
                                <th>Active Admins</th>
                                <th>Last Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            stats.client_activity_summary.forEach(client => {
                const statusBadge = client.status === "active" ? 
                    `<span class="badge bg-success">${client.status}</span>` :
                    `<span class="badge bg-secondary">${client.status}</span>`;
                    
                const lastActivity = client.last_activity ? 
                    new Date(client.last_activity).toLocaleDateString("th-TH") : 
                    "No activity";
                    
                clientActivityHtml += `
                    <tr>
                        <td><strong>${client.client_name}</strong><br><small class="text-muted">${client.client_id}</small></td>
                        <td>${statusBadge}</td>
                        <td><span class="badge bg-info">${client.total_activities}</span></td>
                        <td>${client.unique_actions}</td>
                        <td>${client.unique_admins}</td>
                        <td><small>${lastActivity}</small></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewClientStats(${client.id})">
                                <i class="fas fa-chart-line"></i> Details
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            clientActivityHtml += `
                        </tbody>
                    </table>
                </div>
            `;
            
            clientStatsDiv.innerHTML = clientActivityHtml;
        }
        
        function viewClientStats(clientId) {
            const days = document.getElementById("periodSelect").value;
            
            fetch(`${basePath}/api/clients/${clientId}/statistics?days=${days}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showClientStatsModal(data.data);
                    } else {
                        Swal.fire("Error", data.message, "error");
                    }
                })
                .catch(error => {
                    console.error("Error loading client statistics:", error);
                    Swal.fire("Error", "Failed to load client statistics", "error");
                });
        }
        
        function showClientStatsModal(stats) {
            let activityDetailsHtml = "";
            if (stats.activity_stats && stats.activity_stats.length > 0) {
                activityDetailsHtml = `
                    <h6>Activity Breakdown:</h6>
                    <ul class="list-group list-group-flush mb-3">
                `;
                stats.activity_stats.forEach(activity => {
                    activityDetailsHtml += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${activity.action}</strong>
                                <br><small class="text-muted">${activity.unique_admins} unique admin(s)</small>
                            </div>
                            <span class="badge bg-primary rounded-pill">${activity.count}</span>
                        </li>
                    `;
                });
                activityDetailsHtml += "</ul>";
            }
            
            const jwtViewInfo = stats.jwt_view_count ? `
                <div class="alert alert-info">
                    <h6><i class="fas fa-key me-2"></i>JWT Secret Views:</h6>
                    <ul class="mb-0">
                        <li>Total views: <strong>${stats.jwt_view_count.total_views}</strong></li>
                        <li>Unique viewers: <strong>${stats.jwt_view_count.unique_viewers}</strong></li>
                        <li>Active days: <strong>${stats.jwt_view_count.active_days}</strong></li>
                    </ul>
                </div>
            ` : "";
            
            Swal.fire({
                title: `${stats.client.client_name} Statistics`,
                html: `
                    <div class="text-start">
                        <p><strong>Period:</strong> ${stats.period_days} days</p>
                        <p><strong>Client ID:</strong> <code>${stats.client.client_id}</code></p>
                        <p><strong>Status:</strong> <span class="badge bg-${stats.client.status === "active" ? "success" : "secondary"}">${stats.client.status}</span></p>
                        <hr>
                        ${activityDetailsHtml}
                        ${jwtViewInfo}
                    </div>
                `,
                width: "600px",
                confirmButtonText: "Close"
            });
        }
        
        function generateMockData() {
            Swal.fire({
                title: "Generate Demo Data?",
                html: `
                    <p>This will generate realistic mock authentication and usage data for demonstration purposes.</p>
                    <div class="alert alert-info text-start">
                        <strong>What will be generated:</strong>
                        <ul class="mb-0">
                            <li>Authentication logs (success/failure)</li>
                            <li>Admin activity records</li>
                            <li>JWT secret view logs</li>
                            <li>Client management activities</li>
                        </ul>
                    </div>
                    <p><strong>Time period:</strong> <span id="demo-days">30</span> days</p>
                `,
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Generate Demo Data",
                cancelButtonText: "Cancel",
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const days = document.getElementById("periodSelect").value;
                    return fetch(`${basePath}/api/generate-mock-data`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({ days: parseInt(days) })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message);
                        }
                        return data;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Error: ${error.message}`);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    const data = result.value.data;
                    Swal.fire({
                        title: "Demo Data Generated!",
                        html: `
                            <div class="text-start">
                                <p class="mb-3">Successfully generated mock data for <strong>${data.period_days} days</strong>:</p>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i><strong>${data.authentication_logs}</strong> authentication logs</li>
                                    <li><i class="fas fa-check text-success me-2"></i><strong>${data.admin_activities}</strong> admin activities</li>
                                    <li><i class="fas fa-check text-success me-2"></i><strong>${data.jwt_views}</strong> JWT secret views</li>
                                    <li><i class="fas fa-check text-success me-2"></i><strong>${data.client_modifications}</strong> client modifications</li>
                                </ul>
                                <div class="alert alert-success mt-3">
                                    <i class="fas fa-info-circle me-2"></i>The statistics dashboard will now show realistic demo data!
                                </div>
                            </div>
                        `,
                        icon: "success",
                        confirmButtonText: "View Statistics"
                    }).then(() => {
                        loadStatistics();
                    });
                }
            });
        }
    </script>
</body>
</html>';
}
