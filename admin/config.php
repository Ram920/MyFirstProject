<?php
// admin/config.php
// This file contains sensitive configuration data.
// For production, consider placing this file outside the web-accessible root
// or protecting it with .htaccess to prevent direct access.

// --- Database Configuration ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Default XAMPP username
define('DB_PASS', ''); // Default XAMPP password is empty
define('DB_NAME', 'nush_db');

// --- Admin Panel Configuration ---

// --- Email Configuration (for PHPMailer) ---
define('SMTP_USERNAME', 'sharmaram920@gmail.com'); // Your Gmail address (e.g., your.email@gmail.com)
define('SMTP_PASSWORD', 'yulwyjeylicpnqmg'); // !!! CHANGE THIS to your 16-character Gmail App Password !!!
define('ADMIN_EMAIL_RECIPIENT', 'sharmaram920@gmail.com'); // The email address where admin inquiries are sent
define('COMPANY_NAME', 'NUSH MECHANICAL & FABRICATOR WORKS'); // Your company name, used in emails
?>