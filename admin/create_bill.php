<?php
session_start();
// --- Session Security Check ---
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset();
    session_destroy();
}

// --- Check if admin is logged in ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php"); // Redirect to login page if not logged in
    exit;
}

$_SESSION['last_activity'] = time();

require_once __DIR__ . '/../db_connect.php'; // Connect to the database

// --- CSRF Token Generation ---
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create New Bill</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Create New Bill</h2>
        <div>
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>

    <?php
    // --- Display Feedback Messages ---
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        $alert_type = ($message['type'] === 'success') ? 'alert-success' : 'alert-danger';
        echo "<div class='alert {$alert_type}'>{$message['text']}</div>";
    }
    ?>

    <form action="store_bill.php" method="POST" id="billForm">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <div class="mb-3">
            <label class="form-label">Bill Title / Description</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. PIPE Bending Tool 100x100x5mm" required>
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
                <tr>
                    <td><input type="text" name="items[0][sl_no]" class="form-control" value="01"></td>
                    <td><input type="text" name="items[0][description]" class="form-control" required></td>
                    <td>
                        <select name="items[0][unit]" class="form-control">
                            <option value="Nos.">Nos.</option>
                            <option value="Set">Set</option>
                            <option value="Kg">Kg</option>
                        </select>
                    </td>
                    <td><input type="number" step="0.01" name="items[0][qty]" class="form-control qty" oninput="calculateRow(this)" required></td>
                    <td><input type="number" step="0.01" name="items[0][rate]" class="form-control rate" oninput="calculateRow(this)" required></td>
                    <td><input type="number" step="0.01" name="items[0][amount]" class="form-control amount" readonly></td>
                    <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">X</button></td>
                </tr>
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
        <button type="submit" class="btn btn-success">Save Bill</button>
    </form>
</div>

<script>
let rowIdx = 1;
function addRow() {
    let html = `<tr>
                    <td><input type="text" name="items[${rowIdx}][sl_no]" class="form-control" value="0${rowIdx + 1}"></td>
                    <td><input type="text" name="items[${rowIdx}][description]" class="form-control" required></td>
                    <td>
                        <select name="items[${rowIdx}][unit]" class="form-control">
                            <option value="Nos.">Nos.</option>
                            <option value="Set">Set</option>
                            <option value="Kg">Kg</option>
                        </select>
                    </td>
                    <td><input type="number" step="0.01" name="items[${rowIdx}][qty]" class="form-control qty" oninput="calculateRow(this)" required></td>
                    <td><input type="number" step="0.01" name="items[${rowIdx}][rate]" class="form-control rate" oninput="calculateRow(this)" required></td>
                    <td><input type="number" step="0.01" name="items[${rowIdx}][amount]" class="form-control amount" readonly></td>
                    <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">X</button></td>
                </tr>`;
    document.querySelector('#itemsTable tbody').insertAdjacentHTML('beforeend', html);
    rowIdx++;
}

function removeRow(btn) {
    btn.closest('tr').remove();
    calculateTotal();
}

function calculateRow(input) {
    let row = input.closest('tr');
    let qty = parseFloat(row.querySelector('.qty').value) || 0;
    let rate = parseFloat(row.querySelector('.rate').value) || 0;
    let amount = qty * rate;
    row.querySelector('.amount').value = amount.toFixed(2);
    calculateTotal();
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.amount').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    document.getElementById('grandTotal').value = total.toFixed(2);
}

// Initial calculation on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateTotal();
});
</script>

</body>
</html>