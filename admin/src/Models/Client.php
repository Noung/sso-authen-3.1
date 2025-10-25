<?php

namespace SsoAdmin\Models;

use SsoAdmin\Database\Connection;
use Exception;

/**
 * Client Model - Compatible with PHP 7.4.33
 * Full CRUD operations for client management
 */
class Client
{
    private $id;
    private $client_id;
    private $client_secret;
    private $client_name;
    private $app_redirect_uri;
    private $allowed_scopes;
    private $status;
    private $created_at;
    private $updated_at;

    // Constants for status
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * Get all clients with pagination and filters
     */
    public static function getAll($page = 1, $perPage = 10, $search = '', $status = '')
    {
        try {
            $page = max(1, (int)$page);
            $perPage = min(100, max(1, (int)$perPage));
            $offset = ($page - 1) * $perPage;

            // Build WHERE clause
            $conditions = [];
            $params = [];

            if (!empty($search)) {
                $conditions[] = '(client_name LIKE ? OR client_id LIKE ? OR app_redirect_uri LIKE ?)';
                $searchParam = '%' . $search . '%';
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }

            if (!empty($status)) {
                $conditions[] = 'status = ?';
                $params[] = $status;
            }

            $whereClause = '';
            if (!empty($conditions)) {
                $whereClause = 'WHERE ' . implode(' AND ', $conditions);
            }

            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM clients $whereClause";
            $totalResult = Connection::fetchOne($countSql, $params);
            $total = $totalResult ? (int)$totalResult['total'] : 0;

            // Get clients
            $sql = "SELECT * FROM clients $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $perPage;
            $params[] = $offset;

            $clients = Connection::fetchAll($sql, $params);

            // Calculate pagination
            $totalPages = ceil($total / $perPage);

            return [
                'data' => $clients,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1,
                ]
            ];
        } catch (Exception $e) {
            throw new Exception('Error fetching clients: ' . $e->getMessage());
        }
    }

    /**
     * Get client by ID
     */
    public static function getById($id)
    {
        try {
            $sql = 'SELECT * FROM clients WHERE id = ?';
            return Connection::fetchOne($sql, [(int)$id]);
        } catch (Exception $e) {
            throw new Exception('Error fetching client: ' . $e->getMessage());
        }
    }

    /**
     * Get client by client_id
     */
    public static function getByClientId($clientId)
    {
        try {
            $sql = 'SELECT * FROM clients WHERE client_id = ?';
            return Connection::fetchOne($sql, [$clientId]);
        } catch (Exception $e) {
            throw new Exception('Error fetching client: ' . $e->getMessage());
        }
    }

    /**
     * Create new client
     */
    public static function create($data)
    {
        try {
            Connection::beginTransaction();

            // Validate required fields
            $required = ['client_name', 'app_redirect_uri'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }

            // Validate redirect URI format and scheme
            if (!self::isValidUrl($data['app_redirect_uri'])) {
                throw new Exception('Invalid redirect URI format or unsupported scheme. Only http:// and https:// are allowed.');
            }

            // Generate client ID
            $clientId = self::generateClientId();

            // Default values
            $description = $data['client_description'] ?? null;
            $allowedScopes = $data['allowed_scopes'] ?? 'openid,profile,email';
            $postLogoutUri = $data['post_logout_redirect_uri'] ?? '';
            $userHandlerEndpoint = $data['user_handler_endpoint'] ?? null;
            $apiSecretKey = isset($data['api_secret_key']) && !empty($data['api_secret_key']) ? $data['api_secret_key'] : null;
            $status = $data['status'] ?? self::STATUS_ACTIVE;

            // Validate status
            if (!in_array($status, [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_SUSPENDED])) {
                $status = self::STATUS_ACTIVE;
            }

            // Validate post logout URI if provided
            if (!empty($postLogoutUri) && !self::isValidUrl($postLogoutUri)) {
                throw new Exception('Invalid post logout redirect URI format or unsupported scheme. Only http:// and https:// are allowed.');
            }

            // Validate user handler endpoint URL if in JWT mode
            if (!empty($userHandlerEndpoint) && !empty($apiSecretKey) && !self::isValidUrl($userHandlerEndpoint)) {
                throw new Exception('Invalid user handler endpoint format or unsupported scheme. Only http:// and https:// are allowed.');
            }

            $sql = 'INSERT INTO clients (client_id, client_name, client_description, app_redirect_uri, post_logout_redirect_uri, user_handler_endpoint, api_secret_key, allowed_scopes, status, created_at, updated_at, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)';

            // Get admin email from session, fallback to 'admin' if not available
            $adminEmail = isset($_SESSION['admin_email']) ? $_SESSION['admin_email'] : 'admin';

            $params = [
                $clientId,
                trim($data['client_name']),
                $description,
                trim($data['app_redirect_uri']),
                $postLogoutUri,
                $userHandlerEndpoint,
                $apiSecretKey,
                $allowedScopes,
                $status,
                $adminEmail
            ];

            Connection::query($sql, $params);
            $newId = Connection::lastInsertId();

            Connection::commit();

            // Return created client
            return [
                'id' => $newId,
                'client_id' => $clientId,
                'client_name' => $data['client_name'],
                'client_description' => $description,
                'app_redirect_uri' => $data['app_redirect_uri'],
                'post_logout_redirect_uri' => $postLogoutUri,
                'user_handler_endpoint' => $userHandlerEndpoint,
                'api_secret_key' => $apiSecretKey,
                'allowed_scopes' => $allowedScopes,
                'status' => $status
            ];
        } catch (Exception $e) {
            Connection::rollback();
            throw new Exception('Error creating client: ' . $e->getMessage());
        }
    }

    /**
     * Update existing client
     */
    public static function update($id, $data)
    {
        try {
            $id = (int)$id;

            // Check if client exists
            $existing = self::getById($id);
            if (!$existing) {
                throw new Exception('Client not found');
            }

            // Build update query
            $updateFields = [];
            $params = [];

            $allowedFields = ['client_name', 'client_description', 'app_redirect_uri', 'post_logout_redirect_uri', 'user_handler_endpoint', 'api_secret_key', 'allowed_scopes', 'status'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field]) && $data[$field] !== '') {
                    if ($field === 'app_redirect_uri' && !self::isValidUrl($data[$field])) {
                        throw new Exception('Invalid redirect URI format or unsupported scheme. Only http:// and https:// are allowed.');
                    }
                    if ($field === 'post_logout_redirect_uri' && !empty($data[$field]) && !self::isValidUrl($data[$field])) {
                        throw new Exception('Invalid post logout redirect URI format or unsupported scheme. Only http:// and https:// are allowed.');
                    }
                    if ($field === 'user_handler_endpoint' && !empty($data[$field]) && !empty($data['api_secret_key']) && !self::isValidUrl($data[$field])) {
                        throw new Exception('Invalid user handler endpoint format or unsupported scheme. Only http:// and https:// are allowed.');
                    }
                    if ($field === 'status' && !in_array($data[$field], [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_SUSPENDED])) {
                        throw new Exception('Invalid status value');
                    }

                    $updateFields[] = "$field = ?";
                    $params[] = trim($data[$field]);
                }
            }

            if (empty($updateFields)) {
                throw new Exception('No valid fields to update');
            }

            // Add updated_by field
            $adminEmail = isset($_SESSION['admin_email']) ? $_SESSION['admin_email'] : 'admin';
            $updateFields[] = 'updated_at = NOW()';
            $updateFields[] = 'updated_by = ?';
            $params[] = $adminEmail;
            $params[] = $id;

            $sql = 'UPDATE clients SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
            Connection::query($sql, $params);

            return self::getById($id);
        } catch (Exception $e) {
            throw new Exception('Error updating client: ' . $e->getMessage());
        }
    }

    /**
     * Delete client
     */
    public static function delete($id)
    {
        try {
            $id = (int)$id;

            // Check if client exists
            $existing = self::getById($id);
            if (!$existing) {
                throw new Exception('Client not found');
            }

            // Soft delete by setting status to inactive (recommended)
            // Or hard delete if needed
            $sql = 'DELETE FROM clients WHERE id = ?';
            Connection::query($sql, [$id]);

            return true;
        } catch (Exception $e) {
            throw new Exception('Error deleting client: ' . $e->getMessage());
        }
    }

    /**
     * Get client statistics
     */
    public static function getStatistics()
    {
        try {
            $stats = [];

            // Total clients
            $result = Connection::fetchOne('SELECT COUNT(*) as count FROM clients');
            $stats['total'] = $result ? (int)$result['count'] : 0;

            // Active clients
            $result = Connection::fetchOne('SELECT COUNT(*) as count FROM clients WHERE status = ?', [self::STATUS_ACTIVE]);
            $stats['active'] = $result ? (int)$result['count'] : 0;

            // Inactive clients
            $result = Connection::fetchOne('SELECT COUNT(*) as count FROM clients WHERE status = ?', [self::STATUS_INACTIVE]);
            $stats['inactive'] = $result ? (int)$result['count'] : 0;

            // Suspended clients
            $result = Connection::fetchOne('SELECT COUNT(*) as count FROM clients WHERE status = ?', [self::STATUS_SUSPENDED]);
            $stats['suspended'] = $result ? (int)$result['count'] : 0;

            return $stats;
        } catch (Exception $e) {
            throw new Exception('Error getting client statistics: ' . $e->getMessage());
        }
    }

    /**
     * Validate URL with security checks
     */
    private static function isValidUrl($url)
    {
        // Basic URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Parse URL to check scheme
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['scheme'])) {
            return false;
        }

        // Only allow http and https schemes
        $allowedSchemes = ['http', 'https'];
        if (!in_array(strtolower($parsed['scheme']), $allowedSchemes)) {
            return false;
        }

        // Additional security checks
        if (isset($parsed['host'])) {
            // Prevent localhost bypass (optional - uncomment if needed)
            // $host = strtolower($parsed['host']);
            // if (in_array($host, ['localhost', '127.0.0.1', '::1'])) {
            //     return false;
            // }
        }

        return true;
    }

    /**
     * Generate unique client ID
     */
    private static function generateClientId()
    {
        do {
            $clientId = 'client_' . bin2hex(random_bytes(8));
            $existing = self::getByClientId($clientId);
        } while ($existing);

        return $clientId;
    }

    /**
     * Get available statuses
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_SUSPENDED => 'Suspended'
        ];
    }
}