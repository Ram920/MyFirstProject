<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

if (isset($_POST['id']) && isset($_POST['status'])) {
    $id = (int)$_POST['id'];
    $status = $conn->real_escape_string($_POST['status']);
    
    // Validate status to prevent injection
    $allowed_statuses = ['New', 'Contacted', 'Quoted', 'Closed'];
    if (in_array($status, $allowed_statuses)) {
        $stmt = $conn->prepare("UPDATE enquiries SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            echo "Success";
        } else {
            http_response_code(500);
            echo "Error";
        }
        $stmt->close();
    } else {
        http_response_code(400);
        echo "Invalid status";
    }
} else {
    http_response_code(400);
    echo "Missing parameters";
}

$conn->close();
?>
