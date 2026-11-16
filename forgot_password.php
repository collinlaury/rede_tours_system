<?php
session_start();
include 'includes/config.php';

$error = "";
$success = "";
$step = 1; // Mwanzo tunaanza na hatua ya kuingiza email
$user_email = "";

// 1. MAREKEBISHO YA HATUA YA KWANZA: Kuangalia kama Email ipo
if (isset($_POST['check_email'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    $query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' LIMIT 1");
    
    if (mysqli_num_rows($query) > 0) {
        $step = 2; // Email ipo, mpeleke hatua ya kubadili password
        $user_email = $email; // Tunatunza email kwa muda kwenye fomu ikijisubmit upya
    } else {
        $error = "Email address not found in our system!";
    }
}

// 2. MAREKEBISHO YA HATUA YA PILI: Kuset password mpya ikiwa Hashed
if (isset($_POST['update_password'])) {
    $email = mysqli_real_escape_string($conn, $_POST['hidden_email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match!";
        $step = 2; // Ikiwepo kosa, baki hatua ya pili
        $user_email = $email;
    } else {
        // --- USALAMA: Tunatengeneza BCRYPT Hash mpya hapa ---
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $update_query = "UPDATE users SET password='$hashed_password' WHERE email='$email'";
        
        if (mysqli_query($conn, $update_query)) {
            $success = "Password reset successfully! You can now login.";
            $step = 3; // Hatua ya mafanikio (Onyesha link ya kurudi login)
        } else {
            $error = "Something went wrong. Please try again.";
            $step = 2;
            $user_email = $email;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>REDE TOURS - Reset Password</title>
<link rel="stylesheet" href="assets/css/login.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    /* Inalainisha fomu na kuendana na muundo wako wa login */
    .password-wrapper, .email-wrapper {
        position: relative;
        width: 100%;
        margin-bottom: 15px;
    }

    .password-wrapper .input-box, .email-wrapper .input-box {
        padding-right: 45px; 
        width: 100%;
        box-sizing: border-box;
    }

    .toggle-password, .email-icon {
        position: absolute;
        right: 15px;
        top: 35%;
        color: rgba(255, 255, 255, 0.6);
        font-size: 18px;
    }

    .toggle-password {
        cursor: pointer;
    }

    .toggle-password:hover {
        color: #CFA58C;
    }

    .success-msg {
        color: #2ecc71;
        text-align: center;
        font-weight: bold;
        margin-top: 15px;
    }

    .back-to-login {
        display: block;
        text-align: center;
        margin-top: 20px;
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
        font-size: 14px;
        transition: color 0.3s;
    }

    .back-to-login:hover {
        color: #CFA58C;
        text-decoration: underline;
    }

    /* ----------------------------------------------------
       MOBILE RESPONSIVENESS (Marekebisho kwa ajili ya Simu)
    ------------------------------------------------------- */
    @media (max-width: 576px) {
        body {
            padding: 15px;
        }
        .login-container {
            width: 100% !important;
            max-width: 100%;
            padding: 20px !important;
            box-sizing: border-box;
        }
        .logo {
            font-size: 24px !important;
        }
        .subtitle {
            font-size: 14px !important;
        }
    }
</style>
</head>

<body>

<div class="login-container">

<div class="logo">
REDE TOURS
</div>

<p class="subtitle">
Password Recovery System
</p>

<?php if ($step == 1) { ?>
    <form method="POST">
        <p style="font-size: 14px; text-align: center; color: rgba(255,255,255,0.7); margin-bottom: 20px;">
            Enter your registered email address to reset your password.
        </p>
        
        <div class="email-wrapper">
            <input
            type="email"
            name="email"
            placeholder="Enter Your Email Address"
            class="input-box"
            required>
            <i class="bi bi-envelope email-icon"></i>
        </div>

        <button type="submit" name="check_email" class="login-btn">
            Verify Email
        </button>
    </form>
<?php } ?>

<?php if ($step == 2) { ?>
    <form method="POST">
        <p style="font-size: 14px; text-align: center; color: #CFA58C; margin-bottom: 20px;">
            Email Verified! Enter your new password below.
        </p>
        
        <input type="hidden" name="hidden_email" value="<?php echo $user_email; ?>">

        <div class="password-wrapper">
            <input
            type="password"
            name="new_password"
            id="new_password"
            placeholder="Enter New Password"
            class="input-box"
            required>
            <i class="bi bi-eye-slash toggle-password" onclick="toggleField('new_password', this)"></i>
        </div>

        <div class="password-wrapper">
            <input
            type="password"
            name="confirm_password"
            id="confirm_password"
            placeholder="Confirm New Password"
            class="input-box"
            required>
            <i class="bi bi-eye-slash toggle-password" onclick="toggleField('confirm_password', this)"></i>
        </div>

        <button type="submit" name="update_password" class="login-btn">
            Update Password
        </button>
    </form>
<?php } ?>

<?php if ($step == 3) { ?>
    <p class="success-msg">
        <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
    </p>
<?php } ?>

<?php if (!empty($error)) { ?>
    <p class="error">
        <?php echo $error; ?>
    </p>
<?php } ?>

<a href="login.php" class="back-to-login"><i class="bi bi-arrow-left"></i> Back to Login</a>

</div>

<script>
    function toggleField(inputId, iconElement) {
        const passwordInput = document.getElementById(inputId);
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        iconElement.classList.toggle('bi-eye');
        iconElement.classList.toggle('bi-eye-slash');
    }
</script>

</body>
</html>