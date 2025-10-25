<?php

/**
 * admin/src/Models/AuditLog.php
 * Model for audit logging
 * Compatible with PHP 7.4.33
 */

namespace SsoAdmin\Models;

use SsoAdmin\Database\Connection;
use PDO;

class AuditLog
{
    /** @var PDO */
    private $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Log an administrative action
     * @param string $adminEmail
     * @param string $action
     * @param string $resourceType
     * @param string|null $resourceId
     * @param array|null $oldValues
     * @param array|null $newValues
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return bool
     */
    public function log($adminEmail, $action, $resourceType, $resourceId = null, $oldValues = null, $newValues = null, $ipAddress = null, $userAgent = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO audit_logs (
                admin_email, action, resource_type, resource_id,
                old_values, new_values, ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $adminEmail,
            $action,
            $resourceType,
            $resourceId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $ipAddress,
            $userAgent
        ]);
    }

    /**
     * Get audit logs with pagination
     * @param int $page
     * @param int $limit
     * @param array $filters
     * @return array
     */
    public function getAll($page = 1, $limit = 50, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        $whereClause = [];
        $params = [];

        // Apply filters
        if (!empty($filters['admin_email'])) {
            $whereClause[] = "admin_email = ?";
            $params[] = $filters['admin_email'];
        }

        if (!empty($filters['action'])) {
            $whereClause[] = "action = ?";
            $params[] = $filters['action'];
        }

        if (!empty($filters['resource_type'])) {
            $whereClause[] = "resource_type = ?";
            $params[] = $filters['resource_type'];
        }

        if (!empty($filters['date_from'])) {
            $whereClause[] = "created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $whereClause[] = "created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereSQL = !empty($whereClause) ? 'WHERE ' . implode(' AND ', $whereClause) : '';

        // Get logs
        $sql = "
            SELECT * FROM audit_logs 
            $whereSQL
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        // Get total count
        $countSQL = "SELECT COUNT(*) FROM audit_logs $whereSQL";
        $countParams = array_slice($params, 0, -2); // Remove limit and offset
        $countStmt = $this->db->prepare($countSQL);
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();

        return [
            'data' => $logs,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get recent activities for dashboard
     * @param int $limit
     * @return array
     */
    public function getRecentActivities($limit = 10)
    {
        $stmt = $this->db->prepare("
            SELECT admin_email, action, resource_type, resource_id, created_at
            FROM audit_logs 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get activity statistics
     * @param int $days
     * @return array
     */
    public function getStatistics($days = 30)
    {
        $stmt = $this->db->prepare("
            SELECT 
                action,
                resource_type,
                COUNT(*) as count,
                DATE(created_at) as date
            FROM audit_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY action, resource_type, DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
}