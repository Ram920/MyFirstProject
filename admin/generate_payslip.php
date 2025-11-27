<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/../db_connect.php';
require_once 'config.php';

$slip_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($slip_id <= 0) {
    exit('Invalid Payslip ID.');
}

// Join with employees table to get the email address
$stmt = $conn->prepare("
    SELECT ss.*, e.email_address 
    FROM salary_slips ss
    JOIN employees e ON ss.employee_db_id = e.id
    WHERE ss.id = ?");
$stmt->bind_param("i", $slip_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    exit('Payslip not found.');
}
$slip = $result->fetch_assoc();

// --- Prepare Logo Data as Base64 ---
$logoBase64 = '';
$logoPath = __DIR__ . '/../images/Logo.png';
if (file_exists($logoPath)) {
    $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
    $logoData = file_get_contents($logoPath);
    $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
}

$slip_period = date('F Y', mktime(0, 0, 0, $slip['slip_month'], 1, $slip['slip_year']));

// Check if this script is being called to generate content for PDF
$for_pdf = isset($_GET['for_pdf']) && $_GET['for_pdf'] === 'true';

if ($for_pdf) { ob_start(); } // Start output buffering if for PDF

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip for <?php echo htmlspecialchars($slip['full_name']) . ' - ' . $slip_period; ?></title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .payslip-container { max-width: 800px; margin: 30px auto; padding: 30px; background-color: #fff; border: 1px solid #dee2e6; }
        .payslip-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .payslip-header h2 { margin: 0; font-weight: bold; }
        .payslip-header p { margin: 0; }
        .table-bordered th, .table-bordered td { border: 1px solid #333 !important; }
        .table-sm td, .table-sm th { padding: .4rem; }
        .section-title { font-weight: bold; background-color: #e9ecef; }
        .text-right { text-align: right; }
        .font-weight-bold { font-weight: bold; }
        @media print {
            body { background-color: #fff; }
            .payslip-container { margin: 0; border: none; box-shadow: none; max-width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="payslip-container">
    <?php include 'payslip_template.php'; ?>

    <?php if (!$for_pdf): // Only show buttons if not generating for PDF ?>

    <div class="text-center mt-4 no-print">
        <button class="btn btn-primary" onclick="window.print()">Print Payslip</button>
        <button id="send-payslip-btn" class="btn btn-success" 
                data-slip-id="<?php echo $slip['id']; ?>"
                data-email="<?php echo htmlspecialchars($slip['email_address']); ?>"
                data-name="<?php echo htmlspecialchars($slip['full_name']); ?>"
                data-period="<?php echo $slip_period; ?>">
            Send to Employee
        </button>
        <a href="manage_salaries.php" class="btn btn-secondary">Back to Salaries</a>
        <div id="send-status" class="mt-3"></div>
    </div>
</div>
    
    <script src="../assets/vendor/jquery/jquery.min.js"></script>
    <script>
    $(document).ready(function(){
        $('#send-payslip-btn').on('click', function(e){
            e.preventDefault();
            var button = $(this);
            var statusDiv = $('#send-status');
    
            var customerEmail = button.data('email');
            if (!customerEmail) {
                statusDiv.html('<div class="alert alert-danger">Error: Employee email address is not available.</div>');
                return;
            }
    
            button.prop('disabled', true).text('Sending...');
            statusDiv.html('<div class="alert alert-info">Processing and sending email...</div>');
    
            $.ajax({
                url: 'send_payslip_email.php',
                type: 'POST',
                data: {
                    slip_id: button.data('slip-id'), // Send only the slip ID
                    customer_email: customerEmail,
                    customer_name: button.data('name'),
                    slip_period: button.data('period')
                },
                success: function(response){
                    if (response.trim() === 'OK') {
                        statusDiv.html('<div class="alert alert-success">Payslip sent successfully to ' + customerEmail + '!</div>');
                        button.text('Sent!').removeClass('btn-success').addClass('btn-secondary');
                    } else {
                        statusDiv.html('<div class="alert alert-danger">Error: ' + response + '</div>');
                        button.prop('disabled', false).text('Send to Employee');
                    }
                },
                error: function(xhr){
                    statusDiv.html('<div class="alert alert-danger">An unknown error occurred: ' + xhr.responseText + '</div>');
                    button.prop('disabled', false).text('Send to Employee');
                }
            });
        });
    });
    </script>
<?php endif; // End if (!$for_pdf) ?>

<?php if ($for_pdf) { echo ob_get_clean(); exit; } // End output buffering and exit if for PDF ?>

</body>
</html>
<?php $conn->close(); ?>