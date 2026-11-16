<?php
include '../includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';
checkOverdueDebts($conn);

$overdue = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) as total FROM debts WHERE status='overdue'"))['total'];

$monthly_income = mysqli_query($conn,
"SELECT MONTH(date_received) as month,
SUM(amount) as total
FROM income
GROUP BY MONTH(date_received)");

$monthly_expense = mysqli_query($conn,
"SELECT MONTH(expense_date) as month,
SUM(amount) as total
FROM expenditures
GROUP BY MONTH(expense_date)");

$income = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT 
SUM(CASE WHEN currency='USD' THEN amount ELSE 0 END) as usd,
SUM(CASE WHEN currency='TZS' THEN amount ELSE 0 END) as tzs
FROM income
"));

$expense = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT 
SUM(CASE WHEN currency='USD' THEN amount ELSE 0 END) as usd,
SUM(CASE WHEN currency='TZS' THEN amount ELSE 0 END) as tzs
FROM expenditures
"));

$balance_usd = $income['usd'] - $expense['usd'];
$balance_tzs = $income['tzs'] - $expense['tzs'];

$debts = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) as total FROM debts WHERE status='unpaid'
"))['total'];
?>

<!DOCTYPE html>
<html>
<head>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<title>Dashboard - REDE TOURS</title>

<link rel="stylesheet" href="../assets/css/dashboard.css">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h2>REDE TOURS</h2>

    <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="../finance/income.php"><i class="bi bi-cash"></i> Income</a>
    <a href="../finance/expenditures.php"><i class="bi bi-wallet2"></i> Expenses</a>
    <a href="../finance/debts.php"><i class="bi bi-credit-card"></i> Debts</a>
    <a href="../reports/reports.php"><i class="bi bi-bar-chart"></i> Reports</a>
    <a href="../ai/assistant.php">
    🤖 AI Assistant</a>
    <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    <a href="../users/manage_users.php">
👥 Users
</a>

</div>

<!-- MAIN CONTENT -->
<div class="main">

    <h1>Welcome, <?php echo $_SESSION['fullname']; ?></h1>

  <div class="cards">

    <div class="card income">
    <h3>Total Income</h3>
    <p>
        $ <?php echo $income['usd'] ?? 0; ?> /
        TZS <?php echo $income['tzs'] ?? 0; ?>
    </p>
</div>

    <div class="card expense">
    <h3>Total Expenses</h3>
    <p>
        $ <?php echo $expense['usd'] ?? 0; ?> /
        TZS <?php echo $expense['tzs'] ?? 0; ?>
    </p>
</div>

    <div class="card balance">
    <h3>Balance</h3>
    <p>
        $ <?php echo $balance_usd ?? 0; ?> /
        TZS <?php echo $balance_tzs ?? 0; ?>
    </p>
</div>

    <div class="card debts">
    <h3>Unpaid Debts</h3>
    <p>
        <?php echo $debts; ?>
    </p>
</div>

 <div class="card">
    <h3>Overdue Debts</h3>
    <p><?php echo $overdue; ?></p>
</div>

  </div>

  <div style="margin-top:40px; background:rgba(255,255,255,0.1);
padding:20px; border-radius:20px;">

<canvas id="financeChart"></canvas>

</div>

</div>
<script>

const ctx = document.getElementById('financeChart');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Income', 'Expenses'],
        datasets: [{
            label: 'Financial Overview',
            data: [
                <?php echo $income['usd'] ?? 0; ?>,
                <?php echo $expense['usd'] ?? 0; ?>
            ],
            backgroundColor: [
                '#CFA58C',
                '#ff6b6b'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                labels: {
                    color: 'white'
                }
            }
        },
        scales: {
            x: {
                ticks: { color: 'white' }
            },
            y: {
                ticks: { color: 'white' }
            }
        }
    }
});

</script>

</body>
</html>