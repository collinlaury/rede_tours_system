<?php
include '../includes/auth.php';
include '../includes/config.php';

/* 1. DEFAULT BACKUP EXCHANGE RATES (Ikitokea Internet au API imefeli) */
$exchange_rates = [
    'USD' => 1.0,
    'EUR' => 0.92,
    'GBP' => 0.79,
    'TZS' => 2600.0
];

/* 2. CHUKUA LIVE EXCHANGE RATES KUTOKA INTERNET (API) */
// Tunatumia API ya bure isiyohitaji muundo mgumu kuanza nayo. Base ni USD.
$api_url = "https://open.er-api.com/v6/latest/USD";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Isizidi sekunde 3 kusubiri ili mfumo usikuwe mvivu
$response = curl_exec($ch);
curl_close($ch);

if ($response) {
    $api_data = json_decode($response, true);
    // Hakikisha API imerudisha majibu sahihi
    if (isset($api_data['result']) && $api_data['result'] === 'success') {
        $rates = $api_data['rates'];
        // Tunachukua sarafu zetu maalum tu tunazozitaka
        $exchange_rates['USD'] = (float)($rates['USD'] ?? 1.0);
        $exchange_rates['EUR'] = (float)($rates['EUR'] ?? 0.92);
        $exchange_rates['GBP'] = (float)($rates['GBP'] ?? 0.79);
        $exchange_rates['TZS'] = (float)($rates['TZS'] ?? 2600.0);
        $is_live = true;
    } else {
        $is_live = false; // API imefeli, inatumia backup
    }
} else {
    $is_live = false; // Hakuna internet au API ipo chini, inatumia backup
}

function convertCurrencyForReport($amount, $from_currency, $to_currency, $rates) {
    if ($from_currency == $to_currency) return $amount;
    $amount_in_usd = $amount / ($rates[$from_currency] ?? 1.0);
    return $amount_in_usd * ($rates[$to_currency] ?? 1.0);
}

// Kupokea filters kutoka URL au Form
$selected_currency = isset($_GET['filter_currency']) ? mysqli_real_escape_string($conn, $_GET['filter_currency']) : 'USD';
$time_range = isset($_GET['time_range']) ? mysqli_real_escape_string($conn, $_GET['time_range']) : 'all';

// Logic ya tarehe
$date_condition = "";
switch ($time_range) {
    case 'weekly':
        $date_condition = " WHERE YEARWEEK(tour_date, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'monthly':
        $date_condition = " WHERE MONTH(tour_date) = MONTH(CURDATE()) AND YEAR(tour_date) = YEAR(CURDATE())";
        break;
    case 'terminal':
        $date_condition = " WHERE tour_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
        break;
    case 'annually':
        $date_condition = " WHERE YEAR(tour_date) = YEAR(CURDATE())";
        break;
    default:
        $date_condition = "";
        break;
}

