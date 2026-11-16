<?php
// Jumuisha ulinzi wa login, muunganisho wa DB na sidebar
include '../includes/auth.php';
include '../includes/config.php';

// Ruhusu Sales na roles nyingine husika
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'sales' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../login.php");
    exit();
}

/* SETUP LIVE EXCHANGE RATES KUTOKA BASE CURRENCY (USD) */
$rates = [
    'USD' => 1.0,
    'TZS' => 2600.0, // Default fallback
    'EUR' => 0.92,
    'GBP' => 0.78
];

$api_url = "https://open.er-api.com/v6/latest/USD";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 3);
$response = curl_exec($ch);
curl_close($ch);

if ($response) {
    $api_data = json_decode($response, true);
    if (isset($api_data['result']) && $api_data['result'] === 'success') {
        $rates['TZS'] = (float)($api_data['rates']['TZS'] ?? 2600.0);
        $rates['EUR'] = (float)($api_data['rates']['EUR'] ?? 0.92);
        $rates['GBP'] = (float)($api_data['rates']['GBP'] ?? 0.79);
    }
}

// Kiwango cha kubadili TZS kwenda USD kwa ajili ya normalization
$tzs_to_usd_rate = 1 / $rates['TZS'];

/* 1. KUVUTA ANKARA ZA WATEJA WENYE MADENI */
$query_string = "SELECT * FROM bookings WHERE full_deposit > deposit ORDER BY id DESC";
$invoices_res = mysqli_query($conn, $query_string);

// Jumuisha Sidebar na Header ya mfumo wako
include '../includes/sidebar.php'; 
?>

