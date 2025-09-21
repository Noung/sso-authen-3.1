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
            <div class="text-center py-4">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5>ไม่มีข้อมูล Client Applications</h5>
                <p class="text-muted">คลิก "Add New Client" เพื่อเพิ่ม client แรก</p>
            </div>
        `;
        return;
    }

    let html = `
        <table class="table table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Client Name</th>
                    <th>Client ID</th>
                    <th>Redirect URI</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    clients.forEach(client => {
        const createdDate = new Date(client.created_at).toLocaleDateString('th-TH');
        const statusBadge = getStatusBadge(client.status);
        const truncatedUri = client.app_redirect_uri.length > 50 
            ? client.app_redirect_uri.substring(0, 50) + '...' 
            : client.app_redirect_uri;
            
        html += `
            <tr>
                <td>
                    <strong>${escapeHtml(client.client_name)}</strong>
                    ${client.client_description ? '<br><small class="text-muted">' + escapeHtml(client.client_description) + '</small>' : ''}
                    <br><small class="text-info">${escapeHtml(client.allowed_scopes || 'openid,profile,email')}</small>
                </td>
                <td>
                    <code class="small">${escapeHtml(client.client_id)}</code>
                    <button class="btn btn-sm btn-outline-secondary copy-btn ms-1" 
                            onclick="copyToClipboardText('${escapeHtml(client.client_id)}')"
                            title="Copy Client ID">
                        <i class="fas fa-copy"></i>
                    </button>
                </td>
                <td>
                    <span class="small" title="${escapeHtml(client.app_redirect_uri)}">
                        ${escapeHtml(truncatedUri)}
                    </span>
                </td>
                <td>${statusBadge}</td>
                <td>${createdDate}</td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-info" onclick="viewClient(${client.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary" onclick="editClient(${client.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning" onclick="toggleClientStatus(${client.id}, '${client.status}')" title="Toggle Status">
                            <i class="fas fa-toggle-${client.status === 'active' ? 'on' : 'off'}"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteClient(${client.id}, '${escapeHtml(client.client_name)}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
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
    
    // Reset all scope checkboxes to default
    document.getElementById('scope-openid').checked = true;
    document.getElementById('scope-profile').checked = true;
    document.getElementById('scope-email').checked = true;
    document.getElementById('scope-phone').checked = false;
    document.getElementById('scope-address').checked = false;
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
                
                // Set authentication mode
                const authMode = client.user_handler_endpoint ? 'jwt' : 'legacy';
                document.getElementById('authModeJWT').checked = (authMode === 'jwt');
                document.getElementById('authModeLegacy').checked = (authMode === 'legacy');
                
                // Handle auth mode first to show/hide fields
                handleAuthModeChange();
                
                // Set API Secret Key based on mode AFTER handleAuthModeChange
                if (authMode === 'jwt') {
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
    
    const formData = {
        client_name: document.getElementById('clientName').value.trim(),
        client_description: document.getElementById('clientDescription').value.trim(),
        app_redirect_uri: document.getElementById('redirectUri').value.trim(),
        post_logout_redirect_uri: document.getElementById('postLogoutUri').value.trim(),
        user_handler_endpoint: document.getElementById('userHandlerEndpoint').value.trim(),
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
    
    // Optional URL validations
    if (formData.user_handler_endpoint && formData.user_handler_endpoint !== '') {
        if (isJWTMode) {
            // JWT Mode requires valid URL
            try {
                new URL(formData.user_handler_endpoint);
            } catch (e) {
                Swal.fire('Validation Error', 'Please enter a valid user handler endpoint URL for JWT mode', 'warning');
                return;
            }
        }
        // Legacy Mode: file path validation (no URL validation needed)
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
                // Show success message for new client (no more secret display)
                Swal.fire({
                    title: 'Client Created Successfully!',
                    html: `
                        <div class="text-start">
                            <p class="mb-3">Your new client application has been created successfully.</p>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Client ID:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control secret-field" value="${data.data.client_id}" readonly id="newClientId">
                                    <button class="btn btn-outline-secondary" onclick="copyToClipboard('newClientId')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Use this Client ID to integrate with your application.
                            </div>
                        </div>
                    `,
                    icon: 'success',
                    width: '600px',
                    showCloseButton: true,
                    confirmButtonText: 'Got it!',
                    confirmButtonColor: '#198754'
                });
            } else {
                Swal.fire('Success', data.message, 'success');
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
    fetch(`${basePath}/api/clients/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const client = data.data;
                const content = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Basic Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Client ID:</strong></td><td><code>${escapeHtml(client.client_id)}</code> <button class="btn btn-sm btn-outline-secondary" onclick="copyToClipboardText('${escapeHtml(client.client_id)}')" title="Copy Client ID"><i class="fas fa-copy"></i></button></td></tr>
                                <tr><td><strong>Client Name:</strong></td><td>${escapeHtml(client.client_name)}</td></tr>
                                <tr><td><strong>Description:</strong></td><td>${escapeHtml(client.client_description || 'No description')}</td></tr>
                                <tr><td><strong>Status:</strong></td><td>${getStatusBadge(client.status)}</td></tr>
                                <tr><td><strong>Created:</strong></td><td>${new Date(client.created_at).toLocaleString('th-TH')} by ${client.created_by || 'N/A'}</td></tr>
                                <tr><td><strong>Updated:</strong></td><td>${new Date(client.updated_at).toLocaleString('th-TH')} by ${client.updated_by || 'N/A'}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Configuration</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Redirect URI:</strong></td><td class="text-break">${escapeHtml(client.app_redirect_uri)}</td></tr>
                                <tr><td><strong>Post Logout URI:</strong></td><td class="text-break">${escapeHtml(client.post_logout_redirect_uri || 'Not set')}</td></tr>
                                <tr><td><strong>User Handler:</strong></td><td class="text-break">${escapeHtml(client.user_handler_endpoint || 'Not set')}</td></tr>
                                ${client.user_handler_endpoint ? '<tr><td><strong>API Secret Key:</strong></td><td>' + (client.api_secret_key ? '<code>' + client.api_secret_key.substring(0, 20) + '...</code> <button class="btn btn-sm btn-outline-secondary" onclick="copyToClipboardText(\'' + escapeHtml(client.api_secret_key) + '\')" title="Copy API Secret"><i class="fas fa-copy"></i></button>' : 'Not set') + '</td></tr>' : ''}
                                <tr><td><strong>Allowed Scopes:</strong></td><td>${escapeHtml(client.allowed_scopes || 'openid,profile,email')}</td></tr>
                                <tr><td><strong>Auth Mode:</strong></td><td>${client.user_handler_endpoint ? '<span class="badge bg-primary">JWT Mode</span>' : '<span class="badge bg-secondary">Legacy Mode</span><br><small class="text-warning">⚠️ Same domain required</small>'}</td></tr>
                            </table>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6>System Configuration</h6>
                            <div class="alert alert-info">
                                <strong>JWT Secret Key (Global):</strong> 
                                <code id="jwtSecretDisplay">••••••••••••••••••••</code>
                                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="toggleJwtSecret()" id="jwtToggleBtn">
                                    <i class="fas fa-eye" id="jwtToggleIcon"></i> <span id="jwtToggleText">Show</span>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary ms-1" onclick="copyJwtSecret()" id="jwtCopyBtn">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                                <br><small class="text-muted">This key is used by all applications to verify JWT tokens</small>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('clientDetailsContent').innerHTML = content;
                new bootstrap.Modal(document.getElementById('viewClientModal')).show();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error loading client:', error);
            Swal.fire('Error', 'Failed to load client details', 'error');
        });
}

