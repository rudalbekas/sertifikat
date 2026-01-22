# 🎓 Certificate Management System

A comprehensive web-based certificate generation and verification system built with **PHP Native** and **MySQL**.

## ⚡ Quick Start

```bash
# 1. Clone repository
git clone https://github.com/rudalbekas/sertifikat.git
cd sertifikat

# 2. Run installation script
chmod +x install.sh
./install.sh

# 3. Configure application
nano config/config.php  # Update database credentials and settings

# 4. Access application
# Open browser: http://localhost/sertifikat/
# Login: admin / admin123
```

**⚠️ Important:** This includes placeholder libraries. For production use, install proper FPDF and QR Code libraries (see [PRODUCTION.md](PRODUCTION.md)).

## Features

### 🏆 Certificate Generation
- **Professional Templates**: A4 landscape certificates with custom designs
- **Batch Processing**: Import CSV/Excel files and generate thousands of certificates at once
- **Unique Certificate Numbers**: Auto-generated with customizable format (e.g., EVT-2026-000123)
- **QR Code Integration**: Each certificate includes a secure QR code for easy verification
- **PDF Export**: High-quality PDF generation for printing

### ✅ Public Verification System
- **Multi-method Verification**: Verify by certificate number or QR code scan
- **Secure Tokens**: HMAC-SHA256 signatures prevent forgery
- **Rate Limiting**: Protection against brute force attacks
- **Privacy Protection**: Email masking and GDPR-compliant data display

### 🔐 Security Features
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: All output sanitized
- **CSRF Protection**: Tokens for all forms
- **Password Security**: bcrypt hashing
- **Rate Limiting**: Prevents verification abuse
- **Audit Logging**: Complete trail of all critical operations

### 👥 User Management
- **Role-Based Access**: Admin, Designer, Verifikator roles
- **Secure Authentication**: Session management with regeneration
- **User CRUD Operations**: Create, update, delete users (admin only)

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache/Nginx web server
- PHP extensions: PDO, GD, mbstring

### Step 1: Clone Repository
```bash
git clone https://github.com/rudalbekas/sertifikat.git
cd sertifikat
```

### Step 2: Create Database
```bash
mysql -u root -p
```

