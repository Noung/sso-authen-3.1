# Backup & Restore System Documentation

## Overview

The SSO Admin Panel includes a comprehensive backup and restore system that allows administrators to:

- Create full system backups including client configurations, admin users, and audit logs
- Download and store backups securely
- Restore system state from previous backups
- Automate backup creation with scheduling

## Features

### 1. Manual Backup Creation

- **Full System Backup**: Complete backup of all data and configurations
- **Selective Backup**: Choose what to include (clients, users, audit logs)
- **Custom Naming**: Provide meaningful names and descriptions
- **Audit Log Integration**: Optionally include historical audit data

### 2. Backup Management

- **Download Backups**: Secure download of backup files
- **Delete Old Backups**: Remove unnecessary backup files
- **Backup List**: View all available backups with metadata
- **File Size Information**: Monitor backup storage usage

### 3. System Restore

- **Flexible Restore Options**: Choose merge, replace, or skip modes
- **Selective Restore**: Restore only specific components
- **Data Validation**: Ensure backup compatibility before restore
- **Transaction Safety**: Atomic restore operations with rollback

### 4. Automated Backups

- **Scheduled Backups**: Run via cron jobs or task scheduler
- **Retention Policies**: Automatic cleanup of old backups
- **Multiple Frequencies**: Daily, weekly, monthly backup types
- **Error Logging**: Comprehensive logging for troubleshooting

## File Structure

```
admin/
├── src/Models/BackupManager.php      # Core backup functionality
├── public/backup-restore.html        # Web interface
├── storage/
│   ├── backups/                      # Backup file storage
│   ├── backup_automation.log         # Automation logs
│   └── backup_errors.log             # Error logs
├── automated_backup.php              # Automated backup script
└── public/index.php                  # API endpoints
```

## API Endpoints

### Create Backup

```
POST /api/backup/create
Content-Type: application/json

{
    "name": "weekly_backup_2024",
    "description": "Weekly system backup",
    "type": "full",
    "exclude_clients": false,
    "exclude_users": false,
    "include_audit_logs": true,
    "audit_days": 30
}
```

### List Backups

```
GET /api/backup/list

Response:
{
    "success": true,
    "data": [
        {
            "filename": "sso_backup_2024-09-22_14-30-15.zip",
            "size": 1024768,
            "created_at": "2024-09-22 14:30:15",
            "size_human": "1.02 MB"
        }
    ]
}
```

### Download Backup

```
GET /api/backup/download?file=backup_filename.zip
```

### Delete Backup

```
DELETE /api/backup/delete?file=backup_filename.zip
```

### Restore Backup

```
POST /api/backup/restore
Content-Type: application/json

{
    "filename": "backup_filename.zip",
    "client_mode": "merge",
    "user_mode": "merge",
    "skip_clients": false,
    "skip_users": false,
    "skip_config": false,
    "skip_audit": true
}
```

## Backup Contents

Each backup ZIP file contains:

### 1. backup_data.json

Main data file containing:

- **Metadata**: Creation time, creator, version, description
- **Clients**: All client application configurations
- **Admin Users**: Administrator account information
- **System Config**: System configuration data
- **Audit Logs**: Historical activity logs (optional)

### 2. Configuration Files

- `config/admin_config.php` - Admin panel configuration
- Other system configuration files (as applicable)

### 3. README.txt

Documentation about the backup contents and restore instructions

## Automation Setup

### Windows Task Scheduler

1. Open Task Scheduler
2. Create Basic Task
3. Configure trigger (Daily/Weekly/Monthly)
4. Set action to start program: `php.exe`
5. Add arguments: `C:\path\to\admin\automated_backup.php daily`

### Linux Cron Jobs

Add to crontab (`crontab -e`):

```bash
# Daily backup at 2 AM
0 2 * * * /usr/bin/php /path/to/admin/automated_backup.php daily

# Weekly backup on Sunday at 3 AM
0 3 * * 0 /usr/bin/php /path/to/admin/automated_backup.php weekly

# Monthly backup on 1st day at 4 AM
0 4 1 * * /usr/bin/php /path/to/admin/automated_backup.php monthly
```

## Configuration Options

### Backup Types

- **daily**: Quick backups with 7-day retention
- **weekly**: Full backups with 30-day retention
- **monthly**: Complete backups with 1-year retention

### Restore Modes

- **merge**: Keep existing data, add new from backup
- **replace**: Overwrite existing data with backup data
- **skip**: Don't restore this component

## Security Considerations

### 1. Access Control

- All backup operations require admin authentication
- API endpoints are protected with session validation
- File downloads use secure path validation

### 2. Data Protection

- Backup files contain sensitive configuration data
- Store backups in secure locations with appropriate permissions
- Consider encrypting backup files for additional security

### 3. File Validation

- Backup files are validated before restoration
- ZIP structure is verified to prevent malicious uploads
- Transaction rollback ensures system integrity

## Monitoring and Troubleshooting

### Log Files

- `storage/backup_automation.log` - Automation activity logs
- `storage/backup_errors.log` - Error logs
- Audit logs track all backup/restore activities

### Common Issues

1. **Permission Errors**: Ensure web server has write access to storage directory
2. **Memory Limits**: Large backups may require increased PHP memory limits
3. **Timeout Issues**: Long restore operations may need increased execution time limits

### Best Practices

1. **Regular Testing**: Periodically test backup restoration
2. **Storage Management**: Monitor backup directory size
3. **Retention Policies**: Configure appropriate retention based on needs
4. **Off-site Storage**: Consider copying critical backups to external storage

## Usage Examples

### Creating a Manual Backup

1. Navigate to Backup & Restore page
2. Click "Create New Backup"
3. Configure backup options
4. Click "Create Backup"
5. Download or manage the created backup

### Restoring from Backup

1. Select backup from the list
2. Click "Restore" button
3. Configure restore options
4. Confirm restoration
5. System will be restored to backup state

### Setting Up Automation

1. Configure automated_backup.php script
2. Set up scheduled task or cron job
3. Monitor logs for successful execution
4. Adjust retention policies as needed

## Support

For backup system support:

1. Check log files for specific error messages
2. Verify file permissions and disk space
3. Test backup creation manually before automation
4. Ensure database connectivity for backup operations

This backup system provides enterprise-grade data protection for your SSO authentication system, ensuring business continuity and disaster recovery capabilities.
