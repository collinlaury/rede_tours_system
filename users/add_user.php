<?php
include '../includes/auth.php';
include '../includes/config.php';

if($_SESSION['role'] != 'admin'){
    die("Access Denied");
}

if(isset($_POST['save'])){

    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    mysqli_query($conn,"
    INSERT INTO users(name,username,password,role)
    VALUES('$name','$username','$password','$role')
    ");

    header("Location: manage_users.php");
    exit();
}
?>

<link rel="stylesheet" href="../assets/css/finance.css">

<div class="container">

<h2>Add New User</h2>

<form method="POST">

<input type="text" name="name" placeholder="Full Name" required>

<input type="text" name="username" placeholder="Username" required>

<input type="password" name="password" placeholder="Password" required>

<select name="role">

<option value="accountant">Accountant</option>

<option value="admin">Admin</option>

</select>

<button type="submit" name="save">
Create User
</button>

</form>

</div>