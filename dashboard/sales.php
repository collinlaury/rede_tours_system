<?php
// Jumuisha ulinzi, muunganisho wa database na sidebar yetu mpya
include '../includes/auth.php';
include '../includes/config.php';

// --- ULINZI: Ruhusu akaunti zenye jukumu la Sales tu kufungua ukurasa huu ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sales') {
    header("Location: ../login.php");
    exit();
}

// ID ya aliyelogin (Inaweza kutumika sehemu nyingine ikihitajika)
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0; 

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

// Kiwango cha kubadili TZS kwenda USD kwa ajili ya hesabu za ndani ya DB
$tzs_to_usd_rate = 1 / $rates['TZS'];

// --- 1. KUVUTA DATA ZA MUHTASARI LIVE KUTOKA KWENYE BOOKINGS (KAMPUNI NZIMA) ---

// a) Idadi ya Bookings Zote zilizopo kwenye mfumo wa kampuni
$bookings_q = mysqli_query($conn, "SELECT COUNT(id) AS total_bookings FROM bookings");
$bookings_data = mysqli_fetch_assoc($bookings_q);
$total_bookings = $bookings_data['total_bookings'] ?? 0;

// b) Mauzo yaliyokamilika ya Kampuni (Paid Deposit) - Convert to USD Base
$sales_tzs_q = mysqli_query($conn, "SELECT SUM(deposit) AS total_tzs FROM bookings WHERE currency='TZS'");
$sales_tzs_data = mysqli_fetch_assoc($sales_tzs_q);
$total_sales_tzs = $sales_tzs_data['total_tzs'] ?? 0;

$sales_usd_q = mysqli_query($conn, "SELECT SUM(deposit) AS total_usd FROM bookings WHERE currency='USD'");
$sales_usd_data = mysqli_fetch_assoc($sales_usd_q);
$total_sales_usd = $sales_usd_data['total_usd'] ?? 0;

// Jumla Kuu ya Kampuni kwa USD
$total_sales_in_usd = $total_sales_usd + ($total_sales_tzs * $tzs_to_usd_rate);

// c) Idadi ya Safari zote zenye madeni/malipo yaliyobaki (Kampuni Nzima)
$invoices_q = mysqli_query($conn, "SELECT COUNT(id) AS total_pending FROM bookings WHERE full_deposit > deposit");
$invoices_data = mysqli_fetch_assoc($invoices_q);
$total_invoices = $invoices_data['total_pending'] ?? 0;


// --- 2. DATA ZA CHART.JS (Ukuaji wa Mauzo ya Kampuni Miezi 4 Iliyopita - Kwa USD Base) ---
$miezi_mauzo = [];
$mauzo_data_usd = []; 

for ($i = 3; $i >= 0; $i--) {
    $month_name = date('M', strtotime("-$i months"));
    $month_num = date('m', strtotime("-$i months"));
    $year_num = date('Y', strtotime("-$i months"));
    
    $miezi_mauzo[] = $month_name;
    
    // Kuvuta mauzo ya mwezi husika ya kampuni (TZS)
    $m_tzs_q = mysqli_query($conn, "SELECT SUM(deposit) AS m_tzs FROM bookings WHERE currency='TZS' AND MONTH(tour_date)='$month_num' AND YEAR(tour_date)='$year_num'");
    $m_tzs_data = mysqli_fetch_assoc($m_tzs_q);
    $m_tzs = $m_tzs_data['m_tzs'] ?? 0;
    
    // Kuvuta mauzo ya mwezi husika ya kampuni (USD)
    $m_usd_q = mysqli_query($conn, "SELECT SUM(deposit) AS m_usd FROM bookings WHERE currency='USD' AND MONTH(tour_date)='$month_num' AND YEAR(tour_date)='$year_num'");
    $m_usd_data = mysqli_fetch_assoc($m_usd_q);
    $m_usd = $m_usd_data['m_usd'] ?? 0;
    
    // Jumla ya mwezi husika kwa thamani ya USD
    $mauzo_data_usd[] = $m_usd + ($m_tzs * $tzs_to_usd_rate);
}

// Jumuisha Sidebar na Header
include '../includes/sidebar.php'; 
?>

<div class="main-content">
    
    <div class="dashboard-header-container">
        <div class="dashboard-welcome">
            <h1>Sales & Bookings Dashboard</h1>
            <p>Hello <span><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Sales Agent'); ?></span>, track corporate targets and safari invoices here.</p>
        </div>
        
        <div class="currency-selector-box">
            <label for="currencySwitcher"><i class="bi bi-globe2"></i> Currency:</label>
            <select id="currencySwitcher" class="currency-select">
                <option value="USD" selected>USD ($)</option>
                <option value="TZS">TZS (Sh)</option>
                <option value="EUR">EUR (€)</option>
                <option value="GBP">GBP (£)</option>
            </select>
        </div>
    </div>

    <div class="cards-grid">
        
        <!-- LINK YA TOTAL BOOKINGS -->
        <a href="../finance/booking.php" class="card-link">
            <div class="card">
                <div class="card-icon" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
                <div class="card-info">
                    <h3>Total Bookings</h3>
                    <p><?php echo $total_bookings; ?> Trips</p>
                </div>
            </div>
        </a>

        <!-- LINK YA TOTAL REVENUE (Marekebisho yamefanyika hapa kwenda income.php) -->
        <a href="../finance/income.php" class="card-link">
            <div class="card">
                <div class="card-icon" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="card-info">
                    <h3 id="revenue-card-title">Total Revenue (USD)</h3>
                    <p id="dynamic-revenue" data-usd="<?php echo $total_sales_in_usd; ?>">
                        $<?php echo number_format($total_sales_in_usd, 2); ?>
                    </p>
                </div>
            </div>
        </a>

        <!-- LINK YA PENDING INVOICES (Marekebisho yamefanyika hapa kwenda report_booking.php) -->
        <a href="../finance/report_booking.php" class="card-link">
            <div class="card">
                <div class="card-icon" style="background: rgba(241, 196, 15, 0.1); color: #f1c40f;">
                    <i class="bi bi-file-earmark-text-fill"></i>
                </div>
                <div class="card-info">
                    <h3>Pending Invoices</h3>
                    <p><?php echo $total_invoices; ?> Invoices</p>
                </div>
            </div>
        </a>

    </div>

    <div class="chart-box">
        <h3 id="chart-title">Company Sales Performance (USD)</h3>
        <div class="chart-wrapper">
            <canvas id="salesBarChart"></canvas>
        </div>
    </div>

