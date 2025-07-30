# Database Backup System

A comprehensive PHP-based database backup solution for shared hosting environments where `mysqldump` is disabled.

## Features

### ✅ **Security**
- Admin authentication required
- Session-based access control
- Secure file handling

### ✅ **Performance**
- Chunked data processing (1000 rows per chunk)
- Memory management and garbage collection
- Configurable memory limits
- Progress tracking and logging

### ✅ **File Management**
- Automatic backup directory creation
- GZIP compression (Level 9)
- Automatic cleanup of old backups (30 days)
- Backup file verification

### ✅ **User Interface**
- Modern, responsive design with Tailwind CSS
- Real-time progress indication
- SweetAlert2 notifications
- Backup file listing and management

### ✅ **Error Handling**
- Comprehensive error logging
- Graceful error recovery
- User-friendly error messages

## Files Overview

### **Core Files**
- `backup.php` - Main backup engine and API endpoints
- `call_backup.php` - Full-featured backup management interface
- `backup_interface.php` - Simple backup interface

### **Configuration**
- `Connections/paymaster.php` - Database connection settings
- `BACKUP_README.md` - This documentation

## Installation

1. **Upload Files**: Upload all backup files to your web server
2. **Set Permissions**: Ensure the `backup/` directory is writable (755)
3. **Configure Database**: Update database credentials in `Connections/paymaster.php`
4. **Test Access**: Navigate to `backup_interface.php` to test the system

## Usage

### **Quick Backup Creation**
1. Navigate to `backup_interface.php`
2. Click "Create New Backup"
3. Wait for the backup to complete
4. Download the backup file

### **Full Backup Management**
1. Navigate to `call_backup.php`
2. View existing backups
3. Create new backups
4. Download or delete existing backups

### **Direct API Access**
```php
// Create backup via AJAX
$.post('backup.php', {action: 'create_backup'}, function(response) {
    console.log(response);
});

// Download backup
window.location.href = 'backup.php?action=download&file=backup_2024-01-01_12-00-00.sql.gz';
```

## Configuration Options

### **Backup Settings** (in `backup.php`)
```php
define('BACKUP_DIR', 'backup/');           // Backup directory
define('MAX_BACKUP_AGE', 30);              // Days to keep backups
define('MAX_MEMORY_USAGE', '256M');        // Memory limit
define('CHUNK_SIZE', 1000);                // Rows per chunk
```

### **Security Settings**
- Admin role required: `$_SESSION['role'] == 'Admin'`
- Session validation: `$_SESSION['SESS_MEMBER_ID']`

## Backup Process

1. **Authentication Check**: Verifies admin access
2. **Directory Setup**: Creates backup directory if needed
3. **Old Backup Cleanup**: Removes backups older than 30 days
4. **Table Processing**: Processes each table in chunks
5. **File Creation**: Generates SQL backup file
6. **Compression**: Compresses with GZIP (Level 9)
7. **Verification**: Validates backup file integrity
8. **Cleanup**: Removes temporary files

## File Structure

```
backup/
├── backup_2024-01-01_12-00-00.sql.gz    # Compressed backup files
├── backup_2024-01-02_12-00-00.sql.gz
├── backup_log.txt                        # Backup operation logs
└── ...
```

## Troubleshooting

### **Common Issues**

#### **Permission Denied**
```bash
# Fix directory permissions
chmod 755 backup/
chmod 644 backup/*.sql.gz
```

#### **Memory Limit Exceeded**
- Reduce `CHUNK_SIZE` in configuration
- Increase `MAX_MEMORY_USAGE`
- Process smaller tables first

#### **Timeout Issues**
- Increase `set_time_limit()` in backup.php
- Check server timeout settings
- Use smaller chunk sizes

#### **Download Not Working**
- Check file permissions
- Verify file exists before download
- Clear browser cache
- Check server output buffering

### **Error Logs**
Check `backup/backup_log.txt` for detailed error information.

## Security Considerations

### **Access Control**
- Only admin users can access backup functionality
- Session-based authentication required
- IP restrictions can be added if needed

### **File Security**
- Backup files stored outside web root (recommended)
- Compressed files reduce storage requirements
- Automatic cleanup prevents disk space issues

### **Data Protection**
- Database credentials stored securely
- Backup files contain sensitive data
- Implement additional encryption if needed

## Performance Optimization

### **For Large Databases**
1. **Increase Chunk Size**: Set `CHUNK_SIZE` to 2000-5000
2. **Memory Management**: Monitor memory usage
3. **Scheduled Backups**: Use cron jobs for off-peak hours
4. **Selective Backup**: Backup only critical tables

### **For Small Databases**
1. **Reduce Chunk Size**: Set `CHUNK_SIZE` to 500
2. **Faster Processing**: Smaller chunks = faster completion
3. **Frequent Backups**: More frequent, smaller backups

## Monitoring

### **Backup Health Checks**
- File size validation
- Compression ratio monitoring
- Backup frequency tracking
- Error rate monitoring

### **Log Analysis**
```bash
# Check recent backup activity
tail -f backup/backup_log.txt

# Count successful backups
grep "completed successfully" backup/backup_log.txt | wc -l
```

## Integration

### **With Existing Systems**
- Can be integrated into admin dashboard
- Supports AJAX calls for seamless integration
- JSON responses for API integration

### **Automation**
- Cron job integration possible
- Email notifications can be added
- Webhook support for external systems

## Support

For issues or questions:
1. Check the error logs in `backup/backup_log.txt`
2. Verify file permissions and directory access
3. Test with a small database first
4. Monitor memory usage during backup process

## Version History

- **v2.0**: Complete rewrite with OOP, security, and performance improvements
- **v1.0**: Basic backup functionality

---

**Note**: This backup system is designed for shared hosting environments where `mysqldump` is not available. For production environments with large databases, consider using native MySQL backup tools when available. 