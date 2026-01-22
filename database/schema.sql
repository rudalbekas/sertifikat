-- Certificate Management System Database Schema
-- MySQL 5.7+ / MariaDB 10.3+

CREATE DATABASE IF NOT EXISTS sertifikat_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sertifikat_db;

-- Table: users
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'designer', 'verifikator') NOT NULL DEFAULT 'verifikator',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: templates
CREATE TABLE IF NOT EXISTS templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_name VARCHAR(100) NOT NULL,
    template_file VARCHAR(255) NOT NULL,
    layout_config TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_template_name (template_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: events
CREATE TABLE IF NOT EXISTS events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_name VARCHAR(200) NOT NULL,
    event_date DATE NOT NULL,
    organizer VARCHAR(200) NOT NULL,
    template_id INT,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES templates(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_event_name (event_name),
    INDEX idx_event_date (event_date),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: certificates
CREATE TABLE IF NOT EXISTS certificates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    certificate_number VARCHAR(50) NOT NULL UNIQUE,
    event_id INT NOT NULL,
    recipient_name VARCHAR(200) NOT NULL,
    recipient_email VARCHAR(100),
    institution VARCHAR(200),
    grade VARCHAR(10),
    verification_token VARCHAR(255) NOT NULL UNIQUE,
    qr_code_path VARCHAR(255),
    pdf_path VARCHAR(255),
    status ENUM('valid', 'revoked', 'expired') NOT NULL DEFAULT 'valid',
    issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    email_sent BOOLEAN DEFAULT FALSE,
    email_sent_at DATETIME,
    revoked_at DATETIME,
    revoked_by INT,
    revoke_reason TEXT,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (revoked_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_certificate_number (certificate_number),
    INDEX idx_verification_token (verification_token),
    INDEX idx_event_id (event_id),
    INDEX idx_recipient_name (recipient_name),
    INDEX idx_status (status),
    INDEX idx_issued_at (issued_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: audit_logs
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT,
    old_values TEXT,
    new_values TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_table_name (table_name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: verification_attempts
CREATE TABLE IF NOT EXISTS verification_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(45) NOT NULL,
    certificate_number VARCHAR(50),
    success BOOLEAN NOT NULL DEFAULT FALSE,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempted_at (attempted_at),
    INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: email_queue
CREATE TABLE IF NOT EXISTS email_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    certificate_id INT NOT NULL,
    recipient_email VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    attempts INT NOT NULL DEFAULT 0,
    error_message TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME,
    FOREIGN KEY (certificate_id) REFERENCES certificates(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_certificate_id (certificate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
