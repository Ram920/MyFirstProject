<?php
session_start();
// --- Session Security Check ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}
$_SESSION['last_activity'] = time();

require_once __DIR__ . '/../db_connect.php';

$bill_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($bill_id <= 0) {
    header("Location: manage_bills.php");
    exit;
}

// --- Security Check: Only allow editing of the MOST RECENT bill ---
$latest_bill_result = $conn->query("SELECT MAX(id) as max_id FROM bills");
$latest_bill_id = $latest_bill_result->fetch_assoc()['max_id'] ?? 0;

if ($bill_id != $latest_bill_id) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Error: Only the most recent bill can be edited.'];
    header("Location: manage_bills.php");
    exit;
}

// --- Fetch existing bill data ---
$stmt_bill = $conn->prepare("SELECT * FROM bills WHERE id = ?");
$stmt_bill->bind_param("i", $bill_id);
$stmt_bill->execute();
$result_bill = $stmt_bill->get_result();
if ($result_bill->num_rows === 0) {
    exit('Bill not found.');
}
$bill = $result_bill->fetch_assoc();

$stmt_items = $conn->prepare("SELECT * FROM bill_items WHERE bill_id = ? ORDER BY id ASC");
$stmt_items->bind_param("i", $bill_id);
$stmt_items->execute();
$bill_items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);

// --- CSRF Token Generation ---
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Bill #<?php echo $bill_id; ?></title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Bill #<?php echo $bill_id; ?></h2>
        <div>
            <a href="manage_bills.php" class="btn btn-secondary">Back to Manage Bills</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>

    <?php
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        $alert_type = ($message['type'] === 'success') ? 'alert-success' : 'alert-danger';
        echo "<div class='alert {$alert_type}'>{$message['text']}</div>";
    }
    ?>

    <form action="update_bill.php" method="POST" id="billForm">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="bill_id" value="<?php echo $bill_id; ?>">

        <div class="mb-3">
            <label class="form-label">Bill Title / Description</label>
            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($bill['title']); ?>" required>
        </div>

        <table class="table table-bordered" id="itemsTable">
            <thead>
                <tr>
                    <th>Sl. No.</th>
                    <th>Description of Goods</th>
                    <th>Unit</th>
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bill_items as $index => $item): ?>
                <tr>
                    <td><input type="text" name="items[<?php echo $index; ?>][sl_no]" class="form-control" value="<?php echo htmlspecialchars($item['sl_no']); ?>"></td>
                    <td><input type="text" name="items[<?php echo $index; ?>][description]" class="form-control" value="<?php echo htmlspecialchars($item['description']); ?>" required></td>
                    <td>
                        <select name="items[<?php echo $index; ?>][unit]" class="form-control">
                            <option value="Nos." <?php if ($item['unit'] == 'Nos.') echo 'selected'; ?>>Nos.</option>
                            <option value="Set" <?php if ($item['unit'] == 'Set') echo 'selected'; ?>>Set</option>
                            <option value="Kg" <?php if ($item['unit'] == 'Kg') echo 'selected'; ?>>Kg</option>
                        </select>
                    </td>
                    <td><input type="number" step="0.01" name="items[<?php echo $index; ?>][qty]" class="form-control qty" value="<?php echo htmlspecialchars($item['qty']); ?>" oninput="calculateRow(this)" required></td>
                    <td><input type="number" step="0.01" name="items[<?php echo $index; ?>][rate]" class="form-control rate" value="<?php echo htmlspecialchars($item['rate']); ?>" oninput="calculateRow(this)" required></td>
                    <td><input type="number" step="0.01" name="items[<?php echo $index; ?>][amount]" class="form-control amount" value="<?php echo htmlspecialchars($item['amount']); ?>" readonly></td>
                    <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">X</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-end"><strong>Total:</strong></td>
                    <td><input type="text" name="total_amount" id="grandTotal" class="form-control" readonly></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <button type="button" class="btn btn-secondary" onclick="addRow()">+ Add Row</button>
        <button type="submit" class="btn btn-success">Update Bill</button>
    </form>
</div>

<script>
let rowIdx = <?php echo count($bill_items); ?>;
function addRow() {
    let newIndex = rowIdx++;
    let html = `<tr>
                    <td><input type="text" name="items[${newIndex}][sl_no]" class="form-control" value="0${newIndex + 1}"></td>
                    <td><input type="text" name="items[${newIndex}][description]" class="form-control" required></td>
                    <td><select name="items[${newIndex}][unit]" class="form-control"><option value="Nos.">Nos.</option><option value="Set">Set</option><option value="Kg">Kg</option></select></td>
                    <td><input type="number" step="0.01" name="items[${newIndex}][qty]" class="form-control qty" oninput="calculateRow(this)" required></td>
                    <td><input type="number" step="0.01" name="items[${newIndex}][rate]" class="form-control rate" oninput="calculateRow(this)" required></td>
                    <td><input type="number" step="0.01" name="items[${newIndex}][amount]" class="form-control amount" readonly></td>
                    <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">X</button></td>
                </tr>`;
    document.querySelector('#itemsTable tbody').insertAdjacentHTML('beforeend', html);
}
function removeRow(btn) { btn.closest('tr').remove(); calculateTotal(); }
function calculateRow(input) { let row = input.closest('tr'); let qty = parseFloat(row.querySelector('.qty').value) || 0; let rate = parseFloat(row.querySelector('.rate').value) || 0; row.querySelector('.amount').value = (qty * rate).toFixed(2); calculateTotal(); }
function calculateTotal() { let total = 0; document.querySelectorAll('.amount').forEach(input => { total += parseFloat(input.value) || 0; }); document.getElementById('grandTotal').value = total.toFixed(2); }
document.addEventListener('DOMContentLoaded', function() { calculateTotal(); });
</script>

</body>
</html>