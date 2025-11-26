<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// --- Security & Session Management ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}
$_SESSION['last_activity'] = time();

require_once __DIR__ . '/../db_connect.php';
$companyName = "NUSH MECHANICAL & FABRICATOR WORKS"; // Define company name here

$message = '';

// --- Function to generate the next Employee ID ---
function generateNextEmployeeId($conn) {
    $prefix = 'NUSH-';
    $sql = "SELECT employee_id FROM employees WHERE employee_id LIKE ? ORDER BY CAST(SUBSTRING(employee_id, " . (strlen($prefix) + 1) . ") AS UNSIGNED) DESC, employee_id DESC LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $likePrefix = $prefix . '%';
    $stmt->bind_param("s", $likePrefix);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $lastId = $result->fetch_assoc()['employee_id'];
        $number = (int)substr($lastId, strlen($prefix));
        $nextNumber = $number + 1;
    } else {
        // This is the first employee
        $nextNumber = 1;
    }
    return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
}

// --- Handle Add Employee ---
if (isset($_POST['add_employee'])) {
    // Prepare an insert statement
    $sql = "INSERT INTO employees (employee_id, date_of_joining, date_of_leaving, designation, work_location, full_name, date_of_birth, gender, contact_number, pan_card, aadhar_number, email_address, permanent_address, current_address, emergency_contact_name, emergency_contact_relation, emergency_contact_number, basic_salary, hra, transport_allowance, medical_allowance, special_allowance, overtime_pay, deduction_pf, deduction_esi, deduction_pt, deduction_tds, deduction_loan, bonus, bank_name, bank_account_number, bank_ifsc_code, pay_cycle, uan_number, esi_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        // Bind variables to the prepared statement as parameters
        // Set parameters that might be null
        $doj = !empty($_POST['date_of_joining']) ? trim($_POST['date_of_joining']) : null;
        $dol = !empty($_POST['date_of_leaving']) ? trim($_POST['date_of_leaving']) : null;
        $dob = !empty($_POST['date_of_birth']) ? trim($_POST['date_of_birth']) : null;

        $stmt->bind_param(
    "sssssssssssssssssddddddddddddssssss",
    $employee_id, $doj, $dol, $designation, $work_location,
    $full_name, $dob, $gender, $contact, $pan, $aadhar, $email,
    $perm_address, $curr_address, $emergency_name, $emergency_relation,
    $emergency_number,
    $basic_salary, $hra, $transport_allowance, $medical_allowance,
    $special_allowance, $overtime_pay, $deduction_pf, $deduction_esi,
    $deduction_pt, $deduction_tds, $deduction_loan, $bonus,
    $bank_name, $bank_account_number, $bank_ifsc_code,
    $pay_cycle, $uan_number, $esi_number
);


        // Set parameters
        $employee_id = strtoupper(trim($_POST['employee_id']));
        $designation = strtoupper(trim($_POST['designation']));
        $work_location = strtoupper(trim($_POST['work_location']));

        $full_name = strtoupper(trim($_POST['full_name']));
        $gender = strtoupper(trim($_POST['gender']));
        $contact = trim($_POST['contact_number']);
        $pan = strtoupper(trim($_POST['pan_card']));
        $aadhar = trim($_POST['aadhar_number']);
        $email = trim($_POST['email_address']);
        $perm_address = strtoupper(trim($_POST['permanent_address']));
        $curr_address = strtoupper(trim($_POST['current_address']));
        $emergency_name = strtoupper(trim($_POST['emergency_contact_name']));
        $emergency_relation = strtoupper(trim($_POST['emergency_contact_relation']));
        $emergency_number = trim($_POST['emergency_contact_number']);

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
        $bank_name = strtoupper(trim($_POST['bank_name']));
        $bank_account_number = trim($_POST['bank_account_number']);
        $bank_ifsc_code = strtoupper(trim($_POST['bank_ifsc_code']));
        $pay_cycle = strtoupper(trim($_POST['pay_cycle']));
        $uan_number = strtoupper(trim($_POST['uan_number']));
        $esi_number = trim($_POST['esi_number']);

        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Employee added successfully!'];
            header("location: manage_employees.php");
            exit();
        } else {
            // Check for duplicate entry
            if ($conn->errno == 1062) {
                 $message = '<div class="alert alert-danger">Error: An employee with this Employee ID, PAN, Aadhar, or Email already exists.</div>';
            } else {
                 $message = '<div class="alert alert-danger">Oops! Something went wrong. Please try again later.</div>';
            }
        }
        $stmt->close();
    } else {
        // Error preparing statement
        $message = '<div class="alert alert-danger">Error preparing the database statement: ' . htmlspecialchars($conn->error) . '</div>';
    }
}

