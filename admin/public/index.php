<?php

/**
 * admin/public/index.php
 * Fixed version - Simple Admin Panel Bootstrap
 * Compatible with PHP 7.4.33
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

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
    require_once __DIR__ . '/../src/Models/AdminUser.php';
    require_once __DIR__ . '/../src/Models/BackupManager.php'; // Add this line
    require_once __DIR__ . '/../src/Controllers/AuthController.php';
    require_once __DIR__ . '/../src/Controllers/ClientController.php';
    require_once __DIR__ . '/../src/Controllers/DashboardController.php';
    require_once __DIR__ . '/../src/Controllers/AdminUserController.php';
}

// Use statements - moved to top of file
use SsoAdmin\Database\Connection;
use SsoAdmin\Controllers\AuthController;
use SsoAdmin\Controllers\ClientController;
use SsoAdmin\Controllers\DashboardController;
use SsoAdmin\Controllers\AdminUserController;

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

        case '/settings':
        case '/settings.php':
            handleSettingsPage();
            break;

        case '/admin-users':
        case '/admin-users.php':
            handleAdminUsersPage();
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

        case '/api/update-jwt-secret':
            handleApiUpdateJwtSecret();
            break;

        case '/api/usage-statistics':
            handleApiUsageStatistics();
            break;

        case '/api/generate-mock-data':
            handleApiGenerateMockData();
            break;

        // Admin User APIs
        case '/api/admin-users':
            handleApiAdminUsers();
            break;

        case '/api/admin-user-roles':
            handleApiAdminUserRoles();
            break;

        case '/api/admin-user-statuses':
            handleApiAdminUserStatuses();
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
            } elseif (preg_match('/^\/api\/admin-users\/(\d+)$/', $path, $matches)) {
                handleApiAdminUserById($matches[1]);
            } elseif (preg_match('/^\/api\/admin-users\/(\d+)\/toggle-status$/', $path, $matches)) {
                handleApiToggleAdminUserStatus($matches[1]);
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
    echo renderAlert('Sign out successful', 'You have successfully signed out.', 'success', $basePath . '/login.php');
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

function handleSettingsPage()
{
    checkAdminAuth();
    echo renderSettingsPage();
}

function handleAdminUsersPage()
{
    checkAdminAuth();

    // Include the admin users view
    $viewPath = __DIR__ . '/../views/admin-users.php';
    if (file_exists($viewPath)) {
        try {
            include $viewPath;
        } catch (Exception $e) {
            error_log('Error including admin-users.php: ' . $e->getMessage());
            echo '<h1>Error</h1><p>An error occurred while loading the admin users page: ' . htmlspecialchars($e->getMessage()) . '</p>';
        } catch (Error $e) {
            error_log('Error including admin-users.php: ' . $e->getMessage());
            echo '<h1>Error</h1><p>An error occurred while loading the admin users page: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    } else {
        // If view file doesn't exist, show a simple page
        echo '<h1>Admin Users Management</h1><p>View file not found.</p>';
    }
}

function renderSettingsPage()
{
    // Get the base path from GLOBALS
    $basePath = $GLOBALS['admin_base_path'];
    
    // Get current JWT secret key from config
    $currentSecret = '';
    $configPath = __DIR__ . '/../../config/config.php';
    if (file_exists($configPath)) {
        // Read the config file content
        $configContent = file_get_contents($configPath);
        
        // Use regex to extract the JWT_SECRET_KEY value
        if (preg_match("/define\('JWT_SECRET_KEY',\s*'([^']*)'\);/", $configContent, $matches)) {
            $currentSecret = $matches[1];
        } elseif (preg_match('/define\("JWT_SECRET_KEY",\s*"([^"]*)"\);/', $configContent, $matches)) {
            $currentSecret = $matches[1];
        }
    }
    
    // Get secret key history
    require_once __DIR__ . '/../src/Models/JwtSecretHistory.php';
    $historyResult = SsoAdmin\Models\JwtSecretHistory::getAll(1, 50);
    $historyData = $historyResult['data'] ?? [];
    
    $basePath = $GLOBALS['admin_base_path'];
    $adminName = $_SESSION['admin_name'] ?? 'Administrator';

    $html = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>System Configuration - SSO Admin Panel</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
        <style>
            .admin-content {
                margin-bottom: 20px;
            }
            .card-header {
                font-weight: bold;
            }
            .secret-key-display {
                font-family: monospace;
                background-color: #f8f9fa;
                padding: 10px;
                border-radius: 4px;
                word-break: break-all;
            }
            .btn-copy {
                margin-left: 10px;
            }
            .history-table th {
                font-weight: bold;
            }
            .secret-key-cell {
                font-family: monospace;
                font-size: 0.8rem;
                max-width: 200px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .secret-key-full {
                display: none;
                font-family: monospace;
                background-color: #f8f9fa;
                padding: 10px;
                border-radius: 4px;
                word-break: break-all;
                margin-top: 5px;
            }
        </style>
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
                        <i class="fas fa-sign-out-alt me-1"></i>Sign out
                    </a>
                </div>
            </div>
        </nav>
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                    <div class="position-sticky pt-3">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link" href="' . $basePath . '/">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="' . $basePath . '/clients">
                                    <i class="fas fa-users me-2"></i>Client Applications
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="' . $basePath . '/statistics">
                                    <i class="fas fa-chart-bar me-2"></i>Usage Statistics
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="' . $basePath . '/admin-users">
                                    <i class="fas fa-user-shield me-2"></i>Admin Users
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="' . $basePath . '/settings">
                                    <i class="fas fa-cog me-2"></i>System Configuration
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>

                <!-- Main content -->
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 admin-content">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2"><i class="fas fa-cog me-2"></i>System Configuration</h1>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card mb-4 shadow">
                                <div class="card-header">
                                    <h5 id="time-round">
                                        <i class="fas fa-key me-2"></i>JWT Secret Key Management
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">
                                        The JWT Secret Key is used to sign authentication tokens for client applications. 
                                        It should be a long, random string that is kept secret.
                                    </p>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Current Secret Key:</label>
                                        <div class="input-group mt-2">
                                            <input type="password" class="form-control" id="currentSecretKey" value="' . htmlspecialchars($currentSecret) . '" readonly>
                                            <button class="btn btn-outline-secondary" onclick="toggleJwtSecret()" id="toggleJwtBtn" title="Show JWT Secret">
                                                <i class="fas fa-eye" id="toggleJwtIcon"></i> Show
                                            </button>
                                            <button class="btn btn-outline-secondary" onclick="copyToClipboardText(\'' . htmlspecialchars($currentSecret) . '\')" title="Copy JWT Secret">
                                                <i class="fas fa-copy"></i> Copy
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <button class="btn btn-primary" onclick="generateNewSecret()">
                                            <i class="fas fa-sync-alt me-2"></i>Generate New Secret Key
                                        </button>
                                        <button class="btn btn-success ms-2" onclick="saveSecretKey()" disabled id="saveButton">
                                            <i class="fas fa-save me-2"></i>Save Changes
                                        </button>
                                    </div>
                                    
                                    <div class="alert alert-warning mt-3">
                                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Security Warning</h5>
                                        <p class="mb-0">
                                            Changing the JWT Secret Key will invalidate all existing tokens. 
                                            All users will need to re-authenticate. Make sure to update all 
                                            client applications with the new key.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card shadow">
                                <div class="card-header">
                                    <h5 id="time-round">
                                        <i class="fas fa-history me-2"></i>JWT Secret Key History
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover history-table" id="historyTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Secret Key</th>
                                                    <th>Created By</th>
                                                    <th>Created At</th>
                                                    <th>Notes</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>';
                                            
    foreach ($historyData as $record) {
        $maskedKey = substr($record['secret_key'], 0, 10) . '**********' . substr($record['secret_key'], -10);
        $html .= '
                                                <tr>
                                                    <td>' . htmlspecialchars($record['id']) . '</td>
                                                    <td class="secret-key-cell">
                                                        <div id="masked-' . $record['id'] . '">' . htmlspecialchars($maskedKey) . '</div>
                                                        <div id="full-' . $record['id'] . '" class="secret-key-full">' . htmlspecialchars($record['secret_key']) . '</div>
                                                        <!--<div class="input-group mt-2" style="width: fit-content;">
                                                            <input type="password" class="form-control" id="secret-' . $record['id'] . '" value="' . htmlspecialchars($record['secret_key']) . '" readonly>
                                                            <button class="btn btn-outline-secondary" onclick="toggleHistorySecret(' . $record['id'] . ')" id="toggleHistoryBtn-' . $record['id'] . '" title="Show JWT Secret">
                                                                <i class="fas fa-eye" id="toggleHistoryIcon-' . $record['id'] . '"></i> Show
                                                            </button>
                                                            <button class="btn btn-outline-secondary" onclick="copyToClipboardText(\'' . htmlspecialchars($record['secret_key']) . '\')" title="Copy JWT Secret">
                                                                <i class="fas fa-copy"></i> Copy
                                                            </button>
                                                        </div>-->
                                                    </td>
                                                    <td>' . htmlspecialchars($record['created_by']) . '</td>
                                                    <td>' . htmlspecialchars($record['created_at']) . '</td>
                                                    <td>' . htmlspecialchars($record['notes'] ?? 'N/A') . '</td>
                                                    <td>
                                                        ' . ($record['is_active'] ? 
                                                            '<span class="badge bg-success">Active</span>' : 
                                                            '<span class="badge bg-secondary">Inactive</span>') . '
                                                    </td>
                                                </tr>';
    }
                                            
    $html .= '
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="' . $basePath . '/js/shared.js"></script>
        <script>
            // JWT Secret show/hide toggle function
            function toggleJwtSecret() {
                const field = document.getElementById("currentSecretKey");
                const btn = document.getElementById("toggleJwtBtn");
                const icon = document.getElementById("toggleJwtIcon");
                
                if (field.type === "password") {
                    // Show the secret
                    field.type = "text";
                    icon.className = "fas fa-eye-slash";
                    btn.innerHTML = \'<i class="fas fa-eye-slash" id="toggleJwtIcon"></i> Hide\';
                    btn.title = "Hide JWT Secret";
                } else {
                    // Hide the secret
                    field.type = "password";
                    icon.className = "fas fa-eye";
                    btn.innerHTML = \'<i class="fas fa-eye" id="toggleJwtIcon"></i> Show\';
                    btn.title = "Show JWT Secret";
                }
            }
            
            // Toggle secret visibility for history records
            function toggleHistorySecret(id) {
                const field = document.getElementById("secret-" + id);
                const btn = document.getElementById("toggleHistoryBtn-" + id);
                const icon = document.getElementById("toggleHistoryIcon-" + id);
                
                if (field.type === "password") {
                    // Show the secret
                    field.type = "text";
                    icon.className = "fas fa-eye-slash";
                    btn.innerHTML = \'<i class="fas fa-eye-slash" id="toggleHistoryIcon-\' + id + \'"></i> Hide\';
                    btn.title = "Hide JWT Secret";
                } else {
                    // Hide the secret
                    field.type = "password";
                    icon.className = "fas fa-eye";
                    btn.innerHTML = \'<i class="fas fa-eye" id="toggleHistoryIcon-\' + id + \'"></i> Show\';
                    btn.title = "Show JWT Secret";
                }
            }
            
            // Helper function to copy text to clipboard
            function copyToClipboardText(text) {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(() => {
                        showCopySuccess();
                    }).catch(err => {
                        fallbackCopyTextToClipboard(text);
                    });
                } else {
                    fallbackCopyTextToClipboard(text);
                }
            }
            
            function fallbackCopyTextToClipboard(text) {
                const textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.top = "0";
                textArea.style.left = "0";
                textArea.style.position = "fixed";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                try {
                    document.execCommand("copy");
                    showCopySuccess();
                } catch (err) {
                    console.error("Fallback: Oops, unable to copy", err);
                    Swal.fire("Copy Failed", "Unable to copy to clipboard", "error");
                }
                
                document.body.removeChild(textArea);
            }
            
            function showCopySuccess() {
                showCustomToast("Copied to clipboard successfully!", "success");
            }
            
            let newSecret = "";
            
            // Initialize DataTable
            $(document).ready(function() {
                $("#historyTable").DataTable({
                    "order": [[0, "desc"]],
                    "pageLength": 10,
                    "responsive": true,
                    "columnDefs": [
                        { "orderable": false, "targets": [1] }
                    ]
                });
            });
            
            function generateNewSecret() {
                // Generate a random secret key
                const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+-=[]{}|;:,.<>?";
                let secret = "";
                for (let i = 0; i < 64; i++) {
                    secret += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                
                newSecret = secret;
                document.getElementById("currentSecretKey").value = secret;
                document.getElementById("saveButton").disabled = false;
                
                // Update the copy button with the new secret
                const copyButtons = document.querySelectorAll("button[onclick^=\"copyToClipboardText\"][title=\"Copy JWT Secret\"]");
                copyButtons[0].setAttribute("onclick", "copyToClipboardText(\'" + secret + "\')");
            }
            
            function saveSecretKey() {
                if (!newSecret) return;
                
                Swal.fire({
                    title: "Are you sure?",
                    text: "This will invalidate all existing tokens and users will need to re-authenticate!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Yes, update it!",
                    cancelButtonText: "Cancel"
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading indicator
                        const saveButton = document.getElementById("saveButton");
                        const originalText = saveButton.innerHTML;
                        saveButton.innerHTML = "<i class=\"fas fa-spinner fa-spin me-2\"></i>Saving...";
                        saveButton.disabled = true;
                        
                        // Send API request to update the secret key
                        fetch("' . $basePath . '/api/update-jwt-secret", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify({
                                secret_key: newSecret,
                                notes: "Updated via admin panel"
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Show success message
                                Swal.fire({
                                    title: "Updated!",
                                    text: "JWT Secret Key updated successfully! All existing tokens have been invalidated.",
                                    icon: "success",
                                    confirmButtonText: "OK"
                                }).then(() => {
                                    // Reset button state
                                    document.getElementById("saveButton").disabled = true;
                                    // Reload page to show updated key
                                    location.reload();
                                });
                                
                                // Log the action
                                console.log("JWT secret updated successfully");
                            } else {
                                // Show error message
                                Swal.fire({
                                    title: "Error!",
                                    text: "Error updating JWT Secret Key: " + data.message,
                                    icon: "error",
                                    confirmButtonText: "OK"
                                });
                                
                                // Restore button state
                                saveButton.innerHTML = originalText;
                                saveButton.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error("Error updating JWT secret:", error);
                            Swal.fire({
                                title: "Error!",
                                text: "Error updating JWT Secret Key. Please try again.",
                                icon: "error",
                                confirmButtonText: "OK"
                            });
                            
                            // Restore button state
                            saveButton.innerHTML = originalText;
                            saveButton.disabled = false;
                        });
                    }
                });
            }
        </script>
    </body>
    </html>';
    
    return $html;
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
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
    } catch (Error $e) {
        error_log('Error in handleApiClients: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
    }
}

function handleApiAdminUsers()
{
    checkAdminAuth();
    header('Content-Type: application/json');

    $controller = new AdminUserController();

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
                    public function withStatus($code)
                    {
                        http_response_code($code);
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
        error_log('Exception in handleApiAdminUsers: ' . $e->getMessage());
        error_log('Exception trace: ' . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request: ' . $e->getMessage()]);
    } catch (Error $e) {
        error_log('Error in handleApiAdminUsers: ' . $e->getMessage());
        error_log('Error trace: ' . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request: ' . $e->getMessage()]);
    }
}

function handleApiAdminUserById($id)
{
    checkAdminAuth();
    header('Content-Type: application/json');

    $controller = new AdminUserController();
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
        error_log('Exception in handleApiAdminUserById: ' . $e->getMessage());
        error_log('Exception trace: ' . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request: ' . $e->getMessage()]);
    } catch (Error $e) {
        error_log('Error in handleApiAdminUserById: ' . $e->getMessage());
        error_log('Error trace: ' . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request: ' . $e->getMessage()]);
    }
}

function handleApiToggleAdminUserStatus($id)
{
    checkAdminAuth();
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        http_response_code(405);
        return;
    }

    $controller = new AdminUserController();

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
        error_log('Exception in handleApiToggleAdminUserStatus: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
    } catch (Error $e) {
        error_log('Error in handleApiToggleAdminUserStatus: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
    }
}

function handleApiAdminUserRoles()
{
    checkAdminAuth();
    header('Content-Type: application/json');

    $controller = new AdminUserController();

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

    try {
        $controller->getAvailableRoles($mockRequest, $mockResponse);
        echo $mockResponse->content;
    } catch (Exception $e) {
        error_log('Exception in handleApiAdminUserRoles: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
    } catch (Error $e) {
        error_log('Error in handleApiAdminUserRoles: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
    }
}

function handleApiAdminUserStatuses()
{
    checkAdminAuth();
    header('Content-Type: application/json');

    $controller = new AdminUserController();

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

    try {
        $controller->getAvailableStatuses($mockRequest, $mockResponse);
        echo $mockResponse->content;
    } catch (Exception $e) {
        error_log('Exception in handleApiAdminUserStatuses: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
    } catch (Error $e) {
        error_log('Error in handleApiAdminUserStatuses: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
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

    try {
        $controller->getStats($mockRequest, $mockResponse);
        echo $mockResponse->content;
    } catch (Exception $e) {
        error_log('Exception in handleApiDashboardStats: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
    } catch (Error $e) {
        error_log('Error in handleApiDashboardStats: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
    }
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

    try {
        $controller->getRecentActivities($mockRequest, $mockResponse);
        echo $mockResponse->content;
    } catch (Exception $e) {
        error_log('Exception in handleApiRecentActivities: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
    } catch (Error $e) {
        error_log('Error in handleApiRecentActivities: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
    }
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

    try {
        $controller->getStatistics($mockRequest, $mockResponse);
        echo $mockResponse->content;
    } catch (Exception $e) {
        error_log('Exception in handleApiClientStatistics: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
    } catch (Error $e) {
        error_log('Error in handleApiClientStatistics: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
    }
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
        error_log('Exception in handleApiClientById: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
    } catch (Error $e) {
        error_log('Error in handleApiClientById: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
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
        error_log('Exception in handleApiToggleStatus: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
    } catch (Error $e) {
        error_log('Error in handleApiToggleStatus: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
    }
}

/**
 * Handle JWT Secret API with enhanced security
 * Requires admin authentication and logs access
 * Now retrieves the active JWT secret from the history table
 */
