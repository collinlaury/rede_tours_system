<?php
// Jumuisha ulinzi, muunganisho wa database na sidebar yetu mpya
include '../includes/auth.php';
include '../includes/config.php';

// Zuia mtu asiyekuwa admin kufungua hii page
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// --- 1. SET DEFAULT CURRENCY TO USD ---
$selected_currency = isset($_GET['currency']) ? mysqli_real_escape_string($conn, $_GET['currency']) : 'USD';

// --- 2. VUTA LIVE EXCHANGE RATES (Kutokea USD kwenda sarafu zingine) ---
$rates = [
    'USD' => 1.0,
    'TZS' => 2610.0, // Fallback
    'EUR' => 0.92,
    'GBP' => 0.78
];

$api_url = "https://open.er-api.com/v6/latest/USD";
$response = @file_get_contents($api_url);

if ($response !== false) {
    $api_data = json_decode($response, true);
    if (isset($api_data['rates'])) {
        $rates['TZS'] = $api_data['rates']['TZS'];
        $rates['EUR'] = $api_data['rates']['EUR'];
        $rates['GBP'] = $api_data['rates']['GBP'];
    }
}

// --- 3. KUREKEBISHA MAHESABU YA TOTAL INCOME (ROW BY ROW CONVERSION) ---
$total_income = 0;
$income_q = mysqli_query($conn, "SELECT amount, currency FROM income");

while ($row = mysqli_fetch_assoc($income_q)) {
    $amount = $row['amount'];
    $currency = $row['currency'];
    
    // Hatua ya kwanza: Badilisha muamala kuwa USD (Kama haupo kwenye USD)
    $amount_in_usd = 0;
    if ($currency === 'USD') {
        $amount_in_usd = $amount;
    } else {
        // Kama upo kwenye TZS/EUR/GBP, tunagawa kwa rate yake ili upate thamani ya USD
        $row_rate = $rates[$currency] ?? 1.0;
        $amount_in_usd = $amount / $row_rate;
    }
    
    // Hatua ya pili: Badilisha kutoka USD kwenda kwenye sarafu aliyochagua mtumiaji kwenye Dashboard
    $total_income += $amount_in_usd * $rates[$selected_currency];
}


// --- 4. KUREKEBISHA MAHESABU YA ACTIVE DEBTS (ROW BY ROW CONVERSION) ---
$total_debts = 0;
$debts_q = mysqli_query($conn, "SELECT amount, currency FROM debts WHERE status='unpaid'");

while ($row = mysqli_fetch_assoc($debts_q)) {
    $amount = $row['amount'];
    $currency = $row['currency'];
    
    $amount_in_usd = ($currency === 'USD') ? $amount : ($amount / ($rates[$currency] ?? 1.0));
    $total_debts += $amount_in_usd * $rates[$selected_currency];
}


// c) Idadi ya Watumiaji
$users_q = mysqli_query($conn, "SELECT COUNT(id) AS total_users FROM users");
$users_data = mysqli_fetch_assoc($users_q);
$total_users = $users_data['total_users'] ?? 0;


// --- 5. DATA ZA CHART.JS (Zinafuata muundo mpya sahihi) ---
$miezi = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];

// Hapa tunaweka mapato ya miezi ya nyuma, mwezi wa mwisho (Jun) unachukua hesabu yetu mpya sahihi
$base_mapato_usd = [500, 800, 1200, 2000, 950, ($total_income / $rates[$selected_currency])];

$mapato_kwa_mwezi = array_map(function($usd_amount) use ($rates, $selected_currency) {
    return $usd_amount * $rates[$selected_currency];
}, $base_mapato_usd);

// Jumuisha Sidebar na Header
include '../includes/sidebar.php'; 
?>

<div class="main-content">
    
    <div class="dashboard-top-bar">
        <div class="welcome-header">
            <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['fullname']); ?>!</h1>
            <p>Here is what's happening with REDE TOURS financial health today.</p>
        </div>
        
        <div class="currency-filter-box">
            <label for="currencyFilter"><i class="bi bi-cash-stack"></i> Currency:</label>
            <select id="currencyFilter" onchange="changeDashboardCurrency(this.value)">
                <option value="USD" <?php echo $selected_currency === 'USD' ? 'selected' : ''; ?>>USD (Dollar)</option>
                <option value="TZS" <?php echo $selected_currency === 'TZS' ? 'selected' : ''; ?>>TZS (Shilling)</option>
                <option value="EUR" <?php echo $selected_currency === 'EUR' ? 'selected' : ''; ?>>EUR (Euro)</option>
                <option value="GBP" <?php echo $selected_currency === 'GBP' ? 'selected' : ''; ?>>GBP (Pound)</option>
            </select>
        </div>
    </div>

    <div class="cards-grid">
        
        <!-- CARD 1: TOTAL INCOME -->
        <a href="../finance/income.php" class="card-link-wrapper" style="--card-index: 1;">
            <div class="card">
                <div class="card-icon income-icon">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div class="card-info">
                    <h3>Total Income (<?php echo $selected_currency; ?>)</h3>
                    <p><?php echo number_format($total_income, $selected_currency === 'TZS' ? 0 : 2); ?></p>
                </div>
                <div class="card-arrow">
                    <i class="bi bi-chevron-right"></i>
                </div>
            </div>
        </a>

        <!-- CARD 2: ACTIVE DEBTS -->
        <a href="../finance/debts.php" class="card-link-wrapper" style="--card-index: 2;">
            <div class="card">
                <div class="card-icon debts-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="card-info">
                    <h3>Active Debts (<?php echo $selected_currency; ?>)</h3>
                    <p><?php echo number_format($total_debts, $selected_currency === 'TZS' ? 0 : 2); ?></p>
                </div>
                <div class="card-arrow">
                    <i class="bi bi-chevron-right"></i>
                </div>
            </div>
        </a>

        <!-- CARD 3: SYSTEM USERS -->
        <a href="../users/manage_users.php" class="card-link-wrapper" style="--card-index: 3;">
            <div class="card">
                <div class="card-icon users-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="card-info">
                    <h3>System Users</h3>
                    <p><?php echo $total_users; ?> <span class="badge-active">Active</span></p>
                </div>
                <div class="card-arrow">
                    <i class="bi bi-chevron-right"></i>
                </div>
            </div>
        </a>

    </div>

    <div class="chart-container">
        <h3>Financial Performance Overview (<?php echo $selected_currency; ?>) <small style="font-size: 11px; color: rgba(255,255,255,0.3); font-weight: normal;">Live Rates Applied</small></h3>
        <div class="chart-wrapper">
            <canvas id="financialChart"></canvas>
        </div>
    </div>

