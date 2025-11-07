<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'SSO Admin Panel'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Responsive Sidebar Styles */
        :root {
            --sidebar-width: 250px;
            --navbar-height: 56px;
        }

        body {
            overflow-x: hidden;
        }

        /* Top Navbar */
        .admin-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            height: var(--navbar-height);
        }

        /* Hamburg Menu Button */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 8px;
            left: 10px;
            z-index: 1031;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .sidebar-toggle:hover {
            background: #0b5ed7;
        }

        .sidebar-toggle i {
            font-size: 1.2rem;
        }

        /* Sidebar */
        .admin-sidebar {
            position: fixed;
            top: var(--navbar-height);
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 1020;
            transition: transform 0.3s ease-in-out;
        }

        /* Main Content */
        .admin-content {
            margin-top: var(--navbar-height);
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: calc(100vh - var(--navbar-height));
            transition: margin-left 0.3s ease-in-out;
        }

        /* Sidebar Navigation */
        .admin-sidebar .nav-link {
            color: #495057;
            padding: 12px 20px;
            border-radius: 0.375rem;
            margin: 4px 10px;
            transition: all 0.2s ease;
        }

        .admin-sidebar .nav-link:hover {
            background-color: #e9ecef;
            color: #0d6efd;
        }

        .admin-sidebar .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }

        .admin-sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: var(--navbar-height);
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1010;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.show {
            opacity: 1;
        }

        /* Mobile Responsive - Tablets */
        @media (max-width: 991.98px) {
            .sidebar-toggle {
                display: block;
            }

            .admin-sidebar {
                transform: translateX(-100%);
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            }

            .admin-sidebar.show {
                transform: translateX(0);
            }

            .admin-content {
                margin-left: 0;
            }

            .sidebar-overlay {
                display: block;
            }

            .admin-navbar .navbar-brand {
                margin-left: 50px;
            }
        }

        /* Mobile Responsive - Phones */
        @media (max-width: 575.98px) {
            .admin-content {
                padding: 15px 10px;
            }

            .admin-sidebar {
                width: 80%;
                max-width: 280px;
            }

            .admin-navbar .navbar-text {
                display: none;
            }

            .admin-navbar .navbar-brand {
                font-size: 0.9rem;
            }

            .btn-toolbar {
                flex-wrap: wrap;
            }

            .card {
                margin-bottom: 15px;
            }

            h1.h2 {
                font-size: 1.5rem;
            }
        }

        /* Utility Classes */
        .admin-page-header {
            margin-bottom: 1.5rem;
        }

        .admin-page-title {
            display: flex;
            justify-content: between;
            align-items: center;
            flex-wrap: wrap;
            padding-top: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #dee2e6;
        }

        /* Additional Custom Styles */
        <?php if (isset($additionalStyles)): ?><?php echo $additionalStyles; ?><?php endif; ?>
    </style>
</head>

<body>
    <?php
    $basePath = $GLOBALS['admin_base_path'] ?? '/sso-authen-3/admin/public';
    $adminName = $_SESSION['admin_name'] ?? 'Administrator';
    $adminEmail = $_SESSION['admin_email'] ?? 'admin@example.com';
    ?>

    <!-- Hamburg Menu Button -->
    <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle Sidebar">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary admin-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo $basePath; ?>">
                <i class="fas fa-shield-alt me-2"></i>SSO Admin Panel
            </a>
            <div class="navbar-nav ms-auto d-flex flex-row align-items-center">
                <span class="navbar-text me-3 text-white">
                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($adminName); ?>
                </span>
                <a class="nav-link text-white" href="<?php echo $basePath; ?>/auth/logout" title="Sign out">
                    <i class="fas fa-sign-out-alt me-1"></i><span class="d-none d-md-inline">Sign out</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar Overlay (for mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar Navigation -->
    <nav class="admin-sidebar" id="adminSidebar">
        <div class="position-sticky pt-3">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage ?? '') === 'dashboard' ? 'active' : ''; ?>"
                        href="<?php echo $basePath; ?>">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage ?? '') === 'clients' ? 'active' : ''; ?>"
                        href="<?php echo $basePath; ?>/clients">
                        <i class="fas fa-users me-2"></i>Client Applications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage ?? '') === 'statistics' ? 'active' : ''; ?>"
                        href="<?php echo $basePath; ?>/statistics">
                        <i class="fas fa-chart-bar me-2"></i>Usage Statistics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage ?? '') === 'admin-users' ? 'active' : ''; ?>"
                        href="<?php echo $basePath; ?>/admin-users">
                        <i class="fas fa-user-shield me-2"></i>Admin Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage ?? '') === 'backup-restore' ? 'active' : ''; ?>"
                        href="<?php echo $basePath; ?>/backup-restore">
                        <i class="fas fa-database me-2"></i>Backup & Restore
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage ?? '') === 'settings' ? 'active' : ''; ?>"
                        href="<?php echo $basePath; ?>/settings">
                        <i class="fas fa-cog me-2"></i>System Configuration
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $basePath; ?>/api-docs-v3.html" target="_blank">
                        <i class="fas fa-book me-2"></i>Documentation
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="admin-content">