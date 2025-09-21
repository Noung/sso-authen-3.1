# SSO Admin Panel - Client Management System

## 🎯 Overview
Complete CRUD (Create, Read, Update, Delete) system for managing OAuth2/OIDC client applications with modern UI and comprehensive functionality.

## ✨ Features

### 📊 Dashboard & Statistics
- Real-time client statistics (Total, Active, Inactive, Suspended)
- Visual status indicators with color-coded cards
- Quick overview of system health

### 🔍 Advanced Search & Filtering
- Real-time search across client names, IDs, and redirect URIs
- Status-based filtering (Active/Inactive/Suspended)
- Pagination with customizable items per page (10, 25, 50, 100)
- Debounced search input for optimal performance

### 📝 Client Management
- **Create**: Add new client applications with validation
- **Read**: View detailed client information
- **Update**: Edit client properties (name, redirect URI, scopes, status)
- **Delete**: Remove clients with confirmation dialogs

### 🔐 Security Features
- Secure client secret generation using `bin2hex(random_bytes(32))`
- Client secrets are hashed using `password_hash()` with PHP's default algorithm
- One-time secret display during creation
- Secret regeneration with immediate invalidation of old secrets
- Copy-to-clipboard functionality for credentials

### 📱 Modern UI/UX
- Bootstrap 5 responsive design
- SweetAlert2 integration for beautiful notifications
- Font Awesome icons for visual consistency
- Mobile-friendly interface
- Real-time feedback and loading states

## 🛠 Technical Specifications

### PHP 7.4.33 Compatibility
- All code written for PHP 7.4.33 compatibility
- Uses traditional array syntax `array()` instead of `[]`
- Avoids PHP 8+ specific features
- Proper error handling with try-catch blocks

### Database Schema
```sql
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id VARCHAR(255) UNIQUE NOT NULL,
    client_name VARCHAR(255) NOT NULL,
    app_redirect_uri TEXT NOT NULL,
    post_logout_redirect_uri TEXT,
    user_handler_endpoint TEXT,
    api_secret_key VARCHAR(255),
    allowed_scopes TEXT DEFAULT 'openid,profile,email',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(255)
);
```

### API Endpoints

#### GET /api/clients
- **Purpose**: Retrieve paginated list of clients
- **Parameters**: 
  - `page` (int): Page number (default: 1)
  - `per_page` (int): Items per page (default: 10, max: 100)
  - `search` (string): Search term
  - `status` (string): Filter by status
- **Response**: Paginated client list with metadata

#### POST /api/clients
- **Purpose**: Create new client application
- **Body**: JSON with client_name, app_redirect_uri, allowed_scopes, status
- **Response**: Created client with credentials (secret shown only once)

#### GET /api/clients/{id}
- **Purpose**: Get specific client details
- **Response**: Client information (secret excluded)

#### PUT /api/clients/{id}
- **Purpose**: Update client information
- **Body**: JSON with updated fields
- **Response**: Updated client information

#### DELETE /api/clients/{id}
- **Purpose**: Delete client application
- **Response**: Success confirmation

#### PATCH /api/clients/{id}/toggle-status
- **Purpose**: Toggle client status (active/inactive)
- **Response**: Updated client information

#### GET /api/clients/statistics
- **Purpose**: Get client statistics
- **Response**: Count by status (total, active, inactive, suspended)

## 🚀 Usage Examples

### Creating a New Client
1. Click "Add New Client" button
2. Fill in required fields:
   - **Client Name**: Display name for the application
   - **Redirect URI**: Valid URL where users will be redirected
   - **Post Logout URI**: URL for post-logout redirect (optional)
   - **User Handler Endpoint**: API endpoint for user management (optional)
   - **API Secret Key**: Secret key for API communication (optional)
   - **Allowed Scopes**: OAuth scopes using checkboxes
   - **Status**: Initial status (active/inactive/suspended)
3. Click "Save Client"
4. Client will be created with auto-generated Client ID

### Managing Existing Clients
- **View**: Click the eye icon to see full client details
- **Edit**: Click the pencil icon to modify client properties
- **Toggle Status**: Click the toggle icon to activate/deactivate
- **Delete**: Click the trash icon (requires confirmation)

### Security Best Practices
1. **API Secret Keys**: Store securely when used for API endpoints
2. **Redirect URIs**: Validate and use HTTPS in production
3. **Scopes**: Grant minimal necessary permissions
4. **Status Management**: Use inactive/suspended for temporary disabling

## 🎨 UI Components

### Status Badges
- 🟢 **Active**: Green badge for operational clients
- ⚫ **Inactive**: Gray badge for temporarily disabled clients  
- 🔴 **Suspended**: Red badge for banned/problematic clients

### Interactive Elements
- **Search Bar**: Real-time filtering with 500ms debounce
- **Pagination**: Smart pagination with ellipsis for large datasets
- **Modal Forms**: Overlay forms for create/edit operations
- **Copy Buttons**: One-click clipboard copying for client IDs and API keys

## 🔧 Configuration

### Environment Setup
Ensure the following PHP extensions are enabled:
- `openssl` (for secure random generation)
- `pdo_mysql` (for database connectivity)
- `json` (for API responses)

### Database Configuration
Update `admin/config/admin_config.php`:
```php
'database' => [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'sso_authen',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
],
```

## 📱 Mobile Responsiveness
- Responsive tables with horizontal scrolling
- Touch-friendly button sizes
- Collapsible sidebar navigation
- Optimized modal dialogs for mobile screens

## 🛡 Error Handling
- Comprehensive input validation
- User-friendly error messages
- Graceful fallbacks for network issues
- Detailed logging for debugging

## 🎯 Future Enhancements
- Bulk operations (activate/deactivate multiple clients)
- Client usage analytics and statistics
- Export functionality (CSV/JSON)
- Advanced filtering options
- Client template system for quick setup

---

**Compatible with PHP 7.4.33 and modern web browsers**  
**Built with Bootstrap 5, SweetAlert2, and Font Awesome**