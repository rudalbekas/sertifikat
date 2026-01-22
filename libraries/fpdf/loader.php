<?php
/**
 * FPDF Library Placeholder
 * 
 * ⚠️ CRITICAL: THIS IS A PLACEHOLDER IMPLEMENTATION
 * The current implementation generates only placeholder PDFs for demonstration.
 * 
 * PRODUCTION INSTALLATION REQUIRED:
 * 1. Download FPDF from: http://www.fpdf.org/
 * 2. Extract to this directory
 * 3. The file fpdf.php should be in: libraries/fpdf/fpdf.php
 * 
 * Alternative: Use composer
 * composer require setasign/fpdf
 * 
 * Without the proper FPDF library:
 * - Generated PDFs will be placeholder documents
 * - Certificate content will NOT be rendered
 * - QR codes will NOT be embedded
 * - This is for DEVELOPMENT/TESTING ONLY
 */

// Check if FPDF is installed
if (!file_exists(__DIR__ . '/fpdf.php')) {
    trigger_error('FPDF library not installed. Placeholder mode active. Download from http://www.fpdf.org/', E_USER_WARNING);
}

require_once __DIR__ . '/fpdf.php';
