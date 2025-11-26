<?php
// This is a template file. It expects $slip and $logoBase64 variables to be defined.
if (!isset($slip) || !isset($logoBase64)) {
    exit('Payslip data is not available.');
}
$slip_period = date('F Y', mktime(0, 0, 0, $slip['slip_month'], 1, $slip['slip_year']));
?>

<div class="payslip-header">
    <?php
    if (!empty($logoBase64)) {
        echo '<img src="' . $logoBase64 . '" alt="Company Logo" style="width: 100px; height: auto; margin-bottom: 10px;">';
    }
    ?>
    <h2 style="font-size: 28px; font-weight: bold; margin: 0;"><?php echo COMPANY_NAME; ?></h2>
    <p>Plot no.9158, Block-F, Sanjay colony, Sector 23, Faridabad-121005 (Haryana)</p>
    <h4>Payslip for the month of <?php echo $slip_period; ?></h4>
</div>

<table class="table table-sm table-bordered">
    <tbody>
        <tr>
            <td class="font-weight-bold">Employee ID</td>
            <td><?php echo htmlspecialchars($slip['employee_id_str']); ?></td>
            <td class="font-weight-bold">Name</td>
            <td><?php echo htmlspecialchars($slip['full_name']); ?></td>
        </tr>
        <tr>
            <td class="font-weight-bold">Designation</td>
            <td><?php echo htmlspecialchars($slip['designation']); ?></td>
            <td class="font-weight-bold">PAN</td>
            <td><?php echo htmlspecialchars($slip['pan_card']); ?></td>
        </tr>
        <tr>
            <td class="font-weight-bold">Bank Name</td>
            <td><?php echo htmlspecialchars($slip['bank_name']); ?></td>
            <td class="font-weight-bold">Account No.</td>
            <td><?php echo htmlspecialchars($slip['bank_account_number']); ?></td>
        </tr>
        <tr>
            <td class="font-weight-bold">UAN</td>
            <td><?php echo htmlspecialchars($slip['uan_number']); ?></td>
            <td class="font-weight-bold">ESI No.</td>
            <td><?php echo htmlspecialchars($slip['esi_number']); ?></td>
        </tr>
    </tbody>
</table>

<table class="table table-sm table-bordered mt-4">
    <thead>
        <tr>
            <th class="section-title">Earnings</th>
            <th class="section-title text-right">Amount (INR)</th>
            <th class="section-title">Deductions</th>
            <th class="section-title text-right">Amount (INR)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Basic Salary</td>
            <td class="text-right"><?php echo number_format($slip['basic_salary'], 2); ?></td>
            <td>Provident Fund (PF)</td>
            <td class="text-right"><?php echo number_format($slip['deduction_pf'], 2); ?></td>
        </tr>
        <tr>
            <td>House Rent Allowance (HRA)</td>
            <td class="text-right"><?php echo number_format($slip['hra'], 2); ?></td>
            <td>Employee State Insurance (ESI)</td>
            <td class="text-right"><?php echo number_format($slip['deduction_esi'], 2); ?></td>
        </tr>
        <tr>
            <td>Transport Allowance</td>
            <td class="text-right"><?php echo number_format($slip['transport_allowance'], 2); ?></td>
            <td>Professional Tax (PT)</td>
            <td class="text-right"><?php echo number_format($slip['deduction_pt'], 2); ?></td>
        </tr>
        <tr>
            <td>Medical Allowance</td>
            <td class="text-right"><?php echo number_format($slip['medical_allowance'], 2); ?></td>
            <td>Income Tax (TDS)</td>
            <td class="text-right"><?php echo number_format($slip['deduction_tds'], 2); ?></td>
        </tr>
        <tr>
            <td>Special Allowance</td>
            <td class="text-right"><?php echo number_format($slip['special_allowance'], 2); ?></td>
            <td>Loan EMI</td>
            <td class="text-right"><?php echo number_format($slip['deduction_loan'], 2); ?></td>
        </tr>
         <tr>
            <td>Overtime Pay</td>
            <td class="text-right"><?php echo number_format($slip['overtime_pay'], 2); ?></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>Bonus / Incentives</td>
            <td class="text-right"><?php echo number_format($slip['bonus'], 2); ?></td>
            <td></td>
            <td></td>
        </tr>
    </tbody>
    <tfoot>
        <tr class="section-title">
            <td class="font-weight-bold">Gross Earnings</td>
            <td class="text-right font-weight-bold"><?php echo number_format($slip['gross_earnings'], 2); ?></td>
            <td class="font-weight-bold">Total Deductions</td>
            <td class="text-right font-weight-bold"><?php echo number_format($slip['total_deductions'], 2); ?></td>
        </tr>
    </tfoot>
</table>

<table class="table table-sm table-bordered mt-4">
    <tbody>
        <tr>
            <td class="font-weight-bold section-title">Net Salary</td>
            <td class="text-right font-weight-bold section-title"><?php echo number_format($slip['net_salary'], 2); ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <strong>Amount in Words:</strong> 
                <?php
                    // A simple function to convert number to words for INR
                    function numberToWords($number) {
                        // This is a simplified version. For a production system, a more robust library is recommended.
                        $no = floor($number);
                        $point = round($number - $no, 2) * 100;
                        $hundred = null; $digits_1 = strlen($no);
                        $i = 0; $str = array();
                        $words = array('0' => '', '1' => 'one', '2' => 'two', '3' => 'three', '4' => 'four', '5' => 'five', '6' => 'six', '7' => 'seven', '8' => 'eight', '9' => 'nine', '10' => 'ten', '11' => 'eleven', '12' => 'twelve', '13' => 'thirteen', '14' => 'fourteen', '15' => 'fifteen', '16' => 'sixteen', '17' => 'seventeen', '18' => 'eighteen', '19' =>'nineteen', '20' => 'twenty', '30' => 'thirty', '40' => 'forty', '50' => 'fifty', '60' => 'sixty', '70' => 'seventy', '80' => 'eighty', '90' => 'ninety');
                        $digits = array('', 'hundred', 'thousand', 'lakh', 'crore');
                        while ($i < $digits_1) {
                            $divider = ($i == 2) ? 10 : 100;
                            $number = floor($no % $divider);
                            $no = floor($no / $divider);
                            $i += ($divider == 10) ? 1 : 2;
                            if ($number) {
                                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                                $str [] = ($number < 21) ? $words[$number] . " " . $digits[$counter] . $plural . " " . $hundred : $words[floor($number / 10) * 10] . " " . $words[$number % 10] . " " . $digits[$counter] . " " . $plural . " " . $hundred;
                            } else $str[] = null;
                        }
                        $str = array_reverse($str);
                        $result = implode('', $str);
                        $paise = ($point > 0) ? " and " . ($words[floor($point / 10) * 10] ?? '') . " " . ($words[$point % 10] ?? '') . " Paise" : '';
                        return ucwords($result) . "Rupees" . $paise . " Only";
                    }
                    echo numberToWords($slip['net_salary']);
                ?>
            </td>
        </tr>
    </tbody>
</table>

<div class="mt-5 text-muted">
    <p>This is a computer-generated payslip and does not require a signature.</p>
</div>