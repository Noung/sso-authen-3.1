<?php

/**
 * admin/src/Models/JwtSecretHistory.php
 * Model for JWT Secret Key history management
 * Compatible with PHP 7.4.33
 */

namespace SsoAdmin\Models;

use SsoAdmin\Database\Connection;
use Exception;

class JwtSecretHistory
{
    /**
     * Add a new JWT secret key to history
     * @param string $secretKey
     * @param string $createdBy
     * @param string|null $notes
     * @return bool
     */
    public static function addSecret($secretKey, $createdBy, $notes = null)
    {
        try {
            // Mark all existing secrets as inactive
            Connection::query("UPDATE jwt_secret_history SET is_active = FALSE WHERE is_active = TRUE");
            
            // Add the new secret as active
            $sql = "
                INSERT INTO jwt_secret_history (
                    secret_key, created_by, notes, is_active
                ) VALUES (?, ?, ?, TRUE)
            ";

            Connection::query($sql, [
                $secretKey,
                $createdBy,
                $notes
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Error adding JWT secret to history: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all secret key history with pagination
     * @param int $page
     * @param int $limit
     * @return array
     */
    public static function getAll($page = 1, $limit = 50)
    {
        try {
            $page = max(1, (int)$page);
            $limit = min(100, max(1, (int)$limit));
            $offset = ($page - 1) * $limit;

            // Get secrets
            $sql = "
                SELECT * FROM jwt_secret_history 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ";
            
            $secrets = Connection::fetchAll($sql, [$limit, $offset]);

            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM jwt_secret_history";
            $totalResult = Connection::fetchOne($countSql);
            $total = $totalResult ? (int)$totalResult['total'] : 0;

            return [
                'data' => $secrets,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];
        } catch (Exception $e) {
            error_log("Error fetching JWT secret history: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => 0
            ];
        }
    }

    /**
     * Get the current active secret key
     * @return array|null
     */
    public static function getCurrentSecret()
    {
        try {
            $sql = "
                SELECT * FROM jwt_secret_history 
                WHERE is_active = TRUE 
                ORDER BY created_at DESC 
                LIMIT 1
            ";
            return Connection::fetchOne($sql);
        } catch (Exception $e) {
            error_log("Error fetching current JWT secret: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get secret key by ID
     * @param int $id
     * @return array|null
     */
    public static function getById($id)
    {
        try {
            $sql = "SELECT * FROM jwt_secret_history WHERE id = ?";
            return Connection::fetchOne($sql, [(int)$id]);
        } catch (Exception $e) {
            error_log("Error fetching JWT secret by ID: " . $e->getMessage());
            return null;
        }
    }
}