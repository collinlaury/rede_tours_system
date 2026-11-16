<?php
include '../includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';

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

// 1. Kupokea sarafu ya mtazamaji (Default ni USD)
$selected_currency = isset($_GET['filter_currency']) ? mysqli_real_escape_string($conn, $_GET['filter_currency']) : 'USD';

// 2. Kupokea kichujio cha muda (Default ni 'all')
$time_range = isset($_GET['time_range']) ? mysqli_real_escape_string($conn, $_GET['time_range']) : 'all';

// Kutengeneza Logic ya SQL kulingana na muda uliochaguliwa kwa kutumia 'tour_date'
$date_condition = "";
switch ($time_range) {
    Case 'weekly':
        $date_condition = " WHERE YEARWEEK(tour_date, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    Case 'monthly':
        $date_condition = " WHERE MONTH(tour_date) = MONTH(CURDATE()) AND YEAR(tour_date) = YEAR(CURDATE())";
        break;
    Case 'terminal': // Miezi 6 iliyopita (Au nusu mwaka)
        $date_condition = " WHERE tour_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
        break;
    Case 'annually':
        $date_condition = " WHERE YEAR(tour_date) = YEAR(CURDATE())";
        break;
    Default:
        $date_condition = ""; // Inaonyesha bookings zote bila kujali tarehe
        break;
}

// Vuta bookings kulingana na kichujio cha tarehe kilichochaguliwa
$bookings_query = mysqli_query($conn, "SELECT * FROM bookings $date_condition ORDER BY tour_date DESC");

// TUNAJUMUISHA SIDEBAR
include '../includes/sidebar.php'; 
?>

<link rel="stylesheet" href="../assets/css/booking.css">

