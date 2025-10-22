# SSO-Authen Admin Panel Setup Guide

## 🚀 Quick Setup

### 1. Database Setup
```bash
# Run the database installer
php database/install.php
```

### 2. Admin Panel Configuration
```bash
# Copy environment file
cp admin/.env.example admin/.env

# Edit admin/.env with your database settings
```

### 3. Access Admin Panel

**Development Mode:** 
- URL: `http://localhost/sso-authen-3/admin/public/`
- Click "เข้าสู่ระบบ" to login as admin (no password required)
- Default admin: admin@psu.ac.th

## 📋 Features Available

### ✅ Completed Features:
- **Dashboard:** Statistics and recent activities
- **Client Management:** View existing clients
- **Database Integration:** Clients loaded from database
- **SweetAlert2 Alerts:** Consistent UI notifications
- **Bootstrap 5 UI:** Responsive and modern interface
- **Development Login:** Easy testing without OIDC setup

### 🔄 Coming Soon:
- **OIDC Authentication:** Real admin authentication
- **Client CRUD:** Add/Edit/Delete clients via UI
- **API Secret Management:** Generate/regenerate keys
- **Audit Logs:** Full activity tracking
- **Client Status Management:** Enable/disable clients

## 📁 File Structure

```
admin/
├── public/
│   └── index.php          # Main entry point with routing
├── src/
│   ├── Controllers/       # API controllers
│   ├── Models/           # Database models
│   └── Database/         # Database connection
├── config/
│   └── admin_config.php  # Admin panel configuration
└── .env                  # Environment variables

database/
├── schema.sql           # Database structure
└── install.php         # Installation script
```

## 🔧 Technical Details

- **PHP Version:** 7.4.33 compatible
- **Database:** MySQL with utf8mb4 encoding
- **UI Framework:** Bootstrap 5 + Font Awesome
- **Alerts:** SweetAlert2 (consistent with main SSO system)
- **Architecture:** MVC pattern with simple routing

## 🛠️ Development Notes

The system now loads client configurations from database instead of hard-coded arrays in `config.php`. The main config file has been updated to:

1. **Try database first:** Load clients from MySQL database
2. **Fallback gracefully:** Use default clients if database unavailable
3. **Log errors:** Track database connection issues
4. **Maintain compatibility:** Existing SSO flow continues to work

## 🔐 Security Notes

- Development mode should not be used in production
- API secret keys are stored in database
- Admin authentication will use same OIDC provider as main SSO
- All admin actions are logged for audit purposes