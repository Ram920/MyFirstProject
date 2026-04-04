<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

// NOTE: This file currently uses dompdf for PDF generation.
// To fully move to Supabase Edge Functions for PDF generation,
// the Edge Function would need to return the PDF as a base64 string or a URL to a stored PDF,
// which this PHP script would then fetch and attach.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    exit('Access Denied');
}

require_once 'config.php';
require_once __DIR__ . '/../db_connect.php'; // For PDO connection
// --- Manual Autoloading ---
require_once __DIR__ . '/../phpmailer/Exception.php';
require_once __DIR__ . '/../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/SMTP.php';
require_once __DIR__ . '/../dompdf/autoload.inc.php';

if (isset($_POST['quote_html']) && isset($_POST['customer_email'])) {
    $quote_html = $_POST['quote_html'];
    $customer_email = filter_var($_POST['customer_email'], FILTER_VALIDATE_EMAIL);
    $customer_name = htmlspecialchars($_POST['customer_name'] ?? 'Valued Customer');
    $quote_id = htmlspecialchars($_POST['quote_id'] ?? 'N/A');

    if (!$customer_email) {
        http_response_code(400);
        exit('Invalid customer email address.');
    }

    // --- Define and check a local temporary directory ---
    $local_temp_dir = __DIR__ . '/temp_files';
    if (!is_dir($local_temp_dir)) {
        if (!mkdir($local_temp_dir, 0755, true)) {
            http_response_code(500);
            exit('Error: Failed to create temporary directory. Please manually create a folder named "temp_files" inside the "admin" directory and give it write permissions.');
        }
    }

    $temp_pdf_path = null;
    $mail = new PHPMailer(true);

    try {
        // 1. Generate PDF from HTML using Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', realpath(__DIR__ . '/../'));
        $dompdf = new Dompdf($options);

        // --- Process image with GD and convert to Base64 to fix color rendering issues ---
        $logoBase64 = '';
        $logoPath = __DIR__ . '/../images/Logo.png';
        if (file_exists($logoPath) && function_exists('imagecreatefrompng')) {
            // Load the original PNG
            $source_image = imagecreatefrompng($logoPath);
            $width = imagesx($source_image);
            $height = imagesy($source_image);

            // Create a new true color image (the canvas)
            $true_color_image = imagecreatetruecolor($width, $height);

            // Copy the source image onto the new canvas, which flattens it
            imagecopy($true_color_image, $source_image, 0, 0, 0, 0, $width, $height);

            // Start output buffering to capture the image data
            ob_start();
            imagejpeg($true_color_image, null, 90); // Output as JPEG with 90% quality
            $imageData = ob_get_clean();

            // Encode the flattened JPEG image data to Base64
            $logoBase64 = 'data:image/jpeg;base64,' . base64_encode($imageData);

            // Replace the placeholder with the Base64 data URI in the HTML
            $quote_html = str_replace('src="../images/Logo.png"', 'src="' . $logoBase64 . '"', $quote_html);
        }

        // Wrap the quote HTML in a full HTML document structure for better PDF rendering
        $full_html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Quotation</title>';
        $full_html .= '<style>
            body { font-family: sans-serif; }
            .letterhead-container { max-width: 800px; margin: 0 auto; padding: 20px; background-color: #fff; }
            .letterhead-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px; }
            .letterhead-header img { max-width: 100px; margin-bottom: 10px; }

            .letterhead-header h1 { font-size: 28px; font-weight: bold; margin: 0; }
            .letterhead-header p { margin: 0; font-size: 14px; }
            .quote-details, .customer-details { margin-bottom: 30px; }
            .quote-table { width: 100%; border-collapse: collapse; }
            .quote-table th, .quote-table td { border: 1px solid #dee2e6; padding: 8px; }
            .quote-table th { background-color: #f2f2f2; }
            .text-right { text-align: right; }
            .amount-in-words-container { margin-top: 10px; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
            .terms, .signature { margin-top: 40px; }
            .row { display: flex; flex-wrap: wrap; margin-right: -15px; margin-left: -15px; }
            .col-6 { flex: 0 0 50%; max-width: 50%; position: relative; width: 100%; padding-right: 15px; padding-left: 15px; }
            h1, h2, h3, h4, h5, h6 { margin-top: 0; margin-bottom: 0.5rem; }
            p { margin-top: 0; margin-bottom: 1rem; }
            strong { font-weight: bolder; }
            ol { padding-left: 20px; }
        </style>';
        $full_html .= '</head><body>' . $quote_html . '</body></html>';

        $dompdf->loadHtml($full_html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // 2. Save PDF to a temporary file
        $filename = "Quotation_NUSH_" . $quote_id . ".pdf";
        $temp_pdf_path = $local_temp_dir . DIRECTORY_SEPARATOR . $filename;
        if (file_put_contents($temp_pdf_path, $dompdf->output()) === false) {
            throw new Exception("Failed to write PDF to temporary file. Check permissions for 'admin/temp_files'.");
        }

        //Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom(SMTP_USERNAME, COMPANY_NAME);
        $mail->addAddress($customer_email, $customer_name);
        $mail->addReplyTo(ADMIN_EMAIL_RECIPIENT, COMPANY_NAME);

        // Attach the generated PDF
        $mail->addAttachment($temp_pdf_path, $filename);

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Quotation (Ref: NUSH-' . $quote_id . ') from ' . COMPANY_NAME;
        $mail->Body    = 'Dear ' . $customer_name . ',<br><br>Please find your quotation (Ref: NUSH-' . $quote_id . ') attached to this email.<br><br>We look forward to hearing from you.<br><br>Regards,<br>' . COMPANY_NAME;
        $mail->AltBody = 'Dear ' . $customer_name . ', please find your quotation (Ref: NUSH-' . $quote_id . ') attached to this email. Regards, ' . COMPANY_NAME;

        $mail->send();
        echo 'OK';
    } catch (Exception $e) {
        http_response_code(500);
        $error_message = "Message could not be sent. Mailer Error: " . ($mail->ErrorInfo ?? $e->getMessage());
        error_log("Error sending quote email: " . $error_message); // Log the detailed error
        echo $error_message; // Send a more specific error back to the client
    } finally {
        if (isset($temp_pdf_path) && file_exists($temp_pdf_path)) {
            unlink($temp_pdf_path);
        }
    }
} else {
    http_response_code(400);
    echo 'Invalid request.';
}
?>
<?php $conn = null; // Close connection ?>