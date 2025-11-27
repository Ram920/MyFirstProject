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
    header("Location: manage_bills.php");
    exit;
}

require_once __DIR__ . '/../db_connect.php';

// --- Data Validation and Sanitization ---
$bill_id = isset($_POST['bill_id']) ? (int)$_POST['bill_id'] : 0;
$title = trim($_POST['title'] ?? '');
$items = $_POST['items'] ?? [];

if ($bill_id <= 0 || empty($title) || empty($items)) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Invalid data submitted.'];
    header("Location: edit_bill.php?id=" . $bill_id);
    exit;
}

// --- Security Check: Only allow updating of the MOST RECENT bill ---
$latest_bill_result = $conn->query("SELECT MAX(id) as max_id FROM bills");
$latest_bill_id = $latest_bill_result->fetch_assoc()['max_id'] ?? 0;

if ($bill_id != $latest_bill_id) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'This bill can no longer be edited as a newer one exists.'];
    header("Location: manage_bills.php");
    exit;
}

// --- Server-side recalculation and validation ---
$calculated_total = 0;
foreach ($items as $item) {
    $qty = filter_var($item['qty'] ?? 0, FILTER_VALIDATE_FLOAT);
    $rate = filter_var($item['rate'] ?? 0, FILTER_VALIDATE_FLOAT);
    if ($qty === false || $rate === false || empty(trim($item['description']))) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'All item fields (Description, Qty, Rate) are required.'];
        header("Location: edit_bill.php?id=" . $bill_id);
        exit;
    }
    $calculated_total += $qty * $rate;
}

// --- Database Transaction ---
$conn->begin_transaction();

try {
    // 1. Update the main bill details
    $stmt_bill = $conn->prepare("UPDATE bills SET title = ?, total_amount = ? WHERE id = ?");
    $stmt_bill->bind_param("sdi", $title, $calculated_total, $bill_id);
    if (!$stmt_bill->execute()) {
        throw new Exception("Failed to update bill: " . $stmt_bill->error);
    }
    $stmt_bill->close();

    // 2. Delete old items for this bill
    $stmt_delete = $conn->prepare("DELETE FROM bill_items WHERE bill_id = ?");
    $stmt_delete->bind_param("i", $bill_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    // 3. Insert the new set of items
    $stmt_item = $conn->prepare("INSERT INTO bill_items (bill_id, sl_no, description, unit, qty, rate, amount) VALUES (?, ?, ?, ?, ?, ?, ?)");

    foreach ($items as $item) {
        $sl_no = trim($item['sl_no'] ?? '');
        $description = trim($item['description']);
        $unit = trim($item['unit'] ?? 'Nos.');
        $qty = (float)$item['qty'];
        $rate = (float)$item['rate'];
        $amount = $qty * $rate;

        $stmt_item->bind_param("isssddd", $bill_id, $sl_no, $description, $unit, $qty, $rate, $amount);
        if (!$stmt_item->execute()) {
            throw new Exception("Failed to insert bill item: " . $stmt_item->error);
        }
    }
    $stmt_item->close();

    $conn->commit();
    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Bill #' . $bill_id . ' updated successfully!'];
    header("Location: manage_bills.php");
    exit;
} catch (Exception $e) {
    $conn->rollback();
    error_log("Bill update failed: " . $e->getMessage());
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'A database error occurred. Could not update the bill.'];
    header("Location: edit_bill.php?id=" . $bill_id);
    exit;
} finally {
    $conn->close();
}
?>