function toggleJwtSecret() {
    const display = document.getElementById('jwtSecretDisplay');
    const icon = document.getElementById('jwtToggleIcon');
    const toggleText = document.getElementById('jwtToggleText');
    const copyBtn = document.getElementById('jwtCopyBtn');
    
    if (display.textContent.includes('••••')) {
        // Show the secret - you'll need to get this from config
        fetch(`${basePath}/api/system/jwt-secret`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    display.textContent = data.data.jwt_secret;
                    icon.className = 'fas fa-eye-slash';
                    toggleText.textContent = 'Hide';
                    copyBtn.setAttribute('data-secret', data.data.jwt_secret);
                } else {
                    Swal.fire('Error', 'Unable to retrieve JWT secret', 'error');
                }
            })
            .catch(error => {
                console.error('Error loading JWT secret:', error);
                // Fallback display
                display.textContent = 'YOUR_SUPER_SECRET_KEY_FOR_JWT_GENERATION';
                icon.className = 'fas fa-eye-slash';
                toggleText.textContent = 'Hide';
                copyBtn.setAttribute('data-secret', 'YOUR_SUPER_SECRET_KEY_FOR_JWT_GENERATION');
            });
    } else {
        // Hide the secret
        display.textContent = '••••••••••••••••••••';
        icon.className = 'fas fa-eye';
        toggleText.textContent = 'Show';
        // Copy button remains visible and functional with stored secret
    }
}

