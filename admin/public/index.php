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
    // Debug logging
    error_log('Admin Panel - Path: ' . $path . ', REQUEST_URI: ' . $_SERVER['REQUEST_URI']);
    
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
            
        case '/api/system/jwt-secret':
            handleApiJwtSecret();
            break;
            
        // API routes with parameters (basic matching)
        default:
            // Handle API routes with parameters
            if (preg_match('/^\/api\/clients\/(\d+)$/', $path, $matches)) {
                handleApiClientById($matches[1]);
            } elseif (preg_match('/^\/api\/clients\/(\d+)\/toggle-status$/', $path, $matches)) {
                handleApiToggleStatus($matches[1]);
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
function checkAdminAuth() {
    global $config;
    $requestUri = $_SERVER['REQUEST_URI'];
    
    // Skip auth for login and callback routes
    if (strpos($requestUri, '/auth/') !== false || strpos($requestUri, 'login.php') !== false) {
        return true;
    }
    
    // Check if admin is logged in
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        $basePath = $GLOBALS['admin_base_path'];
        // Redirect to standalone login page
        header('Location: ' . $basePath . '/login.php');
        exit;
    }
    
    return true;
}

function handleDashboard() {
    checkAdminAuth();
    
    $controller = new DashboardController();
    
    // Create simple mock objects
    $mockRequest = new stdClass();
    $mockResponse = new class {
        public $content = '';
        
        public function getBody() {
            return $this;
        }
        
        public function write($content) {
            $this->content = $content;
        }
        
        public function withHeader($name, $value) {
            header("$name: $value");
            return $this;
        }
        
        public function getContent() { 
            return $this->content; 
        }
    };
    
    $controller->index($mockRequest, $mockResponse);
    echo $mockResponse->getContent();
}

function handleAuthLogin() {
    // Debug: Check if we're properly handling the login route
    error_log('handleAuthLogin called - REQUEST_URI: ' . $_SERVER['REQUEST_URI']);
    
    // Ensure we start with a clean session for login
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    echo renderSimpleLoginPage();
}

function handleAuthCallback() {
    echo 'Auth callback - Under development';
}

function handleAuthLogout() {
    // Clear all session data
    $_SESSION = array();
    
    // Destroy the session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    
    $basePath = $GLOBALS['admin_base_path'];
    
    // Show logout success message and redirect to standalone login
    echo renderAlert('ออกจากระบบสำเร็จ', 'คุณได้ออกจากระบบเรียบร้อยแล้ว', 'success', $basePath . '/login.php');
}

function handleClientsPage() {
    checkAdminAuth();
    
    // Include the clients view
    $viewPath = __DIR__ . '/../views/clients.php';
    if (file_exists($viewPath)) {
        include $viewPath;
    } else {
        echo renderClientsPage();
    }
}

function handleApiClients() {
    checkAdminAuth();
    header('Content-Type: application/json');
    
    $controller = new ClientController();
    
    // Handle different methods
    $method = $_SERVER['REQUEST_METHOD'];
    
    try {
        switch ($method) {
            case 'GET':
                $mockRequest = new class {
                    public function getQueryParams() {
                        return $_GET;
                    }
                };
                $mockResponse = new class {
                    public $content = '';
                    public function getBody() { return $this; }
                    public function write($content) { $this->content = $content; }
                    public function withHeader($name, $value) {
                        header("$name: $value");
                        return $this;
                    }
                };
                
                $controller->getAll($mockRequest, $mockResponse);
                echo $mockResponse->content;
                break;
                
            case 'POST':
                $mockRequest = new class {
                    public function getBody() {
                        return new class {
                            public function getContents() {
                                return file_get_contents('php://input');
                            }
                        };
                    }
                };
                $mockResponse = new class {
                    public $content = '';
                    public function getBody() { return $this; }
                    public function write($content) { $this->content = $content; }
                    public function withHeader($name, $value) {
                        header("$name: $value");
                        return $this;
                    }
                    public function withStatus($code) {
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

function handleApiDashboardStats() {
    checkAdminAuth();
    header('Content-Type: application/json');
    
    $controller = new DashboardController();
    
    $mockRequest = new stdClass();
    $mockResponse = new class {
        public $content = '';
        public function getBody() { return $this; }
        public function write($content) { $this->content = $content; }
        public function withHeader($name, $value) {
            header("$name: $value");
            return $this;
        }
        public function withStatus($code) {
            http_response_code($code);
            return $this;
        }
    };
    
    $controller->getStats($mockRequest, $mockResponse);
    echo $mockResponse->content;
}

function handleApiRecentActivities() {
    checkAdminAuth();
    header('Content-Type: application/json');
    
    $controller = new DashboardController();
    
    $mockRequest = new class {
        public function getQueryParams() {
            return $_GET;
        }
    };
    $mockResponse = new class {
        public $content = '';
        public function getBody() { return $this; }
        public function write($content) { $this->content = $content; }
        public function withHeader($name, $value) {
            header("$name: $value");
            return $this;
        }
        public function withStatus($code) {
            http_response_code($code);
            return $this;
        }
    };
    
    $controller->getRecentActivities($mockRequest, $mockResponse);
    echo $mockResponse->content;
}

function handleApiClientStatistics() {
    checkAdminAuth();
    header('Content-Type: application/json');
    
    $controller = new ClientController();
    
    $mockRequest = new stdClass();
    $mockResponse = new class {
        public $content = '';
        public function getBody() { return $this; }
        public function write($content) { $this->content = $content; }
        public function withHeader($name, $value) {
            header("$name: $value");
            return $this;
        }
        public function withStatus($code) {
            http_response_code($code);
            return $this;
        }
    };
    
    $controller->getStatistics($mockRequest, $mockResponse);
    echo $mockResponse->content;
}

function handleApiClientById($id) {
    checkAdminAuth();
    header('Content-Type: application/json');
    
    $controller = new ClientController();
    $method = $_SERVER['REQUEST_METHOD'];
    
    $mockRequest = new class {
        public function getBody() {
            return new class {
                public function getContents() {
                    return file_get_contents('php://input');
                }
            };
        }
    };
    
    $mockResponse = new class {
        public $content = '';
        public function getBody() { return $this; }
        public function write($content) { $this->content = $content; }
        public function withHeader($name, $value) {
            header("$name: $value");
            return $this;
        }
        public function withStatus($code) {
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



function handleApiToggleStatus($id) {
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
        public function getBody() { return $this; }
        public function write($content) { $this->content = $content; }
        public function withHeader($name, $value) {
            header("$name: $value");
            return $this;
        }
        public function withStatus($code) {
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

function renderSimpleLoginPage() {
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

function renderClientsPage() {
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

function renderAlert($title, $message, $icon, $redirectUrl) {
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

function handleApiJwtSecret() {
    checkAdminAuth();
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        http_response_code(405);
        return;
    }
    
    // Load JWT secret from config
    $configPath = __DIR__ . '/../../config/config.php';
    if (file_exists($configPath)) {
        // Define constants that might be needed
        if (!defined('JWT_SECRET_KEY')) {
            require_once $configPath;
        }
        
        $jwtSecret = defined('JWT_SECRET_KEY') ? JWT_SECRET_KEY : 'YOUR_SUPER_SECRET_KEY_FOR_JWT_GENERATION';
        
        echo json_encode([
            'success' => true,
            'data' => [
                'jwt_secret' => $jwtSecret
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Configuration file not found'
        ]);
        http_response_code(500);
    }
}
