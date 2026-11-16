<?php
include '../includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';
checkOverdueDebts($conn);

/* MARK AS PAID */
if(isset($_GET['pay_id'])){
    include '../includes/functions.php';
updateBalance($conn);

    $id = $_GET['pay_id'];
    $user = $_SESSION['user_id'];

    mysqli_query($conn,
    "UPDATE debts 
    SET status='paid', paid_date=CURDATE(), paid_by='$user'
    WHERE id='$id'");

    header("Location: debts.php");
    exit();
}

/* GET DATA */
$debts = mysqli_query($conn,
"SELECT * FROM debts ORDER BY id DESC");
/* ADD DEBT */
if(isset($_POST['add_debt'])){

    $title = $_POST['title'];
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $currency = $_POST['currency'];
    $desc = $_POST['description'];
    $due_date = $_POST['due_date'];

    mysqli_query($conn,
    "INSERT INTO debts
    (title, category, amount, currency, description, due_date, status)
    VALUES
    ('$title','$category','$amount','$currency','$desc','$due_date','unpaid')");

    header("Location: debts.php");
    exit();
}
?>

<h2>Debts Management</h2>

<h4>Add New Debt</h4>

<link rel="stylesheet" href="../assets/css/debts.css">

<div class="container">

<form method="POST">
<input type="text" name="title" placeholder="Debt Title" required><br><br>

<input type="text" name="category" placeholder="Category" required><br><br>

<input type="number" name="amount" placeholder="Amount" required><br><br>

<select name="currency">
    <option value="USD">USD</option>
    <option value="TZS">TZS</option>
</select><br><br>

<textarea name="description" placeholder="Description"></textarea><br><br>

<input type="date" name="due_date" required><br><br>

<button type="submit" name="add_debt">
Add Debt
</button>

</form>

<hr>

<table border="1" width="100%">

<tr>
<th>ID</th>
<th>Title</th>
<th>Amount</th>
<th>Currency</th>
<th>Status</th>
<th>Due Date</th>
<th>Action</th>
</tr>

<?php while($row = mysqli_fetch_assoc($debts)){ ?>

<tr>
    <td><?php echo $row['id']; ?></td>
    <td><?php echo $row['title']; ?></td>
    <td><?php echo $row['amount']; ?></td>
    <td><?php echo $row['currency']; ?></td>
   <td>
<?php if($row['status']=='paid'){ ?>
    <span style="color:green;">Paid ✔</span>

<?php } elseif($row['status']=='overdue'){ ?>
    <span style="color:red;">Overdue ⚠</span>

<?php } else { ?>
    <span style="color:orange;">Unpaid ❌</span>
<?php } ?>
</td>
    <td><?php echo $row['due_date']; ?></td>

    <td>

    <?php if($row['status'] == 'unpaid'){ ?>

        <a href="debts.php?pay_id=<?php echo $row['id']; ?>"
        onclick="return confirm('Mark as PAID?')">
        ✔ Mark Paid
        </a>

    <?php } else { ?>
        ✔ Paid
    <?php } ?>

    </td>

</tr>

<?php } ?>

</table>
</div>
<script>
function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("closed");
}
