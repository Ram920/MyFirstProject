<?php
// Include configuration
require_once __DIR__ . '/admin/config.php';

// --- Database Configuration from config.php ---
$db_host = DB_HOST;
$db_user = DB_USER;
$db_pass = DB_PASS;
$db_name = DB_NAME;

// --- Create Connection ---
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// --- Check Connection ---
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>