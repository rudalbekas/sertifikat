# Quick Start for XAMPP Users

## ⚡ Fast Setup (5 Minutes)

### 1. Extract Files
Copy the `sertifikat` folder to:
```
C:\xampp\htdocs\sertifikat\
```

### 2. Create Database
1. Start Apache & MySQL in XAMPP Control Panel
2. Open phpMyAdmin: http://localhost/phpmyadmin
3. Click "New" to create database
4. Database name: `sertifikat_db`
5. Collation: `utf8mb4_unicode_ci`
6. Click "Create"

### 3. Import Database
1. Click on `sertifikat_db` in phpMyAdmin
2. Go to "Import" tab
3. Click "Choose File" → Select `database/schema.sql` → Click "Go"
4. Click "Choose File" → Select `database/seed.sql` → Click "Go"

### 4. Check Installation
Open: http://localhost/sertifikat/check_installation.php

**All green checkmarks?** You're ready! 🎉

### 5. Access Application

**Home Page:**
http://localhost/sertifikat/

**Admin Login:**
http://localhost/sertifikat/admin/login.php
- Username: `admin`
- Password: `admin123`

**Verify Certificates:**
http://localhost/sertifikat/verify/

---

## 🔧 Troubleshooting

### ❌ Internal Server Error?
1. Check if config.php exists in config/ folder
2. Run: http://localhost/sertifikat/check_installation.php
3. Check Apache error log: `C:\xampp\apache\logs\error.log`

### ❌ Database Connection Failed?
1. Verify MySQL is running in XAMPP
2. Check credentials in config/config.php:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'sertifikat_db');
   ```

### ❌ Page Not Found / 404?
1. Check if mod_rewrite is enabled in Apache
2. Edit: `C:\xampp\apache\conf\httpd.conf`
3. Find: `#LoadModule rewrite_module modules/mod_rewrite.so`
4. Remove the `#` to uncomment it
5. Restart Apache

### ❌ Permission Denied?
On Windows, right-click folders and uncheck "Read-only" for:
- uploads/
- generated/
- logs/

---

## 📚 Need More Help?

- **Full Installation Guide:** See `XAMPP_INSTALL.md`
- **Fix Summary:** See `XAMPP_FIX_SUMMARY.md`
- **Documentation:** See `README.md`
- **Production Setup:** See `PRODUCTION.md`

---

## ⚠️ Important Notes

1. **Change Default Password**: The default admin password is `admin123` - change it after first login!
2. **For Development Only**: The default config uses root with no password - secure it for production
3. **Check PHP Version**: Requires PHP 7.4+ (XAMPP 7.4 or 8.x recommended)

---

**That's it! Your certificate management system is ready to use! 🚀**
