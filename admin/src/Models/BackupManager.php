<?php

/**
 * admin/src/Models/BackupManager.php
 * Backup and Restore Manager for SSO Admin System
 * Compatible with PHP 7.4.33
 */

namespace SsoAdmin\Models;

use SsoAdmin\Database\Connection;
use PDO;
use Exception;
use ZipArchive;

class BackupManager
{
    /** @var PDO */
    private $db;

    /** @var string */
    private $backupDir;

    public function __construct()
    {
        $this->db = Connection::getPdo();
        $this->backupDir = __DIR__ . '/../../storage/backups';

        // Create backup directory if it doesn't exist
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Create a comprehensive system backup
     * @param array $options Backup options
     * @return array Backup result with file information
     */
    public function createBackup($options = [])
    {
        $timestamp = date('Y-m-d_H-i-s');
        $backupName = $options['name'] ?? 'sso_backup_' . $timestamp;
        $backupFileName = $backupName . '.zip';
        $backupPath = $this->backupDir . '/' . $backupFileName;

        try {
            // Initialize backup data structure
            $backupData = [
                'metadata' => [
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => $options['created_by'] ?? 'system',
                    'version' => '3.0.0',
                    'backup_type' => $options['type'] ?? 'full',
                    'description' => $options['description'] ?? 'Automated system backup'
                ],
                'clients' => [],
                'admin_users' => [],
                'system_config' => [],
                'audit_logs' => []
            ];

            // Export clients data
            if (!isset($options['exclude_clients']) || !$options['exclude_clients']) {
                $backupData['clients'] = $this->exportClients();
            }

            // Export admin users
            if (!isset($options['exclude_users']) || !$options['exclude_users']) {
                $backupData['admin_users'] = $this->exportAdminUsers();
            }

            // Export system configuration
            $backupData['system_config'] = $this->exportSystemConfig();

            // Export audit logs (optional, can be large)
            if (isset($options['include_audit_logs']) && $options['include_audit_logs']) {
                $auditDays = $options['audit_days'] ?? 30;
                $backupData['audit_logs'] = $this->exportAuditLogs($auditDays);
            }

            // Create ZIP file
            $zip = new ZipArchive();
            if ($zip->open($backupPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new Exception('Cannot create backup ZIP file');
            }

            // Add main backup data as JSON
            $zip->addFromString('backup_data.json', json_encode($backupData, JSON_PRETTY_PRINT));

            // Add configuration files
            $this->addConfigFiles($zip);

            // Add documentation
            $zip->addFromString('README.txt', $this->generateBackupReadme($backupData['metadata']));

            $zip->close();

            // Log backup creation
            $this->logBackupActivity('backup_created', $backupFileName, $options['created_by'] ?? 'system');

            return [
                'success' => true,
                'backup_file' => $backupFileName,
                'backup_path' => $backupPath,
                'file_size' => filesize($backupPath),
                'metadata' => $backupData['metadata'],
                'contains' => [
                    'clients' => count($backupData['clients']),
                    'admin_users' => count($backupData['admin_users']),
                    'audit_logs' => count($backupData['audit_logs']),
                    'config_files' => $this->getConfigFileCount()
                ]
            ];
        } catch (Exception $e) {
            // Clean up failed backup file
            if (file_exists($backupPath)) {
                unlink($backupPath);
            }

            throw new Exception('Backup creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Restore system from backup
     * @param string $backupFile
     * @param array $options
     * @return array
     */
    public function restoreFromBackup($backupFile, $options = [])
    {
        $backupPath = $this->backupDir . '/' . $backupFile;

        if (!file_exists($backupPath)) {
            throw new Exception('Backup file not found: ' . $backupFile);
        }

        try {
            // Extract and validate backup
            $zip = new ZipArchive();
            if ($zip->open($backupPath) !== TRUE) {
                throw new Exception('Cannot open backup file');
            }

            // Extract backup data
            $backupDataJson = $zip->getFromName('backup_data.json');
            if ($backupDataJson === false) {
                throw new Exception('Invalid backup file: missing backup_data.json');
            }

            $backupData = json_decode($backupDataJson, true);
            if (!$backupData) {
                throw new Exception('Invalid backup data format');
            }

            // Validate backup compatibility
            $this->validateBackupCompatibility($backupData['metadata']);

            // Start transaction for atomic restore
            $this->db->beginTransaction();

            $restoreResults = [
                'clients' => 0,
                'admin_users' => 0,
                'config_files' => 0,
                'audit_logs' => 0
            ];

            // Restore clients
            if (!isset($options['skip_clients']) || !$options['skip_clients']) {
                $restoreResults['clients'] = $this->restoreClients($backupData['clients'], $options);
            }

            // Restore admin users
            if (!isset($options['skip_users']) || !$options['skip_users']) {
                $restoreResults['admin_users'] = $this->restoreAdminUsers($backupData['admin_users'], $options);
            }

            // Restore system configuration
            if (!isset($options['skip_config']) || !$options['skip_config']) {
                $restoreResults['config_files'] = $this->restoreSystemConfig($backupData['system_config'], $zip);
            }

            // Restore audit logs (optional)
            if (isset($backupData['audit_logs']) && (!isset($options['skip_audit']) || !$options['skip_audit'])) {
                $restoreResults['audit_logs'] = $this->restoreAuditLogs($backupData['audit_logs'], $options);
            }

            $zip->close();
            $this->db->commit();

            // Log restore activity
            $this->logBackupActivity('backup_restored', $backupFile, $options['restored_by'] ?? 'system');

            return [
                'success' => true,
                'restored_items' => $restoreResults,
                'backup_metadata' => $backupData['metadata']
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Restore failed: ' . $e->getMessage());
        }
    }

    /**
     * Export clients data
     */
    private function exportClients()
    {
        $stmt = $this->db->prepare("SELECT * FROM clients ORDER BY id");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Export admin users data
     */
    private function exportAdminUsers()
    {
        $stmt = $this->db->prepare("SELECT * FROM admin_users ORDER BY id");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Export system configuration
     */
    private function exportSystemConfig()
    {
        $config = [];

        // Read main configuration files
        $configFiles = [
            'config.php',
            'admin_config.php'
        ];

        foreach ($configFiles as $file) {
            $configPath = __DIR__ . '/../../../config/' . $file;
            if (file_exists($configPath)) {
                $config[$file] = file_get_contents($configPath);
            }
        }

        return $config;
    }

    /**
     * Export audit logs
     */
    private function exportAuditLogs($days = 30)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM audit_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY created_at DESC
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    /**
     * Restore clients data
     */
    private function restoreClients($clients, $options)
    {
        $count = 0;
        $mode = $options['client_mode'] ?? 'merge'; // merge, replace, skip

        if ($mode === 'replace') {
            // Clear existing clients
            $this->db->exec("DELETE FROM clients");
        }

        foreach ($clients as $client) {
            if ($mode === 'merge') {
                // Check if client exists
                $stmt = $this->db->prepare("SELECT id FROM clients WHERE client_id = ?");
                $stmt->execute([$client['client_id']]);
                if ($stmt->fetch()) {
                    continue; // Skip existing clients
                }
            }

            // Insert client
            $stmt = $this->db->prepare("
                INSERT INTO clients (
                    client_id, client_name, client_description, app_redirect_uri,
                    post_logout_redirect_uri, user_handler_endpoint, api_secret_key,
                    allowed_scopes, status, created_by, updated_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $client['client_id'],
                $client['client_name'],
                $client['client_description'],
                $client['app_redirect_uri'],
                $client['post_logout_redirect_uri'],
                $client['user_handler_endpoint'],
                $client['api_secret_key'],
                $client['allowed_scopes'],
                $client['status'],
                $client['created_by'],
                $client['updated_by']
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Restore admin users
     */
    private function restoreAdminUsers($users, $options)
    {
        $count = 0;
        $mode = $options['user_mode'] ?? 'merge';

        if ($mode === 'replace') {
            $this->db->exec("DELETE FROM admin_users");
        }

        foreach ($users as $user) {
            if ($mode === 'merge') {
                $stmt = $this->db->prepare("SELECT id FROM admin_users WHERE email = ?");
                $stmt->execute([$user['email']]);
                if ($stmt->fetch()) {
                    continue;
                }
            }

            $stmt = $this->db->prepare("
                INSERT INTO admin_users (email, name, role, status)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $user['email'],
                $user['name'],
                $user['role'],
                $user['status']
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Restore system configuration
     */
    private function restoreSystemConfig($config, $zip)
    {
        $count = 0;
        // This would restore configuration files
        // Implementation depends on security requirements
        return $count;
    }

    /**
     * Restore audit logs
     */
    private function restoreAuditLogs($logs, $options)
    {
        $count = 0;

        foreach ($logs as $log) {
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (
                    admin_email, action, resource_type, resource_id,
                    old_values, new_values, ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $log['admin_email'],
                $log['action'],
                $log['resource_type'],
                $log['resource_id'],
                $log['old_values'],
                $log['new_values'],
                $log['ip_address'],
                $log['user_agent'],
                $log['created_at']
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Get list of available backups
     */
    public function getBackupList()
    {
        $backups = [];
        $files = glob($this->backupDir . '/*.zip');

        foreach ($files as $file) {
            $filename = basename($file);
            $backups[] = [
                'filename' => $filename,
                'size' => filesize($file),
                'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                'size_human' => $this->formatFileSize(filesize($file))
            ];
        }

        // Sort by creation time (newest first)
        usort($backups, function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        return $backups;
    }

    /**
     * Delete a backup file
     */
    public function deleteBackup($filename, $adminEmail = 'unknown')
    {
        $backupPath = $this->backupDir . '/' . $filename;

        if (!file_exists($backupPath)) {
            throw new Exception('Backup file not found');
        }

        if (!unlink($backupPath)) {
            throw new Exception('Failed to delete backup file');
        }

        $this->logBackupActivity('backup_deleted', $filename, $adminEmail);
        return true;
    }

    /**
     * Validate backup compatibility
     */
    private function validateBackupCompatibility($metadata)
    {
        // Add version compatibility checks here
        return true;
    }

    /**
     * Add configuration files to backup
     */
    private function addConfigFiles($zip)
    {
        // Add selected config files (excluding sensitive data)
        $configDir = __DIR__ . '/../../../config';

        if (file_exists($configDir . '/admin_config.php')) {
            $zip->addFile($configDir . '/admin_config.php', 'config/admin_config.php');
        }
    }

    /**
     * Generate backup README
     */
    private function generateBackupReadme($metadata)
    {
        return "SSO Authentication System Backup\n" .
            "===================================\n\n" .
            "Created: {$metadata['created_at']}\n" .
            "Created by: {$metadata['created_by']}\n" .
            "Version: {$metadata['version']}\n" .
            "Type: {$metadata['backup_type']}\n" .
            "Description: {$metadata['description']}\n\n" .
            "This backup contains client configurations, admin users, and system settings.\n" .
            "Use the SSO-Authen Admin Panel to restore this backup.\n";
    }

    /**
     * Get config file count
     */
    private function getConfigFileCount()
    {
        return 1; // Simplified for now
    }

    /**
     * Format file size
     */
    private function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Log backup activity
     */
    private function logBackupActivity($action, $filename, $adminEmail)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (admin_email, action, resource_type, resource_id, ip_address, user_agent, created_at)
                VALUES (?, ?, 'backup', ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $adminEmail,
                $action,
                $filename,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? 'System'
            ]);
        } catch (Exception $e) {
            // Log silently fails
            error_log('Failed to log backup activity: ' . $e->getMessage());
        }
    }
}
