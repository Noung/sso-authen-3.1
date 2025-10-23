// Client Management JavaScript - Compatible with PHP 7.4.33
// Full CRUD operations with modern UI

function loadClientStatistics() {
    fetch(`${basePath}/api/clients/statistics`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatisticsCards(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading statistics:', error);
        });
}

function updateStatisticsCards(stats) {
    document.getElementById('total-clients').textContent = stats.total || 0;
    document.getElementById('active-clients').textContent = stats.active || 0;
    document.getElementById('inactive-clients').textContent = stats.inactive || 0;
    document.getElementById('suspended-clients').textContent = stats.suspended || 0;
}

function loadClients() {
    const params = new URLSearchParams({
        page: currentPage,
        per_page: currentPerPage,
        search: currentSearch,
        status: currentStatus
    });

    fetch(`${basePath}/api/clients?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderClientsTable(data.data.data);
                renderPagination(data.data.pagination);
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error loading clients:', error);
            Swal.fire('Error', 'Failed to load clients', 'error');
        });
}

function renderClientsTable(clients) {
    const container = document.getElementById('clients-table');
    
    if (clients.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5>No Client Applications Data</h5>
                <p class="text-muted">Click "Add New Client" to add the first client.</p>
                <button class="btn btn-primary mt-2" onclick="showAddClientModal()">
                    <i class="fas fa-plus me-1"></i>Add New Client
                </button>
            </div>
        `;
        return;
    }

    let html = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 15%">Client Name</th>
                        <th style="width: 15%">Client ID</th>
                        <th style="width: 15%" class="hide-mobile">Redirect URI</th>
                        <th style="width: 10%">Authen Mode</th>
                        <th style="width: 10%">Status</th>
                        <th style="width: 10%" class="hide-mobile">Created</th>
                        <th style="width: 15%" class="hide-mobile">Created By</th>
                        <th style="width: 10%">Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    clients.forEach(client => {
        const createdDate = new Date(client.created_at).toLocaleDateString('th-TH');
        const statusBadge = getStatusBadge(client.status);
        const truncatedUri = client.app_redirect_uri.length > 30 
            ? client.app_redirect_uri.substring(0, 30) + '...' 
            : client.app_redirect_uri;
            
        // Determine authentication mode based on the format of user_handler_endpoint
        let authMode = 'Unknown';
        let authModeBadge = '<span class="badge bg-secondary" title="Unknown Mode">Unknown</span>';
        
        if (client.user_handler_endpoint && client.user_handler_endpoint.trim() !== '') {
            if (client.user_handler_endpoint.startsWith('http')) {
                // JWT Mode: URL starting with http
                authMode = 'JWT';
                authModeBadge = '<span class="badge bg-primary" title="JWT Mode"><i class="fas fa-key me-1"></i>JWT</span>';
            } else if (client.user_handler_endpoint.startsWith('/')) {
                // Legacy Mode: File path starting with /
                authMode = 'Legacy';
                authModeBadge = '<span class="badge bg-warning" title="Legacy Mode"><i class="fas fa-server me-1"></i>Legacy</span>';
            } else {
                // Unknown mode
                authModeBadge = '<span class="badge bg-dark" title="Unknown Mode">Unknown</span>';
            }
        } else {
            // No user handler endpoint
            authModeBadge = '<span class="badge bg-dark" title="No Handler">None</span>';
        }
            
        html += `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="me-2">
                            <i class="fas fa-${client.user_handler_endpoint ? (client.user_handler_endpoint.startsWith('http') ? 'key' : 'server') : 'question'} fa-lg text-${client.user_handler_endpoint ? (client.user_handler_endpoint.startsWith('http') ? 'primary' : 'secondary') : 'muted'}"></i>
                        </div>
                        <div>
                            <strong>${escapeHtml(client.client_name)}</strong>
                            ${client.client_description ? '<br><small class="text-muted">' + escapeHtml(client.client_description) + '</small>' : ''}
                        </div>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <code class="small me-1">${escapeHtml(client.client_id)}</code>
                        <button class="btn btn-sm btn-outline-secondary copy-btn" 
                                onclick="copyToClipboardText('${escapeHtml(client.client_id)}')"
                                title="Copy Client ID">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </td>
                <td class="hide-mobile">
                    <span class="small" title="${escapeHtml(client.app_redirect_uri)}">
                        ${escapeHtml(truncatedUri)}
                    </span>
                </td>
                <td class="text-center">
                    ${authModeBadge}
                </td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-center hide-mobile">
                    <span title="${new Date(client.created_at).toLocaleString('th-TH')}">
                        ${createdDate}
                    </span>
                </td>
                <td class="text-center hide-mobile">
                    <span title="${escapeHtml(client.created_by || 'N/A')}">
                        ${client.created_by ? escapeHtml(client.created_by.substring(0, 15) + (client.created_by.length > 15 ? '...' : '')) : 'N/A'}
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
                        <button class="btn btn-outline-${client.status === 'active' ? 'secondary' : 'success'}" onclick="toggleClientStatus(${client.id}, '${client.status}')" title="${client.status === 'active' ? 'Deactivate' : 'Activate'}">
                            <i class="fas fa-toggle-${client.status === 'active' ? 'on' : 'off'}"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteClient(${client.id}, '${escapeHtml(client.client_name)}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function getStatusBadge(status) {
    const badges = {
        'active': '<span class="badge bg-success status-badge">Active</span>',
        'inactive': '<span class="badge bg-secondary status-badge">Inactive</span>',
        'suspended': '<span class="badge bg-danger status-badge">Suspended</span>'
    };
    return badges[status] || '<span class="badge bg-secondary status-badge">Unknown</span>';
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
    
    if (startPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(1)">1</a></li>`;
        if (startPage > 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        html += `
            <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
            </li>
        `;
    }
    
    if (endPage < pagination.total_pages) {
        if (endPage < pagination.total_pages - 1) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        html += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${pagination.total_pages})">${pagination.total_pages}</a></li>`;
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
    loadClients();
}

function showAddClientModal() {
    isEditing = false;
    document.getElementById('modalTitle').textContent = 'Add New Client';
    document.getElementById('clientForm').reset();
    document.getElementById('clientId').value = '';
    document.getElementById('credentialsSection').style.display = 'none';
    
    // Set default auth mode to JWT
    document.getElementById('authModeJWT').checked = true;
    document.getElementById('authModeLegacy').checked = false;
    
    // Handle auth mode first
    handleAuthModeChange();
    
    // Generate API Secret Key for new clients after auth mode is set
    generateApiSecretKey();
    
    // Reset all scope checkboxes to default (all fixed to openid, profile, email)
    document.getElementById('scope-openid').checked = true;
    document.getElementById('scope-profile').checked = true;
    document.getElementById('scope-email').checked = true;
    updateScopesInput();
    
    new bootstrap.Modal(document.getElementById('clientModal')).show();
}

function editClient(id) {
    isEditing = true;
    document.getElementById('modalTitle').textContent = 'Edit Client';
    
    fetch(`${basePath}/api/clients/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const client = data.data;
                document.getElementById('clientId').value = client.id;
                document.getElementById('clientName').value = client.client_name;
                document.getElementById('clientDescription').value = client.client_description || '';
                document.getElementById('redirectUri').value = client.app_redirect_uri;
                document.getElementById('postLogoutUri').value = client.post_logout_redirect_uri || '';
                document.getElementById('userHandlerEndpoint').value = client.user_handler_endpoint || '';
                document.getElementById('apiSecretKey').value = client.api_secret_key || '';
                
                // Set authentication mode - check if client has user_handler_endpoint to determine mode
                // JWT Mode: URL starting with http
                // Legacy Mode: File path starting with /
                let isJWTMode = false;
                if (client.user_handler_endpoint && client.user_handler_endpoint.trim() !== '') {
                    if (client.user_handler_endpoint.startsWith('http')) {
                        isJWTMode = true;
                    } else if (client.user_handler_endpoint.startsWith('/')) {
                        isJWTMode = false;
                    }
                }
                
                document.getElementById('authModeJWT').checked = isJWTMode;
                document.getElementById('authModeLegacy').checked = !isJWTMode;
                
                // Handle auth mode first to show/hide fields
                handleAuthModeChange();
                
                // Set API Secret Key based on mode AFTER handleAuthModeChange
                if (isJWTMode) {
                    document.getElementById('apiSecretKey').value = client.api_secret_key || '';
                    document.getElementById('apiSecretKey').disabled = false;
                } else {
                    // Legacy mode - field should be hidden by handleAuthModeChange
                    document.getElementById('apiSecretKey').value = '';
                    document.getElementById('apiSecretKey').disabled = true;
                }
                
                // Set scopes checkboxes
                const scopes = (client.allowed_scopes || 'openid,profile,email').split(',');
                document.querySelectorAll('.scope-checkbox').forEach(checkbox => {
                    checkbox.checked = scopes.includes(checkbox.value);
                });
                updateScopesInput();
                
                document.getElementById('clientStatus').value = client.status;
                
                // Show credentials section
                document.getElementById('credentialsSection').style.display = 'block';
                document.getElementById('displayClientId').value = client.client_id;
                
                new bootstrap.Modal(document.getElementById('clientModal')).show();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error loading client:', error);
            Swal.fire('Error', 'Failed to load client details', 'error');
        });
}

function saveClient() {
    const isJWTMode = document.getElementById('authModeJWT').checked;
    const userHandlerEndpoint = document.getElementById('userHandlerEndpoint').value.trim();
    
    const formData = {
        client_name: document.getElementById('clientName').value.trim(),
        client_description: document.getElementById('clientDescription').value.trim(),
        app_redirect_uri: document.getElementById('redirectUri').value.trim(),
        post_logout_redirect_uri: document.getElementById('postLogoutUri').value.trim(),
        user_handler_endpoint: userHandlerEndpoint,
        api_secret_key: isJWTMode ? document.getElementById('apiSecretKey').value.trim() : '', // Empty for Legacy Mode
        allowed_scopes: document.getElementById('allowedScopes').value.trim(),
        status: document.getElementById('clientStatus').value
    };
    
    // For Legacy Mode, user_handler_endpoint is now required (no more null fallback)
    
    // Basic validation
    if (!formData.client_name || !formData.app_redirect_uri || !formData.post_logout_redirect_uri) {
        Swal.fire('Validation Error', 'Please fill in all required fields', 'warning');
        return;
    }
    
    // Check authentication mode requirements
    if (isJWTMode) {
        if (!formData.user_handler_endpoint) {
            Swal.fire('Validation Error', 'User Handler Endpoint is required for JWT mode', 'warning');
            return;
        }
        if (!formData.api_secret_key) {
            Swal.fire('Validation Error', 'API Secret Key is required for JWT mode', 'warning');
            return;
        }
    } else {
        // Legacy Mode: user_handler_endpoint is REQUIRED to avoid path conflicts
        if (!formData.user_handler_endpoint) {
            Swal.fire('Validation Error', 'User Handler File Path is required for Legacy mode to specify exact file location', 'warning');
            return;
        }
    }
    
    // URL validation for required redirect URI
    try {
        new URL(formData.app_redirect_uri);
    } catch (e) {
        Swal.fire('Validation Error', 'Please enter a valid redirect URI', 'warning');
        return;
    }
    
    // URL validation for required post logout redirect URI
    try {
        new URL(formData.post_logout_redirect_uri);
    } catch (e) {
        Swal.fire('Validation Error', 'Please enter a valid post logout redirect URI', 'warning');
        return;
    }
    
    // Validation based on authentication mode
    if (formData.user_handler_endpoint && formData.user_handler_endpoint !== '') {
        if (isJWTMode) {
            // JWT Mode requires valid URL starting with http
            if (!formData.user_handler_endpoint.startsWith('http')) {
                Swal.fire('Validation Error', 'User Handler Endpoint for JWT mode must be a valid URL starting with http:// or https://', 'warning');
                return;
            }
            try {
                new URL(formData.user_handler_endpoint);
            } catch (e) {
                Swal.fire('Validation Error', 'Please enter a valid user handler endpoint URL for JWT mode', 'warning');
                return;
            }
        } else {
            // Legacy Mode requires file path starting with /
            if (!formData.user_handler_endpoint.startsWith('/')) {
                Swal.fire('Validation Error', 'User Handler File Path for Legacy mode must be a valid file path starting with /', 'warning');
                return;
            }
        }
    }
    
    const clientId = document.getElementById('clientId').value;
    const url = isEditing ? `${basePath}/api/clients/${clientId}` : `${basePath}/api/clients`;
    const method = isEditing ? 'PUT' : 'POST';
    
    // Show loading
    Swal.fire({
        title: isEditing ? 'Updating Client...' : 'Creating Client...',
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
            bootstrap.Modal.getInstance(document.getElementById('clientModal')).hide();
            
            if (!isEditing) {
                // Determine if this is JWT mode (has user handler endpoint)
                const isJWTMode = data.data.user_handler_endpoint && data.data.user_handler_endpoint.trim() !== '' && data.data.user_handler_endpoint.startsWith('http');
                
                // Show success message for new client with all credentials
                Swal.fire({
                    title: '<i class="fas fa-check-circle text-success me-2"></i>Client Created Successfully!',
                    html: `
                        <div class="text-start">
                            <div class="alert alert-success">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Success!</strong> Your new client application has been created successfully.
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-key me-2"></i>Credentials</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Client ID:</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control secret-field" value="${data.data.client_id}" readonly id="newClientId">
                                            <button class="btn btn-outline-secondary copy-btn" data-target="newClientId" title="Copy Client ID">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                        <div class="form-text text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Use this ID to identify your application when making authentication requests.
                                        </div>
                                    </div>
                                    
                                    ${data.data.api_secret_key ? `
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">API Secret Key:</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control secret-field" value="${data.data.api_secret_key}" readonly id="newApiSecret">
                                            <button class="btn btn-outline-secondary toggle-btn" data-target="newApiSecret" title="Show/Hide API Secret">
                                                <i class="fas fa-eye" id="toggleApiSecretIcon"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary copy-btn" data-target="newApiSecret" title="Copy API Secret">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                        <div class="form-text text-muted">
                                            <i class="fas fa-exclamation-triangle me-1 text-warning"></i>
                                            <strong>Security Note:</strong> Keep this secret key secure and never expose it in client-side code.
                                        </div>
                                    </div>` : ''}
                                    
                                    ${isJWTMode ? `
                                    <div class="mb-0">
                                        <label class="form-label fw-bold">JWT Secret:</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control secret-field" value="Loading JWT secret..." readonly id="newJwtSecret">
                                            <button class="btn btn-outline-secondary toggle-btn" data-target="newJwtSecret" title="Show/Hide JWT Secret">
                                                <i class="fas fa-eye" id="toggleJwtSecretIcon"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary copy-btn" data-target="newJwtSecret" title="Copy JWT Secret">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                        <div class="form-text text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            This is the shared JWT secret used to verify tokens. Same for all clients.
                                        </div>
                                    </div>` : ''}
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-lightbulb me-2"></i>
                                <strong>Next Steps:</strong> 
                                <ul class="mb-0 mt-2">
                                    <li>Copy and securely store your credentials</li>
                                    <li>Configure your application with these credentials</li>
                                    <li>Test the authentication flow</li>
                                </ul>
                            </div>
                        </div>
                    `,
                    icon: 'success',
                    width: '700px',
                    showCloseButton: true,
                    confirmButtonText: '<i class="fas fa-check me-1"></i>Got it!',
                    confirmButtonColor: '#198754',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false
                }).then((result) => {
                    // This runs after the modal is closed
                });
                
                // Use setTimeout to ensure the DOM is ready
                setTimeout(() => {
                    // Add event listeners for copy buttons
                    document.querySelectorAll('.copy-btn').forEach(button => {
                        button.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            const targetId = this.getAttribute('data-target');
                            const targetElement = document.getElementById(targetId);
                            if (targetElement) {
                                const text = targetElement.value;
                                // Copy to clipboard using the existing function with custom toast notification
                                copyToClipboardText(text);
                                
                                // Show feedback on the button itself as well
                                const originalHTML = this.innerHTML;
                                this.innerHTML = '<i class="fas fa-check text-success"></i>';
                                setTimeout(() => {
                                    this.innerHTML = originalHTML;
                                }, 2000);
                            }
                        });
                    });
                    
                    // Add event listeners for toggle buttons
                    document.querySelectorAll('.toggle-btn').forEach(button => {
                        button.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            const targetId = this.getAttribute('data-target');
                            const targetElement = document.getElementById(targetId);
                            if (targetElement) {
                                const isPassword = targetElement.type === 'password';
                                targetElement.type = isPassword ? 'text' : 'password';
                                const icon = this.querySelector('i');
                                if (icon) {
                                    icon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
                                }
                            }
                        });
                    });
                    
                    // Fetch and display the actual JWT secret if this is JWT mode
                    if (isJWTMode) {
                        fetch(`${basePath}/api/jwt-secret`)
                            .then(response => response.json())
                            .then(data => {
                                const jwtSecretField = document.getElementById('newJwtSecret');
                                if (jwtSecretField) {
                                    if (data.success) {
                                        jwtSecretField.value = data.jwt_secret;
                                    } else {
                                        jwtSecretField.value = 'Error loading JWT secret';
                                    }
                                }
                            })
                            .catch(error => {
                                console.error('Error fetching JWT secret:', error);
                                const jwtSecretField = document.getElementById('newJwtSecret');
                                if (jwtSecretField) {
                                    jwtSecretField.value = 'Error loading JWT secret';
                                }
                            });
                    }
                }, 100);
            } else {
                Swal.fire({
                    title: '<i class="fas fa-check-circle text-success me-2"></i>Success!',
                    text: data.message,
                    icon: 'success',
                    confirmButtonColor: '#198754'
                });
            }
            
            loadClients();
            loadClientStatistics();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error saving client:', error);
        Swal.fire('Error', 'Failed to save client', 'error');
    });
}

function viewClient(id) {
    console.log('Starting viewClient for ID:', id);
    
    // First, try to fetch just the client details
    fetch(`${basePath}/api/clients/${id}`)
        .then(response => {
            console.log('Client API Response Status:', response.status);
            // Check if response is OK (2xx status)
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            // Check content type
            const contentType = response.headers.get('content-type');
            console.log('Content-Type:', contentType);
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Received non-JSON response');
            }
            return response.json();
        })
        .then(clientData => {
            console.log('Client API Data:', clientData);
            
            if (clientData.success) {
                // Log the client description specifically
                console.log('Client Description:', clientData.data.client_description);
                
                // Now try to fetch JWT secret
                return fetch(`${basePath}/api/jwt-secret`)
                    .then(response => {
                        console.log('JWT API Response Status:', response.status);
                        // Check if response is OK (2xx status)
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        // Check content type
                        const contentType = response.headers.get('content-type');
                        console.log('JWT Content-Type:', contentType);
                        if (!contentType || !contentType.includes('application/json')) {
                            throw new Error('Received non-JSON response from JWT API');
                        }
                        return response.json();
                    })
                    .then(jwtData => {
                        console.log('JWT API Data:', jwtData);
                        
                        if (jwtData.success) {
                            displayClientDetails(clientData.data, jwtData.jwt_secret);
                        } else {
                            console.error('JWT API failed:', jwtData.message);
                            // Show client details without JWT secret
                            displayClientDetails(clientData.data, 'Error loading JWT secret');
                        }
                    })
                    .catch(jwtError => {
                        console.error('Error fetching JWT secret:', jwtError);
                        // Show client details without JWT secret
                        displayClientDetails(clientData.data, 'Error loading JWT secret');
                    });
            } else {
                console.error('Client API failed:', clientData.message);
                Swal.fire('Error', clientData.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error in viewClient:', error);
            // Check if it's an authentication error
            if (error.message.includes('401') || error.message.includes('403')) {
                Swal.fire('Authentication Error', 'Your session may have expired. Please refresh the page and try again.', 'error');
            } else {
                Swal.fire('Error', 'Failed to load client details: ' + error.message, 'error');
            }
        });
}

function displayClientDetails(client, jwtSecret) {
    console.log('Displaying client details:', client);
    console.log('Client description value:', client.client_description);
    console.log('Client description type:', typeof client.client_description);
    
    // Format dates
    const createdDate = new Date(client.created_at);
    const updatedDate = new Date(client.updated_at);
    
    // Format scopes
    const scopes = client.allowed_scopes ? client.allowed_scopes.split(',').map(scope => 
        `<span class="badge bg-info me-1">${scope.trim()}</span>`
    ).join('') : '';
    
    // Determine authentication mode based on the format of user_handler_endpoint
    let authMode = 'Unknown';
    let authModeBadge = '<span class="badge bg-secondary">Unknown</span>';
    
    if (client.user_handler_endpoint && client.user_handler_endpoint.trim() !== '') {
        if (client.user_handler_endpoint.startsWith('http')) {
            // JWT Mode: URL starting with http
            authMode = 'JWT';
            authModeBadge = '<span class="badge bg-primary">JWT Mode</span>';
        } else if (client.user_handler_endpoint.startsWith('/')) {
            // Legacy Mode: File path starting with /
            authMode = 'Legacy';
            authModeBadge = '<span class="badge bg-warning">Legacy Mode</span><br><small class="text-warning">⚠️ Same domain required</small>';
        } else {
            // Unknown mode
            authModeBadge = '<span class="badge bg-dark">Unknown Mode</span>';
        }
    } else {
        // No user handler endpoint
        authModeBadge = '<span class="badge bg-dark">No Handler</span>';
    }
    
    // Get total requests from client object or default to 0
    const totalRequests = client.total_requests || 0;
    
    const content = `
        <div class="row">
            <div class="col-md-6">
                <h5><i class="fas fa-info-circle me-2 text-primary"></i>Basic Information</h5>
                <table class="table table-borderless table-sm">
                    <tr><td class="text-muted" style="width: 30%">Client ID:</td><td><code>${escapeHtml(client.client_id)}</code> <button class="btn btn-sm btn-outline-secondary" onclick="copyToClipboardText('${escapeHtml(client.client_id)}')" title="Copy Client ID"><i class="fas fa-copy"></i></button></td></tr>
                    <tr><td class="text-muted">Client Name:</td><td><strong>${escapeHtml(client.client_name)}</strong></td></tr>
                    <tr><td class="text-muted">Description:</td><td>${escapeHtml(client.client_description || 'No description')}</td></tr>
                    <tr><td class="text-muted">Status:</td><td>${getStatusBadge(client.status)}</td></tr>
                    <!--<tr><td class="text-muted">Total Requests:</td><td><span class="badge bg-info">${totalRequests}</span></td></tr>-->
                    <tr><td class="text-muted">Created:</td><td>${createdDate.toLocaleString('th-TH')}<br><small class="text-muted">by ${client.created_by || 'N/A'}</small></td></tr>
                    <tr><td class="text-muted">Updated:</td><td>${updatedDate.toLocaleString('th-TH')}<br><small class="text-muted">by ${client.updated_by || 'N/A'}</small></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5><i class="fas fa-cog me-2 text-primary"></i>Configuration</h5>
                <table class="table table-borderless table-sm">
                    <tr><td class="text-muted" style="width: 30%">Redirect URI:</td><td class="text-break">${escapeHtml(client.app_redirect_uri)}</td></tr>
                    <tr><td class="text-muted">Post Logout URI:</td><td class="text-break">${escapeHtml(client.post_logout_redirect_uri || 'Not set')}</td></tr>
                    <tr><td class="text-muted">User Handler:</td><td class="text-break">${escapeHtml(client.user_handler_endpoint || 'Not set')}</td></tr>
                    ${client.user_handler_endpoint ? '<tr><td class="text-muted">API Secret Key:</td><td>' + (client.api_secret_key ? '<code>' + client.api_secret_key.substring(0, 20) + '...</code> <button class="btn btn-sm btn-outline-secondary" onclick="copyToClipboardText(\'' + escapeHtml(client.api_secret_key) + '\')" title="Copy API Secret"><i class="fas fa-copy"></i></button>' : 'Not set') + '</td></tr>' : ''}
                    <tr><td class="text-muted">Allowed Scopes:</td><td>${scopes}</td></tr>
                    <tr><td class="text-muted">Auth Mode:</td><td>${authModeBadge}</td></tr>
                </table>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-12">
                <h5><i class="fas fa-key me-2 text-warning"></i>System Configuration</h5>
                <div class="alert alert-warning">
                    <strong>JWT Secret Key:</strong> 
                    <div class="input-group mt-2">
                        <input type="password" class="form-control" id="jwtSecretField" value="${escapeHtml(jwtSecret)}" readonly>
                        <button class="btn btn-outline-secondary" onclick="toggleJwtSecret(${client.id})" id="toggleJwtBtn" title="Show/Hide JWT Secret">
                            <i class="fas fa-eye" id="toggleJwtIcon"></i> Show
                        </button>
                        <button class="btn btn-outline-secondary" onclick="copyToClipboardText('${escapeHtml(jwtSecret)}')"
                                title="Copy JWT Secret">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>For Developers:</strong> Use this secret to verify JWT tokens in your server-side application. Never expose it in client-side code.
                    </small>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('clientDetailsContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('viewClientModal')).show();
}

function deleteClient(id, clientName) {
    Swal.fire({
        title: '<i class="fas fa-exclamation-triangle text-danger me-2"></i>Delete Client?',
        html: `
            <div class="text-center">
                <p>Are you sure you want to delete this client?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone. All associated data will be permanently removed.
                </div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash me-1"></i>Yes, delete it!',
        cancelButtonText: '<i class="fas fa-times me-1"></i>Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${basePath}/api/clients/${id}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '<i class="fas fa-check-circle text-success me-2"></i>Deleted!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#198754'
                    });
                    loadClients();
                    loadClientStatistics();
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message,
                        icon: 'error',
                        confirmButtonColor: '#198754'
                    });
                }
            })
            .catch(error => {
                console.error('Error deleting client:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to delete client',
                    icon: 'error',
                    confirmButtonColor: '#198754'
                });
            });
        }
    });
}

function toggleClientStatus(id, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const action = newStatus === 'active' ? 'activate' : 'deactivate';
    const actionText = newStatus === 'active' ? 'Activate' : 'Deactivate';
    const actionIcon = newStatus === 'active' ? 'fa-toggle-on' : 'fa-toggle-off';
    const actionColor = newStatus === 'active' ? '#198754' : '#ffc107';
    
    Swal.fire({
        title: `${actionText} Client?`,
        html: `
            <div class="text-center">
                <p>Are you sure you want to ${action} this client?</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: actionColor,
        cancelButtonColor: '#6c757d',
        confirmButtonText: `<i class="fas ${actionIcon} me-1"></i>Yes, ${action}!`,
        cancelButtonText: '<i class="fas fa-times me-1"></i>Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${basePath}/api/clients/${id}/toggle-status`, {
                method: 'PATCH'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '<i class="fas fa-check-circle text-success me-2"></i>Updated!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#198754'
                    });
                    loadClients();
                    loadClientStatistics();
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message,
                        icon: 'error',
                        confirmButtonColor: '#198754'
                    });
                }
            })
            .catch(error => {
                console.error('Error toggling client status:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to update client status',
                    icon: 'error',
                    confirmButtonColor: '#198754'
                });
            });
        }
    });
}

