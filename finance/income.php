<?php
include '../includes/auth.php';
include '../includes/config.php';

/* ADD INCOME */
if(isset($_POST['add_income'])){
    include '../includes/functions.php';
updateBalance($conn);

    $title = $_POST['title'];
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $currency = $_POST['currency'];
    $method = $_POST['method'];
    $desc = $_POST['description'];
    $date = $_POST['date'];

    $user_id = $_SESSION['user_id'];

    mysqli_query($conn,
    "INSERT INTO income
    (title, category, amount, currency, payment_method, description, date_received, created_by)
    VALUES
    ('$title','$category','$amount','$currency','$method','$desc','$date','$user_id')");

    header("Location: income.php");
    exit();
}

/* GET DATA */
$data = mysqli_query($conn, "SELECT * FROM income ORDER BY id DESC");
?>

<h2>Income Management</h2>

<link rel="stylesheet" href="../assets/css/debts.css">

<!-- FORM -->
<form method="POST">

<input type="text" name="title" placeholder="Income Title" required><br><br>

<input type="text" name="category" placeholder="Category" required><br><br>

<input type="number" name="amount" placeholder="Amount" required><br><br>

<select name="currency">
    <option value="USD">USD</option>
    <option value="TZS">TZS</option>
</select><br><br>

<input type="text" name="method" placeholder="Payment Method"><br><br>

<textarea name="description" placeholder="Description"></textarea><br><br>

<input type="date" name="date" required><br><br>

<button type="submit" name="add_income">Add Income</button>

</form>

<hr>

<!-- TABLE -->
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
    <td><?php echo $row['date_received']; ?></td>
</tr>

<?php } ?>

</table>
