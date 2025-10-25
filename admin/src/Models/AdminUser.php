<?php

/**
 * admin/src/Models/AdminUser.php
 * Model for managing admin users
 * Compatible with PHP 7.4.33
 */

namespace SsoAdmin\Models;

use SsoAdmin\Database\Connection;
use PDO;
use Exception;

class AdminUser
{
    /** @var PDO */
    private $db;

    public function __construct()
    {
        $this->db = Connection::getPdo();
    }

    /**
     * Get admin user by email
     * @param string $email
     * @return array|null
     */
    public static function getByEmail($email)
    {
        try {
            $sql = "SELECT * FROM admin_users WHERE email = ? AND status = 'active'";
            return Connection::fetchOne($sql, [$email]);
        } catch (Exception $e) {
            throw new Exception('Error fetching admin user by email: ' . $e->getMessage());
        }
    }

    /**
     * Create or update admin user
     * @param array $userData
     * @return bool
     */
    public static function createOrUpdate(array $userData)
    {
        try {
            // Validate email format
            if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }
            
            $existing = self::getByEmail($userData['email']);
            
            if ($existing) {
                // Update existing user
                $sql = "
                    UPDATE admin_users 
                    SET name = ?, last_login_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                    WHERE email = ?
                ";
                Connection::query($sql, [$userData['name'], $userData['email']]);
                return true;
            } else {
                // Create new user
                $sql = "
                    INSERT INTO admin_users (email, name, role, status) 
                    VALUES (?, ?, ?, 'active')
                ";
                Connection::query($sql, [
                    $userData['email'],
                    $userData['name'],
                    $userData['role'] ?? 'admin'
                ]);
                return true;
            }
        } catch (Exception $e) {
            throw new Exception('Error creating or updating admin user: ' . $e->getMessage());
        }
    }

    /**
     * Check if email is authorized admin
     * @param string $email
     * @param array $authorizedEmails
     * @return bool
     */
    public function isAuthorizedAdmin($email, array $authorizedEmails)
    {
        return in_array($email, $authorizedEmails);
    }

    /**
     * Get all admin users
     * @return array
     */
    public static function getAll()
    {
        try {
            $sql = "
                SELECT id, email, name, role, status, last_login_at, created_at 
                FROM admin_users 
                ORDER BY created_at DESC
            ";
            return Connection::fetchAll($sql);
        } catch (Exception $e) {
            throw new Exception('Error fetching all admin users: ' . $e->getMessage());
        }
    }

    /**
     * Get all admin users with pagination and filters
     * @param int $page
     * @param int $perPage
     * @param string $search
     * @param string $status
     * @return array
     */
    public static function getAllWithPagination($page = 1, $perPage = 10, $search = '', $status = '')
    {
        try {
            $page = max(1, (int)$page);
            $perPage = min(100, max(1, (int)$perPage));
            $offset = ($page - 1) * $perPage;

            // Build query with filters
            $sql = "SELECT id, email, name, role, status, last_login_at, created_at FROM admin_users WHERE 1=1";
            $params = [];

            if (!empty($search)) {
                $sql .= " AND (email LIKE ? OR name LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            if (!empty($status)) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }

            $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $perPage;
            $params[] = $offset;

            $users = Connection::fetchAll($sql, $params);

            // Get total count for pagination
            $countSql = "SELECT COUNT(*) as total FROM admin_users WHERE 1=1";
            $countParams = [];

            if (!empty($search)) {
                $countSql .= " AND (email LIKE ? OR name LIKE ?)";
                $countParams[] = "%$search%";
                $countParams[] = "%$search%";
            }

            if (!empty($status)) {
                $countSql .= " AND status = ?";
                $countParams[] = $status;
            }

            $totalResult = Connection::fetchOne($countSql, $countParams);
            $total = $totalResult ? (int)$totalResult['total'] : 0;

            return [
                'data' => $users,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage),
                    'has_prev' => $page > 1,
                    'has_next' => $page < ceil($total / $perPage)
                ]
            ];
        } catch (Exception $e) {
            throw new Exception('Error fetching admin users: ' . $e->getMessage());
        }
    }

    /**
     * Get admin user by ID
     * @param int $id
     * @return array|null
     */
    public static function getById($id)
    {
        try {
            $sql = 'SELECT * FROM admin_users WHERE id = ?';
            return Connection::fetchOne($sql, [(int)$id]);
        } catch (Exception $e) {
            throw new Exception('Error fetching admin user: ' . $e->getMessage());
        }
    }

    /**
     * Create new admin user
     * @param array $data
     * @return array
     */
    public static function create(array $data)
    {
        try {
            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }
            
            Connection::beginTransaction();
            
            $sql = "
                INSERT INTO admin_users (email, name, role, status) 
                VALUES (?, ?, ?, ?)
            ";
            $params = [
                $data['email'],
                $data['name'],
                $data['role'] ?? 'admin',
                $data['status'] ?? 'active'
            ];
            
            Connection::query($sql, $params);
            $id = Connection::lastInsertId();
            
            Connection::commit();
            
            return self::getById($id);
        } catch (Exception $e) {
            Connection::rollback();
            throw new Exception('Error creating admin user: ' . $e->getMessage());
        }
    }

    /**
     * Update admin user
     * @param int $id
     * @param array $data
     * @return array
     */
    public static function update($id, array $data)
    {
        try {
            // Validate email format if email is being updated
            if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }
            
            // Build dynamic update query
            $fields = [];
            $params = [];
            
            foreach ($data as $key => $value) {
                // Only allow updating specific fields
                if (in_array($key, ['email', 'name', 'role', 'status'])) {
                    $fields[] = "$key = ?";
                    $params[] = $value;
                }
            }
            
            if (empty($fields)) {
                return self::getById($id);
            }
            
            $params[] = $id;
            
            $sql = "UPDATE admin_users SET " . implode(', ', $fields) . " WHERE id = ?";
            Connection::query($sql, $params);
            
            return self::getById($id);
        } catch (Exception $e) {
            throw new Exception('Error updating admin user: ' . $e->getMessage());
        }
    }

    /**
     * Delete admin user
     * @param int $id
     * @return bool
     */
    public static function delete($id)
    {
        try {
            $sql = "DELETE FROM admin_users WHERE id = ?";
            Connection::query($sql, [(int)$id]);
            return true;
        } catch (Exception $e) {
            throw new Exception('Error deleting admin user: ' . $e->getMessage());
        }
    }

    /**
     * Update admin user status
     * @param int $id
     * @param string $status
     * @return bool
     */
    public static function updateStatus($id, $status)
    {
        try {
            $sql = "UPDATE admin_users SET status = ? WHERE id = ?";
            Connection::query($sql, [$status, (int)$id]);
            return true;
        } catch (Exception $e) {
            throw new Exception('Error updating admin user status: ' . $e->getMessage());
        }
    }
}