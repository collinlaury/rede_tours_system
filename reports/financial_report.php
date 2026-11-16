<?php
include '../includes/auth.php';
include '../includes/config.php';

$income_query = mysqli_query($conn,"
SELECT * FROM income
ORDER BY id ASC
");

$total_income_usd = 0;

while($row = mysqli_fetch_assoc($income_query)){
    if($row['currency']=='USD'){
        $total_income_usd += $row['amount'];
    }
}
?>
<div class="report-container">

    <div class="header">
        <h1>REDE TOURS</h1>
        <h1>FINANCIAL REPORT</h1>
        <p>Expense & Balance Summary</p>
    </div>
<div class="section">

<h2>Income Received</h2>

<table>

<tr>
<th>Description</th>
<th>Amount (USD)</th>
</tr>

<?php

$income_query = mysqli_query($conn,"
SELECT * FROM income
ORDER BY id ASC
");

while($row = mysqli_fetch_assoc($income_query)){
?>

<tr>
<td><?php echo $row['description']; ?></td>
<td><?php echo number_format($row['amount']); ?></td>
</tr>

<?php } ?>

<tr class="total-row">
<td>TOTAL RECEIVED</td>
<td><?php echo number_format($total_income_usd); ?> USD</td>
</tr>

</table>

</div>
<?php

$total_expense_usd = 0;
$total_expense_tzs = 0;

$expense_query = mysqli_query($conn,"
SELECT * FROM expenditures
ORDER BY id ASC
");

while($e = mysqli_fetch_assoc($expense_query)){

    if($e['currency']=='USD'){
        $total_expense_usd += $e['amount'];
    }

    if($e['currency']=='TZS'){
        $total_expense_tzs += $e['amount'];
    }
}
?>

<div class="section">

<h2>Expenses</h2>

<table>

<tr>
<th>Amount</th>
<th>Currency</th>
<th>Description</th>
</tr>

<?php

$expense_query = mysqli_query($conn,"
SELECT * FROM expenditures
ORDER BY id ASC
");

while($row = mysqli_fetch_assoc($expense_query)){
?>

<tr>
<td><?php echo number_format($row['amount']); ?></td>
<td><?php echo $row['currency']; ?></td>
<td><?php echo $row['description']; ?></td>
</tr>

<?php } ?>

</table>

</div>
<div class="summary-grid">

<div class="summary-card green">
<h3>Total USD Used</h3>
<p><?php echo number_format($total_expense_usd); ?> USD</p>
</div>

<div class="summary-card red">
<h3>Total TZS Used</h3>
<p><?php echo number_format($total_expense_tzs); ?> TZS</p>
</div>

<div class="summary-card blue">

<h3>Total Income</h3>

<p>
<?php echo number_format($total_income_usd); ?>
USD
</p>

</div>

<div class="summary-card purple">

<h3>Total Expenses</h3>

<p>
<?php echo number_format($total_expense_usd); ?>
USD
</p>

</div>

</div>

<?php

$final_balance =
$total_income_usd -
$total_expense_usd;

?>

<div class="section">

<h2>Final Balance Calculation</h2>

<table>

<tr>
<th>Item</th>
<th>Value</th>
</tr>

<tr>
<td>Total Income</td>
<td>
<?php echo number_format($total_income_usd); ?>
USD
</td>
</tr>

<tr>
<td>Total Expenses</td>
<td>
<?php echo number_format($total_expense_usd); ?>
USD
</td>
</tr>

<tr>

<td>Final Balance</td>

<td>

<?php

echo number_format($final_balance);

?>

USD

</td>

</tr>

<tr class="status-row">

<td>FINAL STATUS</td>

<td>

<?php

if($final_balance >= 0){

echo "CREDIT";

}else{

echo "DEBIT";

}

?>

</td>

</tr>

</table>

</div>

<!DOCTYPE html>
<html>
<head>
    <button onclick="window.print()" class="print-btn">
    Download PDF
</button>
    <title>REDE TOURS FINANCIAL REPORT</title>
    <link rel="stylesheet" href="../assets/css/reports.css">
</head>
<body>


    <div class="footer">
REDE TOURS FINANCIAL MANAGEMENT REPORT
</div>

</div>

</body>
</html>