<!DOCTYPE html>
<html lang="th">

<head>
    <?php
    $basePath = $GLOBALS['admin_base_path'] ?? '/sso-authen-3/admin/public';
    $adminName = $_SESSION['admin_name'] ?? 'Administrator';
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSO-Authen Admin Panel - Admin Users Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="<?php echo $basePath; ?>/css/admin-responsive.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .btn-group-sm>.btn,
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.85rem;
        }

        .status-badge {
            font-size: 0.75rem;
        }

        .admin-card {
            transition: all 0.3s ease;
        }

        .admin-card:hover {
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
                <i class="fas fa-shield-alt me-2"></i>SSO-Authen Admin Panel
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
                            <a class="nav-link" href="<?php echo $basePath; ?>/clients">
                                <i class="fas fa-users me-2"></i>Client Applications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath; ?>/statistics">
                                <i class="fas fa-chart-bar me-2"></i>Usage Statistics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="<?php echo $basePath; ?>/admin-users">
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
                            <a class="nav-link" href="<?php echo $basePath; ?>/api-docs-v3.html" target="_blank">
                                <i class="fas fa-book me-2"></i>Documentation
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Offcanvas Sidebar - Mobile -->
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
                            <a class="nav-link" href="<?php echo $basePath; ?>/clients">
                                <i class="fas fa-users me-2"></i>Client Applications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $basePath; ?>/statistics">
                                <i class="fas fa-chart-bar me-2"></i>Usage Statistics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="<?php echo $basePath; ?>/admin-users">
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
                            <a class="nav-link" href="<?php echo $basePath; ?>/api-docs-v3.html" target="_blank">
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
                        <i class="fas fa-user-shield me-2"></i>Admin Users
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-primary rounded-0" onclick="showAddAdminUserModal()">
                            <i class="fas fa-plus"></i><span class="d-none d-sm-inline ms-1">Add New Admin</span>
                        </button>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="searchInput" class="form-label">Search Admin Users</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="searchInput" placeholder="Search by name or email">
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <label for="statusFilter" class="form-label">Status</label>
                                <select class="form-select" id="statusFilter">
                                    <option value="">All</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
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

                <!-- Admin Users Table -->
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Admin Users
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="admin-users-table" class="table-responsive">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading users data...</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <nav aria-label="Admin users pagination" id="pagination-container" style="display: none;">
                            <ul class="pagination justify-content-center" id="pagination">
                            </ul>
                        </nav>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit Admin User Modal -->
    <div class="modal fade" id="adminUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Admin User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="adminUserForm">
                        <input type="hidden" id="adminUserId">
                        <div class="mb-3">
                            <label for="adminUserEmail" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="adminUserEmail" required>
                            <div class="form-text">Email address of the admin user</div>
                        </div>

                        <div class="mb-3">
                            <label for="adminUserName" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="adminUserName" required>
                            <div class="form-text">Full name of the admin user</div>
                        </div>

                        <div class="mb-3">
                            <label for="adminUserRole" class="form-label">Role</label>
                            <select class="form-select" id="adminUserRole">
                                <option value="super_admin">Super Admin</option>
                                <option value="admin">Admin</option>
                                <option value="user">User</option>
                                <option value="viewer">Viewer</option>
                            </select>
                            <div class="form-text">Role determines the level of access for this admin user</div>
                        </div>

                        <div class="mb-3">
                            <label for="adminUserStatus" class="form-label">Status</label>
                            <select class="form-select" id="adminUserStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveAdminUser()">
                        <i class="fas fa-save me-1"></i>Save Admin User
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Admin User Details Modal -->
    <div class="modal fade" id="viewAdminUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Admin User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="adminUserDetailsContent">
                    <!-- Admin user details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-purple" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $basePath; ?>/js/shared.js?v=<?php echo time(); ?>"></script>
    <script>
        const basePath = '<?php echo $basePath; ?>';
        let currentPage = 1;
        let currentSearch = '';
        let currentStatus = '';
        let currentPerPage = 10;
        let isEditing = false;

        document.addEventListener('DOMContentLoaded', function() {
            loadAdminUsers();
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
                    loadAdminUsers();
                }, 500);
            });

            // Status filter
            document.getElementById('statusFilter').addEventListener('change', function() {
                currentStatus = this.value;
                currentPage = 1;
                loadAdminUsers();
            });

            // Per page selector
            document.getElementById('perPageSelect').addEventListener('change', function() {
                currentPerPage = parseInt(this.value);
                currentPage = 1;
                loadAdminUsers();
            });

            // Form validation
            document.getElementById('adminUserForm').addEventListener('submit', function(e) {
                e.preventDefault();
                saveAdminUser();
            });
        }

        function loadAdminUsers() {
            const params = new URLSearchParams({
                page: currentPage,
                per_page: currentPerPage,
                search: currentSearch,
                status: currentStatus
            });

            fetch(`${basePath}/api/admin-users?${params}`, {
                credentials: 'same-origin'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderAdminUsersTable(data.data.data);
                        renderPagination(data.data.pagination);
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading admin users:', error);
                    Swal.fire('Error', 'Failed to load admin users', 'error');
                });
        }

        function renderAdminUsersTable(users) {
            const container = document.getElementById('admin-users-table');
            
            if (users.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-user-shield fa-3x text-muted mb-3"></i>
                        <h5>No Admin Users Data</h5>
                        <p class="text-muted">Click "Add New Admin User" to add the first admin user.</p>
                        <button class="btn btn-primary mt-2" onclick="showAddAdminUserModal()">
                            <i class="fas fa-plus me-1"></i>Add New Admin User
                        </button>
                    </div>
                `;
                return;
            }

            let html = `
                <div class="table-responsive">
                    <table class="table table-hover" id="adminUsersTable">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 30%">Name</th>
                                <th style="width: 30%">Email</th>
                                <th style="width: 15%">Role</th>
                                <th style="width: 10%">Status</th>
                                <th style="width: 15%">Created</th>
                                <th style="width: 10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            users.forEach(user => {
                const createdDate = new Date(user.created_at).toLocaleDateString('th-TH');
                const statusBadge = getStatusBadge(user.status);
                const roleBadge = getRoleBadge(user.role);
                
                html += `
                    <tr>
                        <td><strong>${escapeHtml(user.name)}</strong></td>
                        <td><code>${escapeHtml(user.email)}</code></td>
                        <td class="text-center">${roleBadge}</td>
                        <td class="text-center">${statusBadge}</td>
                        <td class="text-center">
                            <span title="${new Date(user.created_at).toLocaleString('th-TH')}">
                                ${createdDate}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-outline-info" onclick="viewAdminUser(${user.id})" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-primary" onclick="editAdminUser(${user.id})" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-${user.status === 'active' ? 'secondary' : 'success'}" onclick="toggleAdminUserStatus(${user.id}, '${user.status}')" title="${user.status === 'active' ? 'Deactivate' : 'Activate'}">
                                    <i class="fas fa-toggle-${user.status === 'active' ? 'on' : 'off'}"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table></div>';
            container.innerHTML = html;
            
            // Initialize DataTable
            if (typeof DataTable !== 'undefined' && document.getElementById('adminUsersTable')) {
                new DataTable('#adminUsersTable', {
                    "order": [[4, "desc"]],
                    "pageLength": 10,
                    "responsive": true,
                    "columnDefs": [
                        { "orderable": false, "targets": [5] }
                    ]
                });
            }
        }

        function getStatusBadge(status) {
            const badges = {
                'active': '<span class="badge bg-success status-badge">Active</span>',
                'inactive': '<span class="badge bg-secondary status-badge">Inactive</span>'
            };
            return badges[status] || '<span class="badge bg-secondary status-badge">Unknown</span>';
        }

        function getRoleBadge(role) {
            const badges = {
                'super_admin': '<span class="badge bg-danger">Super Admin</span>',
                'admin': '<span class="badge bg-primary">Admin</span>',
                'user': '<span class="badge bg-warning">User</span>',
                'viewer': '<span class="badge bg-info">Viewer</span>'
            };
            return badges[role] || '<span class="badge bg-secondary">Unknown</span>';
        }

        function renderPagination(pagination) {
            const container = document.getElementById('pagination-container');
            const paginationEl = document.getElementById('pagination');
            
            if (pagination.total_pages <= 1) {
                container.style.display = 'none';
                return;
            }
            
            container.style.display = 'block';
            let html = '';
            
            // Previous button
            html += `
                <li class="page-item ${!pagination.has_prev ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${pagination.current_page - 1})">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
            `;
            
            // Page numbers
            const startPage = Math.max(1, pagination.current_page - 2);
            const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                html += `
                    <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                    </li>
                `;
            }
            
            // Next button
            html += `
                <li class="page-item ${!pagination.has_next ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${pagination.current_page + 1})">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            `;
            
            paginationEl.innerHTML = html;
        }

        function changePage(page) {
            currentPage = page;
            loadAdminUsers();
        }

        function showAddAdminUserModal() {
            isEditing = false;
            document.getElementById('modalTitle').textContent = 'Add New Admin User';
            document.getElementById('adminUserForm').reset();
            document.getElementById('adminUserId').value = '';
            document.getElementById('adminUserEmail').disabled = false;
            new bootstrap.Modal(document.getElementById('adminUserModal')).show();
        }

        function editAdminUser(id) {
            isEditing = true;
            document.getElementById('modalTitle').textContent = 'Edit Admin User';
            document.getElementById('adminUserEmail').disabled = true;
            
            fetch(`${basePath}/api/admin-users/${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.data;
                        document.getElementById('adminUserId').value = user.id;
                        document.getElementById('adminUserEmail').value = user.email;
                        document.getElementById('adminUserName').value = user.name;
                        document.getElementById('adminUserRole').value = user.role;
                        document.getElementById('adminUserStatus').value = user.status;
                        new bootstrap.Modal(document.getElementById('adminUserModal')).show();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading admin user:', error);
                    Swal.fire('Error', 'Failed to load admin user details', 'error');
                });
        }

        function saveAdminUser() {
            const formData = {
                email: document.getElementById('adminUserEmail').value.trim(),
                name: document.getElementById('adminUserName').value.trim(),
                role: document.getElementById('adminUserRole').value,
                status: document.getElementById('adminUserStatus').value
            };

            // Validation
            if (!formData.email || !formData.name) {
                Swal.fire('Validation Error', 'Email and name are required', 'warning');
                return;
            }

            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(formData.email)) {
                Swal.fire('Validation Error', 'Please enter a valid email address', 'warning');
                return;
            }

            const adminUserId = document.getElementById('adminUserId').value;
            const url = isEditing ? `${basePath}/api/admin-users/${adminUserId}` : `${basePath}/api/admin-users`;
            const method = isEditing ? 'PUT' : 'POST';
            
            // Show loading
            Swal.fire({
                title: isEditing ? 'Updating Admin User...' : 'Creating Admin User...',
                text: 'Please wait',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.close();
                    bootstrap.Modal.getInstance(document.getElementById('adminUserModal')).hide();
                    
                    Swal.fire({
                        title: '<i class="fas fa-check-circle text-success me-2"></i>Success!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#198754'
                    });
                    
                    loadAdminUsers();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error saving admin user:', error);
                Swal.fire('Error', 'Failed to save admin user', 'error');
            });
        }

        function viewAdminUser(id) {
            fetch(`${basePath}/api/admin-users/${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.data;
                        const createdDate = new Date(user.created_at);
                        const lastLoginDate = user.last_login_at ? new Date(user.last_login_at) : null;
                        
                        const content = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h5><i class="fas fa-user me-2"></i>User Information</h5>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Name:</strong></td>
                                            <td>${escapeHtml(user.name)}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td><code>${escapeHtml(user.email)}</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Role:</strong></td>
                                            <td>${getRoleBadge(user.role)}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>${getStatusBadge(user.status)}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h5><i class="fas fa-calendar me-2"></i>Timestamps</h5>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Created:</strong></td>
                                            <td>${createdDate.toLocaleString('th-TH')}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Last Login:</strong></td>
                                            <td>${lastLoginDate ? lastLoginDate.toLocaleString('th-TH') : 'Never'}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <!--<div class="mt-3">
                                <button class="btn btn-primary" onclick="editAdminUser(${user.id})">
                                    <i class="fas fa-edit me-1"></i>Edit User
                                </button>
                                <button class="btn btn-${user.status === 'active' ? 'secondary' : 'success'}" onclick="toggleAdminUserStatus(${user.id}, '${user.status}')">
                                    <i class="fas fa-toggle-${user.status === 'active' ? 'on' : 'off'} me-1"></i>${user.status === 'active' ? 'Deactivate' : 'Activate'}
                                </button>
                            </div>-->
                        `;
                        
                        document.getElementById('adminUserDetailsContent').innerHTML = content;
                        new bootstrap.Modal(document.getElementById('viewAdminUserModal')).show();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading admin user:', error);
                    Swal.fire('Error', 'Failed to load admin user details', 'error');
                });
        }

        function toggleAdminUserStatus(id, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const action = currentStatus === 'active' ? 'deactivate' : 'activate';
            const actionText = newStatus === 'active' ? 'Activate' : 'Deactivate';
            const actionIcon = newStatus === 'active' ? 'fa-toggle-on' : 'fa-toggle-off';
            const actionColor = newStatus === 'active' ? '#198754' : '#ffc107';
            
            Swal.fire({
                title: `${actionText} Admin User?`,
                text: `Are you sure you want to ${action} this admin user?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: actionColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: `<i class="fas ${actionIcon} me-1"></i>Yes, ${action}!`,
                cancelButtonText: '<i class="fas fa-times me-1"></i>Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`${basePath}/api/admin-users/${id}/toggle-status`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '<i class="fas fa-check-circle text-success me-2"></i>Update!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonColor: '#198754'
                            });
                            loadAdminUsers();
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error toggling admin user status:', error);
                        Swal.fire('Error', 'Failed to toggle admin user status', 'error');
                    });
                }
            });
        }

        // Utility function to escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    </script>
</body>
</html>