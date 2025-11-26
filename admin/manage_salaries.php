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

// --- Handle Finalize & Store Salaries ---
if (isset($_POST['finalize_salaries'])) {
    $slip_month = (int)date('m');
    $slip_year = (int)date('Y');
    $employees_to_process = $conn->query("SELECT * FROM employees");
    $slips_generated = 0;
    $slips_skipped = 0;

    while ($emp = $employees_to_process->fetch_assoc()) {
        // Check if a slip for this employee and month already exists
        $check_stmt = $conn->prepare("SELECT id FROM salary_slips WHERE employee_db_id = ? AND slip_month = ? AND slip_year = ?");
        $check_stmt->bind_param("iii", $emp['id'], $slip_month, $slip_year);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $slips_skipped++;
            continue; // Skip if already generated
        }

        // Calculations
        $gross_earnings = (float)$emp['basic_salary'] + (float)$emp['hra'] + (float)$emp['transport_allowance'] + (float)$emp['medical_allowance'] + (float)$emp['special_allowance'] + (float)$emp['overtime_pay'] + (float)$emp['bonus'];
        $total_deductions = (float)$emp['deduction_pf'] + (float)$emp['deduction_esi'] + (float)$emp['deduction_pt'] + (float)$emp['deduction_tds'] + (float)$emp['deduction_loan'];
        $net_salary = $gross_earnings - $total_deductions;

        // Prepare insert statement
        $insert_stmt = $conn->prepare("INSERT INTO salary_slips (employee_db_id, slip_month, slip_year, employee_id_str, full_name, designation, pan_card, uan_number, esi_number, bank_name, bank_account_number, pay_cycle, basic_salary, hra, transport_allowance, medical_allowance, special_allowance, overtime_pay, bonus, deduction_pf, deduction_esi, deduction_pt, deduction_tds, deduction_loan, gross_earnings, total_deductions, net_salary) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $insert_stmt->bind_param("iiisssssssssddddddddddddddd", $emp['id'], $slip_month, $slip_year, $emp['employee_id'], $emp['full_name'], $emp['designation'], $emp['pan_card'], $emp['uan_number'], $emp['esi_number'], $emp['bank_name'], $emp['bank_account_number'], $emp['pay_cycle'], $emp['basic_salary'], $emp['hra'], $emp['transport_allowance'], $emp['medical_allowance'], $emp['special_allowance'], $emp['overtime_pay'], $emp['bonus'], $emp['deduction_pf'], $emp['deduction_esi'], $emp['deduction_pt'], $emp['deduction_tds'], $emp['deduction_loan'], $gross_earnings, $total_deductions, $net_salary);

        if ($insert_stmt->execute()) {
            $slips_generated++;
        }
    }

    $message = '<div class="alert alert-success">Payroll processing complete for ' . date('F Y') . '.<br>' . $slips_generated . ' new payslips were generated.<br>' . $slips_skipped . ' employees were skipped as their payslips for this month already exist.</div>';
}

// --- Fetch data for display ---
// Fetch all employees for the current salary overview
$employees_result = $conn->query("SELECT * FROM employees ORDER BY full_name ASC");

// Fetch generated slips for a quick overview
$generated_slips_result = $conn->query("
    SELECT ss.id, ss.slip_month, ss.slip_year, e.full_name 
    FROM salary_slips ss 
    JOIN employees e ON ss.employee_db_id = e.id 
    ORDER BY ss.slip_year DESC, ss.slip_month DESC, e.full_name ASC 
");

// Group slips by month for easier management
$slips_by_month = [];
if ($generated_slips_result->num_rows > 0) {
    while ($slip = $generated_slips_result->fetch_assoc()) {
        $month_year_key = $slip['slip_year'] . '-' . str_pad($slip['slip_month'], 2, '0', STR_PAD_LEFT);
        $slips_by_month[$month_year_key][] = $slip;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Employee Salaries</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Manage Employee Salaries</h2>
        <div>
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
    <?php echo $message; ?>
    <p>This page shows a live calculation of salaries based on current employee data. To store these salaries permanently for the month of <strong><?php echo date('F Y'); ?></strong>, click the "Finalize" button.</p>

    <div class="row mb-4">
        <div class="col-md-6">
            <form action="manage_salaries.php" method="post" onsubmit="return confirm('Are you sure you want to finalize and store the payroll for the current month? This will create a permanent record for each employee and cannot be easily undone.');">
                <button type="submit" name="finalize_salaries" class="btn btn-success btn-lg">Finalize & Store Payroll for <?php echo date('F Y'); ?></button>
            </form>
        </div>
    </div>


    <!-- Current Salaries Table -->
    <div class="card">
        <div class="card-header">Salary Overview</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th>Emp. ID</th>
                            <th>Full Name</th>
                            <th>Basic Salary</th>
                            <th>Gross Earnings</th>
                            <th>Total Deductions</th>
                            <th>Net Salary</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($employees_result->num_rows > 0): ?>
                            <?php while ($emp = $employees_result->fetch_assoc()): ?>
                                <?php
                                    // Calculations
                                    $gross_earnings = (float)$emp['basic_salary']
                                                    + (float)$emp['hra']
                                                    + (float)$emp['transport_allowance']
                                                    + (float)$emp['overtime_pay']
                                                    + (float)$emp['medical_allowance']
                                                    + (float)$emp['special_allowance']
                                                    + (float)$emp['bonus'];

                                    $total_deductions = (float)$emp['deduction_pf']
                                                      + (float)$emp['deduction_esi']
                                                      + (float)$emp['deduction_pt']
                                                      + (float)$emp['deduction_tds']
                                                      + (float)$emp['deduction_loan'];

                                    $net_salary = $gross_earnings - $total_deductions;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['full_name']); ?></td>
                                    <td><?php echo number_format($emp['basic_salary'], 2); ?></td>
                                    <td class="text-success font-weight-bold"><?php echo number_format($gross_earnings, 2); ?></td>
                                    <td class="text-danger"><?php echo number_format($total_deductions, 2); ?></td>
                                    <td class="text-primary font-weight-bold"><?php echo number_format($net_salary, 2); ?></td>
                                    <td class="text-nowrap">
                                        <a href="edit_employee.php?id=<?php echo $emp['id']; ?>" class="btn btn-info btn-sm">Edit Salary</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No employees found. Please add an employee first.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recently Generated Slips -->
    <div class="card mt-5">
        <div class="card-header">Generated Payslips History</div>
        <div class="card-body">
            <?php if (empty($slips_by_month)): ?>
                <p class="text-muted">No payslips have been generated yet.</p>
            <?php else: ?>
                <?php foreach ($slips_by_month as $period => $slips): ?>
                    <?php
                        list($year, $month) = explode('-', $period);
                        $period_display = date('F Y', mktime(0, 0, 0, (int)$month, 1, (int)$year));
                    ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4><?php echo $period_display; ?></h4>
                            <a href="download_payslips_zip.php?month=<?php echo (int)$month; ?>&year=<?php echo (int)$year; ?>" class="btn btn-info">Download All as ZIP</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm mt-2">
                                <thead>
                                    <tr><th>Employee Name</th><th>Action</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($slips as $slip): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($slip['full_name']); ?></td>
                                            <td><a href="generate_payslip.php?id=<?php echo $slip['id']; ?>" class="btn btn-primary btn-sm" target="_blank">View/Print Payslip</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>

</html>
<?php $conn->close(); ?>