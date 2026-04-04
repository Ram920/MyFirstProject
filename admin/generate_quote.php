<?php
session_start();

// --- Session Security Check ---
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset(); 
    session_destroy();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

$_SESSION['last_activity'] = time();

require_once __DIR__ . '/../db_connect.php';
require_once 'config.php';

$inquiry_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($inquiry_id > 0) {
    // Fetch the inquiry details for an existing enquiry
    $stmt = $conn->prepare("SELECT * FROM enquiries WHERE id = :id");
    $stmt->execute([':id' => $inquiry_id]);
    $inquiry = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inquiry) {
        exit('Enquiry not found.');
    }
} else {
    // This is a new, custom quote. Create a blank inquiry array.
    $inquiry = [
        'id' => 'CUSTOM-' . time(), // Create a unique temporary ID
        'name' => '',
        'company_name' => '',
        'email' => '',
        'phone' => '',
        'subject' => 'Quotation',
        'products_inquired' => '' // Start with no products
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quotation - <?php echo COMPANY_NAME; ?></title>
    <!-- Supabase SDK -->
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .letterhead-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 40px;
            background-color: #fff;
            border: 1px solid #dee2e6;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .letterhead-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .letterhead-header img {
            max-width: 100px;
            margin-bottom: 10px;
        }
        .letterhead-header h1 {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
        }
        .letterhead-header p {
            margin: 0;
            font-size: 14px;
        }
        .quote-details, .customer-details {
            margin-bottom: 30px;
        }
        .quote-table th {
            background-color: #f2f2f2;
        }
        .text-right {
            text-align: right;
        }
        .terms, .signature {
            margin-top: 40px;
        }
        .quote-table input, .quote-table textarea {
            border: none;
            width: 100%;
        }
        .print-button {
            margin-top: 20px;
            text-align: center;
        }

        @media print {
            body {
                background-color: #fff;
            }
            .letterhead-container {
                box-shadow: none;
                border: none;
                margin: 0;
                max-width: 100%;
            }
            .no-print {
                display: none;
            }
            .letterhead-container input, .letterhead-container textarea {
                border: none!important;
                box-shadow: none!important;
            }
        }
    </style>
</head>
<body>

<div class="letterhead-container">
    <div class="letterhead-header">
        <img src="../images/Logo.png" alt="Company Logo">
        <h1><?php echo COMPANY_NAME; ?></h1>
        <p>Plot no.9158, Block-F, Sanjay colony, Sector 23, Faridabad-121005 (Haryana)</p>
        <p>Email: <?php echo ADMIN_EMAIL_RECIPIENT; ?> | Phone: +91 9667587686 / +91 9718968844</p>
    </div>

    <div class="row quote-details">
        <div class="col-6">
            <h4>QUOTATION</h4>
        </div>
        <div class="col-6 text-right">
            <p><strong>Quote #:</strong> NUSH-<?php echo $inquiry['id']; ?></p>
            <p><strong>Date:</strong> <?php echo date('d M Y'); ?></p>
        </div>
    </div>

    <div class="customer-details">
        <h5>To:</h5>
        <p>
            <strong><input type="text" class="form-control-plaintext d-inline" id="customer-name" value="<?php echo htmlspecialchars($inquiry['name']); ?>"></strong><br>
            <input type="text" class="form-control-plaintext d-inline" id="customer-company" placeholder="Company Name" value="<?php echo htmlspecialchars($inquiry['company_name']); ?>"><br>
            Email: <input type="email" class="form-control-plaintext d-inline" id="customer-email" value="<?php echo htmlspecialchars($inquiry['email']); ?>"><br>
            Phone: <input type="tel" class="form-control-plaintext d-inline" id="customer-phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($inquiry['phone']); ?>"><br>
        </p>
    </div>

    <p><strong>Subject:</strong> <input type="text" class="form-control-plaintext d-inline" id="quote-subject" value="<?php echo htmlspecialchars($inquiry['subject']); ?>"></p>

    <p>Dear <span id="dear-customer-name"><?php echo htmlspecialchars($inquiry['name']); ?></span>,</p>
    <p>With reference to your inquiry, we are pleased to quote our best prices for the following items:</p>

    <table class="table table-bordered quote-table">
        <thead>
            <tr>
                <th>Sr. No.</th>
                <th style="width: 40%;">Product / Description</th>
                <th style="width: 10%;">Qty</th>
                <th style="width: 15%;">Rate</th>
                <th style="width: 15%;">Amount</th>
                <th style="width: 10%;" class="no-print">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $products = explode(', ', $inquiry['products_inquired']);
            $i = 1;
            foreach ($products as $product): 
                if (empty(trim($product))) continue;
            ?>
            <tr>
                <td><?php echo $i++; ?></td>
                <td><textarea class="form-control-plaintext product-description" rows="1"><?php echo htmlspecialchars($product); ?></textarea></td>
                <td><input type="number" class="form-control-plaintext product-qty" value="1" min="0"></td>
                <td><input type="number" class="form-control-plaintext product-rate" value="0.00" step="0.01" min="0"></td>
                <td class="product-amount">0.00</td>
                <td class="no-print"><button class="btn btn-danger btn-sm remove-product-row">Remove</button></td>
            </tr>
            <?php endforeach; ?>
            <!-- You can add more rows dynamically here if needed -->
            <tr>
                <td colspan="4" class="text-right"><strong>Total</strong></td>
                <td id="grand-total">0.00</td>
                <td class="no-print"></td>
            </tr>
        </tbody>
    </table>

    <div class="amount-in-words-container mb-4">
        <strong>Amount in words:</strong> <span id="amount-in-words" class="text-capitalize">Zero Only</span>
    </div>

    <div class="no-print">
        <button id="add-row" class="btn btn-info btn-sm">Add Row</button>
    </div>

    <div class="terms">
        <h5>Terms & Conditions:</h5>
        <ol>
            <li>Prices are exclusive of GST.</li>
            <li>Payment: 50% advance, balance against delivery.</li>
            <li>Delivery: Within <input type="text" class="form-control-plaintext d-inline" style="width: 80px;" value="2-3 weeks">.</li>
            <li>Validity: This offer is valid for <input type="text" class="form-control-plaintext d-inline" style="width: 80px;" value="30 days">.</li>
        </ol>
    </div>

    <div class="signature">
        <p>For, <strong><?php echo COMPANY_NAME; ?></strong></p>
        <br><br><br>
        <p>(Authorized Signatory)</p>
    </div>

    <div class="print-button no-print">
        <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
        <button id="generate-serverless-pdf" class="btn btn-info">Preview PDF (Serverless)</button>
        <button id="send-quote-btn" class="btn btn-success" data-email="<?php echo htmlspecialchars($inquiry['email']); ?>" data-name="<?php echo htmlspecialchars($inquiry['name']); ?>" data-quote-id="<?php echo $inquiry['id']; ?>">Send to Customer</button>
        <a href="view_enquiries.php" class="btn btn-secondary">Back to Enquiries</a>
        <div id="send-status" class="mt-3"></div>
    </div>

</div>

<script src="../assets/vendor/jquery/jquery.min.js"></script>
<script>
    // Initialize Supabase client
    const SUPABASE_URL = "<?php echo SUPABASE_URL; ?>";
    const SUPABASE_ANON_KEY = "<?php echo SUPABASE_ANON_KEY; ?>";
    const sbClient = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

$(document).ready(function(){
    // --- DYNAMIC CALCULATIONS ---
    function calculateRow(row) {
        var qty = parseFloat($(row).find('.product-qty').val()) || 0;
        var rate = parseFloat($(row).find('.product-rate').val()) || 0;
        var amount = qty * rate;
        $(row).find('.product-amount').text(amount.toFixed(2));
        return amount;
    }

    function calculateTotal() {
        var total = 0;
        $('.quote-table tbody tr').each(function() {
            // Ensure we don't calculate the total row itself
            if ($(this).find('#grand-total').length === 0) {
                total += calculateRow(this);
            }
        });
        $('#grand-total').text(total.toFixed(2));
        $('#amount-in-words').text(numberToIndianWords(total) + ' Only');
    }

    function numberToIndianWords(num) {
        const a = ['', 'one ', 'two ', 'three ', 'four ', 'five ', 'six ', 'seven ', 'eight ', 'nine ', 'ten ', 'eleven ', 'twelve ', 'thirteen ', 'fourteen ', 'fifteen ', 'sixteen ', 'seventeen ', 'eighteen ', 'nineteen '];
        const b = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

        const n = ('000000000' + Math.floor(num)).substr(-9);
        const matched = n.match(/^(\d{2})(\d{2})(\d{2})(\d{1})(\d{2})$/);
        if (!matched) return '';

        let str = '';
        str += matched[1] != 0 ? (a[Number(matched[1])] || b[matched[1][0]] + ' ' + a[matched[1][1]]) + 'crore ' : '';
        str += matched[2] != 0 ? (a[Number(matched[2])] || b[matched[2][0]] + ' ' + a[matched[2][1]]) + 'lakh ' : '';
        str += matched[3] != 0 ? (a[Number(matched[3])] || b[matched[3][0]] + ' ' + a[matched[3][1]]) + 'thousand ' : '';
        str += matched[4] != 0 ? (a[Number(matched[4])] || b[matched[4][0]] + ' ' + a[matched[4][1]]) + 'hundred ' : '';
        str += matched[5] != 0 ? ((str != '') ? 'and ' : '') + (a[Number(matched[5])] || b[matched[5][0]] + ' ' + a[matched[5][1]]) : '';
        
        // Handle Paisa (decimals)
        const decimal = Math.round((num % 1) * 100);
        if (decimal > 0) {
            str += ' and ' + (a[decimal] || b[String(decimal)[0]] + ' ' + a[String(decimal)[1]]) + 'paise';
        }

        return str.trim() || 'zero';
    }

    // Calculate on input change
    $('.quote-table').on('input', '.product-qty, .product-rate', function() {
        calculateTotal();
    });

    // --- DYNAMIC ROW MANAGEMENT ---
    $('#add-row').on('click', function() {
        var newRow = `
            <tr>
                <td class="sr-no"></td>
                <td><textarea class="form-control-plaintext product-description" rows="1"></textarea></td>
                <td><input type="number" class="form-control-plaintext product-qty" value="1" min="0"></td>
                <td><input type="number" class="form-control-plaintext product-rate" value="0.00" step="0.01" min="0"></td>
                <td class="product-amount">0.00</td>
                <td class="no-print"><button class="btn btn-danger btn-sm remove-product-row">Remove</button></td>
            </tr>
        `;
        // Insert the new row before the total row
        $('.quote-table tbody tr:last').before(newRow);
        updateSerialNumbers();
    });

    $('.quote-table').on('click', '.remove-product-row', function() {
        $(this).closest('tr').remove();
        updateSerialNumbers();
        calculateTotal();
    });

    function updateSerialNumbers() {
        $('.quote-table tbody tr').each(function(index) {
            if ($(this).find('#grand-total').length === 0) {
                $(this).find('.sr-no').text(index + 1);
            }
        });
    }

    // --- DYNAMIC TEXT UPDATE ---
    $('#customer-name').on('input', function() {
        $('#dear-customer-name').text($(this).val());
    });

    // --- SERVERLESS PDF PREVIEW ---
    $('#generate-serverless-pdf').on('click', async function() {
        const items = [];
        $('.quote-table tbody tr').each(function() {
            if ($(this).find('.product-description').length > 0) {
                items.push({
                    description: $(this).find('.product-description').val(),
                    qty: parseFloat($(this).find('.product-qty').val()) || 0,
                    rate: parseFloat($(this).find('.product-rate').val()) || 0,
                    amount: parseFloat($(this).find('.product-amount').text()) || 0
                });
            }
        });

        const payload = {
            customerName: $('#customer-name').val(),
            quoteId: "<?php echo $inquiry['id']; ?>",
            companyName: "<?php echo COMPANY_NAME; ?>",
            grandTotal: $('#grand-total').text(),
            items: items
        };

        $(this).prop('disabled', true).text('Generating...');

        try {
            const { data, error } = await sbClient.functions.invoke('generate-pdf', {
                body: payload,
            });

            $(this).prop('disabled', false).text('Preview PDF (Serverless)');

            if (error) {
                alert('Error generating PDF: ' + error.message);
            } else {
                // Open the PDF in a new tab
                const blob = new Blob([data], { type: 'application/pdf' });
                const url = window.URL.createObjectURL(blob);
                window.open(url);
            }
        } catch (err) {
            $(this).prop('disabled', false).text('Preview PDF (Serverless)');
            alert('An unexpected error occurred during PDF generation: ' + err.message);
        }
    });

    // Initial calculation on page load
    calculateTotal();

    // --- SEND EMAIL LOGIC ---
    $('#send-quote-btn').on('click', function(e){
        e.preventDefault();
        var button = $(this);
        var statusDiv = $('#send-status');

        button.prop('disabled', true).text('Sending...');
        statusDiv.html('<div class="alert alert-info">Processing and sending email...</div>');

        // Create a clean version of the quote for the email body
        var quoteHtml = $('.letterhead-container').clone();
        quoteHtml.find('.no-print').remove();
        
        // Replace input fields with their values for a clean look in the email
        quoteHtml.find('input, textarea').each(function() {
            // Replace the input/textarea element itself with its value as text
            $(this).replaceWith(document.createTextNode($(this).val()));
        });

        // Get the current values from the editable fields
        var customerEmail = $('#customer-email').val();
        var customerName = $('#customer-name').val();

        $.ajax({
            url: 'send_quote_email.php',
            type: 'POST',
            data: {
                quote_html: quoteHtml[0].outerHTML,
                customer_email: customerEmail,
                customer_name: customerName,
                quote_id: button.data('quote-id')
            },
            success: function(response){
                if (response.trim() === 'OK') {
                    statusDiv.html('<div class="alert alert-success">Quote sent successfully to ' + customerEmail + '!</div>');
                    button.text('Sent!').removeClass('btn-success').addClass('btn-secondary');
                } else {
                    statusDiv.html('<div class="alert alert-danger">Error: ' + response + '</div>');
                    button.prop('disabled', false).text('Send to Customer');
                }
            },
            error: function(xhr){
                statusDiv.html('<div class="alert alert-danger">An unknown error occurred: ' + xhr.responseText + '</div>');
                button.prop('disabled', false).text('Send to Customer');
            }
        });
    });
});
</script>
</body>
</html>
<?php $conn = null; ?>