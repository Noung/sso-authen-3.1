<?php

/**
 * automated_backup.php
 * Automated backup script for SSO Admin System
 * Can be run via cron job or manually
 * 
 * Usage:
 * php automated_backup.php [daily|weekly|monthly]
 */

// Change to script directory
$scriptDir = dirname(__FILE__);
chdir($scriptDir);

// Include required files
require_once __DIR__ . '/src/Database/Connection.php';
require_once __DIR__ . '/src/Models/BackupManager.php';

// Setup error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/storage/backup_errors.log');

/**
 * Log function for automated backups
 */
function logMessage($message, $level = 'INFO')
{
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

    // Log to file
    file_put_contents(__DIR__ . '/storage/backup_automation.log', $logMessage, FILE_APPEND | LOCK_EX);

    // Also output to console if running from command line
    if (php_sapi_name() === 'cli') {
        echo $logMessage;
    }
}

/**
 * Clean old backups based on retention policy
 */
function cleanOldBackups($retentionDays = 30)
{
    $backupDir = __DIR__ . '/storage/backups';
    $files = glob($backupDir . '/*.zip');
    $now = time();
    $deleted = 0;

    foreach ($files as $file) {
        $fileAge = ($now - filemtime($file)) / (60 * 60 * 24); // Age in days

        if ($fileAge > $retentionDays) {
            if (unlink($file)) {
                logMessage("Deleted old backup: " . basename($file));
                $deleted++;
            } else {
                logMessage("Failed to delete old backup: " . basename($file), 'ERROR');
            }
        }
    }

    return $deleted;
}

/**
 * Main backup function
 */
function runAutomatedBackup($type = 'daily')
{
    try {
        logMessage("Starting automated backup (type: {$type})");

        // Configure backup options based on type
        $options = [
            'name' => "auto_{$type}_" . date('Y-m-d_H-i-s'),
            'description' => "Automated {$type} backup",
            'type' => 'full',
            'created_by' => 'automation',
            'exclude_clients' => false,
            'exclude_users' => false,
            'include_audit_logs' => ($type === 'monthly'), // Only include audit logs for monthly backups
            'audit_days' => 30
        ];

        // Create backup
        $backupManager = new SsoAdmin\Models\BackupManager();
        $result = $backupManager->createBackup($options);

        if ($result['success']) {
            logMessage("Backup created successfully: {$result['backup_file']} ({$result['metadata']['created_at']})");
            logMessage("Backup contains: {$result['contains']['clients']} clients, {$result['contains']['admin_users']} admin users");

            // Clean old backups based on type
            $retentionDays = [
                'daily' => 7,     // Keep daily backups for 7 days
                'weekly' => 30,   // Keep weekly backups for 30 days  
                'monthly' => 365  // Keep monthly backups for 1 year
            ];

            $deleted = cleanOldBackups($retentionDays[$type] ?? 30);
            logMessage("Cleaned {$deleted} old backup files (retention: {$retentionDays[$type]} days)");

            return true;
        } else {
            logMessage("Backup creation failed", 'ERROR');
            return false;
        }
    } catch (Exception $e) {
        logMessage("Backup automation error: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    // Command line execution
    $backupType = isset($argv[1]) ? $argv[1] : 'daily';

    if (!in_array($backupType, ['daily', 'weekly', 'monthly'])) {
        echo "Usage: php automated_backup.php [daily|weekly|monthly]" . PHP_EOL;
        exit(1);
    }

    $success = runAutomatedBackup($backupType);
    exit($success ? 0 : 1);
} else {
    // Web execution (for testing)
    $backupType = $_GET['type'] ?? 'daily';

    if (!in_array($backupType, ['daily', 'weekly', 'monthly'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid backup type']);
        exit;
    }

    header('Content-Type: application/json');

    $success = runAutomatedBackup($backupType);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Automated backup completed successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Automated backup failed']);
    }
}
