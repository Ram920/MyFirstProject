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
$stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
$stmt->bind_param("s", $admin_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // User exists, update password
    $stmt_update = $conn->prepare("UPDATE admin_users SET password_hash = ? WHERE username = ?");
    $stmt_update->bind_param("ss", $hashed_password, $admin_username);
    if ($stmt_update->execute()) {
        echo "<h1>Admin password for '{$admin_username}' updated successfully in the database!</h1>";
    } else {
        echo "<h1>Error updating admin password: " . $conn->error . "</h1>";
    }
    $stmt_update->close();
} else {
    // User does not exist, insert new user
    $stmt_insert = $conn->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
    $stmt_insert->bind_param("ss", $admin_username, $hashed_password);
    if ($stmt_insert->execute()) {
        echo "<h1>Admin user '{$admin_username}' created successfully with hashed password!</h1>";
    } else {
        echo "<h1>Error creating admin user: " . $conn->error . "</h1>";
    }
    $stmt_insert->close();
}

$conn->close();

echo "<p style='color: red; font-weight: bold;'>IMPORTANT: For security, delete this file (<code>admin/set_admin_password.php</code>) from your server immediately after you have set the password.</p>";
?>