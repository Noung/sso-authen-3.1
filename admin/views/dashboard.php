<?php
// Page configuration for responsive template
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

// Get basePath from GLOBALS
$basePath = $GLOBALS['admin_base_path'] ?? '/sso-authen-3/admin/public';

$additionalStyles = '
<style>
    .bg-vibrant-blue {
        background-color: #3498DB !important;
    }
    .bg-vibrant-green {
        background-color: #2ECC71 !important;
    }
    .bg-vibrant-orange {
        background-color: #E67E22 !important;
    }
    .bg-vibrant-purple {
        background-color: #9B59B6 !important;
    }
    .bg-vibrant-red {
        background-color: #E74C3C !important;
    }
    .bg-vibrant-yellow {
        background-color: #F1C40F !important;
    }
    .bg-vibrant-teal {
        background-color: #1ABC9C !important;
    }
    .bg-vibrant-dark-gray {
        background-color: #34495E !important;
    }
</style>
';
$additionalScripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="refresh-btn" onclick="refreshStats()">
                <i class="fas fa-sync-alt me-1"></i>Refresh
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary" id="auto-refresh-btn" onclick="toggleAutoRefresh()">
                <i class="fas fa-pause-circle me-1"></i>Stop Auto Refresh
            </button>
        </div>
    </div>
</div>

<!-- Client Statistics Cards -->
<div class="row mb-4" id="client-stats-cards">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-vibrant-blue text-white shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Clients</h6>
                        <h3 id="total-clients">-</h3>
                    </div>
                    <i class="fas fa-users fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-vibrant-green text-white shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Active Clients</h6>
                        <h3 id="active-clients">-</h3>
                    </div>
                    <i class="fas fa-check-circle fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-vibrant-dark-gray text-white shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Inactive Clients</h6>
                        <h3 id="inactive-clients">-</h3>
                    </div>
                    <i class="fas fa-pause-circle fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-vibrant-red text-white shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Suspended Clients</h6>
                        <h3 id="suspended-clients">-</h3>
                    </div>
                    <i class="fas fa-ban fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Statistics Cards -->
<div class="row mb-4" id="system-stats-cards">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-vibrant-orange text-white shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Requests Today</h6>
                        <h3 id="requests-today">-</h3>
                    </div>
                    <i class="fas fa-chart-line fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-vibrant-yellow text-white shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Authentication Requests</h6>
                        <h3 id="total-requests">-</h3>
                    </div>
                    <i class="fas fa-chart-bar fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-vibrant-teal text-white shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Success Rate</h6>
                        <h3 id="success-rate">-</h3>
                    </div>
                    <i class="fas fa-percentage fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-vibrant-purple text-white shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Activities</h6>
                        <h3 id="recent-activities-count">-</h3>
                    </div>
                    <i class="fas fa-history fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts and Additional Information -->
<div class="row mb-4">
    <!-- Top Client Activities Chart -->
    <div class="col-lg-6 mb-3">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Top Client Activities (30 days)
                </h5>
            </div>
            <div class="card-body">
                <canvas id="topClientActivitiesChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- System Usage Trend Chart -->
    <div class="col-lg-6 mb-3">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>System Usage Trend (7 days)
                </h5>
            </div>
            <div class="card-body">
                <canvas id="systemUsageTrendChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities -->
