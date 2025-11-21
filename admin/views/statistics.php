<?php
// Page configuration for responsive template
$pageTitle = 'Usage Statistics';
$currentPage = 'statistics';

// Get basePath from GLOBALS
$basePath = $GLOBALS['admin_base_path'] ?? '/sso-authen-3/admin/public';

$additionalStyles = '
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
';

$additionalScripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="' . $basePath . '/js/shared.js?v=' . time() . '"></script>
';

// Include responsive header
include __DIR__ . '/partials/header.php';
?>

<!-- Define basePath for JavaScript -->
<script>
    const basePath = "<?php echo $basePath; ?>";
</script>

<!-- Main Content -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-chart-bar me-2"></i>Usage Statistics
        <small class="text-muted fs-6" id="demo-indicator" style="display:none">
            <i class="fas fa-flask me-1"></i>Demo Data Active
        </small>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <select class="form-select" id="periodSelect" onchange="loadStatistics()">
                <option value="7">Last 7 days</option>
                <option value="30" selected>Last 30 days</option>
                <option value="90">Last 90 days</option>
            </select>
        </div>
        <!-- <button class="btn btn-sm btn-outline-secondary" id="usageRefreshBtn" onclick="loadStatistics()">
            <i class="fas fa-sync-alt me-1"></i>Refresh
        </button> -->
    </div>
</div>

