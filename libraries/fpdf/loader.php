<?php
/**
 * FPDF Library Placeholder
 * 
 * INSTALLATION REQUIRED:
 * 1. Download FPDF from: http://www.fpdf.org/
 * 2. Extract to this directory
 * 3. The file fpdf.php should be in: libraries/fpdf/fpdf.php
 * 
 * Alternative: Use composer
 * composer require setasign/fpdf
 */

// Check if FPDF is installed
if (!file_exists(__DIR__ . '/fpdf.php')) {
    die('FPDF library not installed. Please download from http://www.fpdf.org/ and extract to libraries/fpdf/');
}

require_once __DIR__ . '/fpdf.php';