function deleteClient(id, clientName) {
    Swal.fire({
        title: 'Delete Client?',
        html: `Are you sure you want to delete <strong>${escapeHtml(clientName)}</strong>?<br><br>
               <span class="text-danger">This action cannot be undone.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${basePath}/api/clients/${id}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Deleted!', data.message, 'success');
                    loadClients();
                    loadClientStatistics();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error deleting client:', error);
                Swal.fire('Error', 'Failed to delete client', 'error');
            });
        }
    });
}

function toggleClientStatus(id, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const action = newStatus === 'active' ? 'activate' : 'deactivate';
    
    Swal.fire({
        title: `${action.charAt(0).toUpperCase() + action.slice(1)} Client?`,
        text: `Are you sure you want to ${action} this client?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: newStatus === 'active' ? '#198754' : '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Yes, ${action}!`,
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${basePath}/api/clients/${id}/toggle-status`, {
                method: 'PATCH'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Updated!', data.message, 'success');
                    loadClients();
                    loadClientStatistics();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error toggling client status:', error);
                Swal.fire('Error', 'Failed to update client status', 'error');
            });
        }
    });
}

function refreshClients() {
    loadClients();
    loadClientStatistics();
    Swal.fire({
        title: 'Refreshed!',
        text: 'Data has been updated',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
    });
}

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
        showCopySuccess();
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
                    <p><strong>Known Limitations:</strong></p>
                    <ul class="text-muted">
                        <li>Session cookies don't work across subdomains</li>
                        <li>Complex file path configuration required</li>
                        <li>Same-server deployment constraint</li>
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

// Copy JWT Secret
function copyJwtSecret() {
    const copyBtn = document.getElementById('jwtCopyBtn');
    const display = document.getElementById('jwtSecretDisplay');
    let secret = copyBtn.getAttribute('data-secret');
    
    // If secret is not cached, fetch it first
    if (!secret) {
        fetch(`${basePath}/api/system/jwt-secret`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    secret = data.data.jwt_secret;
                    copyBtn.setAttribute('data-secret', secret);
                    copyToClipboardText(secret);
                } else {
                    Swal.fire('Error', 'Unable to retrieve JWT secret for copying', 'error');
                }
            })
            .catch(error => {
                console.error('Error loading JWT secret:', error);
                // Fallback
                secret = 'YOUR_SUPER_SECRET_KEY_FOR_JWT_GENERATION';
                copyBtn.setAttribute('data-secret', secret);
                copyToClipboardText(secret);
            });
    } else {
        copyToClipboardText(secret);
    }
}

// Helper function to copy text to clipboard