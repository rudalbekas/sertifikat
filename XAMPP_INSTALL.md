# XAMPP Installation Instructions

## Prerequisites
- XAMPP installed with PHP 7.4+ or 8.x
- Apache and MySQL services running

## Installation Steps

### 1. Copy Files to XAMPP
Copy the entire `sertifikat` folder to your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\sertifikat\
```

### 2. Create Database
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Create a new database named `sertifikat_db`
3. Set collation to `utf8mb4_unicode_ci`
4. Import the database schema:
   - Click on `sertifikat_db` database
   - Go to "Import" tab
   - Choose file: `database/schema.sql`
   - Click "Go"
5. Import the seed data:
   - Choose file: `database/seed.sql`
   - Click "Go"

### 3. Configure Application
1. Edit `config/config.php` file
2. Update database settings (default XAMPP settings are already configured):
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Empty for XAMPP default
   define('DB_NAME', 'sertifikat_db');
   ```
3. Update APP_URL to match your setup:
   ```php
   define('APP_URL', 'http://localhost/sertifikat');
   ```

### 4. Set Directory Permissions
Ensure these directories exist and are writable:
- `uploads/csv/`
- `uploads/templates/`
- `generated/certificates/`
- `generated/qrcodes/`

### 5. Access the Application
1. Start Apache and MySQL in XAMPP Control Panel
2. **First, run the installation check**: http://localhost/sertifikat/check_installation.php
   - This will verify all files and directories are in place
   - Fix any issues reported before proceeding
3. Open browser and go to: http://localhost/sertifikat/
4. Admin login: http://localhost/sertifikat/admin/login.php
   - Username: `admin`
   - Password: `admin123`

## Troubleshooting

### Quick Check
Run the installation checker first: http://localhost/sertifikat/check_installation.php

This will verify:
- PHP version and extensions
- Config file exists and is valid
- Database connection works
- All required directories exist and are writable

### Internal Server Error
If you get "Internal Server Error":
1. Check if `config/config.php` exists
2. Verify database connection settings
3. Check Apache error logs: `xampp/apache/logs/error.log`
4. Ensure PHP version is 7.4 or higher
5. Check if `mod_rewrite` is enabled in Apache

### Database Connection Failed
1. Ensure MySQL service is running in XAMPP
2. Verify database exists and credentials are correct
3. Check if database user has proper permissions

### Permission Errors
If you get file permission errors:
1. On Windows, ensure folders are not read-only
2. Give write permissions to upload and generated folders

### .htaccess Issues
If .htaccess causes errors:
1. Ensure `mod_rewrite` is enabled in Apache
2. Check if `AllowOverride All` is set in Apache config
3. Verify `mod_headers` module is enabled

## Default Login Credentials

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| Designer | designer | admin123 |
| Verifikator | verifier | admin123 |

**⚠️ IMPORTANT**: Change all passwords after first login!

## Need Help?
- Check main README.md for more documentation
- See PRODUCTION.md for production deployment