<div class="main-content">
    
    <div class="page-header-box">
        <div class="header-info">
            <h1>Sales Invoices (Pending Customer Balances)</h1>
            <p>List of tour invoices for customers with pending balances.</p>
        </div>
        
        <div class="currency-selector-box">
            <label for="tableCurrencySwitcher"><i class="bi bi-globe2"></i> Currency:</label>
            <select id="tableCurrencySwitcher" class="currency-select">
                <option value="USD" selected>USD ($)</option>
                <option value="TZS">TZS (Sh)</option>
                <option value="EUR">EUR (€)</option>
                <option value="GBP">GBP (£)</option>
            </select>
        </div>
    </div>

    <div class="search-container-box">
        <div class="search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input type="text" id="invoiceSearchInput" placeholder="Search by customer name or tour date (e.g., 19 Jun)..." onkeyup="filterInvoicesTable()">
        </div>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table class="custom-table" id="invoicesTable">
                <thead>
                    <tr>
                        <th>#</th> <th>Customer Name</th>
                        <th>Tour Date</th>
                        <th id="th-total">Total Cost (USD)</th>
                        <th id="th-paid">Paid Amount (USD)</th>
                        <th id="th-balance">Balance Due (USD)</th>
                        <th>Orig. Curr.</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (mysqli_num_rows($invoices_res) > 0): 
                        $serial_no = 1; // Namba ya kawaida ya kuanzia
                        while ($row = mysqli_fetch_assoc($invoices_res)): 
                            // Thamani asilia kutoka kwenye Database
                            $orig_total = (float)$row['full_deposit'];
                            $orig_paid = (float)$row['deposit'];
                            $orig_curr = $row['currency'];

                            // Kubadili thamani kuwa USD Base ili JavaScript ifanye kazi kwa urahisi
                            $total_usd = ($orig_curr === 'TZS') ? ($orig_total * $tzs_to_usd_rate) : $orig_total;
                            $paid_usd = ($orig_curr === 'TZS') ? ($orig_paid * $tzs_to_usd_rate) : $orig_paid;
                            $balance_usd = $total_usd - $paid_usd;
                    ?>
                        <tr class="invoice-row">
                            <td class="row-serial"><?php echo $serial_no++; ?></td>
                            <td>
                                <span class="cust-name">
                                    <i class="bi bi-person-fill text-muted me-1"></i>
                                    <?php echo htmlspecialchars($row['client_name'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td class="tour-date-cell"><?php echo date('d M, Y', strtotime($row['tour_date'])); ?></td>
                            
                            <td class="fw-bold td-total" data-usd="<?php echo $total_usd; ?>">
                                $<?php echo number_format($total_usd, 2); ?>
                            </td>
                            <td class="text-success fw-bold td-paid" data-usd="<?php echo $paid_usd; ?>">
                                $<?php echo number_format($paid_usd, 2); ?>
                            </td>
                            <td class="text-danger fw-bold td-balance" data-usd="<?php echo $balance_usd; ?>">
                                $<?php echo number_format($balance_usd, 2); ?>
                            </td>
                            
                            <td><span class="badge-currency"><?php echo htmlspecialchars($orig_curr); ?></span></td>
                            <td>
                                <span class="status-badge pending">
                                    <i class="bi bi-clock-history"></i> Partial Paid
                                </span>
                            </td>
                        </tr>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                        <tr class="no-data-row-static">
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-file-earmark-check-fill d-block mb-3" style="font-size: 40px; color: #3a435e;"></i>
                               Congratulations! There are no pending invoices at the moment.
                            </td>
                        </tr>
                    <?php endif; ?>
                    
                    <tr id="noMatchingSearchRow" style="display: none;">
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-exclamation-circle-fill d-block mb-3" style="font-size: 40px; color: #e74c3c;"></i>
                            No related data found
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<style>
    .main-content {
        padding: 40px 30px 30px 30px;
        box-sizing: border-box;
    }

    .page-header-box {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 25px;
        margin-bottom: 20px;
        gap: 20px;
    }

    .header-info h1 {
        margin: 0;
        font-size: 26px;
        color: #ffffff;
        font-weight: 700;
    }

    .header-info p {
        margin: 6px 0 0 0;
        color: rgba(255, 255, 255, 0.6);
        font-size: 14px;
    }

    .currency-selector-box {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 8px 14px;
        border-radius: 10px;
        backdrop-filter: blur(5px);
    }

    .currency-selector-box label {
        color: rgba(255, 255, 255, 0.7);
        font-size: 14px;
        font-weight: 500;
        white-space: nowrap;
    }

    .currency-selector-box label i { color: #CFA58C; }

    .currency-select {
        background: #1e2436; 
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 5px 10px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        outline: none;
    }

    /* Mitindo Mpya ya Search Bar */
    .search-container-box {
        margin-bottom: 25px;
        width: 100%;
        max-width: 450px;
    }

    .search-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .search-icon {
        position: absolute;
        left: 15px;
        color: rgba(255, 255, 255, 0.4);
        font-size: 16px;
    }

    #invoiceSearchInput {
        width: 100%;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        padding: 12px 15px 12px 45px;
        color: #ffffff;
        font-size: 14px;
        outline: none;
        transition: all 0.3s ease;
    }

    #invoiceSearchInput:focus {
        border-color: #CFA58C;
        background: rgba(255, 255, 255, 0.08);
        box-shadow: 0 0 10px rgba(207, 165, 140, 0.1);
    }

    .table-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        backdrop-filter: blur(10px);
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .custom-table th {
        background: rgba(255, 255, 255, 0.02);
        color: #CFA58C;
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .custom-table td {
        padding: 16px;
        color: rgba(255, 255, 255, 0.85);
        font-size: 14px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        vertical-align: middle;
    }

    .custom-table tr:hover td {
        background: rgba(255, 255, 255, 0.02);
    }

    .row-serial {
        color: rgba(255, 255, 255, 0.4) !important;
        font-weight: 600;
    }

    .cust-name {
        font-weight: 600;
        color: #ffffff;
    }

    .badge-currency {
        background: rgba(207, 165, 140, 0.15);
        color: #CFA58C;
        padding: 4px 8px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 12px;
    }

    .status-badge.pending {
        background: rgba(241, 196, 15, 0.1);
        color: #f1c40f;
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    @media (max-width: 768px) {
        .page-header-box { flex-direction: column; align-items: flex-start; }
        .main-content { padding: 40px 15px 15px 15px; }
        .header-info h1 { font-size: 20px; }
    }
</style>

<script>
    const exchangeRates = <?php echo json_encode($rates); ?>;
    const currencySymbols = {
        'USD': '$',
        'TZS': 'TSh ',
        'EUR': '€',
        'GBP': '£'
    };

    const currencySwitcher = document.getElementById('tableCurrencySwitcher');
    const thTotal = document.getElementById('th-total');
    const thPaid = document.getElementById('th-paid');
    const thBalance = document.getElementById('th-balance');

    // 1. MFUMO WA LIVE CURRENCY SWITCHER
    currencySwitcher.addEventListener('change', function() {
        const selectedCurrency = this.value;
        const rate = exchangeRates[selectedCurrency];
        const symbol = currencySymbols[selectedCurrency];

        thTotal.innerText = `Total Cost (${selectedCurrency})`;
        thPaid.innerText = `Paid Amount (${selectedCurrency})`;
        thBalance.innerText = `Balance Due (${selectedCurrency})`;

        document.querySelectorAll('.invoice-row').forEach(row => {
            const totalEl = row.querySelector('.td-total');
            const paidEl = row.querySelector('.td-paid');
            const balanceEl = row.querySelector('.td-balance');

            const totalUsd = parseFloat(totalEl.getAttribute('data-usd'));
            const paidUsd = parseFloat(paidEl.getAttribute('data-usd'));
            const balanceUsd = parseFloat(balanceEl.getAttribute('data-usd'));

            const convTotal = totalUsd * rate;
            const convPaid = paidUsd * rate;
            const convBalance = balanceUsd * rate;

            totalEl.innerText = symbol + convTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            paidEl.innerText = symbol + convPaid.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            balanceEl.innerText = symbol + convBalance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        });
    });

    // 2. MFUMO WA LIVE SEARCH BAR + "NO RELATED DATA FOUND"
    function filterInvoicesTable() {
        const input = document.getElementById('invoiceSearchInput');
        const filter = input.value.toLowerCase().trim();
        const rows = document.querySelectorAll('.invoice-row');
        const noMatchRow = document.getElementById('noMatchingSearchRow');
        
        let visibleRowsCount = 0;

        rows.forEach(row => {
            // Tafuta ndani ya jina la mteja na tarehe ya safari
            const clientName = row.querySelector('.cust-name').innerText.toLowerCase();
            const tourDate = row.querySelector('.tour-date-cell').innerText.toLowerCase();

            if (clientName.includes(filter) || tourDate.includes(filter)) {
                row.style.display = ""; // Onyesha mstari ukiendana
                visibleRowsCount++;
            } else {
                row.style.display = "none"; // Ficha mstari usioendana
            }
        });

        // Kama hakuna kabisa mstari uliofuzu kwenye utafutaji, onyesha "No related data found"
        if (visibleRowsCount === 0 && rows.length > 0) {
            noMatchRow.style.display = "";
        } else {
            noMatchRow.style.display = "none";
        }
    }
</script>
</body>
</html>