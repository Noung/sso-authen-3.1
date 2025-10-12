<?php

namespace SsoAdmin\Controllers;

use SsoAdmin\Database\Connection;
use SsoAdmin\Models\UsageStatistics;

/**
 * Dashboard Controller
 * Compatible with PHP 7.4.33
 */
class DashboardController
{
    /**
     * Show dashboard page
     */
    public function index($request, $response)
    {
        $html = $this->renderDashboardPage();
        $body = $response->getBody();
        $body->write($html);
        return $response;
    }

    /**
     * Get dashboard statistics
     */
    public function getStats($request, $response)
    {
        try {
            $stats = [
                'total_clients' => $this->getTotalClients(),
                'active_clients' => $this->getActiveClients(),
                'inactive_clients' => $this->getInactiveClients(),
                'suspended_clients' => $this->getSuspendedClients(),
                'total_requests_today' => $this->getRequestsToday(),
                'total_requests' => $this->getTotalRequests(),
                'success_rate' => $this->getSuccessRate(),
                'recent_activities_count' => $this->getRecentActivitiesCount(),
                'top_client_activities' => $this->getTopClientActivities(),
                'system_usage_trend' => $this->getSystemUsageTrend()
            ];

            $body = $response->getBody();
            $body->write(json_encode([
                'success' => true,
                'data' => $stats
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $body = $response->getBody();
            $body->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get recent activities
     */
    public function getRecentActivities($request, $response)
    {
        try {
            $queryParams = $request->getQueryParams();
            $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 10;

            $activities = $this->getRecentActivitiesFromDb($limit);

            $body = $response->getBody();
            $body->write(json_encode([
                'success' => true,
                'data' => $activities
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $body = $response->getBody();
            $body->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get total clients count
     */
    private function getTotalClients()
    {
        try {
            $result = Connection::fetchOne('SELECT COUNT(*) as count FROM clients');
            return $result ? (int)$result['count'] : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get active clients count
     */
    private function getActiveClients()
    {
        try {
            $result = Connection::fetchOne('SELECT COUNT(*) as count FROM clients WHERE status = ?', ['active']);
            return $result ? (int)$result['count'] : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get inactive clients count
     */
    private function getInactiveClients()
    {
        try {
            $result = Connection::fetchOne('SELECT COUNT(*) as count FROM clients WHERE status = ?', ['inactive']);
            return $result ? (int)$result['count'] : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get suspended clients count
     */
    private function getSuspendedClients()
    {
        try {
            $result = Connection::fetchOne('SELECT COUNT(*) as count FROM clients WHERE status = ?', ['suspended']);
            return $result ? (int)$result['count'] : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get requests count for today
     */
    private function getRequestsToday()
    {
        try {
            $today = date('Y-m-d');
            $sql = "SELECT COUNT(*) as count FROM audit_logs WHERE DATE(created_at) = ?";
            $result = Connection::fetchOne($sql, [$today]);
            return $result ? (int)$result['count'] : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get total requests count
     */
    private function getTotalRequests()
    {
        try {
            // Count only authentication-related requests
            $sql = "SELECT COUNT(*) as count FROM audit_logs WHERE action IN ('auth_success', 'auth_failed', 'oidc_auth_success', 'oidc_auth_failed', 'oidc_login_initiated')";
            $result = Connection::fetchOne($sql);
            return $result ? (int)$result['count'] : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get authentication success rate
     */
    private function getSuccessRate()
    {
        try {
            // Get total authentication attempts (both success and failure)
            $sql = "SELECT COUNT(*) as total FROM audit_logs WHERE action IN ('auth_success', 'auth_failed', 'oidc_auth_success', 'oidc_auth_failed')";
            $totalResult = Connection::fetchOne($sql);
            $total = $totalResult ? (int)$totalResult['total'] : 0;

            if ($total == 0) {
                return 0;
            }

            // Get successful authentications
            $sql = "SELECT COUNT(*) as success FROM audit_logs WHERE action IN ('auth_success', 'oidc_auth_success')";
            $successResult = Connection::fetchOne($sql);
            $success = $successResult ? (int)$successResult['success'] : 0;

            // Calculate success rate as percentage
            $successRate = ($success / $total) * 100;
            return round($successRate, 1);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get recent activities count
     */
    private function getRecentActivitiesCount()
    {
        try {
            // Change from 7 days to total activities
            $sql = "SELECT COUNT(*) as count FROM audit_logs";
            $result = Connection::fetchOne($sql);
            return $result ? (int)$result['count'] : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get top client activities
     */
    private function getTopClientActivities()
    {
        try {
            $sql = "SELECT c.client_name, COUNT(al.id) as activity_count 
                    FROM clients c 
                    LEFT JOIN audit_logs al ON (c.id = al.resource_id AND al.resource_type = 'client') 
                    WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY c.id, c.client_name 
                    ORDER BY activity_count DESC 
                    LIMIT 5";
            return Connection::fetchAll($sql);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get system usage trend (last 7 days)
     */
    private function getSystemUsageTrend()
    {
        try {
            $sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
                    FROM audit_logs 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY DATE(created_at) 
                    ORDER BY date";
            return Connection::fetchAll($sql);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get recent activities from database
     */
    private function getRecentActivitiesFromDb($limit)
    {
        try {
            $sql = 'SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT ?';
            $activities = Connection::fetchAll($sql, [$limit]);

            // Format activities for display
            $formatted = [];
            foreach ($activities as $activity) {
                // Create a user-friendly description based on the action
                $description = $this->formatActivityDescription($activity);

                $formatted[] = [
                    'id' => $activity['id'],
                    'action' => $activity['action'],
                    'description' => $description,
                    'admin_email' => $activity['admin_email'],
                    'created_at' => $activity['created_at'],
                ];
            }

            return $formatted;
        } catch (\Exception $e) {
            // Return sample data if database is not available
            return [
                [
                    'id' => 1,
                    'action' => 'client_created',
                    'description' => 'สร้าง client application ใหม่',
                    'admin_email' => 'admin@psu.ac.th',
                    'created_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'id' => 2,
                    'action' => 'client_updated',
                    'description' => 'แก้ไขข้อมูล client application',
                    'admin_email' => 'admin@psu.ac.th',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                ],
            ];
        }
    }

    /**
     * Format activity description for display
     */
    private function formatActivityDescription($activity)
    {
        // If description already exists in the database, use it
        if (!empty($activity['description'])) {
            return $activity['description'];
        }

        // Otherwise, generate a description based on the action and resource
        $action = $activity['action'];
        $resourceType = $activity['resource_type'];
        $adminEmail = $activity['admin_email'];

        switch ($action) {
            case 'client_created':
                return "Admin {$adminEmail} สร้าง client application ใหม่";
            case 'client_updated':
                return "Admin {$adminEmail} แก้ไขข้อมูล client application";
            case 'client_deleted':
                return "Admin {$adminEmail} ลบ client application";
            case 'client_status_changed':
                return "Admin {$adminEmail} เปลี่ยนสถานะ client application";
            case 'auth_success':
                return "Admin {$adminEmail} เข้าสู่ระบบสำเร็จ";
            case 'auth_failed':
                return "Admin {$adminEmail} เข้าสู่ระบบไม่สำเร็จ";
            case 'oidc_auth_success':
                return "ผู้ใช้ {$adminEmail} เข้าสู่ระบบผ่าน OIDC สำเร็จ";
            case 'oidc_auth_failed':
                return "ผู้ใช้ {$adminEmail} เข้าสู่ระบบผ่าน OIDC ไม่สำเร็จ";
            case 'oidc_login_initiated':
                return "ผู้ใช้ {$adminEmail} เริ่มต้นการเข้าสู่ระบบผ่าน OIDC";
            case 'jwt_secret_viewed':
                return "Admin {$adminEmail} ดู JWT secret";
            case 'admin_login':
                return "Admin {$adminEmail} เข้าสู่ระบบผู้ดูแล";
            case 'client_viewed':
                return "Admin {$adminEmail} ดูรายละเอียด client";
            case 'dashboard_accessed':
                return "Admin {$adminEmail} เข้าถึง dashboard";
            case 'statistics_viewed':
                return "Admin {$adminEmail} ดูสถิติการใช้งาน";
            case 'config_checked':
                return "Admin {$adminEmail} ตรวจสอบการตั้งค่าระบบ";
            default:
                return "กิจกรรม: {$action} โดย {$adminEmail}";
        }
    }

    /**
     * Render dashboard HTML page
     */
    private function renderDashboardPage()
    {
        $basePath = $GLOBALS['admin_base_path'] ?? '/sso-authen-3/admin/public';
        $adminName = $_SESSION['admin_name'] ?? 'Administrator';

        return '<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSO Admin Panel - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="' . $basePath . '">
                <i class="fas fa-shield-alt me-2"></i>SSO Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i>' . $adminName . '
                </span>
                <a class="nav-link" href="' . $basePath . '/auth/logout">
                    <i class="fas fa-sign-out-alt me-1"></i>Sign out
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="' . $basePath . '">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="' . $basePath . '/clients">
                                <i class="fas fa-users me-2"></i>Client Applications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="' . $basePath . '/statistics">
                                <i class="fas fa-chart-bar me-2"></i>Usage Statistics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="' . $basePath . '/admin-users">
                                <i class="fas fa-user-shield me-2"></i>Admin Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="' . $basePath . '/backup-restore">
                                <i class="fas fa-database me-2"></i>Backup & Restore
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="' . $basePath . '/settings">
                                <i class="fas fa-cog me-2"></i>System Configuration
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 admin-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-me btn-outline-secondary" id="refresh-btn" onclick="refreshStats()">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </button>
                            <button type="button" class="btn btn-me btn-outline-primary" id="auto-refresh-btn" onclick="toggleAutoRefresh()">
                                <i class="fas fa-pause-circle me-1"></i>Stop Auto Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Client Statistics Cards -->
                <div class="row mb-4" id="client-stats-cards">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-vibrant-blue text-white">
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
                        <div class="card bg-vibrant-green text-white">
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
                        <div class="card bg-vibrant-dark-gray text-white">
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
                        <div class="card bg-vibrant-red text-white">
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
                        <div class="card bg-vibrant-orange text-white">
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
                        <div class="card bg-vibrant-yellow text-white">
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
                        <div class="card bg-vibrant-teal text-white">
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
                        <div class="card bg-vibrant-purple text-white">
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
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="' . $basePath . '/js/shared.js?v=' . time() . '"></script>
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
            
            console.log("Auto-refresh started - updating every " + (refreshInterval/1000) + " seconds");
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
                    button.innerHTML = "<i class=\"fas fa-pause-circle me-1\"></i>Stop Auto Refresh";
                    button.className = "btn btn-me btn-outline-primary";
                } else {
                    button.innerHTML = "<i class=\"fas fa-play-circle me-1\"></i>Start Auto Refresh";
                    button.className = "btn btn-me btn-outline-success";
                }
            }
        }

        function loadDashboardData() {
            loadStats();
            loadRecentActivities();
        }

        function loadStats() {
            fetch("' . $basePath . '/api/dashboard/stats")
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateStatsCards(data.data);
                        updateCharts(data.data);
                    } else {
                        Swal.fire("Error", data.message, "error");
                    }
                })
                .catch(error => {
                    console.error("Error loading stats:", error);
                });
        }

        function loadRecentActivities() {
            fetch("' . $basePath . '/api/dashboard/recent-activities?limit=10")
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderRecentActivities(data.data);
                    } else {
                        document.getElementById("recent-activities").innerHTML = 
                            "<p class=\"text-center text-muted\">ไม่สามารถโหลดข้อมูลได้</p>";
                    }
                })
                .catch(error => {
                    console.error("Error loading activities:", error);
                    document.getElementById("recent-activities").innerHTML = 
                        "<p class=\"text-center text-muted\">ไม่สามารถโหลดข้อมูลได้</p>";
                });
        }

        function updateStatsCards(stats) {
            document.getElementById("total-clients").textContent = stats.total_clients;
            document.getElementById("active-clients").textContent = stats.active_clients;
            document.getElementById("inactive-clients").textContent = stats.inactive_clients;
            document.getElementById("suspended-clients").textContent = stats.suspended_clients;
            document.getElementById("requests-today").textContent = stats.total_requests_today;
            document.getElementById("total-requests").textContent = stats.total_requests;
            document.getElementById("success-rate").textContent = stats.success_rate + "%";
            document.getElementById("recent-activities-count").textContent = stats.recent_activities_count;
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
                container.innerHTML = "<p class=\"text-center text-muted\">ไม่มีกิจกรรมล่าสุด</p>";
                return;
            }

            let html = "<div class=\"list-group list-group-flush\">";
            activities.forEach(activity => {
                const date = new Date(activity.created_at).toLocaleString("th-TH");
                html += "<div class=\"list-group-item\">"+
                    "<div class=\"d-flex w-100 justify-content-between\">"+
                        "<h6 class=\"mb-1\">" + activity.description + "</h6>"+
                        "<small>" + date + "</small>"+
                    "</div>"+
                    "<small class=\"text-muted\">Action: " + activity.action + "</small>"+
                "</div>";
            });
            html += "</div>";
            
            container.innerHTML = html;
        }

        function refreshStats() {
            // Show loading indicator
            const refreshBtn = document.getElementById("refresh-btn");
            let originalHTML = "";
            if (refreshBtn) {
                originalHTML = refreshBtn.innerHTML;
                refreshBtn.innerHTML = "<i class=\"fas fa-spinner fa-spin me-1\"></i>Refreshing...";
                refreshBtn.disabled = true;
            }
            
            loadDashboardData();
            
            // Show success message using custom toast notification for consistency across the application
            showCustomCopySuccess("Data has been updated successfully");
            
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
        .admin-content {
            margin-bottom: 20px;
        }
    </style>
</body>
</html>';
    }
}