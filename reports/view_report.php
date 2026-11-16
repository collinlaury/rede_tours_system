<?php
include '../includes/config.php';
include '../includes/auth.php';

// 1. Kupokea sarafu iliyochaguliwa (Default ni USD)
$target_currency = $_GET['currency'] ?? 'USD';
$target_currency = strtoupper($target_currency);

// 2. Viwango vya kubadilishia fedha (Live Exchange Rates au Fallback rasmi ya REDE)
function getExchangeRates() {
    $url = "https://open.er-api.com/v6/latest/USD";
    $response = @file_get_contents($url);
    if ($response) {
        $data = json_decode($response, true);
        return $data['rates'] ?? ['USD' => 1.0, 'EUR' => 0.92, 'GBP' => 0.78, 'TZS' => 2615.0];
    }
    return ['USD' => 1.0, 'EUR' => 0.92, 'GBP' => 0.78, 'TZS' => 2615.0];
}
$rates = getExchangeRates();

// Alama za sarafu
$symbols = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'TZS' => 'TSh'];
$currency_symbol = $symbols[$target_currency] ?? '$';

// Kiwango cha TZS kwa 1 USD kwa ajili ya kuonesha kwenye summary ya matumizi
$current_tzs_rate = $rates['TZS'] ?? 2615.0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rede Tours Financial Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 20px; color: #333; }
        .container { max-width: 1000px; margin: auto; background: #fff; padding: 30px; box-shadow: 0 0 15px rgba(0,0,0,0.1); border-radius: 8px; }
        .header { text-align: center; background: #0b2265; color: white; padding: 25px; border-radius: 6px 6px 0 0; position: relative; }
        .header h1 { margin: 0; font-size: 28px; letter-spacing: 1px; }
        .header h3 { margin: 5px 0 0 0; color: #CFA58C; font-size: 16px; }
        
        /* Sehemu ya Udhibiti wa Sarafu */
        .controls-box { background: #f8f9fa; padding: 15px; margin-top: 15px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e9ecef; }
        .selector-group { display: flex; align-items: center; gap: 10px; }
        .selector-group select { padding: 8px 15px; font-size: 14px; border-radius: 4px; border: 1px solid #ccc; font-weight: bold; background: white; }
        .btn-download { background: #28a745; color: white; text-decoration: none; padding: 9px 18px; border-radius: 4px; font-weight: bold; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; transition: background 0.2s; }
        .btn-download:hover { background: #218838; }

        h2 { background: #0b2265; color: white; padding: 12px 15px; margin-top: 35px; font-size: 18px; border-left: 5px solid #CFA58C; border-radius: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; }
        table th { background: #0f2b7a; color: white; padding: 12px; font-size: 14px; text-align: left; }
        table td { border: 1px solid #dee2e6; padding: 12px; font-size: 14px; }
        tr:nth-child(even) { background: #f9fbfd; }
        .text-right { text-align: right; }
        
        /* Rangi Maalum za Mistari ya Chini */
        .total { background: #e2f0d9 !important; font-weight: bold; color: #385723; }
        .summary { background: #fff2cc !important; font-weight: bold; color: #7f6000; }
        .balance { background: #ddebf7 !important; font-weight: bold; color: #1f4e78; }
        .credit { background: #28a745; color: white; font-size: 16px; font-weight: bold; text-align: center !important; }
        .debit { background: #dc3545; color: white; font-size: 16px; font-weight: bold; text-align: center !important; }
    </style>
</head>
<body>

<div class="container">

    <!-- TOP LOGO & HEADER -->
    <div class="header">
        <h1>REDE TOURS & SAFARIS</h1>
        <h3>FINANCIAL STATEMENT REPORT</h3>
    </div>

    <!-- CONTROLS: BOX YA SARAFI NA DOWNLOAD -->
    <div class="controls-box">
        <div class="selector-group">
            <label for="currencySelect"><strong>View Report In:</strong></label>
            <select id="currencySelect" onchange="changeReportCurrency(this.value)">
                <option value="USD" <?= $target_currency == 'USD' ? 'selected' : ''; ?>>USD ($) - Dollar</option>
                <option value="TZS" <?= $target_currency == 'TZS' ? 'selected' : ''; ?>>TZS (TSh) - Tanzanian Shilling</option>
                <option value="EUR" <?= $target_currency == 'EUR' ? 'selected' : ''; ?>>EUR (€) - Euro</option>
                <option value="GBP" <?= $target_currency == 'GBP' ? 'selected' : ''; ?>>GBP (£) - British Pound</option>
            </select>
        </div>
        <a href="pdf_report.php?currency=<?= $target_currency; ?>" class="btn-download">
            <i class="bi bi-file-earmark-pdf"></i> Download PDF
        </a>
    </div>

    <!-- ================= 1. TOTAL AMOUNT RECEIVED ================= -->
    <h2>Total Amount Received (Income)</h2>
    <table>
        <thead>
            <tr>
                <th>Description / Source</th>
                <th class="text-right">Amount (<?= $target_currency ?>)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_income_usd = 0;
            $inc_query = mysqli_query($conn, "SELECT description, amount, currency FROM income ORDER BY id ASC");
            
            while ($row = mysqli_fetch_assoc($inc_query)) {
                $amt = (float)$row['amount'];
                $cur = ($row['currency'] == 'EURO') ? 'EUR' : $row['currency'];
                
                // Kubadili kwenda USD kama base currency
                $amt_in_usd = ($cur === 'USD') ? $amt : ($amt / ($rates[$cur] ?? 1.0));
                $total_income_usd += $amt_in_usd;

                // Kubadili kwenda kwenye Target Currency iliyochaguliwa kwa ajili ya kioo
                $display_amt = $amt_in_usd * ($rates[$target_currency] ?? 1.0);
                echo "<tr>
                        <td>".htmlspecialchars($row['description'])."</td>
                        <td class='text-right'>".number_format($display_amt, 2)."</td>
                      </tr>";
            }
            $final_income = $total_income_usd * ($rates[$target_currency] ?? 1.0);
            ?>
            <tr class="total">
                <td>TOTAL AMOUNT RECEIVED</td>
                <td class="text-right"><?= $currency_symbol ?> <?= number_format($final_income, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <!-- ================= 2. EXPENSE REGISTER ================= -->
    <h2>Expense Register</h2>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Original Currency</th>
                <th class="text-right">Original Amount</th>
                <th class="text-right">Converted (<?= $target_currency ?>)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_usd_used = 0;
            $total_tzs_used = 0;
            $total_other_used_usd = 0;
            
            $exp_query = mysqli_query($conn, "SELECT description, amount, currency FROM expenditures ORDER BY id ASC");
            
            while ($row = mysqli_fetch_assoc($exp_query)) {
                $amt = (float)$row['amount'];
                $cur = ($row['currency'] == 'EURO') ? 'EUR' : $row['currency'];
                
                // Kupanga mchanganuo wa kijasusi wa sarafu asilia
                if ($cur === 'USD') {
                    $total_usd_used += $amt;
                } elseif ($cur === 'TZS') {
                    $total_tzs_used += $amt;
                } else {
                    $total_other_used_usd += ($amt / ($rates[$cur] ?? 1.0));
                }

                // Hesabu ya ujumla kwenda USD
                $amt_in_usd = ($cur === 'USD') ? $amt : ($amt / ($rates[$cur] ?? 1.0));
                $display_exp_amt = $amt_in_usd * ($rates[$target_currency] ?? 1.0);

                echo "<tr>
                        <td>".htmlspecialchars($row['description'])."</td>
                        <td>".$cur."</td>
                        <td class='text-right'>".number_format($amt, 2)."</td>
                        <td class='text-right'>".number_format($display_exp_amt, 2)."</td>
                      </tr>";
            }

            // Piga hesabu ya pamoja ya matumizi katika USD kwanza
            $total_expenses_usd = $total_usd_used + ($total_tzs_used / $rates['TZS']) + $total_other_used_usd;
            $final_expenses = $total_expenses_usd * ($rates[$target_currency] ?? 1.0);
            ?>
        </tbody>
    </table>

    <!-- ================= 3. EXPENSE SUMMARY ================= -->
    <h2>Expense Breakdown Summary</h2>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Value</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Pure USD Used directly</td>
                <td class="text-right">$ <?= number_format($total_usd_used, 2) ?></td>
            </tr>
            <tr>
                <td>Total Pure TZS Used directly</td>
                <td class="text-right">TSh <?= number_format($total_tzs_used, 2) ?></td>
            </tr>
            <tr>
                <td>System Base Exchange Rate (1 USD)</td>
                <td class="text-right"><?= number_format($current_tzs_rate, 2) ?> TZS</td>
            </tr>
            <tr>
                <td>TZS Converted & Consolidated into USD</td>
                <td class="text-right">$ <?= number_format(($total_tzs_used / $rates['TZS']), 2) ?></td>
            </tr>
            <tr class="summary">
                <td>TOTAL CONSOLIDATED EXPENSES</td>
                <td class="text-right"><?= $currency_symbol ?> <?= number_format($final_expenses, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <!-- ================= 4. FINAL FINANCIAL POSITION ================= -->
    <h2>Final Financial Position</h2>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Amount (<?= $target_currency ?>)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Amount Received (A)</td>
                <td class="text-right"><?= $currency_symbol ?> <?= number_format($final_income, 2) ?></td>
            </tr>
            <tr>
                <td>Total Expenses Incurred (B)</td>
                <td class="text-right"><?= $currency_symbol ?> <?= number_format($final_expenses, 2) ?></td>
            </tr>
            <?php 
            $final_balance = $final_income - $final_expenses; 
            ?>
            <tr class="balance">
                <td>Remaining Balance (A - B)</td>
                <td class="text-right"><?= $currency_symbol ?> <?= number_format($final_balance, 2) ?></td>
            </tr>
            <?php if ($final_balance >= 0): ?>
                <tr class="credit">
                    <td colspan="2">STATUS: CREDIT BALANCE = <?= $currency_symbol ?> <?= number_format($final_balance, 2) ?></td>
                </tr>
            <?php else: ?>
                <tr class="debit">
                    <td colspan="2">STATUS: DEBIT / DEFICIT BALANCE = <?= $currency_symbol ?> <?= number_format(abs($final_balance), 2) ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>

<script>
// JavaScript ya kubadilisha ukurasa kulingana na sarafu iliyochaguliwa
function changeReportCurrency(val) {
    window.location.href = 'view_report.php?currency=' + val;
}
</script>

</body>
</html>