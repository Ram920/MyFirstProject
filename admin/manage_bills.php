<?php
session_start();

// --- Session Security Check ---
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset();
    session_destroy();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

$_SESSION['last_activity'] = time();

require_once __DIR__ . '/../db_connect.php';

// --- Handle Delete Bill ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Using a transaction to ensure both bill and its items are deleted
    $conn->begin_transaction();
    try {
        // The database is set up with ON DELETE CASCADE, so deleting from `bills` will also delete from `bill_items`.
        $stmt = $conn->prepare("DELETE FROM bills WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $conn->commit();
        
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Bill #' . $id . ' has been deleted.'];
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Error deleting bill: ' . $e->getMessage()];
    }
    
    header("Location: manage_bills.php");
    exit;
}

$bills = $conn->query("SELECT * FROM bills ORDER BY bill_date DESC, id DESC");

// --- Get the ID of the most recent bill to allow editing only for that one ---
$latest_bill_result = $conn->query("SELECT MAX(id) as max_id FROM bills");
$latest_bill_id = $latest_bill_result->fetch_assoc()['max_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Bills</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Manage Bills</h2>
        <div>
            <a href="create_bill.php" class="btn btn-primary">Create New Bill</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>

    <?php
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        $alert_type = ($message['type'] === 'success') ? 'alert-success' : 'alert-danger';
        echo "<div class='alert {$alert_type}'>{$message['text']}</div>";
    }
    ?>

    <div class="card">
        <div class="card-header">Existing Bills</div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Bill ID</th>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Total Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $bills->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo date('d M Y', strtotime($row['bill_date'])); ?></td>
                            <td><?php echo number_format($row['total_amount'], 2); ?></td>
                            <td>
                                <?php if ($row['id'] == $latest_bill_id): ?>
                                    <a href="edit_bill.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                <?php endif; ?>
                                <a href="manage_bills.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this bill? This action cannot be undone.')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>