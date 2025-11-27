<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    exit('Access Denied');
}

// --- Debugging: Enable error reporting ---
error_reporting(E_ALL);
ini_set('display_errors', 1);



require_once 'config.php';
require_once __DIR__ . '/../db_connect.php';

// --- Manual Autoloading ---
require_once __DIR__ . '/../phpmailer/Exception.php';
require_once __DIR__ . '/../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/SMTP.php';
// --- Manual Autoloading for Dompdf ---
require_once __DIR__ . '/../dompdf/autoload.inc.php';


if (isset($_POST['slip_id']) && isset($_POST['customer_email'])) {
    $slip_id = (int)$_POST['slip_id'];
    $customer_email = filter_var($_POST['customer_email'], FILTER_VALIDATE_EMAIL);
    $customer_name = htmlspecialchars($_POST['customer_name'] ?? 'Valued Employee');
    $slip_period = htmlspecialchars($_POST['slip_period'] ?? 'the current month');

    if (!$customer_email) {
        http_response_code(400);
        exit('Invalid employee email address.');
    }

    // Check if SMTP credentials are set
    if (empty(SMTP_USERNAME) || empty(SMTP_PASSWORD)) {
        http_response_code(500);
        exit('SMTP credentials are not set in config.php.');
    }

    // --- Define and check a local temporary directory ---
    $local_temp_dir = __DIR__ . '/temp_files';
    if (!is_dir($local_temp_dir)) {
        // Attempt to create the directory
        if (!mkdir($local_temp_dir, 0755, true)) {
            http_response_code(500);
            exit('Error: Failed to create temporary directory. Please manually create a folder named "temp_files" inside the "admin" directory and give it write permissions.');
        }
    }

    $temp_pdf_path = null; // Initialize variable

    // --- More Detailed Error Handling ---
    try {
        // 1. Fetch Payslip Data
        $stmt = $conn->prepare("SELECT * FROM salary_slips WHERE id = ?");
        $stmt->bind_param("i", $slip_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            http_response_code(404);
            exit('Payslip not found.');
        }
        $slip = $result->fetch_assoc();
        $stmt->close();

        // --- Set logo to empty for email attachment ---
        $logoBase64 = '';

        // 2. Generate Payslip HTML using the template
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Payslip</title>
            <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
            <style>
                body { font-family: sans-serif; font-size: 10pt; margin: 0; padding: 0; } /* Added font-family and font-size for better PDF rendering */
                .payslip-container { max-width: 800px; margin: 0; padding: 0; background-color: #fff; border: 1px solid #dee2e6; }
                .payslip-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px; }
                .payslip-header h2 { font-size: 24px; font-weight: bold; margin: 0; } /* Changed h1 to h2 to match template */
                .payslip-header p { margin: 0; font-size: 12px; }
                .table-bordered th, .table-bordered td { border: 1px solid #333 !important; }
                .table-sm td, .table-sm th { padding: .4rem; }
                .section-title { font-weight: bold; background-color: #e9ecef; }
                .text-right { text-align: right; }
                .font-weight-bold { font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="payslip-container">
                <?php 
                include 'payslip_template.php'; 
                ?>
            </div>
        </body>
        </html>
        <?php
        $payslip_html = ob_get_clean(); // Get the complete HTML with embedded styles

        // 3. Generate PDF using Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true); // Crucial for data URI and modern HTML
        $options->set('isRemoteEnabled', true); // Allows Dompdf to access remote resources like the Bootstrap CDN and local files
        $options->set('chroot', realpath(__DIR__ . '/../'));
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($payslip_html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Save PDF to a temporary file
        $filename = "Payslip_" . $slip['employee_id_str'] . "_" . date('MY', mktime(0, 0, 0, $slip['slip_month'], 1, $slip['slip_year'])) . ".pdf";
        $temp_pdf_path = $local_temp_dir . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($temp_pdf_path, $dompdf->output()); // This is line 79

        // 4. Send Email with PHPMailer
        $mail = new PHPMailer(true);

        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom(SMTP_USERNAME, COMPANY_NAME);
        $mail->addAddress($customer_email, $customer_name);
        $mail->addReplyTo(ADMIN_EMAIL_RECIPIENT, COMPANY_NAME);

        // Attach the PDF
        $mail->addAttachment($temp_pdf_path, $filename);

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Payslip for ' . $slip_period . ' from ' . COMPANY_NAME;
        $mail->Body    = 'Dear ' . $customer_name . ',<br><br>Please find your payslip for ' . $slip_period . ' attached to this email.<br><br>Regards,<br>' . COMPANY_NAME;
        $mail->AltBody = 'Dear ' . $customer_name . ', please find your payslip for ' . $slip_period . ' attached to this email.';
        $mail->send();
        echo 'OK';
        
    } catch (Exception $e) { // This will catch errors from DB, Dompdf, or PHPMailer
        http_response_code(500);
        $error_message = "A general error occurred: " . $e->getMessage();
        if (isset($mail) && !empty($mail->ErrorInfo)) {
            $error_message = "PHPMailer Error: " . $mail->ErrorInfo;
        }
        error_log($error_message); // Log the detailed error
        echo $error_message; // Send a more specific error back to the client
    } finally {
        // Cleanup the temporary file if it was created. This block executes always.
        if (isset($temp_pdf_path) && file_exists($temp_pdf_path)) {
            unlink($temp_pdf_path);
        }
    }
} else {
    http_response_code(400);
    exit('Invalid request.');
}
?>