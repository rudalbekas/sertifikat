# Production Deployment Checklist

## ⚠️ CRITICAL: Third-Party Libraries

The current implementation includes **placeholder libraries** for demonstration. For production use, you **MUST** install the proper libraries:

### Required Libraries

#### 1. FPDF (PDF Generation) - CRITICAL
**Current Status:** Placeholder only - generates non-functional PDFs
**Required For:** Certificate PDF generation

**Installation:**
```bash
# Option 1: Manual download
# Download from: http://www.fpdf.org/
# Extract to: libraries/fpdf/

# Option 2: Composer
composer require setasign/fpdf
```

**Without proper FPDF:**
- ❌ Certificates will be placeholder PDFs
- ❌ No actual certificate content rendered
- ❌ QR codes not embedded
- ❌ System NOT production-ready

#### 2. PHP QR Code (QR Code Generation)
**Current Status:** Placeholder - creates placeholder images
**Required For:** QR code generation for certificates

**Installation:**
```bash
# Option 1: Manual download
# Download from: https://github.com/t0k4rt/phpqrcode
# Extract to: libraries/phpqrcode/

# Option 2: Composer
composer require phpqrcode/phpqrcode
```

#### 3. SimpleXLSX or PhpSpreadsheet (Excel Import)
**Current Status:** CSV only supported
**Required For:** Excel/XLSX file import (optional - CSV works)

**Installation:**
```bash
# Option 1: SimpleXLSX (lightweight)
# Download from: https://github.com/shuchkin/simplexlsx

# Option 2: PhpSpreadsheet (full-featured)
composer require phpoffice/phpspreadsheet
```

## Security Checklist

### Pre-Production Security Tasks

1. **Database Security**
   - [ ] Change all default passwords
   - [ ] Create strong admin password (min 12 characters)
   - [ ] Restrict database access to localhost or specific IPs
   - [ ] Enable MySQL secure installation

2. **Application Security**
   - [ ] Update SECRET_KEY in config/config.php to a strong random string
   - [ ] Set APP_ENV to 'production'
   - [ ] Review and update APP_URL
   - [ ] Disable error display (set display_errors = 0)
   - [ ] Enable error logging

3. **Web Server Security**
   - [ ] Enable HTTPS (SSL/TLS certificate)
   - [ ] Force HTTPS redirect in .htaccess
   - [ ] Update session.cookie_secure to 1
   - [ ] Configure proper file permissions (644 files, 755 dirs)
   - [ ] Move config files outside web root if possible

4. **File Security**
   - [ ] Ensure uploads/ and generated/ are writable (755)
   - [ ] Verify .htaccess blocks access to config/ and includes/
   - [ ] Add to .gitignore: config/config.php

5. **Email Configuration**
   - [ ] Configure SMTP settings in config/config.php
   - [ ] Use app passwords for Gmail
   - [ ] Test email delivery

## Testing Checklist

### Functional Testing
- [ ] User registration and login
- [ ] Event creation and management
- [ ] Certificate generation (single)
- [ ] Certificate batch import (CSV)
- [ ] Certificate verification (by number)
- [ ] Certificate verification (by QR code)
- [ ] Certificate revocation
- [ ] Email sending
- [ ] Rate limiting on verification
- [ ] Audit log recording

### Security Testing
- [ ] SQL injection attempts (use sqlmap)
- [ ] XSS attempts (script injection in forms)
- [ ] CSRF protection (submit forms without tokens)
- [ ] File upload validation (try .php files)
- [ ] Session fixation attempts
- [ ] Password strength enforcement
- [ ] Brute force protection (rate limiting)

### Performance Testing
- [ ] Generate 100+ certificates in one batch
- [ ] Test with 1000+ certificates in database
- [ ] Verify query performance with indexes
- [ ] Test under concurrent user load

## Post-Deployment Tasks

1. **Monitor Logs**
   - Check PHP error logs daily
   - Review audit_logs regularly
   - Monitor verification_attempts for abuse

2. **Database Backups**
   ```bash
   # Daily backup
   mysqldump -u root -p sertifikat_db > backup_$(date +%Y%m%d).sql
   ```

3. **Regular Updates**
   - Keep PHP updated
   - Update MySQL/MariaDB
   - Update libraries (FPDF, QR Code, etc.)

4. **Security Audits**
   - Run security scans quarterly
   - Review user permissions
   - Check for new vulnerabilities

## Support and Maintenance

### Common Issues

**Issue:** "FPDF library not installed"
**Solution:** Install proper FPDF library (see Required Libraries above)

**Issue:** "QR codes are placeholders"
**Solution:** Install PHP QR Code library

**Issue:** "Cannot write to uploads/"
**Solution:** `chmod 755 uploads/csv uploads/templates`

**Issue:** "Email not sending"
**Solution:** Check SMTP settings in config/config.php

### Performance Optimization

1. **Database Indexes:** Already added in schema.sql
2. **PHP OPcache:** Enable in php.ini
3. **Image Optimization:** Compress QR codes and templates
4. **CDN:** Use CDN for Bootstrap/jQuery in production

## License and Compliance

- Ensure GDPR compliance (email masking implemented)
- Review data retention policies
- Document certificate lifecycle
- Maintain audit trails (already implemented)

---

**Remember:** This system is currently in DEVELOPMENT mode with placeholder libraries. Do NOT use in production without installing the required libraries and completing this checklist.
