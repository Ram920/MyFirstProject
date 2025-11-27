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

// --- Handle Overtime Update ---
if (isset($_POST['save_overtime'])) {
    $overtime_data = $_POST['overtime_pay'];
    $all_successful = true;

    // Begin a transaction
    $conn->begin_transaction();

    try {
        $sql = "UPDATE employees SET overtime_pay = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);

        foreach ($overtime_data as $employee_id => $amount) {
            $amount = (float)($amount ?? 0.00);
            $emp_id = (int)$employee_id;
            $stmt->bind_param("di", $amount, $emp_id);
            if (!$stmt->execute()) {
                // If any update fails, mark as unsuccessful
                $all_successful = false;
            }
        }
        $stmt->close();

        // If all updates were successful, commit the transaction
        if ($all_successful) {
            $conn->commit();
            $message = '<div class="alert alert-success">Overtime pay for all employees updated successfully!</div>';
        } else {
            // Otherwise, roll back the transaction
            $conn->rollback();
            $message = '<div class="alert alert-danger">An error occurred. Some overtime values could not be updated.</div>';
        }
    } catch (Exception $e) {
        $conn->rollback();
        $message = '<div class="alert alert-danger">A database error occurred: ' . $e->getMessage() . '</div>';
    }
}

// Fetch all employees
$employees = $conn->query("SELECT id, employee_id, full_name, designation, overtime_pay FROM employees ORDER BY full_name ASC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Monthly Overtime</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Manage Monthly Overtime</h2>
        <div>
            <a href="manage_employees.php" class="btn btn-secondary">← Back to Manage Employees</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
    <?php echo $message; ?>

    <div class="card">
        <div class="card-header">
            Update Overtime Pay for: <strong><?php echo date('F Y'); ?></strong>
        </div>
        <div class="card-body">
            <form action="manage_overtime.php" method="post">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Full Name</th>
                                <th>Designation</th>
                                <th style="width: 200px;">Overtime Pay (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $employees->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['designation']); ?></td>
                                    <td>
                                        <input type="number" step="0.01" name="overtime_pay[<?php echo $row['id']; ?>]" class="form-control" value="<?php echo htmlspecialchars($row['overtime_pay']); ?>">
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" name="save_overtime" class="btn btn-primary">Save All Changes</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>