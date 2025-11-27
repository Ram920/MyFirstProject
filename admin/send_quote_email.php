<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    exit('Access Denied');
}

require_once 'config.php';
require_once '../phpmailer/Exception.php';
require_once '../phpmailer/PHPMailer.php';
require_once '../phpmailer/SMTP.php';

if (isset($_POST['quote_html']) && isset($_POST['customer_email'])) {
    $quote_html = $_POST['quote_html'];
    $customer_email = filter_var($_POST['customer_email'], FILTER_VALIDATE_EMAIL);
    $customer_name = htmlspecialchars($_POST['customer_name'] ?? 'Valued Customer');
    $quote_id = htmlspecialchars($_POST['quote_id'] ?? 'N/A');

    if (!$customer_email) {
        http_response_code(400);
        exit('Invalid customer email address.');
    }

    $mail = new PHPMailer(true);

    try {
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

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Quotation (Ref: NUSH-' . $quote_id . ') from ' . COMPANY_NAME;
        $mail->Body    = $quote_html;
        $mail->AltBody = 'Please view this email in an HTML-compatible email client to see the quotation.';

        $mail->send();
        echo 'OK';
    } catch (Exception $e) {
        http_response_code(500);
        // Don't show detailed errors to the user for security reasons.
        error_log("PHPMailer Error when sending quote: " . $mail->ErrorInfo); // Log the actual error
        echo "Message could not be sent. Please check server logs.";
    }
} else {
    http_response_code(400);
    echo 'Invalid request.';
}
?>