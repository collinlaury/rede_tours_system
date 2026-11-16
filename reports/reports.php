<?php
include '../includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';
checkOverdueDebts($conn);
/* TOTAL INCOME */
$income = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT 
SUM(CASE WHEN currency='USD' THEN amount ELSE 0 END) as usd,
SUM(CASE WHEN currency='TZS' THEN amount ELSE 0 END) as tzs
FROM income"));

/* TOTAL EXPENSES */
$expense = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT 
SUM(CASE WHEN currency='USD' THEN amount ELSE 0 END) as usd,
SUM(CASE WHEN currency='TZS' THEN amount ELSE 0 END) as tzs
FROM expenditures"));

/* DEBTS */
$debts = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) as total FROM debts WHERE status='unpaid'"));

/* BALANCE */
$balance_usd = $income['usd'] - $expense['usd'];
$balance_tzs = $income['tzs'] - $expense['tzs'];
?>

<a href="financial_report.php" target="_blank"
style="background:#CFA58C;padding:10px;
color:#000;border-radius:10px;text-decoration:none;">
Download PDF Report
</a>

<link rel="stylesheet" href="../assets/css/dashboard.css">

<div class="main">

<h1>Financial Reports</h1>

<div class="cards">

<div class="card income">
<h3>Total Income</h3>
<p>$ <?php echo $income['usd']; ?> / TZS <?php echo $income['tzs']; ?></p>
</div>

<div class="card expense">
<h3>Total Expenses</h3>
<p>$ <?php echo $expense['usd']; ?> / TZS <?php echo $expense['tzs']; ?></p>
</div>

<div class="card balance">
<h3>Net Balance</h3>
<p>$ <?php echo $balance_usd; ?> / TZS <?php echo $balance_tzs; ?></p>
</div>

<div class="card debts">
<h3>Unpaid Debts</h3>
<p><?php echo $debts['total']; ?></p>
</div>

</div>

</div>