```sql
CREATE DATABASE sertifikat_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

Import the schema:
```bash
mysql -u root -p sertifikat_db < database/schema.sql
mysql -u root -p sertifikat_db < database/seed.sql
```

### Step 3: Configure Application
1. Copy the configuration template:
```bash
cp config/.env.example config/config.php
```

2. Edit `config/config.php` and update:
- Database credentials (DB_HOST, DB_USER, DB_PASS, DB_NAME)
- Application URL (APP_URL)
- Secret key for HMAC signatures (SECRET_KEY)
- Email settings if using email features

### Step 4: Set Permissions
```bash
chmod 755 uploads/csv uploads/templates generated/certificates generated/qrcodes
```

### Step 5: Access Application
Open your browser and navigate to:
- **Public Home**: http://localhost/sertifikat/
- **Admin Login**: http://localhost/sertifikat/admin/login.php
- **Verification**: http://localhost/sertifikat/verify/

## Default Credentials

After running `seed.sql`, you can login with:

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| Designer | designer | admin123 |
| Verifikator | verifier | admin123 |

**⚠️ IMPORTANT**: Change all passwords immediately in production!

## Directory Structure

```
/sertifikat/
├── config/              # Configuration files
├── includes/            # Core PHP functions (auth, security, helpers)
├── admin/               # Admin panel (events, certificates, users, etc.)
├── verify/              # Public verification page
├── assets/              # CSS, JS, images
├── uploads/             # User uploads (CSV, templates)
├── generated/           # Generated certificates and QR codes
├── libraries/           # Third-party libraries (FPDF, QR Code, etc.)
├── database/            # SQL schema and seed files
└── index.php            # Public landing page
```

## Usage Guide

### Creating an Event
1. Login as admin or designer
2. Navigate to **Events** → **Create New Event**
3. Fill in event name, date, and organizer
4. Select a certificate template (optional)
5. Click **Create Event**

### Generating Certificates

#### Single Certificate
1. Navigate to **Certificates** → **Generate Certificate**
2. Select event
3. Enter recipient details
4. Click **Generate Certificate**

#### Batch Import (CSV)
1. Navigate to **Certificates** → **Batch Import**
2. Select event
3. Upload CSV file with format:
   ```csv
   name,email,institution,grade
   John Doe,john@example.com,ABC University,A
   Jane Smith,jane@example.com,XYZ College,B
   ```
4. Click **Import Certificates**

### Verifying Certificates
1. Go to the public verification page
2. Enter certificate number or scan QR code
3. View certificate status and details

### Revoking Certificates
1. Navigate to **Certificates** → Find certificate
2. Click **Revoke**
3. Enter reason for revocation
4. Confirm action

## Required Libraries

The system requires the following PHP libraries (not included in repo):

### FPDF (PDF Generation)
Download from: http://www.fpdf.org/
Extract to: `libraries/fpdf/`

### PHP QR Code
Download from: https://github.com/t0k4rt/phpqrcode
Extract to: `libraries/phpqrcode/`

### Alternative: Use Composer
```bash
composer require setasign/fpdf
composer require phpqrcode/phpqrcode
```

## Security Best Practices

### Production Deployment Checklist
- [ ] Change all default passwords
- [ ] Update SECRET_KEY in config.php to a random string
- [ ] Set APP_ENV to 'production'
- [ ] Enable HTTPS and force redirect in .htaccess
- [ ] Set restrictive file permissions (644 for files, 755 for directories)
- [ ] Move sensitive files outside web root if possible
- [ ] Enable PHP error logging, disable display_errors
- [ ] Regularly backup database and generated files
- [ ] Keep PHP and MySQL updated
- [ ] Review audit logs regularly

### Database Backup
```bash
# Backup
mysqldump -u root -p sertifikat_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore
mysql -u root -p sertifikat_db < backup_20260122_120000.sql
```

## API Documentation

### Verification API (Future Enhancement)
```
POST /verify/api.php
{
  "certificate_number": "EVT-2026-000123"
}

Response:
{
  "valid": true,
  "status": "valid",
  "recipient": "John Doe",
  "event": "Workshop 2026"
}
```

## Troubleshooting

### Database Connection Error
- Check database credentials in `config/config.php`
- Ensure MySQL service is running
- Verify database exists and user has proper permissions

### Permission Denied for Uploads
```bash
chmod 755 uploads/csv uploads/templates generated/certificates generated/qrcodes
```

### QR Code Not Generating
- Check if GD extension is enabled: `php -m | grep gd`
- Verify write permissions on `generated/qrcodes/`

### Session Issues
- Check session directory permissions
- Ensure cookies are enabled in browser
- Clear browser cache and cookies

## FAQ

**Q: Can I customize certificate templates?**
A: Yes, you can upload custom templates and configure layout positions in the templates section.

**Q: How many certificates can I generate at once?**
A: The system is designed to handle 1000+ certificates in a single batch. Adjust PHP memory_limit and max_execution_time if needed.

**Q: Is the verification page publicly accessible?**
A: Yes, the verification page is public and does not require authentication.

**Q: Can I integrate this with my existing system?**
A: Yes, you can use the verification API or integrate the database directly.

**Q: How do I add custom fields to certificates?**
A: Modify the database schema and update the certificate generation logic in `admin/certificates/generate.php`.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

This project is open source and available under the MIT License.

## Support

For issues and questions:
- GitHub Issues: https://github.com/rudalbekas/sertifikat/issues
- Documentation: See README.md

## Credits

Built with:
- PHP Native (no framework)
- MySQL
- Bootstrap 5
- FPDF (PDF generation)
- PHP QR Code (QR generation)

---

**Made with ❤️ for educational and professional use**
