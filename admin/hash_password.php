<?php
// A simple utility to generate a secure password hash.
// Use this file once to create your hash, then update config.php.

$passwordToHash = 'admin'; // <-- CHANGE THIS to your desired secret password

// Generate the password hash
$hashedPassword = password_hash($passwordToHash, PASSWORD_DEFAULT);

echo "<h3>Password Hashing Utility</h3>";
echo "<p><strong>Password to hash:</strong> " . htmlspecialchars($passwordToHash) . "</p>";
echo "<p><strong>Generated Hash:</strong></p>";
echo "<textarea readonly style='width: 100%; height: 80px;'>" . $hashedPassword . "</textarea>";
echo "<p>Copy the generated hash above and paste it into your <code>config.php</code> file as the value for the <code>ADMIN_PASSWORD_HASH</code> constant.</p>";
echo "<p style='color: red; font-weight: bold;'>IMPORTANT: For security, delete this file (hash_password.php) from your server after you have generated and saved your hash.</p>";

?>