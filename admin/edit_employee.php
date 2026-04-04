<?php
session_start();

// --- Security & Session Management ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}
$_SESSION['last_activity'] = time();

require_once __DIR__ . '/../db_connect.php';

$message = '';
$employee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($employee_id <= 0) {
    header("Location: manage_employees.php");
    exit;
}

// --- Handle Update ---
if (isset($_POST['update_employee'])) {
    $sql = "UPDATE employees SET 
                employee_id=:employee_id_val, date_of_joining=:doj, date_of_leaving=:dol, 
                designation=:designation, work_location=:work_location, full_name=:full_name, 
                date_of_birth=:dob, gender=:gender, contact_number=:contact, pan_card=:pan, 
                aadhar_number=:aadhar, email_address=:email, permanent_address=:perm_address, 
                current_address=:curr_address, emergency_contact_name=:emergency_name, 
                emergency_contact_relation=:emergency_relation, emergency_contact_number=:emergency_number, 
                basic_salary=:basic_salary, hra=:hra, transport_allowance=:transport_allowance, 
                medical_allowance=:medical_allowance, special_allowance=:special_allowance, 
                overtime_pay=:overtime_pay, deduction_pf=:deduction_pf, deduction_esi=:deduction_esi, 
                deduction_pt=:deduction_pt, deduction_tds=:deduction_tds, deduction_loan=:deduction_loan, 
                bonus=:bonus, bank_name=:bank_name, bank_account_number=:bank_account_number, 
                bank_ifsc_code=:bank_ifsc_code, pay_cycle=:pay_cycle, uan_number=:uan_number, 
                esi_number=:esi_number 
            WHERE id=:id";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // Set parameters
        $employee_id_val = trim($_POST['employee_id'] ?? '');
        $doj = !empty($_POST['date_of_joining']) ? trim($_POST['date_of_joining']) : null;
        $dol = !empty($_POST['date_of_leaving']) ? trim($_POST['date_of_leaving']) : null;
        $designation = trim($_POST['designation'] ?? '');
        $work_location = trim($_POST['work_location'] ?? '');

        $full_name = trim($_POST['full_name'] ?? '');
        $dob = !empty($_POST['date_of_birth']) ? trim($_POST['date_of_birth']) : null;
        $gender = trim($_POST['gender'] ?? '');
        $contact = trim($_POST['contact_number'] ?? '');
        $pan = trim($_POST['pan_card'] ?? '');
        $aadhar = trim($_POST['aadhar_number'] ?? '');
        $email = trim($_POST['email_address'] ?? '');
        $perm_address = trim($_POST['permanent_address'] ?? '');
        $curr_address = trim($_POST['current_address'] ?? '');
        $emergency_name = trim($_POST['emergency_contact_name'] ?? '');
        $emergency_relation = trim($_POST['emergency_contact_relation'] ?? '');
        $emergency_number = trim($_POST['emergency_contact_number'] ?? '');

        // Salary & Compensation
        $basic_salary = (float)($_POST['basic_salary'] ?? 0.00);
        $hra = (float)($_POST['hra'] ?? 0.00);
        $transport_allowance = (float)($_POST['transport_allowance'] ?? 0.00);
        $medical_allowance = (float)($_POST['medical_allowance'] ?? 0.00);
        $special_allowance = (float)($_POST['special_allowance'] ?? 0.00);
        $overtime_pay = (float)($_POST['overtime_pay'] ?? 0.00);
        $deduction_pf = (float)($_POST['deduction_pf'] ?? 0.00);
        $deduction_esi = (float)($_POST['deduction_esi'] ?? 0.00);
        $deduction_pt = (float)($_POST['deduction_pt'] ?? 0.00);
        $deduction_tds = (float)($_POST['deduction_tds'] ?? 0.00);
        $deduction_loan = (float)($_POST['deduction_loan'] ?? 0.00);
        $bonus = (float)($_POST['bonus'] ?? 0.00);
        $bank_name = trim($_POST['bank_name'] ?? '');
        $bank_account_number = trim($_POST['bank_account_number'] ?? '');
        $bank_ifsc_code = trim($_POST['bank_ifsc_code'] ?? '');
        $pay_cycle = trim($_POST['pay_cycle'] ?? '');
        $uan_number = trim($_POST['uan_number'] ?? '');
        $esi_number = trim($_POST['esi_number'] ?? '');

        if ($stmt->execute([
            ':employee_id_val' => $employee_id_val,
            ':doj' => $doj,
            ':dol' => $dol,
            ':designation' => $designation,
            ':work_location' => $work_location,
            ':full_name' => $full_name,
            ':dob' => $dob,
            ':gender' => $gender,
            ':contact' => $contact,
            ':pan' => $pan,
            ':aadhar' => $aadhar,
            ':email' => $email,
            ':perm_address' => $perm_address,
            ':curr_address' => $curr_address,
            ':emergency_name' => $emergency_name,
            ':emergency_relation' => $emergency_relation,
            ':emergency_number' => $emergency_number,
            ':basic_salary' => $basic_salary,
            ':hra' => $hra,
            ':transport_allowance' => $transport_allowance,
            ':medical_allowance' => $medical_allowance,
            ':special_allowance' => $special_allowance,
            ':overtime_pay' => $overtime_pay,
            ':deduction_pf' => $deduction_pf,
            ':deduction_esi' => $deduction_esi,
            ':deduction_pt' => $deduction_pt,
            ':deduction_tds' => $deduction_tds,
            ':deduction_loan' => $deduction_loan,
            ':bonus' => $bonus,
            ':bank_name' => $bank_name,
            ':bank_account_number' => $bank_account_number,
            ':bank_ifsc_code' => $bank_ifsc_code,
            ':pay_cycle' => $pay_cycle,
            ':uan_number' => $uan_number,
            ':esi_number' => $esi_number,
            ':id' => $employee_id
        ])) {
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Employee details updated successfully!'];
            header("Location: manage_employees.php");
            exit;
        } else {
            $errorInfo = $stmt->errorInfo();
            if ($errorInfo[0] === '23505') { // PostgreSQL unique violation error code
                $message = '<div class="alert alert-danger">Error: An employee with this Employee ID, PAN, Aadhar, or Email already exists.</div>';
            } else {
                $message = '<div class="alert alert-danger">Error updating record: ' . $errorInfo[2] . '</div>';
            }
        }
    }
}

