<?php
// Start the session to access it
session_start();

// Unset all of the session variables
$_SESSION = array();

// Destroy the session completely
session_destroy();

// Redirect to the login page
header("location: index.php");
exit;