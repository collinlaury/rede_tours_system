<?php
include '../includes/auth.php';
include '../includes/config.php';

/* SETUP EXCHANGE RATES */
$exchange_rates = [
    'USD' => 1.0,
    'EUR' => 0.92,
    'GBP' => 0.79,
    'TZS' => 2600.0
];

function convertCurrencyForReport($amount, $from_currency, $to_currency, $rates) {
    if ($from_currency == $to_currency) return $amount;
    $amount_in_usd = $amount / ($rates[$from_currency] ?? 1.0);
    return $amount_in_usd * ($rates[$to_currency] ?? 1.0);
}

$selected_currency = isset($_GET['filter_currency']) ? mysqli_real_escape_string($conn, $_GET['filter_currency']) : 'USD';
$time_range = isset($_GET['time_range']) ? mysqli_real_escape_string($conn, $_GET['time_range']) : 'all';

$date_condition = "";
switch ($time_range) {
    case 'weekly': $date_condition = " WHERE YEARWEEK(tour_date, 1) = YEARWEEK(CURDATE(), 1)"; break;
    case 'monthly': $date_condition = " WHERE MONTH(tour_date) = MONTH(CURDATE()) AND YEAR(tour_date) = YEAR(CURDATE())"; break;
    case 'terminal': $date_condition = " WHERE tour_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)"; break;
    case 'annually': $date_condition = " WHERE YEAR(tour_date) = YEAR(CURDATE())"; break;
    default: $date_condition = ""; break;
}

$bookings_query = mysqli_query($conn, "SELECT * FROM bookings $date_condition ORDER BY tour_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rede Tours - Bookings Financial Report (<?= strtoupper($time_range) ?>)</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; background: #fff; margin: 30px; }
        .pdf-container { max-width: 100%; margin: auto; }
        .invoice-header { text-align: center; border-bottom: 3px solid #003366; padding-bottom: 15px; margin-bottom: 30px; }
        .invoice-header h1 { margin: 0; color: #003366; font-size: 28px; font-weight: bold; }
        .invoice-header p { margin: 5px 0; font-size: 14px; text-transform: uppercase; color: #555; }
        .meta-info { display: flex; justify-content: space-between; margin-bottom: 25px; font-size: 13px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background-color: #003366; color: #fff; padding: 10px; font-size: 12px; text-transform: uppercase; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; font-size: 12px; }
        .text-right { text-align: right; }
        .total-row { background-color: #f2f2f2; font-weight: bold; font-size: 13px; color: #000; }
        /* Automatic trigger download box on load */
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print();">

<div class="pdf-container">
    <div class="invoice-header">
        <h1>REDE TOURS & SAFARIS</h1>
        <p>Bookings Financial Statement Report (<?= strtoupper($time_range) ?>)</p>
    </div>

    <div class="meta-info">
        <div>Generated Date: <?= date('d-m-Y H:i A') ?></div>
        <div>Reporting Currency: <?= htmlspecialchars($selected_currency) ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Client Name</th>
                <th>Tour Title</th>
                <th>Orig. Curr</th>
                <th class="text-right">Full Amount</th>
                <th class="text-right">Paid Deposit</th>
                <th class="text-right">Remaining (<?= htmlspecialchars($selected_currency) ?>)</th>
                <th>Tour Date</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $grand_total_full = 0;
            $grand_total_paid = 0;
            $grand_total_remaining = 0;

            if ($bookings_query && mysqli_num_rows($bookings_query) > 0) {
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
                <td><?= htmlspecialchars($row['currency']) ?></td>
                <td class="text-right"><?= number_format($converted_full, 2) ?></td>
                <td class="text-right"><?= number_format($converted_deposit, 2) ?></td>
                <td class="text-right"><?= number_format($converted_remaining, 2) ?></td>
                <td><?= $row['tour_date'] ?></td>
            </tr>
            <?php 
                }
            ?>
            <tr class="total-row">
                <td colspan="3">CONSOLIDATED REPORT TOTALS</td>
                <td class="text-right"><?= number_format($grand_total_full, 2) ?></td>
                <td class="text-right"><?= number_format($grand_total_paid, 2) ?></td>
                <td class="text-right"><?= number_format($grand_total_remaining, 2) ?></td>
                <td></td>
            </tr>
            <?php
            } else {
                echo "<tr><td colspan='7' style='text-align:center;'>No booking records found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>