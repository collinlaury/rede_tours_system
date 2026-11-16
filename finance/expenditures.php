<?php
include '../includes/auth.php';
include '../includes/config.php';

/* ADD EXPENSE */
if(isset($_POST['add_expense'])){
    include '../includes/functions.php';
updateBalance($conn);

    $title = $_POST['title'];
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $currency = $_POST['currency'];
    $desc = $_POST['description'];
    $date = $_POST['date'];

    $user_id = $_SESSION['user_id'];

    mysqli_query($conn,
    "INSERT INTO expenditures
    (title, category, amount, currency, description, expense_date, created_by)
    VALUES
    ('$title','$category','$amount','$currency','$desc','$date','$user_id')");

    header("Location: expenditures.php");
    exit();
}

/* DATA */
$data = mysqli_query($conn, "SELECT * FROM expenditures ORDER BY id DESC");
?>

<h2>Expenditures</h2>

<link rel="stylesheet" href="../assets/css/debts.css">

<form method="POST">

<input type="text" name="title" placeholder="Expense Title" required><br><br>

<input type="text" name="category" placeholder="Category" required><br><br>

<input type="number" name="amount" placeholder="Amount" required><br><br>

<select name="currency">
    <option value="USD">USD</option>
    <option value="TZS">TZS</option>
</select><br><br>

<textarea name="description"></textarea><br><br>

<input type="date" name="date" required><br><br>

<button type="submit" name="add_expense">Add Expense</button>

</form>

<hr>

<table border="1" width="100%">

<tr>
<th>ID</th>
<th>Title</th>
<th>Category</th>
<th>Amount</th>
<th>Currency</th>
<th>Date</th>
</tr>

<?php while($row = mysqli_fetch_assoc($data)){ ?>

<tr>
<td><?php echo $row['id']; ?></td>
<td><?php echo $row['title']; ?></td>
<td><?php echo $row['category']; ?></td>
<td><?php echo $row['amount']; ?></td>
<td><?php echo $row['currency']; ?></td>
<td><?php echo $row['expense_date']; ?></td>
</tr>

<?php } ?>
</table>
