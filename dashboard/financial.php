<?php
// Jumuisha ulinzi, muunganisho wa database na sidebar yetu mpya
include '../includes/auth.php';
include '../includes/config.php';

// --- ULINZI: Ruhusu akaunti zenye jukumu la kifedha tu kufungua ukurasa huu ---
if ($_SESSION['role'] !== 'financial' && $_SESSION['role'] !== 'accountant') {
    header("Location: ../login.php");
    exit();
}

// --- 1. KUVUTA DATA ZA MKUTASARI (ACCOUNTING CARDS) ---
// a) Bank & Cash Balance (Mizania iliyopo sasa hivi - Mfano kutoka functions zako)
// Hapa tunaiweka kama stat, unaweza kuivuta kutoka kwenye table yako ya balance
$balance_q = mysqli_query($conn, "SELECT SUM(amount) AS total_balance FROM income WHERE currency='TZS'"); 
$balance_data = mysqli_fetch_assoc($balance_q);
$current_balance = $balance_data['total_balance'] ?? 0;

// b) Jumla ya Income (Mapato yote)
$income_q = mysqli_query($conn, "SELECT SUM(amount) AS total_income FROM income WHERE currency='TZS'");
$income_data = mysqli_fetch_assoc($income_q);
$total_income = $income_data['total_income'] ?? 0;

// c) Madeni Yanayodaiwa (Unpaid Debts)
$debts_q = mysqli_query($conn, "SELECT SUM(amount) AS total_debts FROM debts WHERE status='unpaid' AND currency='TZS'");
$debts_data = mysqli_fetch_assoc($debts_q);
$total_debts = $debts_data['total_debts'] ?? 0;


// --- 2. DATA ZA CHART.JS (Mchanganuo wa Mapato vs Madeni) ---
$chart_labels = ['Total Income', 'Pending Debts'];
$chart_values = [$total_income, $total_debts];

// Jumuisha Sidebar na Header (Zitasoma jina na role ya Mhasibu juu kulia)
include '../includes/sidebar.php'; 
?>

<div class="main-content">
    
    <div style="margin-bottom: 30px;">
        <h1 style="margin: 0; font-size: 26px; color: #ffffff;">Financial Dashboard</h1>
        <p style="margin: 5px 0 0 0; color: rgba(255,255,255,0.6); font-size: 14px;">
            Welcome back, <?php echo htmlspecialchars($_SESSION['fullname']); ?>. Review and manage REDE TOURS accounts.
        </p>
    </div>

    <div class="cards-grid">
        
        <div class="card">
            <div class="card-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                <i class="bi bi-bank"></i>
            </div>
            <div class="card-info">
                <h3>Available Balance (TZS)</h3>
                <p><?php echo number_format($current_balance, 2); ?></p>
            </div>
        </div>

        <div class="card">
            <div class="card-icon" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                <i class="bi bi-cash-coin"></i>
            </div>
            <div class="card-info">
                <h3>Total Income (TZS)</h3>
                <p><?php echo number_format($total_income, 2); ?></p>
            </div>
        </div>

        <div class="card">
            <div class="card-icon" style="background: rgba(230, 126, 34, 0.1); color: #e67e22;">
                <i class="bi bi-credit-card-2-front"></i>
            </div>
            <div class="card-info">
                <h3>Receivable Debts (TZS)</h3>
                <p><?php echo number_format($total_debts, 2); ?></p>
            </div>
        </div>

    </div>

    <div class="chart-box">
        <h3>Income vs. Outstanding Debts Ratio</h3>
        <div style="max-width: 500px; margin: 0 auto;">
            <canvas id="accountingChart"></canvas>
        </div>
    </div>

</div>

<style>
    .cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 20px;
        backdrop-filter: blur(10px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .card-icon {
        width: 55px;
        height: 55px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .card-info h3 {
        margin: 0;
        font-size: 13px;
        color: rgba(255, 255, 255, 0.6);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .card-info p {
        margin: 5px 0 0 0;
        font-size: 22px;
        font-weight: 700;
        color: #ffffff;
    }

    .chart-box {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .chart-box h3 {
        margin: 0 0 20px 0;
        font-size: 18px;
        color: #CFA58C;
        font-weight: 600;
        text-align: center;
    }
</style>

<script>
    const ctx = document.getElementById('accountingChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'doughnut', // Aina ya duara ya kisasa yenye tundu katikati
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($chart_values); ?>,
                backgroundColor: [
                    '#2ecc71', // Rangi ya Income (Kijani)
                    '#e67e22'  // Rangi ya Madeni (Chungwa)
                ],
                borderWidth: 2,
                borderColor: '#0f2b7a' // Rangi inayolingana na background ya kadi
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#ffffff',
                        padding: 20,
                        font: {
                            size: 14
                        }
                    }
                }
            }
        }
    });
</script>

</body>
</html>