function handleApiJwtSecret()
{
    checkAdminAuth();
    header('Content-Type: application/json');

    try {
        // Load JWT secret from history table (active secret)
        require_once __DIR__ . '/../src/Models/JwtSecretHistory.php';
        
        $jwtSecretRecord = SsoAdmin\Models\JwtSecretHistory::getCurrentSecret();
        
        if (!$jwtSecretRecord) {
            echo json_encode([
                'success' => false,
                'message' => 'No active JWT secret found in history'
            ]);
            return;
        }

        $jwtSecret = $jwtSecretRecord['secret_key'];

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
    } catch (Exception $e) {
        error_log("Error fetching JWT secret: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching JWT secret: ' . $e->getMessage()
        ]);
        http_response_code(500);
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
    } catch (Error $e) {
        error_log('Error in handleApiLogJwtView: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while processing your request'
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
 * Handle JWT Secret Key Update
 * Updates the JWT secret key in config.php and logs the change
 */
function handleApiUpdateJwtSecret()
{
    checkAdminAuth();
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        http_response_code(405);
        return;
    }

    try {
        // Parse input
        $input = json_decode(file_get_contents('php://input'), true);
        $newSecret = $input['secret_key'] ?? null;
        $notes = $input['notes'] ?? null;

        if (!$newSecret || strlen($newSecret) < 32) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid secret key. Must be at least 32 characters long.'
            ]);
            return;
        }

        // Get admin info
        $adminEmail = $_SESSION['admin_email'] ?? 'unknown';
        $ipAddress = getClientIpAddress();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Update config.php
        $configPath = __DIR__ . '/../../config/config.php';
        if (!file_exists($configPath)) {
            throw new Exception('Configuration file not found');
        }

        // Read current config
        $configContent = file_get_contents($configPath);
        
        // Backup current config
        $backupPath = __DIR__ . '/../../config/config.php.backup.' . date('Y-m-d_H-i-s');
        file_put_contents($backupPath, $configContent);

        // Update JWT_SECRET_KEY definition
        $pattern = "/define\('JWT_SECRET_KEY',\s*'[^']*'\);/";
        $replacement = "define('JWT_SECRET_KEY', '$newSecret');";
        $updatedContent = preg_replace($pattern, $replacement, $configContent);

        // If pattern not found, try alternative pattern
        if ($updatedContent === $configContent) {
            $pattern = '/define\("JWT_SECRET_KEY",\s*"[^"]*"\);/';
            $replacement = 'define("JWT_SECRET_KEY", "' . $newSecret . '");';
            $updatedContent = preg_replace($pattern, $replacement, $configContent);
        }

        // Write updated config
        if (!file_put_contents($configPath, $updatedContent)) {
            throw new Exception('Failed to update configuration file');
        }

        // Log to database
        require_once __DIR__ . '/../src/Models/JwtSecretHistory.php';
        SsoAdmin\Models\JwtSecretHistory::addSecret($newSecret, $adminEmail, $notes);

        // Log to audit trail
        $sql = 'INSERT INTO audit_logs (admin_email, action, resource_type, resource_id, new_values, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())';

        Connection::query($sql, [
            $adminEmail,
            'jwt_secret_updated',
            'system',
            null,
            json_encode(['secret_key' => substr($newSecret, 0, 10) . '...']), // Only store partial secret for security
            $ipAddress,
            $userAgent
        ]);

        // Send email notification (if configured)
        sendJwtSecretUpdateEmail($adminEmail, $newSecret, $ipAddress);

        echo json_encode([
            'success' => true,
            'message' => 'JWT secret key updated successfully',
            'new_secret' => $newSecret
        ]);
    } catch (Exception $e) {
        error_log("Error updating JWT secret: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update JWT secret key: ' . $e->getMessage()
        ]);
        http_response_code(500);
    } catch (Error $e) {
        error_log('Error in handleApiUpdateJwtSecret: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while processing your request'
        ]);
        http_response_code(500);
    }
}

