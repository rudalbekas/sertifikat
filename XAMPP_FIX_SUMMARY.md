# XAMPP Deployment Fix - Summary

## Problem Identified
The Internal Server Error on XAMPP was caused by:

1. **Missing config/config.php file** - The application was trying to load this file but it didn't exist in the repository
2. **Incorrect file path in verify/index.php** - Using `../../config/` instead of `../config/`
3. **.htaccess compatibility issues** - Used PHP 7 specific directives that don't work on PHP 8.x

## What Was Fixed

### 1. Created config/config.php
- Added the missing configuration file with default XAMPP settings
- Database user: `root`, password: `` (empty - default for XAMPP)
- Database name: `sertifikat_db`
- All paths configured correctly

### 2. Fixed File Paths
- **verify/index.php**: Changed `require_once __DIR__ . '/../../config/config.php'` to `require_once __DIR__ . '/../config/config.php'`
- This was causing the "Failed to open stream" error you saw

### 3. Updated .htaccess
- Changed from `<IfModule mod_php7.c>` to `<IfModule mod_php.c>`
- Removed deprecated PHP directives (register_globals, magic_quotes_gpc)
- Made it compatible with both PHP 7.x and PHP 8.x

### 4. Added Installation Checker
- Created `check_installation.php` to help diagnose issues
- Checks PHP version, config file, database connection, directories, and extensions
- Run this first: http://localhost/sertifikat/check_installation.php

### 5. Created XAMPP_INSTALL.md
- Step-by-step installation guide specifically for XAMPP
- Troubleshooting section with common issues
- Quick setup instructions

### 6. Added Required Directories
- Created `logs/` directory for error logging
- Added `.gitkeep` files to ensure directories are tracked in git

## How to Deploy on XAMPP

1. **Pull the latest changes** from GitHub
2. **Copy files** to `C:\xampp\htdocs\sertifikat\`
3. **Create database** in phpMyAdmin:
   - Database name: `sertifikat_db`
   - Collation: `utf8mb4_unicode_ci`
4. **Import database**:
   - Import `database/schema.sql`
   - Import `database/seed.sql`
5. **Update config/config.php** if needed (default settings should work)
6. **Run installation check**: http://localhost/sertifikat/check_installation.php
7. **Access application**: http://localhost/sertifikat/

## Default Login
- Username: `admin`
- Password: `admin123`

## Files Modified/Created

### Created:
- `config/config.php` - Main configuration file
- `XAMPP_INSTALL.md` - XAMPP-specific installation guide
- `check_installation.php` - Installation verification tool
- `logs/.gitkeep` - Log directory marker

### Modified:
- `.htaccess` - Updated for PHP 8.x compatibility
- `.gitignore` - Updated to include config.php
- `verify/index.php` - Fixed file path issue

## Verification Steps

After deployment, verify:
1. ✅ Home page loads: http://localhost/sertifikat/
2. ✅ Verify page works: http://localhost/sertifikat/verify/
3. ✅ Admin login works: http://localhost/sertifikat/admin/login.php
4. ✅ No Internal Server Errors

## Need Help?

If you still get errors:
1. Run check_installation.php to diagnose
2. Check Apache error log: `xampp/apache/logs/error.log`
3. Ensure Apache and MySQL are running in XAMPP
4. Verify mod_rewrite is enabled in Apache
5. Check file permissions on Windows (folders not read-only)

## Security Note

⚠️ **Important**: The config.php file now includes default XAMPP credentials. For production:
- Change database password
- Update SECRET_KEY in config.php
- Set APP_ENV to 'production'
- Enable HTTPS

---
All issues should now be resolved. The application should work on XAMPP without any Internal Server Errors.
