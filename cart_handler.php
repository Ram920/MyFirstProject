<?php
/**
 * Handles adding, removing, and sharing products.
 */

// Always start the session at the very beginning.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection for fetching product details for WhatsApp sharing.
require_once 'admin/config.php'; // For COMPANY_NAME
require_once 'db_connect.php';

// Initialize the cart in the session if it doesn't exist yet.
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check for 'action' and 'id' parameters in the URL.
if ((isset($_GET['action']) && isset($_GET['id'])) || (isset($_POST['action']) && isset($_POST['id']))) {
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    $action = $_POST['action'] ?? $_GET['action'];
    $id = (int)($_POST['id'] ?? $_GET['id']); // Sanitize the ID

    if ($action === 'add') {
        // Add the product ID to the cart array if it's not already present.
        if (!in_array($id, $_SESSION['cart'])) {
            $_SESSION['cart'][] = $id;
        }

        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'cart_count' => count($_SESSION['cart'])
            ]);
            exit;
        }
    } elseif ($action === 'remove') {
        // Find the key of the product ID in the cart array.
        $key = array_search($id, $_SESSION['cart']);
        // If found, remove it from the array.
        if ($key !== false) {
            unset($_SESSION['cart'][$key]);
        }
    } elseif ($action === 'whatsapp') {
        // Handle direct WhatsApp sharing for a single product.
        $stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($product = $result->fetch_assoc()) {
            $product_name = htmlspecialchars($product['name']);
            $whatsapp_phone = '+918600222111'; // Your WhatsApp number (from config or hardcoded)
            $message = "Hello, I would like to inquire about your product: *{$product_name}* from " . COMPANY_NAME . ".";
            $whatsapp_url = "https://wa.me/" . $whatsapp_phone . "?text=" . urlencode($message);
            
            // --- Save WhatsApp inquiry to database ---
            $inquiry_type = 'Direct WhatsApp Inquiry';
            $submission_date = date('Y-m-d H:i:s');
            $products_inquired_text = $product_name;
            $status = 'New'; // Default status for new inquiries

            $stmt_insert = $conn->prepare("INSERT INTO enquiries (submission_date, status, name, email, phone, company_name, products_inquired, inquiry_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            // For WhatsApp, we don't have name, email, phone, company_name directly, so use placeholders or empty strings
            $empty_str = '';
            $stmt_insert->bind_param("ssssssss", $submission_date, $status, $empty_str, $empty_str, $empty_str, $empty_str, $products_inquired_text, $inquiry_type);
            
            if (!$stmt_insert->execute()) {
                error_log("Error saving WhatsApp inquiry to DB: " . $stmt_insert->error);
            }
            $stmt_insert->close();

            // Redirect to WhatsApp
            header("Location: " . $whatsapp_url);
            exit;
        } else {
            // Product not found, redirect back with an error (optional)
            header("Location: index.php?error=product_not_found");
            exit;
        }
    }
}

// Redirect the user back to the page they came from.
// If the referer is not available, redirect to index.php as a fallback.
$redirect_url = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header("Location: " . $redirect_url);
exit;
?>