/**
 * Send email notification for JWT secret key update
 * @param string $adminEmail
 * @param string $newSecret
 * @param string $ipAddress
 */
function sendJwtSecretUpdateEmail($adminEmail, $newSecret, $ipAddress)
{
    // In a real implementation, you would send an email here
    // For now, we'll just log it
    error_log("JWT Secret Update Notification: Admin $adminEmail updated JWT secret from IP $ipAddress");
    
    // You could implement actual email sending here using PHPMailer or similar
    // Example:
    /*
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->isSMTP();
    $mail->Host = 'smtp.example.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'admin@example.com';
    $mail->Password = 'password';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    
    $mail->setFrom('admin@example.com', 'SSO Admin');
    $mail->addAddress($adminEmail);
    $mail->Subject = 'JWT Secret Key Updated';
    $mail->Body = "The JWT Secret Key has been updated by $adminEmail from IP $ipAddress.";
    
    $mail->send();
    */
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
    } catch (Error $e) {
        error_log('Error in handleApiUsageStatistics: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while processing your request'
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
    } catch (Error $e) {
        error_log('Error in handleApiIndividualClientStatistics: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while processing your request'
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
    } catch (Error $e) {
        error_log('Error in handleApiCreateBackup: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while processing your request'
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
    } catch (Error $e) {
        error_log('Error in handleApiListBackups: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while processing your request'
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
    } catch (Error $e) {
        error_log('Error in handleApiDownloadBackup: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while processing your request'
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
    } catch (Error $e) {
        error_log('Error in handleApiDeleteBackup: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while processing your request'
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
    } catch (Error $e) {
        error_log('Error in handleApiRestoreBackup: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while processing your request'
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
    } catch (Error $e) {
        error_log('Error in handleApiGenerateMockData: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while processing your request'
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
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
                    <i class="fas fa-sign-out-alt me-1"></i>Sign out
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
                            <a class="nav-link" href="' . $basePath . '/settings">
                                <i class="fas fa-cog me-2"></i>System Configuration
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="' . $basePath . '/js/shared.js"></script>
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

            let html = `<table class="table table-hover" id="clientsTable">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 25%">Client Name</th>
                        <th style="width: 20%">Client ID</th>
                        <th style="width: 20%">Redirect URI</th>
                        <th style="width: 15%">Authentication Mode</th>
                        <th style="width: 10%">Status</th>
                        <th style="width: 10%">Created</th>
                        <th style="width: 5%">Actions</th>
                    </tr>
                </thead>
                <tbody>`;
            
            clients.forEach(client => {
                const createdDate = new Date(client.created_at).toLocaleDateString("th-TH");
                const statusBadge = client.status === "active" ? 
                    `<span class="badge bg-success status-badge">${client.status}</span>` :
                    `<span class="badge bg-secondary status-badge">${client.status}</span>`;
                    
                // Determine auth mode badge
                let authModeBadge;
                if (client.user_handler_endpoint === null || client.user_handler_endpoint === "") {
                    authModeBadge = `<span class="badge bg-dark" title="No Handler">None</span>`;
                } else if (client.user_handler_endpoint.startsWith("http")) {
                    authModeBadge = `<span class="badge bg-primary" title="JWT Mode"><i class="fas fa-key me-1"></i>JWT</span>`;
                } else {
                    authModeBadge = `<span class="badge bg-secondary" title="Legacy Mode"><i class="fas fa-server me-1"></i>Legacy</span>`;
                }
                
                // Truncate long redirect URI
                let displayUri = client.app_redirect_uri;
                if (displayUri.length > 30) {
                    displayUri = displayUri.substring(0, 30) + "...";
                }
                
                // Escape single quotes in client ID and name for JavaScript
                const escapedClientId = client.client_id.replace(/\'/g, "\\\'");
                const escapedClientName = client.client_name.replace(/\'/g, "\\\'");
                
                html += `<tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="me-2">
                                ${authModeBadge.includes("JWT") ? `<i class="fas fa-key fa-lg text-primary"></i>` : authModeBadge.includes("Legacy") ? `<i class="fas fa-server fa-lg text-secondary"></i>` : `<i class="fas fa-question fa-lg text-muted"></i>`}
                            </div>
                            <div>
                                <strong>${client.client_name}</strong>
                                <br><small class="text-muted">${client.description || "No description"}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <code class="small me-1">${client.client_id}</code>
                            <button class="btn btn-sm btn-outline-secondary copy-btn" onclick="copyToClipboardText(\'${escapedClientId}\')" title="Copy Client ID">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </td>
                    <td>
                        <span class="small" title="${client.app_redirect_uri}">
                            ${displayUri}
                        </span>
                    </td>
                    <td class="text-center">
                        ${authModeBadge}
                    </td>
                    <td class="text-center">${statusBadge}</td>
                    <td class="text-center">
                        <span title="${new Date(client.created_at).toLocaleString("th-TH")}">
                            ${createdDate}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-outline-info" onclick="viewClient(${client.id})" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-outline-primary" onclick="editClient(${client.id})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-secondary" onclick="toggleClientStatus(${client.id}, \'${client.status}\')" title="${client.status === "active" ? "Deactivate" : "Activate"}">
                                <i class="fas ${client.status === "active" ? "fa-toggle-on" : "fa-toggle-off"}"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteClient(${client.id}, \'${escapedClientName}\')" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
            });
            
            html += `</tbody></table>`;
            container.innerHTML = html;
            
            // Initialize DataTable
            $("#clientsTable").DataTable({
                "order": [[5, "desc"]],
                "pageLength": 10,
                "responsive": true,
                "columnDefs": [
                    { "orderable": false, "targets": [6] }
                ]
            });
        }

        // Helper function to copy text to clipboard
        function copyToClipboardText(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    showCustomToast("Copied to clipboard successfully!", "success");
                }).catch(err => {
                    fallbackCopyTextToClipboard(text);
                });
            } else {
                fallbackCopyTextToClipboard(text);
            }
        }
        
        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand("copy");
                showCustomToast("Copied to clipboard successfully!", "success");
            } catch (err) {
                console.error("Fallback: Oops, unable to copy", err);
                Swal.fire("Copy Failed", "Unable to copy to clipboard", "error");
            }
            
            document.body.removeChild(textArea);
        }

        function showAddClientModal() {
            Swal.fire({
                title: "Add Client Application",
                text: "ฟีเจอร์นี้จะพร้อมใช้งานในเร็วๆ นี้",
                icon: "info"
            });
        }

        function viewClient(id) {
            Swal.fire({
                title: "View Client",
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

        function toggleClientStatus(id, currentStatus) {
            Swal.fire({
                title: "Toggle Client Status",
                text: "ฟีเจอร์นี้จะพร้อมใช้งานในเร็วๆ นี้",
                icon: "info"
            });
        }

        function deleteClient(id, clientName) {
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
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-content {
            margin-bottom: 20px;
        }
    </style>
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
                    <i class="fas fa-sign-out-alt me-1"></i>Sign out
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
                        <li class="nav-item">
                            <a class="nav-link" href="' . $basePath . '/admin-users">
                                <i class="fas fa-user-shield me-2"></i>Admin Users
                            </a>
                        </li>
                        <li class="nav-item">
                                <a class="nav-link" href="' . $basePath . '/settings">
                                    <i class="fas fa-cog me-2"></i>System Configuration
                                </a>
                            </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 admin-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-chart-bar me-2"></i>Usage Statistics <small class="text-muted fs-6" id="demo-indicator" style="display:none"><i class="fas fa-flask me-1"></i>Demo Data Active</small></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <select class="form-select" id="periodSelect" onchange="loadStatistics()">
                                <option value="7">Last 7 days</option>
                                <option value="30" selected>Last 30 days</option>
                                <option value="90">Last 90 days</option>
                            </select>
                        </div>
                        <!--<div class="btn-group me-2">
                            <button type="button" class="btn btn-success" onclick="generateMockData()">
                                <i class="fas fa-magic me-1"></i>Generate Demo Data
                            </button>
                            <a href="oidc-explanation.html" class="btn btn-outline-info" target="_blank">
                                <i class="fas fa-question-circle me-1"></i>What is OIDC Action?
                            </a>
                        </div>
                        <button type="button" class="btn btn-outline-secondary" onclick="loadStatistics()">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>-->
                    </div>
                </div>

                <!-- System Statistics Overview -->
                <div class="row mb-4" id="system-stats" style="display:none">
                    <div class="col-12">
                        <div class="card shadow">
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
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 id="time-round"><i class="fas fa-users me-2"></i>Client Activity Summary</h5>
                            </div>
                            <div class="card-body" id="client-stats">
                                <div class="text-center">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2">Loading clients statistics...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
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
            timeRound = document.getElementById("time-round");
            
            // Render system overview
            timeRound.innerHTML = `<i class="fas fa-users me-2"></i>Client Activity Summary (${stats.period_days} days)`;
            systemStatsDiv.innerHTML = `
                <div class="col-12">
                    <div class="card shadow">
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
                    <table class="table table-striped" id="clientStatsTable">
                        <thead>
                            <tr>
                                <th>Client Name</th>
                                <th>Status</th>
                                <th>Total Activities</th>
                                <th>Total Requests</th>
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
                        <td><span class="badge" style="background:#9B59B6">${client.total_activities}</span></td>
                        <td><span class="badge" style="background:#F1C40F">${client.total_requests || 0}</span></td>
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
            
            // Initialize DataTable
            $("#clientStatsTable").DataTable({
                "order": [[2, "desc"]],
                "pageLength": 10,
                "responsive": true,
                "columnDefs": [
                    { "orderable": false, "targets": [7] }
                ]
            });
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
                    console.error("Error loading clients statistics:", error);
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
                        <p><strong>Total Requests:</strong> <span class="badge bg-primary">${stats.client.total_requests || 0}</span></p>
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
