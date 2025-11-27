<?php
session_start();

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    exit('Access Denied');
}

require_once 'config.php';
require_once __DIR__ . '/../db_connect.php';
// --- Manual Autoloading for Dompdf ---
require_once __DIR__ . '/../dompdf/autoload.inc.php';

$month = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$year = isset($_GET['year']) ? (int)$_GET['year'] : 0;

if ($month <= 0 || $year <= 0) {
    exit('Invalid month or year specified.');
}

$period_string = date('F-Y', mktime(0, 0, 0, $month, 1, $year));

// 1. Fetch all salary slips for the given month and year
$stmt = $conn->prepare("SELECT * FROM salary_slips WHERE slip_month = ? AND slip_year = ?");
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    exit('No payslips found for the selected period.');
}

// --- Define and check a local temporary directory ---
$local_temp_dir = __DIR__ . '/temp_files';
if (!is_dir($local_temp_dir)) {
    if (!mkdir($local_temp_dir, 0755, true)) {
        exit('Error: Failed to create temporary directory. Please manually create a folder named "temp_files" inside the "admin" directory and give it write permissions.');
    }
}

// 2. Create a temporary ZIP file
$zip = new ZipArchive();
$zip_filename = $local_temp_dir . DIRECTORY_SEPARATOR . 'Payslips_' . $period_string . '.zip';

if ($zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    exit("Cannot create ZIP archive.");
}

// 3. Loop through each slip, generate a PDF, and add it to the ZIP
while ($slip = $result->fetch_assoc()) {
    // --- Prepare Logo Data as Base64 ---
    $logoBase64 = '';
    $logoPath = __DIR__ . '/../images/Logo.png';
    if (file_exists($logoPath)) {
        $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
        $logoData = file_get_contents($logoPath);
        $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
    }

    // Generate the HTML for the payslip using the template, with embedded styles
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Payslip</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
        <style>
            body { font-family: sans-serif; font-size: 10pt; margin: 0; padding: 0; }
            .payslip-container { max-width: 800px; margin: 0; padding: 0; background-color: #fff; border: 1px solid #dee2e6; }
            .payslip-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px; }
            .payslip-header h2 { font-size: 24px; font-weight: bold; margin: 0; }
            .payslip-header p { margin: 0; font-size: 12px; }
            .table-bordered th, .table-bordered td { border: 1px solid #333 !important; }
            .table-sm td, .table-sm th { padding: .4rem; }
            .section-title { font-weight: bold; background-color: #e9ecef; }
            .text-right { text-align: right; }
            .font-weight-bold { font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="payslip-container">
            <?php include 'payslip_template.php'; ?>
        </div>
    </body>
    </html>
    <?php
    $payslip_html = ob_get_clean();

    // Generate PDF from HTML
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true); // Allows Dompdf to access remote resources like the Bootstrap CDN
    $options->set('chroot', realpath(__DIR__ . '/../')); // Set chroot for local file access
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($payslip_html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdf_content = $dompdf->output();

    // Define a unique filename for the PDF inside the ZIP
    $pdf_filename = "Payslip_" . $slip['employee_id_str'] . "_" . $period_string . ".pdf";

    // Add the generated PDF content to the ZIP archive
    $zip->addFromString($pdf_filename, $pdf_content);
}

$stmt->close();
$conn->close();

// 4. Close the ZIP archive
$zip->close();

// 5. Send the ZIP file to the browser for download
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($zip_filename) . '"');
header('Content-Length: ' . filesize($zip_filename));
header('Pragma: no-cache');
header('Expires: 0');

// Clear output buffer before reading the file
ob_clean();
flush();

// Read the file and send its contents to the output buffer
readfile($zip_filename);

// 6. Delete the temporary ZIP file from the server
unlink($zip_filename);

exit;
?>