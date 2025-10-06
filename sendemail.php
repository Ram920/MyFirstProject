<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'phpmailer/Exception.php';
require_once 'phpmailer/PHPMailer.php';
require_once 'phpmailer/SMTP.php';

$mail = new PHPMailer(true);

$alert = '';

if(isset($_POST['submit'])){
  // Sanitize user input to prevent XSS attacks
  $name = htmlspecialchars(strip_tags(trim($_POST['name'])));
  $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
  $message = htmlspecialchars(strip_tags(trim($_POST['message'])));

  // Basic validation
  if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($message)) {
      $alert = '<div class="alert-error"><span>Please fill all fields correctly.</span></div>';
  } else {
      try{
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        // SECURITY WARNING: Do not store credentials in code. Use environment variables or a secure config file.
        $mail->Username = 'sharmaram920@gmail.com'; // Your Gmail address (e.g., your.email@gmail.com)
        $mail->Password = 'xxxxxxxxxxxxxxxx'; // PASTE YOUR 16-CHARACTER APP PASSWORD HERE
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = '587';

        $mail->setFrom($email, $name); // Set From to the user's email and name
        $mail->addAddress('nushmechanical@gmail.com'); // The email address where you want to receive messages
        $mail->addReplyTo($email, $name); // So you can reply directly to the user

        $mail->isHTML(true);
        $mail->Subject = "New Contact Form Message from {$name}";
        $mail->Body = "<h3>New message from your website:</h3><p><b>Name:</b> {$name}</p><p><b>Email:</b> {$email}</p><p><b>Message:</b><br>" . nl2br($message) . "</p>";
        $mail->AltBody = "Name: {$name}\nEmail: {$email}\nMessage: {$message}";

        $mail->send();
        $alert = '<div class="alert-success"><span>Message Sent! Thank you for contacting us.</span></div>';
      } catch (Exception $e){
        // Don't show detailed errors to the user for security reasons.
        // error_log("PHPMailer Error: " . $mail->ErrorInfo); // Log the actual error for debugging
        $alert = '<div class="alert-error"><span>Something went wrong. Please try again later.</span></div>';
      }
  }
}
?>