<style>
    Body {
        Font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        Background-color: #0b2265;
        Color: #ffffff;
        Margin: 0;
        Padding: 0;
    }
    .main-content {
        Margin-left: 260px;
        Padding: 90px 30px 30px 30px;
        Box-sizing: border-box;
    }
    .container {
        Background: rgba(255, 255, 255, 0.05);
        Padding: 25px;
        Border-radius: 15px;
        Box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        Backdrop-filter: blur(10px);
    }
    .header-section {
        Display: flex;
        Justify-content: space-between;
        Align-items: center;
        Border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        Padding-bottom: 15px;
        Margin-bottom: 20px;
    }
    H2 { margin: 0; font-size: 26px; font-weight: 600; color: #ffffff; }
    H3.sub-title { margin: 5px 0 0 0; color: #CFA58C; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }

    .btn-group-actions {
        Display: flex;
        Gap: 10px;
    }
    .btn-back-booking, .btn-download-pdf {
        Border-radius: 8px;
        Font-weight: bold;
        Text-decoration: none;
        Cursor: pointer;
        Transition: all 0.3s;
        Display: inline-flex;
        Align-items: center;
        Gap: 8px;
        Padding: 10px 18px;
        Font-size: 14px;
    }
    .btn-back-booking {
        Background-color: transparent;
        Border: 1px solid #CFA58C;
        Color: #CFA58C;
    }
    .btn-back-booking:hover {
        Background-color: #CFA58C;
        Color: #0b2265;
    }
    .btn-download-pdf {
        Background-color: #2ecc71;
        Border: 1px solid #2ecc71;
        Color: #ffffff;
    }
    .btn-download-pdf:hover {
        Background-color: #27ae60;
        Border-color: #27ae60;
    }
    
    .dashboard-summary {
        Display: grid;
        Grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        Gap: 20px;
        Margin-bottom: 25px;
    }
    .summary-card {
        Background: rgba(255, 255, 255, 0.08);
        Border: 1px solid rgba(255, 255, 255, 0.1);
        Padding: 20px;
        Border-radius: 12px;
        Display: flex;
        Flex-direction: column;
        Gap: 8px;
    }
    .summary-card.total-card { border-left: 5px solid #2ecc71; }
    .summary-card.paid-card { border-left: 5px solid #3498db; }
    .summary-card.remaining-card { border-left: 5px solid #CFA58C; }
    
    .summary-card span {
        Font-size: 13px;
        Color: rgba(255, 255, 255, 0.6);
        Text-transform: uppercase;
        Font-weight: 600;
        Letter-spacing: 0.5px;
    }
    .summary-card h4 { margin: 0; font-size: 24px; font-weight: 700; }

    .filters-wrapper {
        Display: flex;
        Justify-content: flex-end;
        Align-items: center;
        Margin-bottom: 20px;
        Gap: 15px;
    }
    .filter-group {
        Display: flex;
        Align-items: center;
        Gap: 8px;
    }
    .currency-filter-select {
        Padding: 10px 15px;
        Background: #0f2b7a;
        Border: 1px solid #CFA58C;
        Border-radius: 8px;
        Color: #ffffff;
        Font-size: 14px;
        Font-weight: bold;
        Cursor: pointer;
    }

    Table { width: 100%; border-collapse: collapse; margin-top: 10px; background: rgba(255, 255, 255, 0.02); border-radius: 10px; overflow: hidden; }
    Th, td { padding: 14px 18px; text-align: left; border-bottom: 1px solid rgba(255, 255, 255, 0.1); font-size: 14px; }
    Th { background-color: rgba(255, 255, 255, 0.1); color: #CFA58C; font-weight: 600; text-transform: uppercase; font-size: 13px; }
    Tr:hover { background-color: rgba(255, 255, 255, 0.05); }
    .text-right { text-align: right; }
    .total-row { background: rgba(46, 204, 113, 0.1) !important; font-weight: bold; color: #2ecc71; }
</style>

<div class="main-content">
    <div class="container">
        
        <div class="header-section">
            <div>
                <h2>REDE TOURS & SAFARIS</h2>
                <h3 class="sub-title">Bookings Financial Statement Report</h3>
            </div>
           <div class="btn-group-actions">
    <a href="view_report_booking.php?filter_currency=<?= $selected_currency ?>&time_range=<?= $time_range ?>" target="_blank" class="btn-back-booking" style="border-color: #3498db; color: #3498db;">
        <i class="bi bi-eye-fill"></i> View HTML Report
    </a>
    <a href="pdf_booking_report.php?filter_currency=<?= $selected_currency ?>&time_range=<?= $time_range ?>" target="_blank" class="btn-download-pdf">
        <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF
    </a>
</div>
        </div>

        <?php
        $rows_buffer = [];
        $grand_total_full = 0;
        $grand_total_paid = 0;
        $grand_total_remaining = 0;

        if ($bookings_query && mysqli_num_rows($bookings_query) > 0) {
            While ($row = mysqli_fetch_assoc($bookings_query)) {
                $converted_full = convertCurrencyForReport($row['full_deposit'], $row['currency'], $selected_currency, $exchange_rates);
                $converted_deposit = convertCurrencyForReport($row['deposit'], $row['currency'], $selected_currency, $exchange_rates);
                $converted_remaining = $converted_full - $converted_deposit;

                $grand_total_full += $converted_full;
                $grand_total_paid += $converted_deposit;
                $grand_total_remaining += $converted_remaining;

                $row['display_full'] = $converted_full;
                $row['display_deposit'] = $converted_deposit;
                $row['display_remaining'] = $converted_remaining;
                $rows_buffer[] = $row;
            }
        }
        ?>

        <div class="dashboard-summary">
            <div class="summary-card total-card">
                <span>Total Bookings Value</span>
                <h4 style="color: #2ecc71;"><?php echo number_format($grand_total_full, 2) . ' ' . $selected_currency; ?></h4>
            </div>
            <div class="summary-card paid-card">
                <span>Total Collected (Paid Deposit)</span>
                <h4 style="color: #3498db;"><?php echo number_format($grand_total_paid, 2) . ' ' . $selected_currency; ?></h4>
            </div>
            <div class="summary-card remaining-card">
                <span>Total Outstanding Balance</span>
                <h4 style="color: #CFA58C;"><?php echo number_format($grand_total_remaining, 2) . ' ' . $selected_currency; ?></h4>
            </div>
        </div>

        <div class="filters-wrapper">
            <form method="GET" id="reportFilterForm" style="display: flex; gap: 15px;">
                <div class="filter-group">
                    <label style="font-size: 13px; font-weight:600; color: rgba(255,255,255,0.7);">Time Period:</label>
                    <select name="time_range" class="currency-filter-select" onchange="document.getElementById('reportFilterForm').submit();">
                        <option value="all" <?php echo $time_range == 'all' ? 'selected' : ''; ?>>All Bookings (Lifetime)</option>
                        <option value="weekly" <?php echo $time_range == 'weekly' ? 'selected' : ''; ?>>Weekly Report (This Week)</option>
                        <option value="monthly" <?php echo $time_range == 'monthly' ? 'selected' : ''; ?>>Monthly Report (This Month)</option>
                        <option value="terminal" <?php echo $time_range == 'terminal' ? 'selected' : ''; ?>>Terminal Report (6 Months)</option>
                        <option value="annually" <?php echo $time_range == 'annually' ? 'selected' : ''; ?>>Annually Report (This Year)</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label style="font-size: 13px; font-weight:600; color: rgba(255,255,255,0.7);">View Currency:</label>
                    <select name="filter_currency" class="currency-filter-select" onchange="document.getElementById('reportFilterForm').submit();">
                        <option value="USD" <?php echo $selected_currency == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                        <option value="GBP" <?php echo $selected_currency == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                        <option value="EUR" <?php echo $selected_currency == 'EUR' ? 'selected' : ''; ?>>EURO (€)</option>
                        <option value="TZS" <?php echo $selected_currency == 'TZS' ? 'selected' : ''; ?>>TZS (Shilingi)</option>
                    </select>
                </div>
            </form>
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
                if(!empty($rows_buffer)) {
                    Foreach($rows_buffer as $row) { 
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['tour_title']); ?></td>
                    <td style="color: rgba(255,255,255,0.6);"><?php echo htmlspecialchars($row['currency']); ?></td>
                    <td class="text-right"><?php echo number_format($row['display_full'], 2); ?></td>
                    <td class="text-right" style="color: #3498db;"><?php echo number_format($row['display_deposit'], 2); ?></td>
                    <td class="text-right" style="color: #CFA58C; font-weight: 600;">
                        <?php echo number_format($row['display_remaining'], 2); ?>
                    </td>
                    <td><?php echo $row['tour_date']; ?></td>
                </tr>
                <?php 
                    } 
                ?>
                <tr class="total-row">
                    <td colspan="3">CONSOLIDATED REPORT TOTALS (<?= htmlspecialchars($time_range) ?>)</td>
                    <td class="text-right"><?php echo number_format($grand_total_full, 2); ?></td>
                    <td class="text-right"><?php echo number_format($grand_total_paid, 2); ?></td>
                    <td class="text-right"><?php echo number_format($grand_total_remaining, 2); ?></td>
                    <td></td>
                </tr>
                <?php
                } else { 
                ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px; color: rgba(255,255,255,0.6);">No booking ledger records found for the selected filter.</td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

    </div>
</div>

</body>
</html>