<?php
// admin/config.php
// This file contains sensitive configuration data.
// For production, consider placing this file outside the web-accessible root
// or protecting it with .htaccess to prevent direct access.

// --- Database Configuration ---
define('DB_HOST', 'localhost');
define('DB_USER', 'your_database_user'); // !!! CHANGE THIS to your actual database username !!!
define('DB_PASS', 'your_database_password'); // !!! CHANGE THIS to your actual database password !!!
define('DB_NAME', 'nush_db');

// --- Admin Panel Configuration ---
define('ADMIN_PASSWORD', 'your_secret_admin_password'); // !!! CHANGE THIS to a strong, secret password !!!

// --- Email Configuration (for PHPMailer) ---
define('SMTP_USERNAME', 'sharmaram920@gmail.com'); // Your Gmail address (e.g., your.email@gmail.com)
define('SMTP_PASSWORD', 'YOUR_16_CHARACTER_APP_PASSWORD_HERE'); // !!! CHANGE THIS to your 16-character Gmail App Password !!!
define('ADMIN_EMAIL_RECIPIENT', 'nushmechanical@gmail.com'); // The email address where admin inquiries are sent
define('COMPANY_NAME', 'NUSH MECHANICAL & FABRICATOR WORKS'); // Your company name, used in emails
?>