$bookings_query = mysqli_query($conn, "SELECT * FROM bookings $date_condition ORDER BY tour_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rede Tours - Bookings Financial Report</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 20px;
            color: #333;
        }
        .container {
            max-width: 1300px;
            margin: auto;
            background: #fff;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .no-print-zone {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #e9ecef;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 25px;
        }
        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .currency-filter-select {
            padding: 8px 12px;
            background: #ffffff;
            border: 1px solid #003366;
            border-radius: 6px;
            color: #003366;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-action {
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            border: none;
        }
        .btn-print { background: #003366; color: white; }
        .btn-close { background: #6c757d; color: white; }
        
        .header {
            text-align: center;
            background: #003366;
            color: white;
            padding: 25px;
            border-radius: 6px;
            margin-bottom: 25px;
        }
        .header h1 { margin: 0; font-size: 28px; letter-spacing: 1px; }
        .header p { margin: 5px 0 0 0; font-size: 14px; color: #CFA58C; text-transform: uppercase; font-weight: bold; }
        
        .report-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 14px;
            border-bottom: 2px solid #003366;
            padding-bottom: 10px;
        }
        .meta-box strong { color: #003366; }
        .status-badge {
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-live { background: #c6f6d5; color: #22543d; }
        .status-backup { background: #feebc8; color: #744210; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        table th {
            background: #003366;
            color: white;
            padding: 12px 10px;
            font-size: 12px;
            text-transform: uppercase;
        }
        table td {
            border: 1px solid #ddd;
            padding: 10px;
            font-size: 12px;
        }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .text-right { text-align: right; }
        .orig-currency-cell { color: #666; font-style: italic; background-color: #fcfcfc; }
        .converted-currency-cell { font-weight: bold; background-color: #f4f8ff; }
        
        .summary-row {
            background: #e2eafc !important;
            font-weight: bold;
            color: #003366;
            font-size: 13px;
        }

        @media print {
            body { background: #fff; margin: 0; }
            .container { box-shadow: none; padding: 0; max-width: 100%; }
            .no-print-zone { display: none; }
            table th { background: #003366 !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .summary-row { background: #e2eafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="container">
    
    <div class="no-print-zone">
        <form method="GET" id="htmlReportFilterForm" style="display: flex; gap: 15px; align-items: center;">
            <div class="filter-group">
                <label style="font-size: 13px; font-weight:600; color: #333;">Period:</label>
                <select name="time_range" class="currency-filter-select" onchange="document.getElementById('htmlReportFilterForm').submit();">
                    <option value="all" <?= $time_range == 'all' ? 'selected' : ''; ?>>All Bookings (Lifetime)</option>
                    <option value="weekly" <?= $time_range == 'weekly' ? 'selected' : ''; ?>>Weekly Report</option>
                    <option value="monthly" <?= $time_range == 'monthly' ? 'selected' : ''; ?>>Monthly Report</option>
                    <option value="terminal" <?= $time_range == 'terminal' ? 'selected' : ''; ?>>Terminal Report (6 Mos)</option>
                    <option value="annually" <?= $time_range == 'annually' ? 'selected' : ''; ?>>Annually Report (This Year)</option>
                </select>
            </div>

            <div class="filter-group">
                <label style="font-size: 13px; font-weight:600; color: #333;">Convert To:</label>
                <select name="filter_currency" class="currency-filter-select" onchange="document.getElementById('htmlReportFilterForm').submit();">
                    <option value="USD" <?= $selected_currency == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                    <option value="GBP" <?= $selected_currency == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                    <option value="EUR" <?= $selected_currency == 'EUR' ? 'selected' : ''; ?>>EURO (€)</option>
                    <option value="TZS" <?= $selected_currency == 'TZS' ? 'selected' : ''; ?>>TZS (Shilingi)</option>
                </select>
            </div>
        </form>

        <div style="display: flex; gap: 10px;">
            <button onclick="window.print();" class="btn-action btn-print">
                <i class="bi bi-printer-fill"></i> Print Report
            </button>
            <button onclick="window.close();" class="btn-action btn-close">
                <i class="bi bi-x-circle-fill"></i> Close
            </button>
        </div>
    </div>

    <div class="header">
        <h1>REDE TOURS & SAFARIS</h1>
        <p>Bookings Financial Statement Report</p>
    </div>

    <div class="report-meta">
        <div class="meta-box">
            <strong>Time Period:</strong> 
            <?php 
                switch($time_range) {
                    case 'weekly': echo 'Weekly Report (This Week)'; break;
                    case 'monthly': echo 'Monthly Report (This Month)'; break;
                    case 'terminal': echo 'Terminal Report (6 Months)'; break;
                    case 'annually': echo 'Annually Report (This Year)'; break;
                    default: echo 'All Bookings (Lifetime)'; break;
                }
            ?>
        </div>
        <div class="meta-box">
            <strong>Target Currency:</strong> <span style="color: #003366; font-weight: bold;"><?= htmlspecialchars($selected_currency) ?></span>
        </div>
        <div class="meta-box">
            <strong>Rates Status:</strong> 
            <?php if($is_live): ?>
                <span class="status-badge status-live"><i class="bi bi-globe"></i> Live Internet Rates</span>
            <?php else: ?>
                <span class="status-badge status-backup"><i class="bi bi-exclamation-triangle-fill"></i> Backup Rates</span>
            <?php endif; ?>
        </div>
        <div class="meta-box">
            <strong>Generated On:</strong> <?= date('d-M-Y H:i A') ?>
        </div>
    </div>

    <div style="font-size: 11px; color: #555; margin-bottom: 10px; text-align: right; font-style: italic;">
        1 USD = 1.00 USD | <?= number_format($exchange_rates['TZS'], 2) ?> TZS | <?= number_format($exchange_rates['EUR'], 2) ?> EUR | <?= number_format($exchange_rates['GBP'], 2) ?> GBP
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="text-align: left; vertical-align: middle;">Client Name</th>
                <th rowspan="2" style="text-align: left; vertical-align: middle;">Tour Title</th>
                <th colspan="3" style="text-align: center; background: #002244;">Original Amounts</th>
                <th colspan="3" style="text-align: center; background: #004488;">Converted Amounts (<?= htmlspecialchars($selected_currency) ?>)</th>
                <th rowspan="2" style="text-align: left; vertical-align: middle;">Tour Date</th>
            </tr>
            <tr>
                <th>Curr</th>
                <th class="text-right">Full</th>
                <th class="text-right">Paid</th>
                <th class="text-right">Full Amount</th>
                <th class="text-right">Paid Deposit</th>
                <th class="text-right">Remaining</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $grand_total_full = 0;
            $grand_total_paid = 0;
            $grand_total_remaining = 0;
            $has_records = false;

            if ($bookings_query && mysqli_num_rows($bookings_query) > 0) {
                $has_records = true;
                while ($row = mysqli_fetch_assoc($bookings_query)) {
                    $converted_full = convertCurrencyForReport($row['full_deposit'], $row['currency'], $selected_currency, $exchange_rates);
                    $converted_deposit = convertCurrencyForReport($row['deposit'], $row['currency'], $selected_currency, $exchange_rates);
                    $converted_remaining = $converted_full - $converted_deposit;

                    $grand_total_full += $converted_full;
                    $grand_total_paid += $converted_deposit;
                    $grand_total_remaining += $converted_remaining;
            ?>
            <tr>
                <td><?= htmlspecialchars($row['client_name']) ?></td>
                <td><?= htmlspecialchars($row['tour_title']) ?></td>
                
                <td style="text-align: center;" class="orig-currency-cell"><?= htmlspecialchars($row['currency']) ?></td>
                <td class="text-right orig-currency-cell"><?= number_format($row['full_deposit'], 2) ?></td>
                <td class="text-right orig-currency-cell" style="color: #2b6cb0;"><?= number_format($row['deposit'], 2) ?></td>
                
                <td class="text-right converted-currency-cell"><?= number_format($converted_full, 2) ?></td>
                <td class="text-right converted-currency-cell" style="color: #2b6cb0;"><?= number_format($converted_deposit, 2) ?></td>
                <td class="text-right converted-currency-cell" style="color: #e53e3e;"><?= number_format($converted_remaining, 2) ?></td>
                
                <td><?= date('d-M-Y', strtotime($row['tour_date'])) ?></td>
            </tr>
            <?php 
                }
            }

            if($has_records) {
            ?>
            <tr class="summary-row">
                <td colspan="5" style="text-align: left;">CONSOLIDATED REPORT TOTALS (<?= htmlspecialchars($selected_currency) ?>)</td>
                <td class="text-right"><?= number_format($grand_total_full, 2) ?></td>
                <td class="text-right"><?= number_format($grand_total_paid, 2) ?></td>
                <td class="text-right" style="color: #b7791f;"><?= number_format($grand_total_remaining, 2) ?></td>
                <td></td>
            </tr>
            <?php 
            } else { 
            ?>
            <tr>
                <td colspan="9" style="text-align: center; padding: 30px; color: #777;">No booking ledger records found for the selected filter.</td>
            </tr>
            <?php } ?>
        </tbody>
    </table>

</div>

</body>
</html>