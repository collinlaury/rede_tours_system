<?php
session_start();
include 'includes/config.php';

$error = "";

if(isset($_POST['login'])) {

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = mysqli_query($conn,
    "SELECT * FROM users WHERE username='$username' LIMIT 1");

    if(mysqli_num_rows($query) > 0){

        $user = mysqli_fetch_assoc($query);

       if($password == "admin123"){
        

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];

            header("Location: dashboard/dashboard.php");
            exit();

        } else {
            $error = "Invalid password!";
        }

    } else {
        $error = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>REDE TOURS LOGIN</title>
<link rel="stylesheet"
href="assets/css/login.css">
</head>

<body>

<div class="login-container">

<div class="logo">
REDE TOURS
</div>

<p class="subtitle">
Financial Management System
</p>

<form method="POST">

<input
type="text"
name="username"
placeholder="Enter Username"
class="input-box"
required>

<input
type="password"
name="password"
placeholder="Enter Password"
class="input-box"
required>

<button
type="submit"
name="login"
class="login-btn">
Login
</button>

</form>

<p class="error">
<?php echo $error; ?>
</p>

</div>

</body>
</html>