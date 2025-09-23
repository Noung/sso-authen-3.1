<?php

/**
 * admin/src/Models/MockDataGenerator.php
 * Generates realistic mock authentication and usage data for demo purposes
 * Compatible with PHP 7.4.33
 */

namespace SsoAdmin\Models;

use SsoAdmin\Database\Connection;
use PDO;
use Exception;

class MockDataGenerator
{
    /** @var PDO */
    private $db;

    public function __construct()
    {
        $this->db = Connection::getPdo();
    }

    /**
     * Generate comprehensive mock authentication and usage data
     * @param int $days Number of days to generate data for
     * @return array Summary of generated data
     */
    public function generateMockData($days = 30)
    {
        $summary = [
            'generated_at' => date('Y-m-d H:i:s'),
            'period_days' => $days,
            'oidc_authentications' => 0, // NEW: Real user OIDC logins
            'authentication_logs' => 0,
            'admin_activities' => 0,
            'jwt_views' => 0,
            'client_modifications' => 0
        ];

        try {
            // Clear existing mock data (keeping original audit logs)
            $this->clearMockData();

            // Generate mock OIDC user authentications (NEW!)
            $summary['oidc_authentications'] = $this->generateOidcAuthentications($days);

            // Generate mock authentication logs
            $summary['authentication_logs'] = $this->generateAuthenticationLogs($days);

            // Generate mock admin activities
            $summary['admin_activities'] = $this->generateAdminActivities($days);

            // Generate mock JWT secret views
            $summary['jwt_views'] = $this->generateJwtSecretViews($days);

            // Generate mock client management activities
            $summary['client_modifications'] = $this->generateClientManagementActivities($days);

            return $summary;
        } catch (Exception $e) {
            throw new Exception('Failed to generate mock data: ' . $e->getMessage());
        }
    }

    /**
     * Generate realistic OIDC user authentication logs (NEW!)
     * These represent actual users logging in through client applications
     */
    private function generateOidcAuthentications($days)
    {
        $count = 0;
        $clients = $this->getActiveClients();

        // Realistic user emails for different user types
        $userEmails = [
            'student01@psu.ac.th',
            'student02@psu.ac.th',
            'student03@psu.ac.th',
            'student04@psu.ac.th',
            'student05@psu.ac.th',
            'student06@psu.ac.th',
            'faculty01@psu.ac.th',
            'faculty02@psu.ac.th',
            'faculty03@psu.ac.th',
            'staff01@psu.ac.th',
            'staff02@psu.ac.th',
            'staff03@psu.ac.th'
        ];

        for ($day = 0; $day < $days; $day++) {
            $date = date('Y-m-d', strtotime("-{$day} days"));

            // Generate 15-80 user authentication attempts per day (more realistic volume)
            $dailyOidcAuths = rand(15, 80);

            for ($i = 0; $i < $dailyOidcAuths; $i++) {
                $client = $clients[array_rand($clients)];
                $user = $userEmails[array_rand($userEmails)];
                $success = rand(1, 100) <= 88; // 88% success rate (higher than admin attempts)

                // Business hours pattern (8 AM - 6 PM with peak at 10-11 AM and 2-3 PM)
                $hour = $this->generateBusinessHour();
                $timestamp = $date . ' ' . sprintf(
                    '%02d:%02d:%02d',
                    $hour,
                    rand(0, 59),
                    rand(0, 59)
                );

                // Different actions for OIDC flow
                if ($success) {
                    // Successful OIDC authentication process
                    $actions = [
                        'oidc_login_initiated' => 0.3, // 30% - user starts login
                        'oidc_auth_success' => 0.7     // 70% - user completes login
                    ];
                    $action = $this->weightedRandomSelect($actions);
                } else {
                    $action = 'oidc_auth_failed';
                }

                $this->insertAuditLog([
                    'admin_email' => $user, // In OIDC context, this represents the user
                    'action' => $action,
                    'resource_type' => 'authentication',
                    'resource_id' => $client['id'],
                    'description' => $this->getOidcActionDescription($action, $client['client_name'], $user),
                    'ip_address' => $this->generateRandomIP(),
                    'user_agent' => $this->generateRandomUserAgent(),
                    'created_at' => $timestamp
                ]);

                $count++;
            }
        }

        return $count;
    }

    /**
     * Generate business hour with peak patterns
     */
    private function generateBusinessHour()
    {
        $weights = [
            8 => 5,
            9 => 15,
            10 => 25,
            11 => 25,  // Morning peak
            12 => 10,
            13 => 8,
            14 => 20,
            15 => 20, // Afternoon peak 
            16 => 15,
            17 => 10,
            18 => 5            // Evening decline
        ];

        return $this->weightedRandomSelect($weights);
    }

