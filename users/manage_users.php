<?php
include '../includes/auth.php';
include '../includes/config.php';

if($_SESSION['role'] != 'admin'){
    die("Access Denied");
}

$users = mysqli_query($conn,"
SELECT * FROM users
ORDER BY id DESC
");
?>

<link rel="stylesheet" href="../assets/css/finance.css">

<div class="container">

<h2>Manage Users</h2>

<a href="add_user.php">
Add User
</a>

<br><br>

<table>

<tr>
<th>ID</th>
<th>Name</th>
<th>Username</th>
<th>Role</th>
<th>Action</th>
</tr>

<?php while($row = mysqli_fetch_assoc($users)){ ?>

<tr>

<td><?php echo $row['id']; ?></td>

<td><?php echo $row['name']; ?></td>

<td><?php echo $row['username']; ?></td>

<td><?php echo $row['role']; ?></td>

<td>

<a href="edit_user.php?id=<?php echo $row['id']; ?>">
Edit
</a>

|

<a href="delete_user.php?id=<?php echo $row['id']; ?>"
onclick="return confirm('Delete user?')">

Delete

</a>

</td>

</tr>

<?php } ?>
</table>

</div>