// --- Fetch Employee Data ---
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = :id");
$stmt->execute([':id' => $employee_id]); // Execute with named parameter
$employee = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch as associative array
$stmt = null; // Close statement

if (!$employee) {
    header("Location: manage_employees.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Employee Details</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <a href="manage_employees.php" class="btn btn-secondary mb-3">← Back to Manage Employees</a>
    <h2>Edit Employee: <?php echo htmlspecialchars($employee['full_name']); ?></h2>
    <?php echo $message; ?>

    <div class="card my-4">
        <div class="card-body">
            <form action="edit_employee.php?id=<?php echo $employee_id; ?>" method="post">
                <h5>Employment Details</h5>
                <hr>
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Employee ID*</label>
                        <input type="text" name="employee_id" class="form-control" required style="text-transform: uppercase;" value="<?php echo htmlspecialchars($employee['employee_id']); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Date of Joining</label>
                        <input type="date" name="date_of_joining" class="form-control" value="<?php echo htmlspecialchars($employee['date_of_joining']); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Designation</label>
                        <input type="text" name="designation" class="form-control" style="text-transform: uppercase;" value="<?php echo htmlspecialchars($employee['designation']); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Work Location</label>
                        <input type="text" name="work_location" class="form-control" style="text-transform: uppercase;" value="<?php echo htmlspecialchars($employee['work_location']); ?>">
                    </div>
                     <div class="form-group col-md-3">
                        <label>Date of Leaving</label>
                        <input type="date" name="date_of_leaving" class="form-control" value="<?php echo htmlspecialchars($employee['date_of_leaving']); ?>">
                    </div>
                </div>

                <h5>Personal Information</h5>
                <hr>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Full Name*</label>
                        <input type="text" name="full_name" class="form-control" required style="text-transform: uppercase;" value="<?php echo htmlspecialchars($employee['full_name']); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" value="<?php echo htmlspecialchars($employee['date_of_birth']); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <option value="Male" <?php echo ($employee['gender'] == 'MALE') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($employee['gender'] == 'FEMALE') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($employee['gender'] == 'OTHER') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Contact Number</label>
                        <input type="tel" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($employee['contact_number']); ?>">
                    </div>
                     <div class="form-group col-md-4">
                        <label>Email Address</label>
                        <input type="email" name="email_address" class="form-control" value="<?php echo htmlspecialchars($employee['email_address']); ?>">
                    </div>
                </div>
                 <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>PAN Card</label>
                        <input type="text" name="pan_card" class="form-control" style="text-transform: uppercase;" value="<?php echo htmlspecialchars($employee['pan_card']); ?>">
                    </div>
                     <div class="form-group col-md-4">
                        <label>Aadhar Number</label>
                        <input type="text" name="aadhar_number" class="form-control" value="<?php echo htmlspecialchars($employee['aadhar_number']); ?>">
                    </div>
                </div>

                <h5>Salary & Compensation</h5>
                <hr>
                <div class="form-row">
                    <div class="form-group col-md-3"><label>Basic Salary</label><input type="number" step="0.01" name="basic_salary" class="form-control" value="<?php echo htmlspecialchars($employee['basic_salary']); ?>"></div>
                    <div class="form-group col-md-3"><label>HRA</label><input type="number" step="0.01" name="hra" class="form-control" value="<?php echo htmlspecialchars($employee['hra']); ?>"></div>
                    <div class="form-group col-md-3">
                        <label>Pay Cycle</label>
                        <select name="pay_cycle" class="form-control">
                            <option value="Monthly" <?php echo ($employee['pay_cycle'] == 'Monthly') ? 'selected' : ''; ?>>Monthly</option>
                            <option value="Weekly" <?php echo ($employee['pay_cycle'] == 'Weekly') ? 'selected' : ''; ?>>Weekly</option>
                        </select>
                    </div>
                </div>
                <h6>Allowances</h6>
                <div class="form-row">
                    <div class="form-group col-md-2"><label>Transport</label><input type="number" step="0.01" name="transport_allowance" class="form-control" value="<?php echo htmlspecialchars($employee['transport_allowance']); ?>"></div>
                    <div class="form-group col-md-2"><label>Medical</label><input type="number" step="0.01" name="medical_allowance" class="form-control" value="<?php echo htmlspecialchars($employee['medical_allowance']); ?>"></div>
                    <div class="form-group col-md-2"><label>Special</label><input type="number" step="0.01" name="special_allowance" class="form-control" value="<?php echo htmlspecialchars($employee['special_allowance']); ?>"></div>
                    <div class="form-group col-md-3"><label>Overtime Pay</label><input type="number" step="0.01" name="overtime_pay" class="form-control" value="<?php echo htmlspecialchars($employee['overtime_pay']); ?>"></div>
                    <div class="form-group col-md-3"><label>Bonus/Incentive</label><input type="number" step="0.01" name="bonus" class="form-control" value="<?php echo htmlspecialchars($employee['bonus']); ?>"></div>
                </div>
                <h6>Deductions</h6>
                <div class="form-row">
                    <div class="form-group col-md-2"><label>PF</label><input type="number" step="0.01" name="deduction_pf" class="form-control" value="<?php echo htmlspecialchars($employee['deduction_pf']); ?>"></div>
                    <div class="form-group col-md-2"><label>ESI</label><input type="number" step="0.01" name="deduction_esi" class="form-control" value="<?php echo htmlspecialchars($employee['deduction_esi']); ?>"></div>
                    <div class="form-group col-md-2"><label>Prof. Tax</label><input type="number" step="0.01" name="deduction_pt" class="form-control" value="<?php echo htmlspecialchars($employee['deduction_pt']); ?>"></div>
                    <div class="form-group col-md-2"><label>Income Tax</label><input type="number" step="0.01" name="deduction_tds" class="form-control" value="<?php echo htmlspecialchars($employee['deduction_tds']); ?>"></div>
                    <div class="form-group col-md-2"><label>Loan EMI</label><input type="number" step="0.01" name="deduction_loan" class="form-control" value="<?php echo htmlspecialchars($employee['deduction_loan']); ?>"></div>
                </div>

                <h5>Address Details</h5>
                <hr>
                <div class="form-group">
                    <label>Permanent Address</label>
                    <textarea name="permanent_address" class="form-control" rows="3" style="text-transform: uppercase;"><?php echo htmlspecialchars($employee['permanent_address']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Current Address</label>
                    <textarea name="current_address" class="form-control" rows="3" style="text-transform: uppercase;"><?php echo htmlspecialchars($employee['current_address']); ?></textarea>
                </div>

                <h5>Emergency Contact</h5>
                <hr>
                <div class="form-row">
                    <div class="form-group col-md-5"><label>Contact Name</label><input type="text" name="emergency_contact_name" class="form-control" style="text-transform: uppercase;" value="<?php echo htmlspecialchars($employee['emergency_contact_name']); ?>"></div>
                    <div class="form-group col-md-3"><label>Relation</label><input type="text" name="emergency_contact_relation" class="form-control" style="text-transform: uppercase;" value="<?php echo htmlspecialchars($employee['emergency_contact_relation']); ?>"></div>
                    <div class="form-group col-md-4"><label>Contact Number</label><input type="tel" name="emergency_contact_number" class="form-control" value="<?php echo htmlspecialchars($employee['emergency_contact_number']); ?>"></div>
                </div>

                <h5>Statutory & Bank Details</h5>
                <hr>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>UAN Number</label>
                        <input type="text" name="uan_number" class="form-control" value="<?php echo htmlspecialchars($employee['uan_number']); ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label>ESI Number</label>
                        <input type="text" name="esi_number" class="form-control" value="<?php echo htmlspecialchars($employee['esi_number']); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Bank Name</label>
                        <input type="text" name="bank_name" class="form-control" value="<?php echo htmlspecialchars($employee['bank_name']); ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Bank Account Number</label>
                        <input type="text" name="bank_account_number" class="form-control" value="<?php echo htmlspecialchars($employee['bank_account_number']); ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label>IFSC Code</label>
                        <input type="text" name="bank_ifsc_code" class="form-control" value="<?php echo htmlspecialchars($employee['bank_ifsc_code']); ?>">
                    </div>
                </div>

                <button type="submit" name="update_employee" class="btn btn-primary">Update Employee</button>
            </form>
        </div>
    </div>
</div>
</body>

</html>
<?php $conn = null; ?>