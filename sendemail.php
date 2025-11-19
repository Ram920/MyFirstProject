<?php
session_start(); // Start session here to ensure it's available for all includes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once 'admin/db_connect.php'; // Include db_connect for fetching product details
require_once 'phpmailer/Exception.php';
require_once 'phpmailer/PHPMailer.php';
require_once 'phpmailer/SMTP.php';

$mail = new PHPMailer(true);
$alert = '';

if(isset($_POST['submit'])){
  // Sanitize user input to prevent XSS attacks
  $name = htmlspecialchars(strip_tags(trim($_POST['name'])));
  $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
  $subject = htmlspecialchars(strip_tags(trim($_POST['subject'])));
  $phone = htmlspecialchars(strip_tags(trim($_POST['phone'] ?? ''))); // Phone is optional
  
  // New fields for Quote Basket
  $company_name = htmlspecialchars(strip_tags(trim($_POST['company_name'] ?? '')));
  $delivery_location = htmlspecialchars(strip_tags(trim($_POST['delivery_location'] ?? '')));
  $required_customization = htmlspecialchars(strip_tags(trim($_POST['required_customization'] ?? '')));
  $quantity = htmlspecialchars(strip_tags(trim($_POST['quantity'] ?? '')));
  $additional_requirements = htmlspecialchars(strip_tags(trim($_POST['additional_requirements'] ?? '')));

  // For the main contact form, 'message' is the primary content.
  // For the cart form, 'additional_requirements' is the primary content.
  $message_content = isset($_POST['message']) ? htmlspecialchars(strip_tags(trim($_POST['message']))) : $additional_requirements;

  // Basic validation
  // The message is required only if it's from the main contact form (where $_POST['message'] is set)
  $is_main_contact_form = isset($_POST['message']);
  if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($subject) || ($is_main_contact_form && empty($message_content))) {
      $alert = '<div class="alert-error"><span>Please fill all required fields correctly.</span></div>';
  } else {
      try{
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Gmail SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = '587';

        $mail->setFrom($email, $name);
        $mail->addAddress('nushmechanical@gmail.com'); // The email address where you want to receive messages
        $mail->addReplyTo($email, $name);

        $mail->isHTML(true);
        $mail->Subject = "[NUSH Website Contact] - {$subject}";
        
        $email_body = "<h3>New message from your website:</h3>";
        $email_body .= "<p><b>Name:</b> {$name}</p>";
        $email_body .= "<p><b>Email:</b> {$email}</p>";
        $email_body .= "<p><b>Phone:</b> {$phone}</p>";
        $email_body .= "<p><b>Subject:</b> {$subject}</p>";

        // Add new fields if they are present (from cart.php)
        if (!empty($company_name)) {
            $email_body .= "<p><b>Company Name:</b> {$company_name}</p>";
        }
        if (!empty($delivery_location)) {
            $email_body .= "<p><b>Delivery Location:</b> {$delivery_location}</p>";
        }
        if (!empty($quantity)) {
            $email_body .= "<p><b>Quantity:</b> {$quantity}</p>";
        }
        if (!empty($required_customization)) {
            $email_body .= "<p><b>Required Customization:</b><br>" . nl2br($required_customization) . "</p>";
        }
        if (!empty($additional_requirements)) {
            $email_body .= "<p><b>Additional Requirements:</b><br>" . nl2br($additional_requirements) . "</p>";
        }
        $products_inquired_text = "";
        $products_html_list = ""; // Variable to hold only the product list HTML

        // Add products from Quote Basket if available
        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
            $products_html_list = "<h4>Products in Quote Basket:</h4><ul>";
            $product_ids = implode(',', array_map('intval', $_SESSION['cart']));
            $result = $conn->query("SELECT name FROM products WHERE id IN ($product_ids)");
            while ($product = $result->fetch_assoc()) {
                $products_html_list .= "<li>" . htmlspecialchars($product['name']) . "</li>";
                $products_inquired_text .= htmlspecialchars($product['name']) . ", ";
            }
            $products_html_list .= "</ul>";
            $email_body .= $products_html_list; // Add the clean product list to the admin email
            $products_inquired_text = rtrim($products_inquired_text, ", ");
        } else {
            // Fallback for general contact form message
            $email_body .= "<p><b>Message:</b><br>" . nl2br($message_content) . "</p>";
        }

        // Handle file upload for drawing
        if (isset($_FILES['drawing']) && $_FILES['drawing']['error'] == UPLOAD_ERR_OK) {
            $drawing_file_name = time() . '_' . basename($_FILES['drawing']['name']);
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $upload_path = $upload_dir . $drawing_file_name;

            if (move_uploaded_file($_FILES['drawing']['tmp_name'], $upload_path)) {
                $mail->addAttachment($upload_path, $drawing_file_name);
                $email_body .= "<p><b>Drawing Attached:</b> {$drawing_file_name}</p>";
            }
        } else {
            $drawing_file_name = null;
        }

        $mail->Body = $email_body;
        $mail->AltBody = strip_tags($email_body); // Plain text version

        $mail->send();

        // --- Send confirmation email to the customer ---
        $mail->clearAddresses();
        $mail->clearReplyTos();
        $mail->clearAttachments();

        // Set the "From" address to your company email (same as SMTP_USERNAME)
        $mail->setFrom(SMTP_USERNAME, COMPANY_NAME);
        $mail->addAddress($email, $name); // Add customer's email as the recipient
        $mail->Subject = 'Confirmation: We have received your enquiry';

        $customer_email_body = "<h3>Dear {$name},</h3>";
        $customer_email_body .= "<p>Thank you for contacting " . COMPANY_NAME . ". We have successfully received your enquiry and will get back to you as soon as possible.</p>";
        if (!empty($products_inquired_text)) {
            $customer_email_body .= "<p><b>You enquired about the following products:</b></p>" . $products_html_list; // Use the clean product list HTML
        }
        $customer_email_body .= "<p>Best Regards,<br>The Team at NUSH MECHANICAL & FABRICATOR WORKS</p>";

        $mail->Body = $customer_email_body;
        $mail->send();

        // --- Save enquiry to the database ---
        $stmt = $conn->prepare("INSERT INTO enquiries (name, email, phone, company_name, delivery_location, quantity, customization_req, additional_req, products_inquired, drawing_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssss", $name, $email, $phone, $company_name, $delivery_location, $quantity, $required_customization, $additional_requirements, $products_inquired_text, $drawing_file_name);
        $stmt->execute();
        $stmt->close();

        // Clear the cart after successful submission
        if (isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $alert = '<div class="alert-success"><span>Message Sent! Thank you for contacting us.</span></div>';
      } catch (Exception $e){
        // Don't show detailed errors to the user for security reasons.
        error_log("PHPMailer Error: " . $mail->ErrorInfo); // Log the actual error for debugging
        $alert = '<div class="alert-error"><span>Something went wrong. Please try again later.</span></div>';
      }
  }
}
?>