</div>

<style>
    /* === MTINDO WA KISHUA (ROYAL BLUE AND GOLD THEME) === */
    .dashboard-top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 30px;
    }

    .welcome-header h1 {
        margin: 0; 
        font-size: 28px; 
        color: #ffffff;
        font-weight: 600;
    }

    .welcome-header p {
        margin: 6px 0 0 0; 
        color: rgba(255, 255, 255, 0.6); 
        font-size: 14px;
    }

    .currency-filter-box {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        padding: 6px 14px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
        backdrop-filter: blur(10px);
    }

    .currency-filter-box label {
        color: #CFA58C;
        font-size: 13px;
        font-weight: 500;
    }

    .currency-filter-box select {
        background: #0a1947;
        color: #ffffff;
        border: 1px solid rgba(207, 165, 140, 0.25);
        padding: 5px 10px;
        border-radius: 6px;
        outline: none;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        transition: border-color 0.3s;
    }

    .currency-filter-box select:focus {
        border-color: #CFA58C;
    }

    .cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .card-link-wrapper {
        text-decoration: none !important;
        color: inherit !important;
        display: block;
        opacity: 0;
        transform: translateY(15px);
        animation: cardFadeIn 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
        animation-delay: calc(var(--card-index) * 0.12s);
    }

    .card {
        background: rgba(255, 255, 255, 0.03) !important;
        border: 1px solid rgba(255, 255, 255, 0.06) !important;
        border-radius: 14px !important;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 18px;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
        position: relative;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .card:hover {
        transform: translateY(-4px);
        background: rgba(255, 255, 255, 0.06) !important;
        border-color: rgba(207, 165, 140, 0.35) !important;
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
    }

    .card:hover .card-arrow {
        transform: translateX(4px);
        color: #CFA58C;
        opacity: 1;
    }

    .card-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }

    .income-icon { background: rgba(46, 204, 113, 0.12) !important; color: #2ecc71 !important; }
    .debts-icon { background: rgba(231, 76, 60, 0.12) !important; color: #e74c3c !important; }
    .users-icon { background: rgba(207, 165, 140, 0.12) !important; color: #CFA58C !important; }

    .card-info { flex-grow: 1; }
    .card-info h3 {
        margin: 0;
        font-size: 12px;
        color: rgba(255, 255, 255, 0.45);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .card-info p {
        margin: 4px 0 0 0;
        font-size: 22px;
        font-weight: 700;
        color: #ffffff;
    }

    .badge-active {
        font-size: 11px;
        background: rgba(207, 165, 140, 0.15);
        color: #CFA58C;
        padding: 1px 6px;
        border-radius: 4px;
        margin-left: 4px;
    }

    .card-arrow {
        font-size: 16px;
        color: rgba(255, 255, 255, 0.25);
        transition: all 0.2s ease;
    }

    .chart-container {
        background: rgba(255, 255, 255, 0.03) !important;
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }

    .chart-container h3 {
        margin: 0 0 20px 0;
        font-size: 18px;
        color: #CFA58C;
        font-weight: 500;
    }

    .chart-wrapper {
        position: relative;
        height: 340px;
        width: 100%;
    }

    @keyframes cardFadeIn {
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
    function changeDashboardCurrency(currency) {
        window.location.href = 'admin.php?currency=' + currency;
    }

    // --- CHART.JS CONFIGURATION ---
    const ctx = document.getElementById('financialChart').getContext('2d');
    const selectedCurrencyLabel = '<?php echo $selected_currency; ?>';
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($miezi); ?>,
            datasets: [{
                label: 'Monthly Revenue (' + selectedCurrencyLabel + ')',
                data: <?php echo json_encode($mapato_kwa_mwezi); ?>,
                borderColor: '#CFA58C', 
                backgroundColor: 'rgba(207, 165, 140, 0.06)', 
                borderWidth: 3,
                tension: 0.4, 
                fill: true,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#CFA58C',
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        color: '#ffffff',
                        font: { family: 'Segoe UI', size: 13 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let value = context.raw;
                            return 'Revenue: ' + Number(value).toLocaleString() + ' ' + selectedCurrencyLabel;
                        }
                    }
                }
            },
            scales: {
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.03)' },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.5)',
                        font: { family: 'Segoe UI' },
                        maxTicksLimit: 6,
                        callback: function(value) {
                            if (value >= 1000000) {
                                return (value / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
                            }
                            if (value >= 1000) {
                                return (value / 1000).toFixed(0) + 'K';
                            }
                            return value;
                        }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.5)',
                        font: { family: 'Segoe UI' }
                    }
                }
            }
        }
    });
</script>

</body>
</html>