<!-- System Statistics Overview -->
<div class="row mb-4" id="system-stats" style="display:none">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h5><i class="fas fa-globe me-2"></i>System Overview</h5>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading system statistics...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Client Statistics -->
<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h5 id="time-round"><i class="fas fa-users me-2"></i>Client Activity Summary</h5>
            </div>
            <div class="card-body" id="client-stats">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading clients statistics...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        loadStatistics();
    });

    function loadStatistics() {
        const days = document.getElementById("periodSelect").value;

        // Load system statistics
        fetch(`${basePath}/api/usage-statistics?days=${days}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderSystemStats(data.data);
                } else {
                    Swal.fire("Error", data.message, "error");
                }
            })
            .catch(error => {
                console.error("Error loading statistics:", error);
                Swal.fire("Error", "Failed to load statistics", "error");
            });
    }

    function renderSystemStats(stats) {
        const systemStatsDiv = document.getElementById("system-stats");
        const clientStatsDiv = document.getElementById("client-stats");
        const timeRound = document.getElementById("time-round");

        console.log("Statistics API Response:", stats); // Debug log

        // Render system overview
        timeRound.innerHTML = `<i class="fas fa-users me-2"></i>Client Activity Summary (${stats.period_days || 30} days)`;
        systemStatsDiv.style.display = 'block';
        systemStatsDiv.innerHTML = `
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header">
                        <h5><i class="fas fa-globe me-2"></i>System Overview (${stats.period_days || 30} days)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h3 class="text-primary">${stats.total_clients || 0}</h3>
                                    <p class="mb-0">Total Clients</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h3 class="text-success">${stats.total_requests || 0}</h3>
                                    <p class="mb-0">Total Requests</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h3 class="text-info">${stats.unique_users || 0}</h3>
                                    <p class="mb-0">Unique Users</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h3 class="text-warning">${stats.average_requests_per_day || 0}</h3>
                                    <p class="mb-0">Avg Requests/Day</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Render client statistics table
        let tableHtml = '<div class="table-responsive"><table class="table table-striped table-hover" id="client-stats-table">';
        tableHtml += '<thead><tr>';
        tableHtml += '<th>Client Name</th>';
        tableHtml += '<th>Status</th>';
        tableHtml += '<th>Total Activities</th>';
        tableHtml += '<th>Authentication Requests</th>';
        tableHtml += '<th>Unique Actions</th>';
        tableHtml += '<th>Active Admins</th>';
        tableHtml += '<th>Last Activity</th>';
        tableHtml += '<th>Actions</th>';
        tableHtml += '</tr></thead><tbody>';

        if (stats.client_stats && stats.client_stats.length > 0) {
            stats.client_stats.forEach(client => {
                const statusBadge = client.status === 'active' ?
                    '<span class="badge bg-success">' + client.status + '</span>' :
                    '<span class="badge bg-secondary">' + client.status + '</span>';

                const lastActivity = client.last_activity ?
                    new Date(client.last_activity).toLocaleDateString('th-TH') :
                    'No activity';

                tableHtml += '<tr>';
                tableHtml += `<td><strong>${client.client_name || 'N/A'}</strong><br><small class="text-muted">${client.client_id || ''}</small></td>`;
                tableHtml += `<td>${statusBadge}</td>`;
                tableHtml += `<td><span class="badge" style="background:#9B59B6">${client.total_activities || 0}</span></td>`;
                tableHtml += `<td><span class="badge" style="background:#F1C40F">${client.total_requests || 0}</span></td>`;
                tableHtml += `<td>${client.unique_actions || 0}</td>`;
                tableHtml += `<td>${client.unique_admins || 0}</td>`;
                tableHtml += `<td><small>${lastActivity}</small></td>`;
                tableHtml += `<td><button class="btn btn-sm btn-outline-primary" onclick="viewClientStats(${client.id})"><i class="fas fa-chart-line"></i> Details</button></td>`;
                tableHtml += '</tr>';
            });

            tableHtml += '</tbody></table></div>';
            clientStatsDiv.innerHTML = tableHtml;

            // Initialize DataTable with error handling
            try {
                if ($.fn.DataTable.isDataTable('#client-stats-table')) {
                    $('#client-stats-table').DataTable().destroy();
                }
                $('#client-stats-table').DataTable({
                    order: [
                        [2, 'desc']
                    ],
                    pageLength: 10,
                    language: {
                        emptyTable: "No client statistics available",
                        zeroRecords: "No matching records found"
                    }
                });
            } catch (error) {
                console.error("DataTable initialization error:", error);
            }
        } else {
            // Don't use DataTable for empty data - just show a simple message
            tableHtml += '<tr><td colspan="6" class="text-center py-4">No client statistics available for the selected period.</td></tr>';
            tableHtml += '</tbody></table></div>';
            clientStatsDiv.innerHTML = tableHtml;
        }
    }

    /**
     * View detailed statistics for a specific client
     */
    function viewClientStats(clientId) {
        // Load client details
        fetch(`${basePath}/api/clients/${clientId}/statistics?days=` + document.getElementById('periodSelect').value)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showClientStatsModal(data.data);
                } else {
                    Swal.fire('Error', data.message || 'Failed to load client statistics', 'error');
                }
            })
            .catch(error => {
                console.error('Error loading client statistics:', error);
                Swal.fire('Error', 'Failed to load client statistics', 'error');
            });
    }

    /**
     * Show client statistics modal
     */
    function showClientStatsModal(clientData) {
        const modalHtml = `
            <div class="modal fade" id="clientStatsModal" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-chart-line me-2"></i>Client Statistics: ${clientData.client.client_name}
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p><strong>Client ID:</strong> <code>${clientData.client.client_id}</code></p>
                                    <p><strong>Status:</strong> <span class="badge bg-${clientData.client.status === 'active' ? 'success' : 'secondary'}">${clientData.client.status}</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Period:</strong> Last ${clientData.period_days} days</p>
                                    <p><strong>Total Activities:</strong> <span class="badge bg-primary">${clientData.client.total_requests || 0}</span></p>
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Detailed statistics for ${clientData.client.client_name} over the selected period.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if any
        const existingModal = document.getElementById('clientStatsModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('clientStatsModal'));
        modal.show();

        // Clean up on close
        document.getElementById('clientStatsModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }
</script>

<?php
// Include responsive footer
include __DIR__ . '/partials/footer.php';
?>