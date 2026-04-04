<?php
session_start();

// -----------------------------------------------------------------------------
// 1. SETUP & CONFIGURATION
// -----------------------------------------------------------------------------

// Require dompdf autoload file
require_once 'dompdf/autoload.inc.php';

// Reference the Dompdf namespace
use Dompdf\Dompdf;
use Dompdf\Options;

// -----------------------------------------------------------------------------
// 2. DATA INPUTS (These can be replaced with variables from your database)
// -----------------------------------------------------------------------------

// --- Get Invoice ID and Fetch Data ---
if (!isset($_GET['invoice_id'])) {
    die("Error: Invoice ID is missing. Please access this page from the admin panel.");
} // Ensure invoice_id is an integer
$invoice_id = intval($_GET['invoice_id']);

require_once 'db_connect.php'; // Connect to the database

// --- Fetch main invoice data ---
$stmt = $conn->prepare("SELECT * FROM invoices WHERE id = :id");
$stmt->execute([':id' => $invoice_id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    die("Error: Invoice with ID {$invoice_id} not found.");
}

// --- Fetch line items for the invoice ---
$items_stmt = $conn->prepare("SELECT * FROM invoice_items WHERE invoice_id = :invoice_id ORDER BY id ASC");
$items_stmt->execute([':invoice_id' => $invoice_id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Supplier Details (Your Company) ---
$supplier_name    = "NUSH MECHANICAL & FABRICATOR WORKS";
$supplier_desc    = "Deals in: All Type of Conveyor, Conveyor Spare Parts, Hydraulic & Rubber Moulding Machine";
$supplier_address = "Plot No. 9158, Block-F Sanjay Colony, Sector-23, Faridabad-121005 Haryana";
$supplier_gstin   = "06BUDPS4194P1ZU";
$supplier_mobile  = "9667587686";
$logo_path        = 'assets/images/company_logo.png'; // <-- IMPORTANT: Place your logo at this path

// --- Assign database data to variables for the PDF ---
// The keys ('invoice_number', 'customer_name', etc.) must match your table columns.
$invoice_no       = $invoice['invoice_number'] ?? 'N/A';
$invoice_date     = isset($invoice['invoice_date']) ? date("d/m/Y", strtotime($invoice['invoice_date'])) : 'N/A';
$transport_mode   = $invoice['transport_mode'] ?? 'N/A';
$vehicle_no       = $invoice['vehicle_no'] ?? 'N/A';
$po_no            = $invoice['po_number'] ?? 'N/A';
$challan_no       = $invoice['challan_no'] ?? '';

$customer_name    = $invoice['customer_name'] ?? 'N/A';
$customer_sub     = $invoice['customer_sub_name'] ?? '';
$customer_address = $invoice['customer_address'] ?? 'N/A';
$customer_gstin   = $invoice['customer_gstin'] ?? 'N/A';
$customer_state   = $invoice['customer_state_code'] ?? 'N/A';

// --- Calculations ---
$total_taxable = 0;
foreach ($items as $key => $item) {
    // Extract numeric part from quantity for calculation
    $qty_numeric = floatval($item['quantity']); // Assuming column name is 'quantity'
    $rate_numeric = floatval($item['rate']);
    $amount = $qty_numeric * $rate_numeric;
    $items[$key]['amount'] = $amount;
    $total_taxable += $amount;
}

// -----------------------------------------------------------------------------
// 3. PDF GENERATION LOGIC
// -----------------------------------------------------------------------------

// Instantiate and use the dompdf class
$options = new Options();
$options->set('isRemoteEnabled', TRUE); // Important for loading images
$dompdf = new Dompdf($options);

// --- CSS STYLES ---
$style = '
<style>
    @page { margin: 15px; }
    body { font-family: sans-serif; font-size: 10px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { vertical-align: top; }
    .header-table td { padding: 2px 5px; }
    .items-table { margin-top: 10px; }
    .items-table th, .items-table td { border: 1px solid #000; padding: 4px; }
    .items-table th { font-weight: bold; text-align: center; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-left { text-align: left; }
    .font-bold { font-weight: bold; }
    .company-name { font-size: 15px; font-weight: bold; }
    .invoice-title { font-size: 16px; font-weight: bold; text-decoration: underline; }
    .no-border { border: none; }
    .no-border-top-bottom { border-top: none; border-bottom: none; }
    .whitespace-pre-line { white-space: pre-line; }
</style>';

// --- HTML CONTENT CONSTRUCTION ---

// Function to get logo data
function getLogoBase64($path) {
    if (file_exists($path)) {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
    return ''; // Return empty if no logo
}

$logoBase64 = getLogoBase64($logo_path);

$html = $style;
$html .= '
<table class="header-table" border="0">
    <tr>
        <td width="15%" class="text-center">
            ' . ($logoBase64 ? '<img src="' . $logoBase64 . '" width="80px">' : '') . '
        </td>
        <td width="85%" class="text-center">
            <div class="invoice-title">TAX INVOICE</div>
            <div class="company-name">' . $supplier_name . '</div>
            <div>' . $supplier_desc . '</div>
            <div>' . $supplier_address . '</div>
        </td>
    </tr>
    <tr>
        <td class="text-left font-bold">GSTIN: ' . $supplier_gstin . '</td>
        <td class="text-right font-bold">Mob: ' . $supplier_mobile . '</td>
    </tr>
</table>

<table class="items-table">
    <tr>
        <td width="50%">
            ' . $customer_name . '<br>
            ' . $customer_sub . '<br>
            ' . $customer_address . '<br>
            <b>GSTIN-</b>' . $customer_gstin . '
        </td>
        <td width="50%">
            <b>INVOICE NO.</b> ' . $invoice_no . '<br>
            <b>Dated :</b> ' . $invoice_date . '<br>
            <b>Mode of Transport:</b> ' . $transport_mode . '<br>
            <b>Your P.O No.</b> ' . $po_no . '<br>
            <b>Challan/RMGP No.</b> ' . $challan_no . '<br>
            <b>Vehicle No-</b> ' . $vehicle_no . '<br>
            <b>State Code :</b> ' . $customer_state . '
        </td>
    </tr>
</table>

<table class="items-table">
    <thead>
        <tr>
            <th width="8%">S.NO.</th>
            <th width="47%">Description of Goods</th>
            <th width="10%">HSN / SAC Code</th>
            <th width="10%">Quantity</th>
            <th width="10%">Rate</th>
            <th width="15%">Amount (₹)</th>
        </tr>
    </thead>
    <tbody>';

// Items Rows
foreach ($items as $item) {
    $html .= '
        <tr>
            <td class="text-center">' . htmlspecialchars($item['sno']) . '</td>
            <td class="whitespace-pre-line">' . nl2br(htmlspecialchars($item['description'])) . '</td>
            <td class="text-center">' . htmlspecialchars($item['hsn_code']) . '</td>
            <td class="text-center">' . htmlspecialchars($item['quantity']) . '</td>
            <td class="text-right">' . number_format($item['rate'], 3) . '</td>
            <td class="text-right">' . number_format($item['amount'], 2) . '</td>
        </tr>';
}

// Empty rows for spacing - adjust the loop condition for more/less space
for ($i = count($items); $i < 8; $i++) {
    $html .= '<tr><td class="no-border-top-bottom">&nbsp;</td><td class="no-border-top-bottom"></td><td class="no-border-top-bottom"></td><td class="no-border-top-bottom"></td><td class="no-border-top-bottom"></td><td class="no-border-top-bottom"></td></tr>';
}

// Totals Section
$html .= '
        <tr>
            <td colspan="3" rowspan="2">
                For <b>' . $supplier_name . '</b>
                <br><br><br><br>
                <b>Authorised Signatory</b>
            </td>
            <td colspan="2" class="text-right font-bold">TOTAL</td>
            <td class="text-right font-bold">' . number_format($total_taxable, 2) . '</td>
        </tr>
        <tr>
            <td colspan="3" class="text-center">
                E. & O.E
            </td>
        </tr>
    </tbody>
</table>
';

// Load HTML to dompdf
$dompdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

// -----------------------------------------------------------------------------
// 4. OUTPUT
// -----------------------------------------------------------------------------

// Output the generated PDF to Browser
// Use 'attachment' => 1 to force download, 0 to stream
$dompdf->stream('Invoice_' . str_replace('/', '-', $invoice_no) . '.pdf', ["Attachment" => 0]);

$conn = null; // Close connection
?>