// Generate the new Employee ID for the form
$new_employee_id = generateNextEmployeeId($conn);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Employee</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Use html2pdf.js which includes jsPDF and html2canvas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        /* Styles for the preview modal */
        .pdf-preview-modal {
            display: none; /* Hidden by default */
            position: fixed; z-index: 1060; left: 0; top: 0;
            width: 100%; height: 100%;
            overflow: auto; background-color: rgba(0,0,0,0.5);
        }
        .pdf-preview-modal-content {
            background-color: #fefefe;
            margin: 5% auto; padding: 20px;
            border: 1px solid #888;
            width: 80%; max-width: 800px;
            position: relative;
        }
        .pdf-preview-modal-header {
            padding: 10px 15px; border-bottom: 1px solid #dee2e6;
            display: flex; justify-content: space-between; align-items: center;
        }
        .pdf-preview-modal-body {
            padding: 15px; height: 60vh; overflow-y: auto;
            border: 1px solid #ccc; margin-top: 15px;
        }
        .pdf-preview-modal-footer {
            padding: 15px; border-top: 1px solid #dee2e6; text-align: right;
        }
        .close-preview-btn { cursor: pointer; font-size: 1.5rem; }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="manage_employees.php" class="btn btn-secondary">← Back to Manage Employees</a>
        <h2>Add New Employee</h2>
        <button id="downloadPdfBtn" class="btn btn-info">Download Blank Form (PDF)</button>
        <span id="company-name" style="display: none;"><?php echo htmlspecialchars($companyName); ?></span>
    </div>
    <?php echo $message; ?>

    <div id="form-container" class="card my-4">
        <div class="card-body">
            <form action="add_employee.php" method="post">
                <h5>Employment Details</h5>
                <hr>
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Employee ID*</label>
                        <input type="text" name="employee_id" class="form-control" required readonly style="text-transform: uppercase;" value="<?php echo htmlspecialchars($new_employee_id); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Date of Joining</label>
                        <input type="date" name="date_of_joining" class="form-control" value="<?php echo isset($_POST['date_of_joining']) ? htmlspecialchars($_POST['date_of_joining']) : ''; ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Designation</label>
                        <input type="text" name="designation" class="form-control" style="text-transform: uppercase;" value="<?php echo isset($_POST['designation']) ? htmlspecialchars($_POST['designation']) : ''; ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Work Location</label>
                        <input type="text" name="work_location" class="form-control" style="text-transform: uppercase;" value="<?php echo isset($_POST['work_location']) ? htmlspecialchars($_POST['work_location']) : ''; ?>">
                    </div>
                     <div class="form-group col-md-3">
                        <label>Date of Leaving</label>
                        <input type="date" name="date_of_leaving" class="form-control" value="<?php echo isset($_POST['date_of_leaving']) ? htmlspecialchars($_POST['date_of_leaving']) : ''; ?>">
                    </div>
                </div>
                <h5>Personal Information</h5>
                <hr>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Full Name*</label>
                        <input type="text" name="full_name" class="form-control" required style="text-transform: uppercase;" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">Select...</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Contact Number</label>
                        <input type="tel" name="contact_number" class="form-control" pattern="[0-9]{10}" maxlength="10" title="Enter a 10-digit contact number" value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : ''; ?>">
                    </div>
                     <div class="form-group col-md-4">
                        <label>Email Address</label>
                        <input type="email" name="email_address" class="form-control" value="<?php echo isset($_POST['email_address']) ? htmlspecialchars($_POST['email_address']) : ''; ?>">
                    </div>
                </div>
                 <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>PAN Card</label>
                        <input type="text" name="pan_card" class="form-control" pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}" title="Enter a valid PAN number" style="text-transform: uppercase;" value="<?php echo isset($_POST['pan_card']) ? htmlspecialchars($_POST['pan_card']) : ''; ?>">
                    </div>
                     <div class="form-group col-md-4">
                        <label>Aadhar Number</label>
                        <input type="text" name="aadhar_number" class="form-control" pattern="\d{12}" title="Enter a 12-digit Aadhar number" value="<?php echo isset($_POST['aadhar_number']) ? htmlspecialchars($_POST['aadhar_number']) : ''; ?>">
                    </div>
                </div>

                <h5>Address Details</h5>
                <hr>
                <div class="form-group">
                    <label>Permanent Address</label>
                    <textarea name="permanent_address" class="form-control" rows="3" style="text-transform: uppercase;"><?php echo isset($_POST['permanent_address']) ? htmlspecialchars($_POST['permanent_address']) : ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label>Current Address</label>
                    <textarea name="current_address" class="form-control" rows="3" style="text-transform: uppercase;"><?php echo isset($_POST['current_address']) ? htmlspecialchars($_POST['current_address']) : ''; ?></textarea>
                </div>

                <h5>Emergency Contact</h5>
                <hr>
                <div class="form-row">
                    <div class="form-group col-md-5"><label>Contact Name</label><input type="text" name="emergency_contact_name" class="form-control" style="text-transform: uppercase;" value="<?php echo isset($_POST['emergency_contact_name']) ? htmlspecialchars($_POST['emergency_contact_name']) : ''; ?>"></div>
                    <div class="form-group col-md-3"><label>Relation</label><input type="text" name="emergency_contact_relation" class="form-control" style="text-transform: uppercase;" value="<?php echo isset($_POST['emergency_contact_relation']) ? htmlspecialchars($_POST['emergency_contact_relation']) : ''; ?>"></div>
                    <div class="form-group col-md-4"><label>Contact Number</label><input type="tel" name="emergency_contact_number" class="form-control" pattern="[0-9]{10}" maxlength="10" title="Enter a 10-digit contact number" value="<?php echo isset($_POST['emergency_contact_number']) ? htmlspecialchars($_POST['emergency_contact_number']) : ''; ?>"></div>
                </div>
                
                <h5 id="salary-section-header">Salary & Compensation</h5>
                <hr>
                <div class="form-row">
                    <div class="form-group col-md-3"><label>Basic Salary</label><input type="number" step="0.01" name="basic_salary" class="form-control" value="<?php echo isset($_POST['basic_salary']) ? htmlspecialchars($_POST['basic_salary']) : '0.00'; ?>"></div>
                    <div class="form-group col-md-3"><label>HRA</label><input type="number" step="0.01" name="hra" class="form-control" value="<?php echo isset($_POST['hra']) ? htmlspecialchars($_POST['hra']) : '0.00'; ?>"></div>
                    <div class="form-group col-md-3"><label>Pay Cycle</label>
                        <select name="pay_cycle" class="form-control">
                            <option value="Monthly" selected>Monthly</option>
                            <option value="Weekly">Weekly</option>
                        </select>
                    </div>
                </div>
                <h6 class="allowances-header">Allowances</h6>
                <div class="form-row">
                    <div class="form-group col-md-2"><label>Transport</label><input type="number" step="0.01" name="transport_allowance" class="form-control" value="<?php echo isset($_POST['transport_allowance']) ? htmlspecialchars($_POST['transport_allowance']) : '0.00'; ?>"></div>
                    <div class="form-group col-md-2"><label>Medical</label><input type="number" step="0.01" name="medical_allowance" class="form-control" value="<?php echo isset($_POST['medical_allowance']) ? htmlspecialchars($_POST['medical_allowance']) : '0.00'; ?>"></div>
                    <div class="form-group col-md-2"><label>Special</label><input type="number" step="0.01" name="special_allowance" class="form-control" value="<?php echo isset($_POST['special_allowance']) ? htmlspecialchars($_POST['special_allowance']) : '0.00'; ?>"></div>
                    <div class="form-group col-md-3"><label>Overtime Pay</label><input type="number" step="0.01" name="overtime_pay" class="form-control" value="<?php echo isset($_POST['overtime_pay']) ? htmlspecialchars($_POST['overtime_pay']) : '0.00'; ?>"></div>
                    <div class="form-group col-md-3"><label>Bonus/Incentive</label><input type="number" step="0.01" name="bonus" class="form-control" value="<?php echo isset($_POST['bonus']) ? htmlspecialchars($_POST['bonus']) : '0.00'; ?>"></div>
                </div>
                <h6 class="deductions-header">Deductions</h6>
                <div class="form-row">
                    <div class="form-group col-md-2"><label>PF</label><input type="number" step="0.01" name="deduction_pf" class="form-control" value="<?php echo isset($_POST['deduction_pf']) ? htmlspecialchars($_POST['deduction_pf']) : '0.00'; ?>"></div>
                    <div class="form-group col-md-2"><label>ESI</label><input type="number" step="0.01" name="deduction_esi" class="form-control" value="<?php echo isset($_POST['deduction_esi']) ? htmlspecialchars($_POST['deduction_esi']) : '0.00'; ?>"></div>
                    <div class="form-group col-md-2"><label>Prof. Tax</label><input type="number" step="0.01" name="deduction_pt" class="form-control" value="<?php echo isset($_POST['deduction_pt']) ? htmlspecialchars($_POST['deduction_pt']) : '0.00'; ?>"></div>
                    <div class="form-group col-md-2"><label>Income Tax</label><input type="number" step="0.01" name="deduction_tds" class="form-control" value="<?php echo isset($_POST['deduction_tds']) ? htmlspecialchars($_POST['deduction_tds']) : '0.00'; ?>"></div>
                    <div class="form-group col-md-2"><label>Loan EMI</label><input type="number" step="0.01" name="deduction_loan" class="form-control" value="<?php echo isset($_POST['deduction_loan']) ? htmlspecialchars($_POST['deduction_loan']) : '0.00'; ?>"></div>
                </div>

                <h5>Statutory & Bank Details</h5>
                <hr>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>UAN Number</label>
                        <input type="text" name="uan_number" class="form-control" value="<?php echo isset($_POST['uan_number']) ? htmlspecialchars($_POST['uan_number']) : ''; ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label>ESI Number</label>
                        <input type="text" name="esi_number" class="form-control" value="<?php echo isset($_POST['esi_number']) ? htmlspecialchars($_POST['esi_number']) : ''; ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Bank Name</label>
                        <input type="text" name="bank_name" class="form-control" style="text-transform: uppercase;" value="<?php echo isset($_POST['bank_name']) ? htmlspecialchars($_POST['bank_name']) : ''; ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Bank Account Number</label>
                        <input type="text" name="bank_account_number" class="form-control" value="<?php echo isset($_POST['bank_account_number']) ? htmlspecialchars($_POST['bank_account_number']) : ''; ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label>IFSC Code</label>
                        <input type="text" name="bank_ifsc_code" class="form-control" style="text-transform: uppercase;" value="<?php echo isset($_POST['bank_ifsc_code']) ? htmlspecialchars($_POST['bank_ifsc_code']) : ''; ?>">
                    </div>
                </div>

                <button type="submit" name="add_employee" class="btn btn-primary">Add Employee</button>
            </form>
        </div>
    </div>

    <!-- PDF Preview Modal -->
    <div id="pdfPreviewModal" class="pdf-preview-modal">
        <div class="pdf-preview-modal-content">
            <div class="pdf-preview-modal-header">
                <h4>PDF Preview</h4>
                <span id="closePreviewBtn" class="close-preview-btn">&times;</span>
            </div>
            <div id="pdfPreviewBody" class="pdf-preview-modal-body">
                <!-- Preview content will be injected here -->
            </div>
            <div class="pdf-preview-modal-footer">
                <button id="cancelPreviewBtn" class="btn btn-secondary">Cancel</button>
                <button id="confirmDownloadBtn" class="btn btn-primary">Confirm & Download</button>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('downloadPdfBtn').addEventListener('click', function () {
    const companyName = document.getElementById('company-name').textContent.trim();

    const openPreview = () => {
        // 1. Clone the form to avoid altering the original
        const formToPrint = document.getElementById('form-container').cloneNode(true);

        // 2. Remove salary and related sections from the cloned form
        const salaryHeader = formToPrint.querySelector('#salary-section-header');
        const allowancesHeader = formToPrint.querySelector('.allowances-header');

        if (salaryHeader) {
            let currentElement = salaryHeader;
            // Loop and remove all elements until we hit the next H5 tag, which marks a new section.
            while (currentElement && currentElement.nextElementSibling && currentElement.nextElementSibling.tagName !== 'H5') {
                currentElement.nextElementSibling.remove();
            }
            // Finally, remove the salary header itself.
            currentElement.remove();
        }

        // 3. Remove the final submit button from the clone
        const submitButton = formToPrint.querySelector('button[name="add_employee"]');
        if (submitButton) {
            submitButton.remove();
        }

        // 4. Replace all inputs, textareas, and selects with blank lines for a clean look
        formToPrint.querySelectorAll('input, textarea, select').forEach(el => {
            const blankLine = document.createElement('div');
            blankLine.style.height = '20px'; // Adjust height to match form control
            blankLine.style.borderBottom = '1px solid #333';
            blankLine.style.marginTop = '5px';
            el.parentNode.replaceChild(blankLine, el);
        });

        // 3. Create the full HTML content for the PDF
        const header = `
            <div style="display:flex; align-items:center; padding: 0 10px; margin-bottom: 20px;">
                <h2 style="font-size:1.5rem; font-family: sans-serif; font-weight: bold; text-align:center; width: 100%;">${companyName}</h2>
            </div>`;
        const finalHtml = header + formToPrint.innerHTML;

        // 4. Show the preview modal
        const modal = document.getElementById('pdfPreviewModal');
        const previewBody = document.getElementById('pdfPreviewBody');
        previewBody.innerHTML = finalHtml;
        modal.style.display = 'block';

        // 5. Handle modal buttons
        const closeModal = () => {
            modal.style.display = 'none';
            previewBody.innerHTML = ''; // Clean up
        };

        document.getElementById('closePreviewBtn').onclick = closeModal;
        document.getElementById('cancelPreviewBtn').onclick = closeModal;
        document.getElementById('confirmDownloadBtn').onclick = () => {
            generatePdf(finalHtml);
            closeModal();
        };
    };

    const generatePdf = (htmlContent) => {
        const opt = {
            margin: [15, 10, 15, 10],
            filename: 'blank_employee_form.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true, logging: true },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
            pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
        };
        html2pdf().set(opt).from(htmlContent).save();
    };

    // Open the preview immediately
    openPreview();
});
</script>
<?php
$conn->close();
?>
</body>
</html>