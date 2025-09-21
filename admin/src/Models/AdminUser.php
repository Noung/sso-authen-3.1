<?php

/**
 * admin/src/Models/AdminUser.php
 * Model for managing admin users
 * Compatible with PHP 7.4.33
 */

namespace SsoAdmin\Models;

use SsoAdmin\Database\Connection;
use PDO;

class AdminUser
{
    /** @var PDO */
    private $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Get admin user by email
     * @param string $email
     * @return array|null
     */
    public function getByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Create or update admin user
     * @param array $userData
     * @return bool
     */
    public function createOrUpdate(array $userData)
    {
        $existing = $this->getByEmail($userData['email']);
        
        if ($existing) {
            // Update existing user
            $stmt = $this->db->prepare("
                UPDATE admin_users 
                SET name = ?, last_login_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                WHERE email = ?
            ");
            return $stmt->execute([$userData['name'], $userData['email']]);
        } else {
            // Create new user
            $stmt = $this->db->prepare("
                INSERT INTO admin_users (email, name, role, status) 
                VALUES (?, ?, ?, 'active')
            ");
            return $stmt->execute([
                $userData['email'],
                $userData['name'],
                $userData['role'] ?? 'admin'
            ]);
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
    public function getAll()
    {
        $stmt = $this->db->query("
            SELECT id, email, name, role, status, last_login_at, created_at 
            FROM admin_users 
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Update admin user status
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function updateStatus($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE admin_users SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
}