    /**
     * Select random item based on weights
     */
    private function weightedRandomSelect($weights)
    {
        $total = array_sum($weights);
        $random = rand(1, $total);

        $currentWeight = 0;
        foreach ($weights as $item => $weight) {
            $currentWeight += $weight;
            if ($random <= $currentWeight) {
                return $item;
            }
        }

        return array_key_first($weights); // Fallback
    }

    /**
     * Get OIDC action description
     */
    private function getOidcActionDescription($action, $clientName, $userEmail)
    {
        switch ($action) {
            case 'oidc_login_initiated':
                return "User {$userEmail} initiated OIDC login for {$clientName}";
            case 'oidc_auth_success':
                return "User {$userEmail} successfully authenticated via OIDC for {$clientName}";
            case 'oidc_auth_failed':
                return "User {$userEmail} failed OIDC authentication for {$clientName}";
            default:
                return "OIDC action {$action} for {$clientName}";
        }
    }

    /**
     * Generate realistic authentication logs
     */
    private function generateAuthenticationLogs($days)
    {
        $count = 0;
        $clients = $this->getActiveClients();
        $adminEmails = ['admin@psu.ac.th', 'manager@psu.ac.th', 'developer@psu.ac.th'];

        for ($day = 0; $day < $days; $day++) {
            $date = date('Y-m-d', strtotime("-{$day} days"));

            // Generate 5-25 authentication attempts per day
            $dailyAuths = rand(5, 25);

            for ($i = 0; $i < $dailyAuths; $i++) {
                $client = $clients[array_rand($clients)];
                $admin = $adminEmails[array_rand($adminEmails)];
                $success = rand(1, 100) <= 85; // 85% success rate

                $timestamp = $date . ' ' . sprintf(
                    '%02d:%02d:%02d',
                    rand(8, 23),
                    rand(0, 59),
                    rand(0, 59)
                );

                $action = $success ? 'auth_success' : 'auth_failed';
                $description = $success ?
                    "Successful authentication for client {$client['client_name']}" :
                    "Failed authentication attempt for client {$client['client_name']}";

                $this->insertAuditLog([
                    'admin_email' => $admin,
                    'action' => $action,
                    'resource_type' => 'authentication',
                    'resource_id' => $client['id'],
                    'description' => $description,
                    'ip_address' => $this->generateRandomIP(),
                    'user_agent' => $this->generateRandomUserAgent(),
                    'created_at' => $timestamp
                ]);

                $count++;
            }
        }

        return $count;
    }

    /**
     * Generate mock admin activities
     */
    private function generateAdminActivities($days)
    {
        $count = 0;
        $clients = $this->getActiveClients();
        $adminEmails = ['admin@psu.ac.th', 'manager@psu.ac.th', 'developer@psu.ac.th'];

        $activities = [
            'admin_login' => 'Admin logged into panel',
            'client_viewed' => 'Viewed client details',
            'dashboard_accessed' => 'Accessed admin dashboard',
            'statistics_viewed' => 'Viewed usage statistics',
            'config_checked' => 'Checked system configuration'
        ];

        for ($day = 0; $day < $days; $day++) {
            $date = date('Y-m-d', strtotime("-{$day} days"));

            // Generate 3-15 admin activities per day
            $dailyActivities = rand(3, 15);

            for ($i = 0; $i < $dailyActivities; $i++) {
                $admin = $adminEmails[array_rand($adminEmails)];
                $action = array_rand($activities);
                $client = null;

                // Some activities are client-specific
                if (in_array($action, ['client_viewed', 'jwt_secret_viewed'])) {
                    $client = $clients[array_rand($clients)];
                }

                $timestamp = $date . ' ' . sprintf(
                    '%02d:%02d:%02d',
                    rand(8, 18),
                    rand(0, 59),
                    rand(0, 59)
                );

                $this->insertAuditLog([
                    'admin_email' => $admin,
                    'action' => $action,
                    'resource_type' => $client ? 'client' : 'system',
                    'resource_id' => $client ? $client['id'] : null,
                    'description' => $activities[$action] . ($client ? " for {$client['client_name']}" : ''),
                    'ip_address' => $this->generateRandomIP(),
                    'user_agent' => $this->generateRandomUserAgent(),
                    'created_at' => $timestamp
                ]);

                $count++;
            }
        }

        return $count;
    }

    /**
     * Generate mock JWT secret views
     */
    private function generateJwtSecretViews($days)
    {
        $count = 0;
        $clients = $this->getActiveClients();
        $adminEmails = ['admin@psu.ac.th', 'developer@psu.ac.th']; // Only these admins view JWT secrets

        for ($day = 0; $day < $days; $day++) {
            $date = date('Y-m-d', strtotime("-{$day} days"));

            // Generate 0-5 JWT views per day (less frequent)
            $dailyViews = rand(0, 5);

            for ($i = 0; $i < $dailyViews; $i++) {
                $client = $clients[array_rand($clients)];
                $admin = $adminEmails[array_rand($adminEmails)];

                $timestamp = $date . ' ' . sprintf(
                    '%02d:%02d:%02d',
                    rand(9, 17),
                    rand(0, 59),
                    rand(0, 59)
                );

                $this->insertAuditLog([
                    'admin_email' => $admin,
                    'action' => 'jwt_secret_viewed',
                    'resource_type' => 'client',
                    'resource_id' => $client['id'],
                    'description' => "Viewed JWT secret for client {$client['client_name']}",
                    'ip_address' => $this->generateRandomIP(),
                    'user_agent' => $this->generateRandomUserAgent(),
                    'created_at' => $timestamp
                ]);

                $count++;
            }
        }

        return $count;
    }

