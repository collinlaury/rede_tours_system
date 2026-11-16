<?php
include '../includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';
checkOverdueDebts($conn);

/* TOTALS */
$income = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT SUM(amount) as total FROM income"))['total'] ?? 0;

$expense = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT SUM(amount) as total FROM expenditures"))['total'] ?? 0;

$debts = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) as total FROM debts WHERE status='unpaid'"))['total'] ?? 0;

$balance = $income - $expense;

/* SIMPLE AI LOGIC */
$advice = "";

if($balance > 0){
    $advice = "Business is PROFITABLE. Keep scaling operations.";
} elseif($balance < 0){
    $advice = "Warning: Expenses are higher than income. Reduce costs.";
} else {
    $advice = "Business is stable but no profit yet.";
}

if($debts > 5){
    $advice .= " High number of unpaid debts detected.";
}
?>

<link rel="stylesheet" href="../assets/css/dashboard.css">

<div class="main">

<h1>AI Financial Assistant 🤖</h1>



<!-- AI RESPONSE -->
<div style="margin-top:10px;
padding:10px;
background:rgba(255,255,255,0.1);
border-radius:20px;
backdrop-filter:blur(10px);">

<h2>AI Insight</h2>
<p style="font-size:18px;">
<?php echo $advice; ?>
</p>

</div>

</div>