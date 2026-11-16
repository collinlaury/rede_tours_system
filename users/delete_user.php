 <?php

include '../includes/auth.php';
include '../includes/config.php';

if($_SESSION['role'] != 'admin'){
    die("Access Denied");
}

$id = $_GET['id'];

mysqli_query($conn,"
DELETE FROM users
WHERE id='$id'
");

header("Location: manage_users.php");
exit();