    /**
     * Generate mock client management activities
     */
    private function generateClientManagementActivities($days)
    {
        $count = 0;
        $clients = $this->getActiveClients();
        $adminEmails = ['admin@psu.ac.th', 'manager@psu.ac.th'];

        $activities = [
            'client_updated' => 'Updated client configuration',
            'client_status_changed' => 'Changed client status',
            'client_created' => 'Created new client',
            'client_deleted' => 'Deleted client'
        ];

        for ($day = 0; $day < $days; $day++) {
            $date = date('Y-m-d', strtotime("-{$day} days"));

            // Generate 0-3 management activities per day (less frequent)
            $dailyMgmt = rand(0, 3);

            for ($i = 0; $i < $dailyMgmt; $i++) {
                $client = $clients[array_rand($clients)];
                $admin = $adminEmails[array_rand($adminEmails)];
                $action = array_rand($activities);

                $timestamp = $date . ' ' . sprintf(
                    '%02d:%02d:%02d',
                    rand(10, 16),
                    rand(0, 59),
                    rand(0, 59)
                );

                $this->insertAuditLog([
                    'admin_email' => $admin,
                    'action' => $action,
                    'resource_type' => 'client',
                    'resource_id' => $client['id'],
                    'description' => $activities[$action] . " {$client['client_name']}",
                    'ip_address' => $this->generateRandomIP(),
                    'user_agent' => $this->generateRandomUserAgent(),
                    'created_at' => $timestamp
                ]);

                $count++;
            }
        }

        return $count;
    }

    /**
     * Get active clients from database
     */
    private function getActiveClients()
    {
        $stmt = $this->db->prepare("SELECT * FROM clients WHERE status = 'active'");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Insert audit log entry
     */
    private function insertAuditLog($data)
    {
        $sql = "INSERT INTO audit_logs (admin_email, action, resource_type, resource_id, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['admin_email'],
            $data['action'],
            $data['resource_type'],
            $data['resource_id'],
            $data['ip_address'],
            $data['user_agent'],
            $data['created_at']
        ]);
    }

    /**
     * Clear existing mock data (keep original admin activities)
     */
    private function clearMockData()
    {
        // Clear mock authentication and demo data
        $mockActions = [
            'auth_success',
            'auth_failed',
            'admin_login',
            'client_viewed',
            'dashboard_accessed',
            'statistics_viewed',
            'config_checked'
        ];

        $placeholders = str_repeat('?,', count($mockActions) - 1) . '?';
        $sql = "DELETE FROM audit_logs WHERE action IN ($placeholders) AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($mockActions);
    }

    /**
     * Generate random IP address
     */
    private function generateRandomIP()
    {
        $ips = [
            '192.168.1.' . rand(10, 250),
            '10.0.0.' . rand(10, 250),
            '172.16.0.' . rand(10, 250),
            '203.154.' . rand(1, 255) . '.' . rand(1, 255), // Thai IP range
            '125.24.' . rand(1, 255) . '.' . rand(1, 255)    // Thai IP range
        ];

        return $ips[array_rand($ips)];
    }

    /**
     * Generate random user agent
     */
    private function generateRandomUserAgent()
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/121.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ];

        return $userAgents[array_rand($userAgents)];
    }

    /**
     * Get generation statistics
     */
    public function getGenerationStats()
    {
        $stats = [];

        // Count different types of mock data
        $mockActions = [
            'OIDC User Authentications' => ['oidc_login_initiated', 'oidc_auth_success', 'oidc_auth_failed'],
            'Authentication Logs' => ['auth_success', 'auth_failed'],
            'Admin Activities' => ['admin_login', 'client_viewed', 'dashboard_accessed', 'statistics_viewed'],
            'JWT Secret Views' => ['jwt_secret_viewed'],
            'Client Management' => ['client_updated', 'client_status_changed', 'client_created', 'client_deleted']
        ];

        foreach ($mockActions as $category => $actions) {
            $placeholders = str_repeat('?,', count($actions) - 1) . '?';
            $sql = "SELECT COUNT(*) as count FROM audit_logs WHERE action IN ($placeholders) AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($actions);
            $result = $stmt->fetch();

            $stats[$category] = (int)$result['count'];
        }

        return $stats;
    }
}
