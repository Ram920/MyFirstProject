<?php
session_start();

// Note: You should have a login check here to secure your admin area.
// For example:
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: login.php");
//     exit;
// }

require_once __DIR__ . '/../db_connect.php'; // Connect to the database

// --- Fetch Invoices from Database (PDO) ---
try {
    $invoices_stmt = $conn->query("SELECT id, invoice_number, customer_name, invoice_date, total_amount FROM invoices ORDER BY invoice_date DESC");
    $invoices = $invoices_stmt->fetchAll(PDO::FETCH_ASSOC);
    $invoices_stmt = null; // Close statement
    if (empty($invoices)) {
        $table_error = "No invoices found. Please ensure the 'invoices' table exists and contains data.";
    }
} catch (PDOException $e) {
    $table_error = "Database error: " . $e->getMessage() . ". Please ensure the 'invoices' table exists and contains data.";
}

$conn = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - View Invoices</title>
    <!-- Simple styling for demonstration -->
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>

    <h1>Manage Invoices</h1>
    <p>This page lists all invoices from the database. Click "Generate PDF" to view the invoice.</p>

    <?php if (isset($table_error)): ?>
        <p class="error"><?php echo $table_error; ?></p>
        <p><b>Sample SQL to create an `invoices` table:</b></p>
        <pre>CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `invoice_date` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
);</pre>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Customer Name</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                        <td><?php echo htmlspecialchars($invoice['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($invoice['invoice_date']); ?></td>
                        <td><?php echo htmlspecialchars($invoice['total_amount']); ?></td>
                        <td>
                            <!-- This is the link that triggers the PDF generation -->
                            <a href="../generate_invoice.php?invoice_id=<?php echo $invoice['id']; ?>" target="_blank">Generate PDF</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</body>
</html>