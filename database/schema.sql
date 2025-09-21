-- Database Schema for SSO Authentication Service v.3
-- Created for MySQL 8.0+

-- Create database (run manually if needed)
-- CREATE DATABASE IF NOT EXISTS sso_authen CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE sso_authen;

-- Table: clients
-- Stores registered client applications
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id VARCHAR(255) NOT NULL UNIQUE,
    client_name VARCHAR(255) NOT NULL,
    client_description TEXT,
    app_redirect_uri VARCHAR(500) NOT NULL,
    post_logout_redirect_uri VARCHAR(500) NULL,
    user_handler_endpoint VARCHAR(500) NULL, -- NULL for legacy mode
    api_secret_key VARCHAR(255) NULL,
    allowed_scopes VARCHAR(255) DEFAULT 'openid,profile,email',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(255),
    updated_by VARCHAR(255),
    
    INDEX idx_client_id (client_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: admin_users
-- Stores authorized admin users
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'viewer') DEFAULT 'admin',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: audit_logs
-- Tracks all administrative actions
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_email VARCHAR(255) NOT NULL,
    action VARCHAR(100) NOT NULL, -- 'create', 'update', 'delete', 'login', 'logout'
    resource_type VARCHAR(50) NOT NULL, -- 'client', 'admin_user', 'auth'
    resource_id VARCHAR(255) NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_admin_email (admin_email),
    INDEX idx_action (action),
    INDEX idx_resource_type (resource_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (modify email as needed)
INSERT INTO admin_users (email, name, role, status) VALUES 
('admin@psu.ac.th', 'System Administrator', 'super_admin', 'active')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Insert sample client data (for testing)
INSERT INTO clients (
    client_id, 
    client_name, 
    client_description,
    app_redirect_uri,
    post_logout_redirect_uri,
    user_handler_endpoint,
    api_secret_key,
    allowed_scopes,
    status,
    created_by
) VALUES 
(
    'my_react_app',
    'React Application',
    'Sample React application for testing SSO integration',
    'http://localhost:3000/callback',
    'http://localhost:3000/logout-success',
    'http://localhost:8080/api/sso-user-handler',
    'VERY_SECRET_KEY_FOR_REACT_APP',
    'openid,profile,email',
    'active',
    'system'
),
(
    'my_js_app',
    'JavaScript Application',
    'Sample JavaScript application using Live Server',
    'http://localhost:5500/public/callback.html',
    'http://localhost:5500/public/index.html',
    'http://localhost:8080/sso-user-handler',
    'VERY_SECRET_KEY_FOR_JS_APP',
    'openid,profile,email',
    'active',
    'system'
),
(
    'legacy_php_app',
    'Legacy PHP Application',
    'Legacy PHP application using session-based authentication',
    'http://old-app.test/index.php',
    'http://old-app.test/',
    NULL,
    NULL,
    'openid,profile',
    'active',
    'system'
)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;