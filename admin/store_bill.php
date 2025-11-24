<?php
session_start();

// --- Security and Session Check ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("HTTP/1.1 403 Forbidden");
    exit("Access denied.");
}

// --- CSRF Token Validation ---
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Invalid CSRF token. Please try again.'];
    header("Location: create_bill.php");
    exit;
}

require_once __DIR__ . '/../db_connect.php';

// --- Validate Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("HTTP/1.1 405 Method Not Allowed");
    exit("Invalid request method.");
}

// --- Data Validation and Sanitization ---
$error_messages = [];
$title = trim($_POST['title'] ?? '');
$items = $_POST['items'] ?? [];

if (empty($title)) {
    $error_messages[] = "Bill Title is required.";
}

if (empty($items)) {
    $error_messages[] = "At least one bill item is required.";
}

$calculated_total = 0;
$validated_items = [];

// --- Validate and Calculate Each Item ---
foreach ($items as $index => $item) {
    $description = trim($item['description'] ?? '');
    $qty = filter_var($item['qty'] ?? null, FILTER_VALIDATE_FLOAT);
    $rate = filter_var($item['rate'] ?? null, FILTER_VALIDATE_FLOAT);

    if (empty($description)) {
        $error_messages[] = "Description is missing for item #" . ($index + 1) . ".";
    }
    if ($qty === false || $qty <= 0) {
        $error_messages[] = "A valid quantity (greater than 0) is required for item #" . ($index + 1) . ".";
    }
    if ($rate === false || $rate < 0) {
        $error_messages[] = "A valid rate is required for item #" . ($index + 1) . ".";
    }

    $calculated_total += $qty * $rate;
}

if (!empty($error_messages)) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => implode('<br>', $error_messages)];
    header("Location: create_bill.php");
    exit;
}

// --- Database Transaction ---
$conn->begin_transaction();

try {
    // 1. Save the main bill details
    $bill_date = date("Y-m-d"); // Use current date for the bill
    $stmt_bill = $conn->prepare("INSERT INTO bills (title, bill_date, total_amount) VALUES (?, ?, ?)");
    if (!$stmt_bill) { // @phpstan-ignore-line
        throw new Exception("Prepare failed (bill): " . $conn->error);
    }
    $stmt_bill->bind_param("ssd", $title, $bill_date, $calculated_total);
    if (!$stmt_bill->execute()) {
        throw new Exception("Execute failed (bill): " . $stmt_bill->error);
    }

    $bill_id = $conn->insert_id; // Get the ID of the newly created bill
    $stmt_bill->close();

    // 2. Prepare statement for bill items
    $stmt_item = $conn->prepare("INSERT INTO bill_items (bill_id, sl_no, description, unit, qty, rate, amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt_item) { // @phpstan-ignore-line
        throw new Exception("Prepare failed (item): " . $conn->error);
    }

    // 3. Loop through and save each bill item
    foreach ($items as $item) {
        // Sanitize and validate each item field
        $sl_no = trim($item['sl_no'] ?? '');
        $description = trim($item['description'] ?? '');
        $unit = trim($item['unit'] ?? 'Nos.');
        $qty = filter_var($item['qty'] ?? 0, FILTER_VALIDATE_FLOAT);
        $rate = filter_var($item['rate'] ?? 0, FILTER_VALIDATE_FLOAT);
        $amount = filter_var($item['amount'] ?? 0, FILTER_VALIDATE_FLOAT);

        $stmt_item->bind_param("isssddd", $bill_id, $sl_no, $description, $unit, $qty, $rate, $amount);
        if (!$stmt_item->execute()) {
            throw new Exception("Execute failed (item): " . $stmt_item->error);
        }
    }
    $stmt_item->close();

    // If everything was successful, commit the transaction
    $conn->commit();
    
    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Bill created successfully!'];
    // Redirect to a success page or the list of bills
    header("Location: manage_bills.php?success=true"); // Assuming a manage_bills.php will be created
    exit;

} catch (Exception $e) {
    $conn->rollback();
    error_log("Bill creation failed: " . $e->getMessage());
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'A database error occurred while saving the bill. Please try again.'];
    header("Location: create_bill.php");
    exit;
} finally {
    $conn->close();
}
?>