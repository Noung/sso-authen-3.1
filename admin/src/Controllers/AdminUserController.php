<?php

namespace SsoAdmin\Controllers;

use SsoAdmin\Database\Connection;
use SsoAdmin\Models\AdminUser;
use Exception;

/**
 * Admin User Controller - Full CRUD Operations  
 * Compatible with PHP 7.4.33
 */
class AdminUserController
{
    /**
     * Get all admin users with pagination and filters
     */
    public function getAll($request, $response)
    {
        try {
            $queryParams = $request->getQueryParams();
            $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
            $perPage = isset($queryParams['per_page']) ? (int)$queryParams['per_page'] : 10;
            $search = isset($queryParams['search']) ? trim($queryParams['search']) : '';
            $status = isset($queryParams['status']) ? trim($queryParams['status']) : '';

            $result = AdminUser::getAllWithPagination($page, $perPage, $search, $status);

            $body = $response->getBody();
            $body->write(json_encode([
                'success' => true,
                'data' => $result
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $body = $response->getBody();
            $body->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get admin user by ID
     */
    public function getById($request, $response, $args)
    {
        try {
            $id = (int)$args['id'];
            $user = AdminUser::getById($id);

            if (!$user) {
                $body = $response->getBody();
                $body->write(json_encode([
                    'success' => false,
                    'message' => 'Admin user not found'
                ]));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $body = $response->getBody();
            $body->write(json_encode([
                'success' => true,
                'data' => $user
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $body = $response->getBody();
            $body->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Create new admin user
     */
    public function create($request, $response)
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $body = $response->getBody();
                $body->write(json_encode([
                    'success' => false,
                    'message' => 'Invalid JSON data'
                ]));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Validate required fields
            if (empty($data['email']) || empty($data['name'])) {
                $body = $response->getBody();
                $body->write(json_encode([
                    'success' => false,
                    'message' => 'Email and name are required'
                ]));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $body = $response->getBody();
                $body->write(json_encode([
                    'success' => false,
                    'message' => 'Invalid email format'
                ]));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Check if user already exists
            $existingUser = AdminUser::getByEmail($data['email']);
            if ($existingUser) {
                $body = $response->getBody();
                $body->write(json_encode([
                    'success' => false,
                    'message' => 'Admin user with this email already exists'
                ]));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $user = AdminUser::create($data);

            // Log activity
            $this->logActivity('admin_user_created', "Created admin user: {$data['name']}", $user['id']);

            $body = $response->getBody();
            $body->write(json_encode([
                'success' => true,
                'data' => $user,
                'message' => 'Admin user created successfully'
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (Exception $e) {
            $body = $response->getBody();
            $body->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Update existing admin user
     */
    public function update($request, $response, $args)
    {
        try {
            $id = (int)$args['id'];
            $data = json_decode($request->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $body = $response->getBody();
                $body->write(json_encode([
                    'success' => false,
                    'message' => 'Invalid JSON data'
                ]));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Check if user exists
            $existingUser = AdminUser::getById($id);
            if (!$existingUser) {
                $body = $response->getBody();
                $body->write(json_encode([
                    'success' => false,
                    'message' => 'Admin user not found'
                ]));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Validate email format if email is being updated
            if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $body = $response->getBody();
                $body->write(json_encode([
                    'success' => false,
                    'message' => 'Invalid email format'
                ]));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $user = AdminUser::update($id, $data);

            // Log activity
            $this->logActivity('admin_user_updated', "Updated admin user: {$user['name']}", $id);

            $body = $response->getBody();
            $body->write(json_encode([
                'success' => true,
                'data' => $user,
                'message' => 'Admin user updated successfully'
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $body = $response->getBody();
            $body->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Delete admin user
     */
    public function delete($request, $response, $args)
    {
        try {
            $id = (int)$args['id'];

            // Get user for logging
            $user = AdminUser::getById($id);
            if (!$user) {
                $body = $response->getBody();
                $body->write(json_encode([
                    'success' => false,
                    'message' => 'Admin user not found'
                ]));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Prevent deletion of the current user
            $currentEmail = $_SESSION['admin_email'] ?? '';
            if ($currentEmail === $user['email']) {
                $body = $response->getBody();
                $body->write(json_encode([
                    'success' => false,
                    'message' => 'You cannot delete your own account'
                ]));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            AdminUser::delete($id);

            // Log activity
            $this->logActivity('admin_user_deleted', "Deleted admin user: {$user['name']}", $id);

            $body = $response->getBody();
            $body->write(json_encode([
                'success' => true,
                'message' => 'Admin user deleted successfully'
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $body = $response->getBody();
            $body->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Toggle admin user status (active/inactive)
     */
    public function toggleStatus($request, $response, $args)
    {
        try {
            $id = (int)$args['id'];

            $user = AdminUser::getById($id);
            if (!$user) {
                $body = $response->getBody();
                $body->write(json_encode([
                    'success' => false,
                    'message' => 'Admin user not found'
                ]));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Prevent toggling status of the current user
            $currentEmail = $_SESSION['admin_email'] ?? '';
            if ($currentEmail === $user['email']) {
                $body = $response->getBody();
                $body->write(json_encode([
                    'success' => false,
                    'message' => 'You cannot change your own status'
                ]));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Toggle status
            $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';
            $updatedUser = AdminUser::update($id, ['status' => $newStatus]);

            // Log activity
            $this->logActivity('admin_user_status_changed', "Changed status of admin user: {$user['name']} to {$newStatus}", $id);

            $body = $response->getBody();
            $body->write(json_encode([
                'success' => true,
                'data' => $updatedUser,
                'message' => "Admin user status changed to {$newStatus}"
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $body = $response->getBody();
            $body->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get available roles
     */
    public function getAvailableRoles($request, $response)
    {
        try {
            $roles = [
                ['value' => 'super_admin', 'label' => 'Super Admin'],
                ['value' => 'admin', 'label' => 'Admin'],
                ['value' => 'viewer', 'label' => 'Viewer']
            ];

            $body = $response->getBody();
            $body->write(json_encode([
                'success' => true,
                'data' => $roles
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $body = $response->getBody();
            $body->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get available statuses
     */
    public function getAvailableStatuses($request, $response)
    {
        try {
            $statuses = [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive']
            ];

            $body = $response->getBody();
            $body->write(json_encode([
                'success' => true,
                'data' => $statuses
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $body = $response->getBody();
            $body->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Log admin activity
     */
    private function logActivity($action, $description, $userId = null)
    {
        try {
            // Get admin info from session
            $adminEmail = $_SESSION['admin_email'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $ipAddress = $this->getClientIpAddress();

            $sql = 'INSERT INTO audit_logs (admin_email, action, resource_type, resource_id, ip_address, user_agent, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())';

            Connection::query($sql, [
                $adminEmail,
                $action,
                'admin_user',
                $userId,
                $ipAddress,
                $userAgent
            ]);
        } catch (Exception $e) {
            // Log silently fails, don't interrupt main operation
            error_log('Failed to log activity: ' . $e->getMessage());
        }
    }

    /**
     * Get client IP address safely
     */
    private function getClientIpAddress()
    {
        // Check for shared IP
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            // Take the first IP if multiple
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }

        // Validate IP address
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = 'unknown';
        }

        return $ip;
    }
}