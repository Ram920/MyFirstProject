<?php
session_start();
require_once __DIR__ . '/../db_connect.php';
require_once 'config.php'; // Include configuration

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    exit('Access Denied');
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=enquiries-' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// Add column headers
fputcsv($output, [
    'ID', 'Date', 'Status', 'Name', 'Company', 'Email', 'Phone',
    'Delivery Location', 'Quantity', 'Products Inquired', 
    'Customization Req', 'Additional Req', 'Drawing File', 'Inquiry Type'
]);

$stmt = $conn->query("SELECT * FROM enquiries ORDER BY submission_date DESC");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['id'],
        $row['submission_date'],
        $row['status'],
        $row['name'],
        $row['company_name'],
        $row['email'],
        $row['phone'],
        $row['delivery_location'],
        $row['quantity'],
        $row['products_inquired'],
        $row['customization_req'],
        $row['additional_req'],
        $row['drawing_file'],
        $row['inquiry_type']
    ]);
}

fclose($output);
$conn = null;
exit();
?>