function refreshClients() {
    // Show loading indicator
    const refreshBtn = document.querySelector('button[onclick="refreshClients()"]');
    const originalHTML = refreshBtn.innerHTML;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Refreshing...';
    refreshBtn.disabled = true;
    
    loadClients();
    loadClientStatistics();
    
    // Show success message using toast notification for consistency across the application
    showCustomCopySuccess('Data has been updated successfully');
    
    // Restore button after a short delay
    setTimeout(() => {
        refreshBtn.innerHTML = originalHTML;
        refreshBtn.disabled = false;
    }, 1500);
}

function copyToClipboardText(text) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showCustomCopySuccess();
        }).catch(err => {
            fallbackCopyTextToClipboard(text);
        });
    } else {
        fallbackCopyTextToClipboard(text);
    }
}

function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element ? element.value : elementId; // Fallback to direct text
    copyToClipboardText(text);
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
        document.execCommand('copy');
        showCustomCopySuccess();
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
        Swal.fire('Copy Failed', 'Unable to copy to clipboard', 'error');
    }
    
    document.body.removeChild(textArea);
}

function showCopySuccess() {
    Swal.fire({
        title: 'Copied!',
        text: 'Text copied to clipboard',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}

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

// Custom copy success notification that doesn't interfere with existing modals
// JWT Secret show/hide toggle function
function toggleJwtSecret(clientId) {
    const field = document.getElementById('jwtSecretField');
    const btn = document.getElementById('toggleJwtBtn');
    const icon = document.getElementById('toggleJwtIcon');
    
    if (field.type === 'password') {
        // Show the secret and log the action
        field.type = 'text';
        icon.className = 'fas fa-eye-slash';
        btn.innerHTML = '<i class="fas fa-eye-slash" id="toggleJwtIcon"></i> Hide';
        btn.title = 'Hide JWT Secret';
        
        // Log JWT secret view to audit log
        logJwtSecretView(clientId);
    } else {
        // Hide the secret
        field.type = 'password';
        icon.className = 'fas fa-eye';
        btn.innerHTML = '<i class="fas fa-eye" id="toggleJwtIcon"></i> Show';
        btn.title = 'Show JWT Secret';
    }
}

// Log JWT secret view to audit log
function logJwtSecretView(clientId) {
    fetch(`${basePath}/api/log-jwt-view`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            client_id: clientId
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('JWT view logged:', data);
        if (!data.success) {
            console.error('Failed to log JWT view:', data.message);
        }
    })
    .catch(error => {
        console.error('Error logging JWT view:', error);
    });
}

// Toggle secret visibility (show/hide)
function toggleSecretVisibility(elementId) {
    const field = document.getElementById(elementId);
    if (field) {
        if (field.type === 'password') {
            field.type = 'text';
            // Update icon in the closest button
            const button = field.closest('.input-group').querySelector('button[title*="Show/Hide"]');
            if (button) {
                const icon = button.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-eye-slash';
                }
            }
        } else {
            field.type = 'password';
            // Update icon in the closest button
            const button = field.closest('.input-group').querySelector('button[title*="Show/Hide"]');
            if (button) {
                const icon = button.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-eye';
                }
            }
        }
    }
}

