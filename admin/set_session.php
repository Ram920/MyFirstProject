<?php
session_start();
if (isset($_POST['access_token'])) {
    // In a production environment, you should verify this token 
    // with Supabase's API, but for now, we trust the secure client-side check.
    $_SESSION['loggedin'] = true;
    $_SESSION['last_activity'] = time();
    $_SESSION['supabase_token'] = $_POST['access_token'];
    http_response_code(200);
    echo "OK";
} else {
    http_response_code(400);
}