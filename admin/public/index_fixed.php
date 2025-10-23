<?php

/**
 * admin/public/index_fixed.php
 * Fixed version - Simple Admin Panel Bootstrap (without Slim dependencies)
 * Compatible with PHP 7.4.33
 */

// Load autoloader when available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Load main project autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

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

// Simple routing
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = dirname($scriptName);
$path = str_replace($basePath, '', $requestUri);
$path = parse_url($path, PHP_URL_PATH);

// Remove query string
if (($pos = strpos($path, '?')) !== false) {
    $path = substr($path, 0, $pos);
}

// Clean up path
$path = '/' . trim($path, '/');

try {
    // Route handling
    switch ($path) {
        case '/':
        case '/index.php':
            handleDashboard();
            break;
            
        case '/auth/login':
        case '/auth/login.php':
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
            
        case '/api/dashboard/stats':
            handleApiDashboardStats();
            break;
            
        case '/api/dashboard/recent-activities':
            handleApiRecentActivities();
            break;
            
        default:
            http_response_code(404);
            echo '404 - Page Not Found';
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo 'Server Error: ' . $e->getMessage();
}

// Route handlers
function checkAdminAuth() {
    global $config;
    $path = $_SERVER['REQUEST_URI'];
    
    // Skip auth for login and callback routes
    if (strpos($path, '/auth/') !== false) {
        return true;
    }
    
    // Check if admin is logged in
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        header('Location: /admin/public/auth/login.php');
        exit;
    }
    
    return true;
}

function handleDashboard() {
    checkAdminAuth();
    
    $controller = new DashboardController();
    
    // Create mock request/response objects
    $mockRequest = new stdClass();
    $mockResponse = new class {
        private $body = '';
        private $headers = [];
        
        public function getBody() {
            return new class($this) {
                private $response;
                public function __construct($response) { $this->response = $response; }
                public function write($content) { $this->response->body = $content; }
            };
        }
        
        public function withHeader($name, $value) {
            $this->headers[$name] = $value;
            header("$name: $value");
            return $this;
        }
        
        public function getContent() { return $this->body; }
    };
    
    $controller->index($mockRequest, $mockResponse);
    echo $mockResponse->getContent();
}

function handleAuthLogin() {
    echo renderSimpleLoginPage();
}

function handleAuthCallback() {
    echo 'Auth callback - Under development';
}

function handleAuthLogout() {
    $_SESSION = array();
    session_destroy();
    
    echo renderAlert('Sign out successful', 'You have successfully signed out.', 'success', '/admin/public/auth/login.php');
}

function handleClientsPage() {
    checkAdminAuth();
    echo renderClientsPage();
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
                    public function getBody() {
                        return new class {
                            public function write($content) {
                                echo $content;
                            }
                        };
                    }
                    public function withHeader($name, $value) {
                        header("$name: $value");
                        return $this;
                    }
                };
                
                $controller->getAll($mockRequest, $mockResponse);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Method not implemented yet']);
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
        public function getBody() {
            return new class {
                public function write($content) {
                    echo $content;
                }
            };
        }
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
        public function getBody() {
            return new class {
                public function write($content) {
                    echo $content;
                }
            };
        }
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
}

function renderSimpleLoginPage() {
    return '<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSO-Authen Admin Panel - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bai+Jamjuree:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: \'Bai Jamjuree\', sans-serif;
        }
        body {
            font-family: \'Bai Jamjuree\', sans-serif;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow mt-5">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                            <h3>SSO-Authen Admin Panel</h3>
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
            // Development mode - simulate login
            fetch("/admin/public/", {
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
                window.location.href = "/admin/public/";
            });
        }
    </script>
</body>
</html>';
}

function renderClientsPage() {
    return '<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSO-Authen Admin Panel - Clients</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bai+Jamjuree:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: \'Bai Jamjuree\', sans-serif;
        }
        body {
            font-family: \'Bai Jamjuree\', sans-serif;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <!-- Same navbar and sidebar as dashboard -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin/public/">
                <i class="fas fa-shield-alt me-2"></i>SSO-Authen Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i>' . ($_SESSION['admin_name'] ?? 'Administrator') . '
                </span>
                <a class="nav-link" href="/admin/public/auth/logout">
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
                            <a class="nav-link" href="/admin/public/">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="/admin/public/clients">
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

                <!-- Clients Table -->
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
            fetch("/admin/public/api/clients")
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
                container.innerHTML = "<p class=\"text-center text-muted\">ไม่มีข้อมูล Client Applications</p>";
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <link href="https://fonts.googleapis.com/css2?family=Bai+Jamjuree:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            font-family: "Bai Jamjuree", sans-serif;
        }
        body {
            font-family: "Bai Jamjuree", sans-serif;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="text-center">
            <h3>Please wait...</h3>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "' . addslashes($title) . '",
                text: "' . addslashes($message) . '",
                icon: "' . addslashes($icon) . '",
                confirmButtonText: "OK"
            }).then((result) => {
                window.location.href = "' . addslashes($redirectUrl) . '";
            });
        });
    </script>
</body>
</html>';
}