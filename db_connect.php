<?php
// Include configuration
require_once __DIR__ . '/admin/config.php';

// --- Database Configuration from config.php ---
$db_host = DB_HOST;
$db_user = DB_USER;
$db_pass = DB_PASS;
$db_name = DB_NAME;

// --- Create Connection ---
// For Supabase (PostgreSQL), use PDO
try { // Added port 5432 for PostgreSQL
    $conn = new PDO("pgsql:host=$db_host;dbname=$db_name;port=5432", $db_user, $db_pass);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// --- Check Connection ---
// The PDO connection will throw an exception on failure, caught above.
// No explicit check needed here.

// Note: All subsequent database interactions in PHP files will need to be updated
// from mysqli_* functions/methods to PDO equivalents.
?>