</div>

<style>
    .main-content {
        padding: 40px 30px 30px 30px;
        box-sizing: border-box;
    }

    .dashboard-header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 25px;
        margin-bottom: 35px;
        gap: 20px;
    }

    .dashboard-welcome h1 {
        margin: 0; 
        font-size: 28px; 
        color: #ffffff;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .dashboard-welcome p {
        margin: 8px 0 0 0; 
        color: rgba(255,255,255,0.6); 
        font-size: 15px;
    }

    .dashboard-welcome p span {
        color: #CFA58C;
        font-weight: 600;
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
        transition: border-color 0.3s ease;
    }

    .currency-select:focus { border-color: #CFA58C; }

    .cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px;
        margin-bottom: 35px;
    }

    .card-link {
        text-decoration: none;
        display: block;
        color: inherit;
    }

    .card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 14px;
        padding: 24px;
        display: flex;
        align-items: center;
        gap: 20px;
        backdrop-filter: blur(10px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1), box-shadow 0.3s ease, border-color 0.3s ease;
    }

    .card-link:hover .card {
        transform: translateY(-6px) scale(1.03);
        background: rgba(255, 255, 255, 0.08);
        box-shadow: 0 15px 30px rgba(0,0,0,0.3);
        border-color: rgba(207, 165, 140, 0.4);
    }

    .card-icon {
        width: 55px;
        height: 55px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        transition: transform 0.3s ease;
    }

    .card-link:hover .card-icon { transform: scale(1.15) rotate(3deg); }

    .card-info h3 {
        margin: 0;
        font-size: 13px;
        color: rgba(255, 255, 255, 0.5);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }

    .card-info p {
        margin: 6px 0 0 0;
        font-size: 24px;
        font-weight: 700;
        color: #ffffff;
    }

    .chart-box {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }

    .chart-box h3 {
        margin: 0 0 20px 0;
        font-size: 18px;
        color: #CFA58C;
        font-weight: 600;
    }

    .chart-wrapper {
        position: relative;
        width: 100%;
        height: 320px;
    }

    @media (max-width: 768px) {
        .dashboard-header-container {
            flex-direction: column;
            align-items: center;
            padding-top: 50px;
            margin-bottom: 25px;
        }
        .main-content { padding: 40px 15px 15px 15px; }
        .dashboard-welcome { text-align: center; }
        .dashboard-welcome h1 { font-size: 23px; }
        .cards-grid { grid-template-columns: 1fr; gap: 18px; }
        .chart-wrapper { height: 260px; }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const exchangeRates = <?php echo json_encode($rates); ?>;
    const originalSalesDataUSD = <?php echo json_encode($mauzo_data_usd); ?>;
    const monthsLabels = <?php echo json_encode($miezi_mauzo); ?>;

    const currencySymbols = {
        'USD': '$',
        'TZS': 'TSh ',
        'EUR': '€',
        'GBP': '£'
    };

    // 1. CHART.JS INITIALIZATION
    const ctx = document.getElementById('salesBarChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: monthsLabels,
            datasets: [{
                label: 'Monthly Bookings Revenue',
                data: [...originalSalesDataUSD], 
                backgroundColor: 'rgba(207, 165, 140, 0.6)',
                borderColor: '#CFA58C',
                borderWidth: 2,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, 
            plugins: {
                legend: {
                    labels: { color: '#ffffff', font: { size: 12 } }
                }
            },
            scales: {
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: 'rgba(255, 255, 255, 0.6)' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: 'rgba(255, 255, 255, 0.6)' }
                }
            }
        }
    });

    // 2. LIVE CURRENCY SWITCHER LOGIC
    const currencySwitcher = document.getElementById('currencySwitcher');
    const dynamicRevenueEl = document.getElementById('dynamic-revenue');
    const revenueCardTitle = document.getElementById('revenue-card-title');
    const chartTitle = document.getElementById('chart-title');

    currencySwitcher.addEventListener('change', function() {
        const selectedCurrency = this.value;
        const rate = exchangeRates[selectedCurrency];
        const symbol = currencySymbols[selectedCurrency];

        // Convert Total Revenue Card
        const baseUsdValue = parseFloat(dynamicRevenueEl.getAttribute('data-usd'));
        const convertedRevenue = baseUsdValue * rate;
        
        dynamicRevenueEl.innerHTML = symbol + convertedRevenue.toLocaleString('en-US', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
        revenueCardTitle.innerText = `Total Revenue (${selectedCurrency})`;
        chartTitle.innerText = `Company Sales Performance (${selectedCurrency})`;

        // Dynamic Graph Update
        const updatedChartData = originalSalesDataUSD.map(usdVal => usdVal * rate);
        salesChart.data.datasets[0].data = updatedChartData;
        salesChart.data.datasets[0].label = `Monthly Bookings Revenue (${selectedCurrency})`;
        salesChart.update(); 
    });
</script>
</body>
</html>