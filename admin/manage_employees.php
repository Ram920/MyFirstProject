<?php
session_start();

// --- Security & Session Management ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset(); session_destroy();
    header("Location: index.php");
    exit;
}
$_SESSION['last_activity'] = time();

require_once __DIR__ . '/../db_connect.php';

$message = '';
if (isset($_SESSION['flash_message'])) {
    $flash = $_SESSION['flash_message'];
    $message = '<div class="alert alert-' . $flash['type'] . '">' . $flash['text'] . '</div>';
    unset($_SESSION['flash_message']);
}

// --- Handle Delete Employee ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Employee deleted successfully!'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Error deleting employee.'];
    }
    header("Location: manage_employees.php");
    exit;
}

$employees = $conn->query("SELECT id, employee_id, full_name, email_address, contact_number, designation FROM employees ORDER BY full_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Employees</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Manage Employees</h2>
        <div>
            <a href="manage_increments.php" class="btn btn-info">Manage Increments</a>
            <a href="manage_overtime.php" class="btn btn-warning">Manage Overtime</a>
            <a href="add_employee.php" class="btn btn-success">Add New Employee</a>
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
    <?php echo $message; ?>

    <!-- Existing Members List -->
    <div class="card">
        <div class="card-header">Existing Employees</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Contact Number</th>
                            <th>Designation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($employees->num_rows > 0): ?>
                            <?php while ($row = $employees->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email_address']); ?></td>
                                    <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['designation']); ?></td>
                                    <td class="text-nowrap">
                                        <a href="edit_employee.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Edit / View</a>
                                        <a href="manage_employees.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this employee? This action cannot be undone.')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No employees found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>