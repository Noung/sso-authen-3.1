<!DOCTYPE html>
<html lang="th">

<head>
    <?php
    $basePath = $GLOBALS['admin_base_path'] ?? '/sso-authen-3/admin/public';
    $adminName = $_SESSION['admin_name'] ?? 'Administrator';
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSO Admin Panel - Client Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo $basePath; ?>/css/admin-responsive.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Remove conflicting table-responsive styles */
        /* .table-responsive is now fully controlled by admin-responsive.css */
        
        .btn-group-sm>.btn,
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.85rem;
        }

        .status-badge {
            font-size: 0.75rem;
        }

        .client-card {
            transition: all 0.3s ease;
        }

        .client-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .secret-field {
            font-family: 'Courier New', monospace;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px;
            font-size: 12px;
        }

        .copy-btn {
            cursor: pointer;
        }

        .text-small {
            font-size: 0.9rem;
        }
        
        .admin-content {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <!-- Mobile Menu Toggle -->
            <button class="btn mobile-menu-toggle d-md-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
            
            <a class="navbar-brand" href="<?php echo $basePath; ?>">
                <i class="fas fa-shield-alt me-2"></i>SSO Admin Panel
            </a>
            
            <div class="navbar-nav ms-auto d-flex flex-row align-items-center">
                <span class="navbar-text me-2 me-md-3">
                    <i class="fas fa-user me-1"></i><span class="d-none d-sm-inline"><?php echo $adminName; ?></span>
                </span>
                <a class="nav-link" href="<?php echo $basePath; ?>/auth/logout">
                    <i class="fas fa-sign-out-alt"></i><span class="d-none d-sm-inline ms-1">Sign out</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar - Desktop -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath; ?>">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="<?php echo $basePath; ?>/clients">
                                <i class="fas fa-users me-2"></i>Client Applications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath; ?>/statistics">
                                <i class="fas fa-chart-bar me-2"></i>Usage Statistics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath; ?>/admin-users">
                                <i class="fas fa-user-shield me-2"></i>Admin Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath; ?>/backup-restore">
                                <i class="fas fa-database me-2"></i>Backup & Restore
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath; ?>/settings">
                                <i class="fas fa-cog me-2"></i>System Configuration
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath; ?>/api-docs.html" target="_blank">
                                <i class="fas fa-book me-2"></i>Documentation
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Sidebar - Mobile Offcanvas -->
            <div class="offcanvas offcanvas-start offcanvas-sidebar d-md-none" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
                <div class="offcanvas-header bg-primary text-white">
                    <h5 class="offcanvas-title" id="sidebarOffcanvasLabel">
                        <i class="fas fa-bars me-2"></i>Menu
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body p-0">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath; ?>">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="<?php echo $basePath; ?>/clients">
                                <i class="fas fa-users me-2"></i>Client Applications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath; ?>/statistics">
                                <i class="fas fa-chart-bar me-2"></i>Usage Statistics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath; ?>/admin-users">
                                <i class="fas fa-user-shield me-2"></i>Admin Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath; ?>/backup-restore">
                                <i class="fas fa-database me-2"></i>Backup & Restore
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath; ?>/settings">
                                <i class="fas fa-cog me-2"></i>System Configuration
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath; ?>/api-docs.html" target="_blank">
                                <i class="fas fa-book me-2"></i>Documentation
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 admin-content">
                <div class="d-flex justify-content-between flex-wrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 mb-2 mb-md-0">
                        <i class="fas fa-users me-2"></i>Client Management
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <!-- <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshClients()">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </button>
                        </div> -->
                        <button class="btn btn-primary rounded-0" onclick="showAddClientModal()">
                            <i class="fas fa-plus"></i><span class="d-none d-sm-inline ms-1">Add New Client</span>
                        </button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <!-- <div class="row mb-4" id="stats-cards">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Total Clients</h6>
                                        <h3 id="total-clients" class="mb-0">-</h3>
                                    </div>
                                    <i class="fas fa-users fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Active</h6>
                                        <h3 id="active-clients" class="mb-0">-</h3>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-warning text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Inactive</h6>
                                        <h3 id="inactive-clients" class="mb-0">-</h3>
                                    </div>
                                    <i class="fas fa-pause-circle fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-danger text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-0">Suspended</h6>
                                        <h3 id="suspended-clients" class="mb-0">-</h3>
                                    </div>
                                    <i class="fas fa-ban fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> -->

                <!-- Search and Filter -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="searchInput" class="form-label">Search Clients</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="searchInput" placeholder="Search by name, client ID, or redirect URI">
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <label for="statusFilter" class="form-label">Status</label>
                                <select class="form-select" id="statusFilter">
                                    <option value="">All</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label for="perPageSelect" class="form-label">Per Page</label>
                                <select class="form-select" id="perPageSelect">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Clients Table -->
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Client Applications
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="clients-table" class="table-responsive">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading clients data...</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <nav aria-label="Client pagination" id="pagination-container" style="display: none;">
                            <ul class="pagination justify-content-center" id="pagination">
                            </ul>
                        </nav>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit Client Modal -->
    <div class="modal fade" id="clientModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="clientForm">
                        <input type="hidden" id="clientId">
                        <div class="mb-3">
                            <label for="clientName" class="form-label">Client Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="clientName" required>
                            <div class="form-text">Display name for this client application</div>
                        </div>

                        <div class="mb-3">
                            <label for="clientDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="clientDescription" rows="3"></textarea>
                            <div class="form-text">Brief description of the client application (optional)</div>
                        </div>

                        <!-- Authentication Mode Selection -->
                        <div class="mb-3">
                            <label class="form-label">Authentication Mode</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card h-100 border-primary">
                                        <div class="card-body">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="authMode" value="jwt" id="authModeJWT" checked>
                                                <label class="form-check-label" for="authModeJWT">
                                                    <strong>JWT Mode</strong> <small class="text-muted">(Recommended)</small>
                                                </label>
                                            </div>
                                            <div class="mt-2">
                                                <ul class="small text-muted">
                                                    <li>Stateless authentication using JWT tokens</li>
                                                    <li>Works across subdomains and different servers</li>
                                                    <li>More secure and scalable</li>
                                                    <li>Requires User Handler Endpoint</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100 border-warning">
                                        <div class="card-body">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="authMode" value="legacy" id="authModeLegacy">
                                                <label class="form-check-label" for="authModeLegacy">
                                                    <strong>Legacy Mode</strong> <span class="badge bg-warning text-dark">DEPRECATED</span>
                                                </label>
                                            </div>
                                            <div class="mt-2">
                                                <ul class="small text-muted">
                                                    <li>Session-based authentication</li>
                                                    <li>Only works within the same domain</li>
                                                    <li>Limited to PHP applications</li>
                                                    <li>Requires local file path</li>
                                                </ul>
                                                <div class="alert alert-warning mt-2 p-2 small">
                                                    <i class="fas fa-exclamation-triangle"></i> <strong>Notice:</strong> Legacy Mode is being phased out due to subdomain limitations. Please use JWT Mode for new applications.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="clientStatus" class="form-label">Status</label>
                            <select class="form-select" id="clientStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="redirectUri" class="form-label">Redirect URI <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="redirectUri" required>
                            <div class="form-text">The URI where users will be redirected after authentication</div>
                        </div>
                        <div class="mb-3">
                            <label for="postLogoutUri" class="form-label">Post Logout Redirect URI <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="postLogoutUri" required>
                            <div class="form-text">The URI where users will be redirected after logout</div>
                        </div>
                        <div class="mb-3" id="userHandlerSection">
                            <label for="userHandlerEndpoint" class="form-label">User Handler Endpoint <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="userHandlerEndpoint" required>
                            <div class="form-text">API endpoint to handle user registration/update <strong>(required for JWT mode)</strong></div>
                        </div>
                        <div class="mb-3">
                            <label for="apiSecretKey" class="form-label">API Secret Key</label>
                            <div class="input-group">
                                <input type="text" class="form-control secret-field" id="apiSecretKey" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="generateApiSecretKey()" title="Generate New Key">
                                    <i class="fas fa-refresh"></i>
                                </button>
                                <button class="btn btn-outline-secondary copy-btn" type="button" onclick="copyToClipboard('apiSecretKey')" title="Copy Key">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <div class="form-text">Secret key for API communication (auto-generated)</div>
                        </div>
                        <div class="mb-3">
                            <label for="allowedScopes" class="form-label">Allowed Scopes</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input scope-checkbox" type="checkbox" value="openid" id="scope-openid" checked>
                                        <label class="form-check-label" for="scope-openid">
                                            <strong>openid</strong> <small class="text-muted">(Required)</small>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input scope-checkbox" type="checkbox" value="profile" id="scope-profile" checked>
                                        <label class="form-check-label" for="scope-profile">
                                            <strong>profile</strong> <small class="text-muted">(Name, Email)</small>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input scope-checkbox" type="checkbox" value="email" id="scope-email" checked>
                                        <label class="form-check-label" for="scope-email">
                                            <strong>email</strong> <small class="text-muted">(Email Address)</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input scope-checkbox" type="checkbox" value="phone" id="scope-phone">
                                        <label class="form-check-label" for="scope-phone">
                                            <strong>phone</strong> <small class="text-muted">(Phone Number)</small>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input scope-checkbox" type="checkbox" value="address" id="scope-address">
                                        <label class="form-check-label" for="scope-address">
                                            <strong>address</strong> <small class="text-muted">(Physical Address)</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" class="form-control" id="allowedScopes" value="openid,profile,email">
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Scopes define what user information your application can access. <strong>openid</strong> is always required.
                            </div>
                        </div>

                        <!-- Client ID Display (Edit Mode Only) -->
                        <div id="credentialsSection" style="display: none;">
                            <hr>
                            <h6>Client Information</h6>
                            <div class="mb-3">
                                <label for="displayClientId" class="form-label">Client ID</label>
                                <div class="input-group">
                                    <input type="text" class="form-control secret-field" id="displayClientId" readonly>
                                    <button class="btn btn-outline-secondary copy-btn" type="button" onclick="copyToClipboard('displayClientId')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <div class="form-text">This is the unique identifier for your client application</div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveClient()">
                        <i class="fas fa-save me-1"></i>Save Client
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Client Details Modal -->
    <div class="modal fade" id="viewClientModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Client Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="clientDetailsContent">
                    <!-- Client details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-purple" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $basePath; ?>/js/shared.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo $basePath; ?>/js/client-management.js?v=<?php echo time(); ?>"></script>
    <script>
        const basePath = '<?php echo $basePath; ?>';
        let currentPage = 1;
        let currentSearch = '';
        let currentStatus = '';
        let currentPerPage = 10;
        let isEditing = false;

        document.addEventListener('DOMContentLoaded', function() {
            loadClientStatistics();
            loadClients();
            setupEventListeners();
        });

        function setupEventListeners() {
            // Search input with debounce
            let searchTimeout;
            document.getElementById('searchInput').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentSearch = this.value;
                    currentPage = 1;
                    loadClients();
                }, 500);
            });

            // Status filter
            document.getElementById('statusFilter').addEventListener('change', function() {
                currentStatus = this.value;
                currentPage = 1;
                loadClients();
            });

            // Per page selector
            document.getElementById('perPageSelect').addEventListener('change', function() {
                currentPerPage = parseInt(this.value);
                currentPage = 1;
                loadClients();
            });

            // Form validation
            document.getElementById('clientForm').addEventListener('submit', function(e) {
                e.preventDefault();
                saveClient();
            });

            // Scope checkboxes
            document.querySelectorAll('.scope-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    // Ensure openid is always checked
                    if (this.value === 'openid' && !this.checked) {
                        this.checked = true;
                        Swal.fire('Info', 'openid scope is required and cannot be unchecked', 'info');
                    }
                    updateScopesInput();
                });
            });

            // Authentication mode change
            document.querySelectorAll('input[name="authMode"]').forEach(radio => {
                radio.addEventListener('change', handleAuthModeChange);
            });
        }
    </script>
</body>

</html>