// Show JWT Secret
function showJwtSecret() {
    // Log JWT secret access
    logJwtSecretView(null);
    
    fetch(`${basePath}/api/jwt-secret`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'JWT Secret',
                    html: `
                        <div class="text-start">
                            <div class="mb-3">
                                <label class="form-label">JWT Secret Key:</label>
                                <div class="input-group">
                                    <input type="password" class="form-control secret-field" value="${data.jwt_secret}" readonly id="jwtSecretDisplay">
                                    <button class="btn btn-outline-secondary" onclick="toggleSecretVisibility('jwtSecretDisplay')" title="Show/Hide JWT Secret">
                                        <i class="fas fa-eye" id="toggleJwtSecretIcon"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary" onclick="copyToClipboardText('${data.jwt_secret}')" title="Copy JWT Secret">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Security Notice:</strong> Keep this secret secure and never expose it in client-side code.
                            </div>
                        </div>
                    `,
                    icon: 'info',
                    width: '600px',
                    confirmButtonText: 'Close',
                    confirmButtonColor: '#198754'
                });
            } else {
                Swal.fire('Error', 'Failed to retrieve JWT secret', 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching JWT secret:', error);
            Swal.fire('Error', 'Failed to retrieve JWT secret', 'error');
        });
}

// Utility functions for new features
function updateScopesInput() {
    const checkboxes = document.querySelectorAll('.scope-checkbox:checked');
    const scopes = Array.from(checkboxes).map(cb => cb.value).join(',');
    document.getElementById('allowedScopes').value = scopes;
}

