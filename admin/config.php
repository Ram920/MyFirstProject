<?php
// admin/config.php
// This file contains sensitive configuration data.
// For production, consider placing this file outside the web-accessible root
// or protecting it with .htaccess to prevent direct access.

// --- Error Reporting (Set to 0 in production) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Global UI Variables ---
$alert = ''; // Initialize to prevent "Undefined variable" warnings on page load

// --- Database Configuration ---
define('DB_HOST', 'aws-1-ap-northeast-1.pooler.supabase.com'); 
define('DB_USER', 'postgres.bgyhipluueoddayoifph'); // Format must be postgres.[PROJECT_ID]
define('DB_NAME', 'postgres'); // e.g., postgres - REPLACE THIS
define('DB_PASS', 'Nushmechanical$2002'); // Replace with your actual password
define('DB_PORT', '6543'); 

// --- Supabase Configuration ---
define('SUPABASE_URL', 'https://bgyhipluueoddayoifph.supabase.co'); // REPLACE WITH YOUR ACTUAL SUPABASE URL
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImJneWhpcGx1dWVvZGRheW9pZnBoIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzUzMDI5NDEsImV4cCI6MjA5MDg3ODk0MX0.5taupNZibiIX1yHITIUAv6RFORSn1HmnlHf6h7UWMkU'); // REPLACE WITH YOUR ACTUAL SUPABASE ANON KEY

// --- Admin Panel Configuration ---

// --- Email Configuration (for PHPMailer) ---
define('SMTP_HOST', 'smtp.gmail.com'); // Your SMTP server host
define('SMTP_USERNAME', 'sharmaram920@gmail.com'); // Your Gmail address (e.g., your.email@gmail.com)
define('SMTP_PASSWORD', 'yulwyjeylicpnqmg'); // !!! CHANGE THIS to your 16-character Gmail App Password !!!
define('ADMIN_EMAIL_RECIPIENT', 'sharmaram920@gmail.com'); // The email address where admin inquiries are sent
define('COMPANY_NAME', 'NUSH MECHANICAL & FABRICATOR WORKS'); // Your company name, used in emails
?>