<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>Recent Activities
                </h5>
            </div>
            <div class="card-body">
                <div id="recent-activities">
                    <p class="text-center">Loading...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-refresh interval (in milliseconds)
    let autoRefreshInterval = null;
    const refreshInterval = 30000; // 30 seconds

    // Chart instances
    let topClientActivitiesChart = null;
    let systemUsageTrendChart = null;

    document.addEventListener("DOMContentLoaded", function() {
        loadDashboardData();
        startAutoRefresh();
    });

    function startAutoRefresh() {
        // Clear any existing interval
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }

        // Set up automatic refresh
        autoRefreshInterval = setInterval(function() {
            loadDashboardData();
        }, refreshInterval);

        console.log("Auto-refresh started - updating every " + (refreshInterval / 1000) + " seconds");
        updateAutoRefreshButton(true);
    }

    function stopAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
            console.log("Auto-refresh stopped");
        }
        updateAutoRefreshButton(false);
    }

    function updateAutoRefreshButton(isActive) {
        const button = document.getElementById("auto-refresh-btn");
        if (button) {
            if (isActive) {
                button.innerHTML = '<i class="fas fa-pause-circle me-1"></i>Stop Auto Refresh';
                button.className = "btn btn-sm btn-outline-primary";
            } else {
                button.innerHTML = '<i class="fas fa-play-circle me-1"></i>Start Auto Refresh';
                button.className = "btn btn-sm btn-outline-success";
            }
        }
    }

    function loadDashboardData() {
        loadStats();
        loadRecentActivities();
    }

    function loadStats() {
        fetch(basePath + "/api/dashboard/stats")
            .then(response => response.json())
            .then(data => {
                console.log("Dashboard API Response:", data); // Debug log
                if (data.success) {
                    updateStatsCards(data.data);
                    updateCharts(data.data);
                } else {
                    console.error("Dashboard API Error:", data.message);
                    Swal.fire("Error", data.message, "error");
                }
            })
            .catch(error => {
                console.error("Error loading stats:", error);
                Swal.fire("Error", "Failed to load dashboard statistics", "error");
            });
    }

    function loadRecentActivities() {
        fetch(basePath + "/api/dashboard/recent-activities?limit=10")
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderRecentActivities(data.data);
                } else {
                    document.getElementById("recent-activities").innerHTML =
                        '<p class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</p>';
                }
            })
            .catch(error => {
                console.error("Error loading activities:", error);
                document.getElementById("recent-activities").innerHTML =
                    '<p class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</p>';
            });
    }

    function updateStatsCards(stats) {
        // Use default values if data is missing
        document.getElementById("total-clients").textContent = stats.total_clients || 0;
        document.getElementById("active-clients").textContent = stats.active_clients || 0;
        document.getElementById("inactive-clients").textContent = stats.inactive_clients || 0;
        document.getElementById("suspended-clients").textContent = stats.suspended_clients || 0;
        document.getElementById("requests-today").textContent = stats.total_requests_today || 0;
        document.getElementById("total-requests").textContent = stats.total_requests || 0;
        document.getElementById("success-rate").textContent = (stats.success_rate || 0) + "%";
        document.getElementById("recent-activities-count").textContent = stats.recent_activities_count || 0;
    }

    function updateCharts(stats) {
        // Update Top Client Activities Chart
        if (topClientActivitiesChart) {
            topClientActivitiesChart.destroy();
        }

        const topClientCtx = document.getElementById("topClientActivitiesChart").getContext("2d");
        topClientActivitiesChart = new Chart(topClientCtx, {
            type: "bar",
            data: {
                labels: stats.top_client_activities.map(item => item.client_name),
                datasets: [{
                    label: "Activities",
                    data: stats.top_client_activities.map(item => item.activity_count),
                    backgroundColor: "rgba(54, 162, 235, 0.6)",
                    borderColor: "rgba(54, 162, 235, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // Update System Usage Trend Chart
        if (systemUsageTrendChart) {
            systemUsageTrendChart.destroy();
        }

        const trendCtx = document.getElementById("systemUsageTrendChart").getContext("2d");
        systemUsageTrendChart = new Chart(trendCtx, {
            type: "line",
            data: {
                labels: stats.system_usage_trend.map(item => item.date),
                datasets: [{
                    label: "Activities",
                    data: stats.system_usage_trend.map(item => item.count),
                    fill: false,
                    borderColor: "rgb(75, 192, 192)",
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    function renderRecentActivities(activities) {
        const container = document.getElementById("recent-activities");

        if (activities.length === 0) {
            container.innerHTML = '<p class="text-center text-muted">ไม่มีกิจกรรมล่าสุด</p>';
            return;
        }

        let html = '<div class="list-group list-group-flush">';
        activities.forEach(activity => {
            const date = new Date(activity.created_at).toLocaleString("th-TH");
            html += '<div class="list-group-item">' +
                '<div class="d-flex w-100 justify-content-between">' +
                '<h6 class="mb-1">' + activity.description + '</h6>' +
                '<small>' + date + '</small>' +
                '</div>' +
                '<small class="text-muted">Action: ' + activity.action + '</small>' +
                '</div>';
        });
        html += '</div>';

        container.innerHTML = html;
    }

    function refreshStats() {
        // Show loading indicator
        const refreshBtn = document.getElementById("refresh-btn");
        let originalHTML = "";
        if (refreshBtn) {
            originalHTML = refreshBtn.innerHTML;
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Refreshing...';
            refreshBtn.disabled = true;
        }

        loadDashboardData();

        // Show success message
        showCustomToast("Data has been updated successfully", "success");

        // Restore button after a short delay
        if (refreshBtn) {
            setTimeout(() => {
                refreshBtn.innerHTML = originalHTML;
                refreshBtn.disabled = false;
            }, 1500);
        }
    }

    // Toggle auto-refresh
    function toggleAutoRefresh() {
        if (autoRefreshInterval) {
            stopAutoRefresh();
        } else {
            startAutoRefresh();
        }
    }
</script>

<?php
// Include responsive footer
include __DIR__ . '/partials/footer.php';
?>