// Generate API Secret Key
function generateApiSecretKey() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    for (let i = 0; i < 64; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('apiSecretKey').value = result;
}

// Handle Authentication Mode Change
function handleAuthModeChange() {
    const isJWTMode = document.getElementById('authModeJWT').checked;
    const userHandlerSection = document.getElementById('userHandlerSection');
    const userHandlerInput = document.getElementById('userHandlerEndpoint');
    const apiSecretSection = document.getElementById('apiSecretKey').closest('.mb-3'); // Get parent div
    const apiSecretInput = document.getElementById('apiSecretKey');
    const generateBtn = document.querySelector('button[onclick="generateApiSecretKey()"]');
    
    if (isJWTMode) {
        // JWT Mode
        userHandlerSection.style.display = 'block';
        userHandlerInput.required = true;
        userHandlerSection.querySelector('label').innerHTML = 'User Handler Endpoint <span class="text-danger">*</span>';
        userHandlerSection.querySelector('.form-text').innerHTML = 'API endpoint to handle user registration/update <strong>(required for JWT mode)</strong><br><small class="text-info">Example: http://my-app.com/api/sso-handler</small>';
        
        // Show and enable API Secret Key
        apiSecretSection.style.display = 'block';
        apiSecretInput.disabled = false;
        if (generateBtn) generateBtn.disabled = false;
        
        // Auto-generate API key for JWT mode
        if (!apiSecretInput.value || apiSecretInput.value === '') {
            generateApiSecretKey();
        }
    } else {
        // Legacy Mode
        userHandlerSection.style.display = 'block';
        userHandlerInput.required = true; // REQUIRED for Legacy Mode to specify exact file path
        userHandlerSection.querySelector('label').innerHTML = 'User Handler File Path <span class="text-danger">*</span>';
        userHandlerSection.querySelector('.form-text').innerHTML = 'Local file path to user_handler.php within the same server <strong>(required for Legacy mode)</strong><br><small class="text-warning">⚠️ Restriction: PHP app must be in the same domain as this SSO Gateway</small><br><small class="text-info">Example: /my-app/api/user_handler.php</small><br><small class="text-danger">🚨 <strong>Important:</strong> Each app must specify its own user_handler.php path to avoid conflicts</small>';
        
        // Hide API Secret Key section completely for Legacy Mode
        apiSecretSection.style.display = 'none';
        apiSecretInput.disabled = true;
        apiSecretInput.value = '';
        if (generateBtn) generateBtn.disabled = true;
        
        // Show deprecation warning
        Swal.fire({
            title: 'Legacy Mode Selected',
            html: `
                <div class="text-start">
                    <p><i class="fas fa-exclamation-triangle text-warning"></i> <strong>Legacy Mode is being deprecated</strong></p>
                    <hr>
                    <p><strong>Benefits of JWT Mode:</strong></p>
                    <ul class="text-muted">
                        <li>Works across subdomains and different servers</li>
                        <li>More secure and scalable</li>
                        <li>No domain restrictions</li>
                        <li>Better for modern web applications</li>
                    </ul>
                    <p><strong class="text-primary">Recommendation:</strong> Use JWT Mode for better compatibility and security.</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Continue with Legacy Mode',
            cancelButtonText: 'Switch to JWT Mode',
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#198754'
        }).then((result) => {
            if (!result.isConfirmed) {
                // Switch back to JWT Mode
                document.getElementById('authModeJWT').checked = true;
                document.getElementById('authModeLegacy').checked = false;
                handleAuthModeChange();
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    loadClientStatistics();
    loadClients();
    setupEventListeners();
});

function setupEventListeners() {
    // Search input with debounce
    let searchTimeout;
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentSearch = this.value;
            currentPage = 1;
            loadClients();
        }, 300); // Reduced debounce time for better responsiveness
    });
    
    // Add Enter key support for search
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            clearTimeout(searchTimeout);
            currentSearch = this.value;
            currentPage = 1;
            loadClients();
        }
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

    // Scope checkboxes - Since scopes are now fixed, no need for complex validation
    document.querySelectorAll('.scope-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Prevent any changes - scopes are fixed to openid, profile, email
            this.checked = ['openid', 'profile', 'email'].includes(this.value);
            updateScopesInput();
        });
    });

    // Authentication mode change
    document.querySelectorAll('input[name="authMode"]').forEach(radio => {
        radio.addEventListener('change', handleAuthModeChange);
    });
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl + N to add new client
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            showAddClientModal();
        }
        
        // ESC to close modals
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.modal.show');
            modals.forEach(modal => {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            });
        }
    });
}

// Toggle secret visibility (show/hide)
function toggleSecretVisibility(elementId) {
    const field = document.getElementById(elementId);
    if (field) {
        if (field.type === 'password') {
            field.type = 'text';
            // Update icon in the closest button
            const button = field.closest('.input-group').querySelector('button[title*="Show/Hide"]');
            if (button) {
                const icon = button.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-eye-slash';
                }
            }
        } else {
            field.type = 'password';
            // Update icon in the closest button
            const button = field.closest('.input-group').querySelector('button[title*="Show/Hide"]');
            if (button) {
                const icon = button.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-eye';
                }
            }
        }
    }
}

// Helper function to copy text to clipboard
function copyToClipboardText(text) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showCustomCopySuccess();
        }).catch(err => {
            fallbackCopyTextToClipboard(text);
        });
    } else {
        fallbackCopyTextToClipboard(text);
    }
}
