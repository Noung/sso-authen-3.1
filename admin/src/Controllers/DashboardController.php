<?php

namespace SsoAdmin\Controllers;

use SsoAdmin\Database\Connection;

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
                'total_requests_today' => 0, // TODO: Implement from audit logs
                'success_rate' => 95.5, // TODO: Calculate from audit logs
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
                $formatted[] = [
                    'id' => $activity['id'],
                    'action' => $activity['action'],
                    'description' => $activity['description'],
                    'admin_id' => $activity['admin_id'],
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
                    'admin_id' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'id' => 2,
                    'action' => 'client_updated',
                    'description' => 'แก้ไขข้อมูล client application',
                    'admin_id' => 1,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                ],
            ];
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
                    <i class="fas fa-sign-out-alt me-1"></i>ออกจากระบบ
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
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshStats()">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4" id="stats-cards">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-primary text-white">
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
                        <div class="card bg-success text-white">
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
                        <div class="card bg-info text-white">
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
                        <div class="card bg-warning text-white">
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
                </div>

                <!-- Recent Activities -->
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
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            loadDashboardData();
        });

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
                            `<p class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</p>`;
                    }
                })
                .catch(error => {
                    console.error("Error loading activities:", error);
                    document.getElementById("recent-activities").innerHTML = 
                        `<p class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</p>`;
                });
        }

        function updateStatsCards(stats) {
            document.getElementById("total-clients").textContent = stats.total_clients;
            document.getElementById("active-clients").textContent = stats.active_clients;
            document.getElementById("requests-today").textContent = stats.total_requests_today;
            document.getElementById("success-rate").textContent = stats.success_rate + "%";
        }

        function renderRecentActivities(activities) {
            const container = document.getElementById("recent-activities");
            
            if (activities.length === 0) {
                container.innerHTML = `<p class="text-center text-muted">ไม่มีกิจกรรมล่าสุด</p>`;
                return;
            }

            let html = `<div class="list-group list-group-flush">`;
            activities.forEach(activity => {
                const date = new Date(activity.created_at).toLocaleString("th-TH");
                html += `<div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${activity.description}</h6>
                        <small>${date}</small>
                    </div>
                    <small class="text-muted">Action: ${activity.action}</small>
                </div>`;
            });
            html += `</div>`;
            
            container.innerHTML = html;
        }

        function refreshStats() {
            loadDashboardData();
            Swal.fire({
                title: "Refreshed",
                text: "ข้อมูลได้รับการปรับปรุงแล้ว",
                icon: "success",
                timer: 1500,
                showConfirmButton: false
            });
        }
    </script>
</body>
</html>';
    }
}