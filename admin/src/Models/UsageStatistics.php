<?php

/**
 * admin/src/Models/UsageStatistics.php
 * Model for usage statistics and analytics
 * Compatible with PHP 7.4.33
 */

namespace SsoAdmin\Models;

use SsoAdmin\Database\Connection;
use PDO;

class UsageStatistics
{
    /** @var PDO */
    private $db;

    public function __construct()
    {
        $this->db = Connection::getPdo();
    }

    /**
     * Get comprehensive statistics for a specific client
     * @param int $clientId
     * @param int $days Number of days to analyze (default 30)
     * @return array
     */
    public function getClientStatistics($clientId, $days = 30)
    {
        // Get client basic info
        $client = Connection::fetchOne('SELECT * FROM clients WHERE id = ?', [$clientId]);
        if (!$client) {
            return ['error' => 'Client not found'];
        }

        // Add total requests to client data
        $totalRequests = $this->getClientTotalRequests($client['client_id'], $days);
        $client['total_requests'] = $totalRequests;

        // Get activity statistics
        $activityStats = $this->getClientActivityStats($clientId, $days);

        // Get JWT secret view count
        $jwtViewCount = $this->getJwtViewCount($clientId, $days);

        // Get daily activity trend
        $dailyTrend = $this->getDailyActivityTrend($clientId, $days);

        // Get most active admin users for this client
        $topAdmins = $this->getTopAdminsForClient($clientId, $days);

        return [
            'client' => $client,
            'period_days' => $days,
            'activity_stats' => $activityStats,
            'jwt_view_count' => $jwtViewCount,
            'daily_trend' => $dailyTrend,
            'top_admins' => $topAdmins,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get total requests for a client
     */
    private function getClientTotalRequests($clientId, $days)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM audit_logs 
            WHERE resource_type = 'authentication' 
                AND resource_id = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$clientId, $days]);
        $result = $stmt->fetch();
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Get overall system usage statistics
     * @param int $days
     * @return array
     */
    public function getSystemStatistics($days = 30)
    {
        return [
            'total_clients' => $this->getTotalClientsCount(),
            'active_clients' => $this->getActiveClientsCount(),
            'total_activities' => $this->getTotalActivitiesCount($days),
            'top_activities' => $this->getTopActivities($days),
            'admin_activity' => $this->getAdminActivityStats($days),
            'client_activity_summary' => $this->getClientActivitySummary($days),
            'daily_system_trend' => $this->getDailySystemTrend($days),
            'period_days' => $days,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get activity statistics for a specific client
     */
    private function getClientActivityStats($clientId, $days)
    {
        $stmt = $this->db->prepare("
            SELECT 
                action,
                COUNT(*) as count,
                COUNT(DISTINCT admin_email) as unique_admins,
                MIN(created_at) as first_activity,
                MAX(created_at) as last_activity
            FROM audit_logs 
            WHERE (resource_type = 'client' AND resource_id = ?) 
               OR (resource_type = 'authentication' AND resource_id = ?) 
               AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY action
            ORDER BY count DESC
        ");
        $stmt->execute([$clientId, $clientId, $days]);
        return $stmt->fetchAll();
    }

    /**
     * Get JWT secret view count for a client
     */
    private function getJwtViewCount($clientId, $days)
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_views,
                COUNT(DISTINCT admin_email) as unique_viewers,
                COUNT(DISTINCT DATE(created_at)) as active_days
            FROM audit_logs 
            WHERE action = 'jwt_secret_viewed' 
                AND resource_id = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$clientId, $days]);
        return $stmt->fetch();
    }

    /**
     * Get daily activity trend for a client
     */
    private function getDailyActivityTrend($clientId, $days)
    {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_activities,
                COUNT(DISTINCT action) as unique_actions,
                COUNT(DISTINCT admin_email) as unique_users,
                SUM(CASE WHEN action IN ('oidc_auth_success', 'auth_success') THEN 1 ELSE 0 END) as successful_logins,
                SUM(CASE WHEN action IN ('oidc_auth_failed', 'auth_failed') THEN 1 ELSE 0 END) as failed_logins
            FROM audit_logs 
            WHERE ((resource_type = 'client' AND resource_id = ?) 
               OR (resource_type = 'authentication' AND resource_id = ?))
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
            LIMIT 30
        ");
        $stmt->execute([$clientId, $clientId, $days]);
        return $stmt->fetchAll();
    }

    /**
     * Get top admin users for a client
     */
    private function getTopAdminsForClient($clientId, $days)
    {
        $stmt = $this->db->prepare("
            SELECT 
                admin_email,
                COUNT(*) as activity_count,
                COUNT(DISTINCT action) as unique_actions,
                MAX(created_at) as last_activity
            FROM audit_logs 
            WHERE resource_type = 'client' 
                AND resource_id = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY admin_email
            ORDER BY activity_count DESC
            LIMIT 10
        ");
        $stmt->execute([$clientId, $days]);
        return $stmt->fetchAll();
    }

    /**
     * Get total clients count
     */
    private function getTotalClientsCount()
    {
        $result = Connection::fetchOne('SELECT COUNT(*) as count FROM clients');
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Get active clients count
     */
    private function getActiveClientsCount()
    {
        $result = Connection::fetchOne('SELECT COUNT(*) as count FROM clients WHERE status = "active"');
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Get total activities count
     */
    private function getTotalActivitiesCount($days)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM audit_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$days]);
        $result = $stmt->fetch();
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Get top activities across all clients
     */
    private function getTopActivities($days)
    {
        $stmt = $this->db->prepare("
            SELECT 
                action,
                resource_type,
                COUNT(*) as count,
                COUNT(DISTINCT admin_email) as unique_admins
            FROM audit_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY action, resource_type
            ORDER BY count DESC
            LIMIT 10
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    /**
     * Get admin activity statistics
     */
    private function getAdminActivityStats($days)
    {
        $stmt = $this->db->prepare("
            SELECT 
                admin_email,
                COUNT(*) as total_activities,
                COUNT(DISTINCT action) as unique_actions,
                COUNT(DISTINCT resource_id) as clients_managed,
                MAX(created_at) as last_activity
            FROM audit_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY admin_email
            ORDER BY total_activities DESC
            LIMIT 10
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    /**
     * Get client activity summary
     */
    private function getClientActivitySummary($days)
    {
        $stmt = $this->db->prepare("
            SELECT 
                c.id,
                c.client_name,
                c.client_id,
                c.status,
                COUNT(al.id) as total_activities,
                COUNT(DISTINCT al.action) as unique_actions,
                COUNT(DISTINCT al.admin_email) as unique_admins,
                MAX(al.created_at) as last_activity,
                (SELECT COUNT(*) FROM audit_logs WHERE resource_type = 'authentication' AND resource_id = c.client_id AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)) as total_requests
            FROM clients c
            LEFT JOIN audit_logs al ON (c.id = al.resource_id AND al.resource_type = 'client' 
                                       AND al.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY))
            GROUP BY c.id, c.client_name, c.client_id, c.status
            ORDER BY total_activities DESC
        ");
        $stmt->execute([$days, $days]);
        return $stmt->fetchAll();
    }

    /**
     * Get daily system trend
     */
    private function getDailySystemTrend($days)
    {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_activities,
                COUNT(DISTINCT admin_email) as active_admins,
                COUNT(DISTINCT resource_id) as active_clients
            FROM audit_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
            LIMIT 30
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
}
