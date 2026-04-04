<?php
// This is a temporary utility script to set or update the admin password in the database.
// DELETE THIS FILE IMMEDIATELY AFTER USE FOR SECURITY REASONS.

require_once __DIR__ . '/../db_connect.php'; // Connect to the database
require_once 'config.php'; // For DB credentials

// --- Configuration ---
$admin_username = 'admin'; // The username for the admin account
$new_password = 'admin';   // <-- CHANGE THIS to your desired plain-text password
                           //     (e.g., 'MySuperSecretPassword123!')

// --- Process ---
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Check if the admin user already exists
$stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = :username");
$stmt->execute([':username' => $admin_username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt = null; // Close statement

if ($user) {
    // User exists, update password
    $stmt_update = $conn->prepare("UPDATE admin_users SET password_hash = :password_hash WHERE username = :username");
    if ($stmt_update->execute([':password_hash' => $hashed_password, ':username' => $admin_username])) {
        echo "<h1>Admin password for '{$admin_username}' updated successfully in the database!</h1>";
    } else {
        $errorInfo = $stmt_update->errorInfo();
        echo "<h1>Error updating admin password: " . $errorInfo[2] . "</h1>";
    }
    $stmt_update = null; // Close statement
} else {
    // User does not exist, insert new user
    $stmt_insert = $conn->prepare("INSERT INTO admin_users (username, password_hash) VALUES (:username, :password_hash)");
    if ($stmt_insert->execute([':username' => $admin_username, ':password_hash' => $hashed_password])) {
        echo "<h1>Admin user '{$admin_username}' created successfully with hashed password!</h1>";
    } else {
        $errorInfo = $stmt_insert->errorInfo();
        echo "<h1>Error creating admin user: " . $errorInfo[2] . "</h1>";
    }
    $stmt_insert = null; // Close statement
}

$conn = null;

echo "<p style='color: red; font-weight: bold;'>IMPORTANT: For security, delete this file (<code>admin/set_admin_password.php</code>) from your server immediately after you have set the password.</p>";
?>