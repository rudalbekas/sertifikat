#!/bin/bash
# Installation script for Certificate Management System

echo "================================================"
echo "Certificate Management System - Installation"
echo "================================================"
echo ""

# Check PHP version
echo "Checking PHP version..."
php_version=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
if (( $(echo "$php_version >= 7.4" | bc -l) )); then
    echo "✓ PHP version: $php_version (OK)"
else
    echo "✗ PHP version $php_version is too old. Requires PHP 7.4+"
    exit 1
fi

# Check required PHP extensions
echo ""
echo "Checking PHP extensions..."
required_extensions=("pdo" "pdo_mysql" "gd" "mbstring" "session" "json")
for ext in "${required_extensions[@]}"; do
    if php -m | grep -q "^$ext$"; then
        echo "✓ $ext extension installed"
    else
        echo "✗ $ext extension NOT installed"
        exit 1
    fi
done

# Check MySQL
echo ""
echo "Checking MySQL/MariaDB..."
if command -v mysql &> /dev/null; then
    echo "✓ MySQL client found"
else
    echo "✗ MySQL client not found. Please install MySQL or MariaDB"
    exit 1
fi

# Set permissions
echo ""
echo "Setting directory permissions..."
chmod 755 uploads/csv uploads/templates generated/certificates generated/qrcodes
echo "✓ Permissions set"

# Check if config exists
echo ""
if [ ! -f "config/config.php" ]; then
    echo "Creating config.php from template..."
    cp config/.env.example config/config.php
    echo "✓ config/config.php created"
    echo "⚠️  IMPORTANT: Edit config/config.php and update:"
    echo "   - Database credentials"
    echo "   - Application URL"
    echo "   - Secret key"
else
    echo "✓ config/config.php already exists"
fi

# Database setup
echo ""
echo "================================================"
echo "Database Setup"
echo "================================================"
read -p "Do you want to setup the database now? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    read -p "Enter MySQL root password: " -s mysql_password
    echo ""
    read -p "Enter database name [sertifikat_db]: " db_name
    db_name=${db_name:-sertifikat_db}
    
    echo "Creating database..."
    mysql -u root -p"$mysql_password" -e "CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    
    echo "Importing schema..."
    mysql -u root -p"$mysql_password" $db_name < database/schema.sql
    
    echo "Importing seed data..."
    mysql -u root -p"$mysql_password" $db_name < database/seed.sql
    
    echo "✓ Database setup complete!"
fi

# Check for FPDF
echo ""
echo "================================================"
echo "Third-Party Libraries Check"
echo "================================================"

if [ ! -f "libraries/fpdf/fpdf.php" ]; then
    echo "⚠️  WARNING: FPDF library not installed"
    echo "   This is REQUIRED for production use"
    echo "   Download from: http://www.fpdf.org/"
    echo "   Extract to: libraries/fpdf/"
else
    echo "✓ FPDF library found"
fi

if [ ! -f "libraries/phpqrcode/qrlib.php" ]; then
    echo "⚠️  WARNING: PHP QR Code library not installed"
    echo "   This is REQUIRED for production use"
    echo "   Download from: https://github.com/t0k4rt/phpqrcode"
    echo "   Extract to: libraries/phpqrcode/"
else
    echo "✓ PHP QR Code library found"
fi

echo ""
echo "================================================"
echo "Installation Complete!"
echo "================================================"
echo ""
echo "Next steps:"
echo "1. Edit config/config.php with your settings"
echo "2. Install FPDF and PHP QR Code libraries (see above)"
echo "3. Configure your web server (Apache/Nginx)"
echo "4. Access your application at: http://localhost/sertifikat/"
echo ""
echo "Default login credentials:"
echo "  Username: admin"
echo "  Password: admin123"
echo ""
echo "⚠️  IMPORTANT: Change default passwords in production!"
echo ""
echo "For production deployment, see PRODUCTION.md"
echo "================================================"
