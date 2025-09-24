<?php

namespace SsoAdmin\Controllers;

use SsoAdmin\Database\Connection;
use SsoAdmin\Models\Client;
use Exception;

/**
 * Client Controller - Full CRUD Operations  
 * Compatible with PHP 7.4.33
 */
class ClientController
{
    /**
     * Get all clients with pagination and filters
     */
    public function getAll($request, $response)
    {
        try {
            $queryParams = $request->getQueryParams();
            $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
            $perPage = isset($queryParams['per_page']) ? (int)$queryParams['per_page'] : 10;
            $search = isset($queryParams['search']) ? trim($queryParams['search']) : '';
            $status = isset($queryParams['status']) ? trim($queryParams['status']) : '';

            $result = Client::getAll($page, $perPage, $search, $status);

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
     * Get client by ID
     */
    public function getById($request, $response, $args)
    {
        try {
            $id = (int)$args['id'];
            $client = Client::getById($id);

            if (!$client) {
                $body = $response->getBody();
                $body->write(json_encode([
                    'success' => false,
                    'message' => 'Client not found'
                ]));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $body = $response->getBody();
            $body->write(json_encode([
                'success' => true,
                'data' => $client
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
     * Create new client
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

            $client = Client::create($data);

            // Log activity
            $this->logActivity('client_created', "Created client: {$data['client_name']}", $client['id']);

            $body = $response->getBody();
            $body->write(json_encode([
                'success' => true,
                'data' => $client,
                'message' => 'Client created successfully'
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
     * Update existing client
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

            $client = Client::update($id, $data);

            // Log activity
            $this->logActivity('client_updated', "Updated client: {$client['client_name']}", $id);

            // Remove sensitive data
            // unset($client['client_secret']); // client_secret field has been removed

            $body = $response->getBody();
            $body->write(json_encode([
                'success' => true,
                'data' => $client,
                'message' => 'Client updated successfully'
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
     * Delete client
     */
    public function delete($request, $response, $args)
    {
        try {
            $id = (int)$args['id'];

            // Get client name for logging
            $client = Client::getById($id);
            if (!$client) {
                $body = $response->getBody();
                $body->write(json_encode([
                    'success' => false,
                    'message' => 'Client not found'
                ]));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            Client::delete($id);

            // Log activity
            $this->logActivity('client_deleted', "Deleted client: {$client['client_name']}", $id);

            $body = $response->getBody();
            $body->write(json_encode([
                'success' => true,
                'message' => 'Client deleted successfully'
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
     * Get client statistics
     */
    public function getStatistics($request, $response)
    {
        try {
            $stats = Client::getStatistics();

            $body = $response->getBody();
            $body->write(json_encode([
                'success' => true,
                'data' => $stats
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
     * Toggle client status (active/inactive)
     */
    public function toggleStatus($request, $response, $args)
    {
        try {
            $id = (int)$args['id'];

            $client = Client::getById($id);
            if (!$client) {
                $body = $response->getBody();
                $body->write(json_encode([
                    'success' => false,
                    'message' => 'Client not found'
                ]));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Toggle status
            $newStatus = $client['status'] === 'active' ? 'inactive' : 'active';
            $updatedClient = Client::update($id, ['status' => $newStatus]);

            // Log activity
            $this->logActivity('client_status_changed', "Changed status of client: {$client['client_name']} to {$newStatus}", $id);



            $body = $response->getBody();
            $body->write(json_encode([
                'success' => true,
                'data' => $updatedClient,
                'message' => "Client status changed to {$newStatus}"
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
            $statuses = Client::getAvailableStatuses();

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
    private function logActivity($action, $description, $clientId = null)
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
                'client',
                $clientId,
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
