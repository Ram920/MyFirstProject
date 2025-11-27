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

// --- Define allowed salary components and get the selected one ---
$allowed_components = [
    'basic_salary' => 'Basic Salary',
    'hra' => 'HRA',
    'transport_allowance' => 'Transport Allowance',
    'medical_allowance' => 'Medical Allowance',
    'special_allowance' => 'Special Allowance',
    'bonus' => 'Bonus / Incentive'
];

$selected_component = 'basic_salary'; // Default component
if (isset($_GET['component']) && array_key_exists($_GET['component'], $allowed_components)) {
    $selected_component = $_GET['component'];
}

// --- Handle Increment Update ---
if (isset($_POST['save_increments'])) {
    $component_to_update = $_POST['component_name'];
    $new_values = $_POST[$component_to_update];
    $all_successful = true;

    $conn->begin_transaction();

    try {
        $sql = "UPDATE employees SET `$component_to_update` = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);

        foreach ($new_values as $employee_id => $new_salary) {
            $salary = (float)($new_salary ?? 0.00);
            $emp_id = (int)$employee_id;
            $stmt->bind_param("di", $salary, $emp_id);
            if (!$stmt->execute()) {
                $all_successful = false;
            }
        }
        $stmt->close();

        if ($all_successful) {
            $conn->commit();
            $message = '<div class="alert alert-success">Employee salaries updated successfully!</div>';
        } else {
            $conn->rollback();
            $message = '<div class="alert alert-danger">An error occurred. Some salaries could not be updated.</div>';
        }
    } catch (Exception $e) {
        $conn->rollback();
        $message = '<div class="alert alert-danger">A database error occurred: ' . $e->getMessage() . '</div>';
    }
}

// Fetch all employees
$employees = $conn->query("SELECT id, employee_id, full_name, designation, `$selected_component` FROM employees ORDER BY full_name ASC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Annual Increments</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Manage Annual Increments</h2>
        <div>
            <a href="manage_employees.php" class="btn btn-secondary">← Back to Manage Employees</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
    <?php echo $message; ?>

    <!-- Component Selector Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="manage_increments.php" method="get" class="form-inline">
                <div class="form-group">
                    <label for="component" class="mr-2"><strong>Select Salary Component to Update:</strong></label>
                    <select name="component" id="component" class="form-control mr-2" onchange="this.form.submit()">
                        <?php foreach ($allowed_components as $key => $value): ?>
                            <option value="<?php echo $key; ?>" <?php if ($key === $selected_component) echo 'selected'; ?>>
                                <?php echo $value; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Update <strong><?php echo $allowed_components[$selected_component]; ?></strong> for Annual Increment
        </div>
        <div class="card-body">
            <form action="manage_increments.php" method="post">
                <input type="hidden" name="component_name" value="<?php echo $selected_component; ?>">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Full Name</th>
                                <th>Current <?php echo $allowed_components[$selected_component]; ?> (₹)</th>
                                <th style="width: 250px;">New <?php echo $allowed_components[$selected_component]; ?> (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $employees->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo number_format($row[$selected_component], 2); ?></td>
                                    <td>
                                        <input type="number" step="0.01" name="<?php echo $selected_component; ?>[<?php echo $row['id']; ?>]" class="form-control" value="<?php echo htmlspecialchars($row[$selected_component]); ?>">
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" name="save_increments" class="btn btn-primary">Save All Salary Changes</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>