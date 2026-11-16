<?php
// Include authentication protection, database connection, and sidebar
include '../includes/auth.php';
include '../includes/config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = mysqli_real_escape_string($conn, $_POST['current_password']);
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_msg = "Please fill in all fields.";
    } elseif ($new_password !== $confirm_password) {
        $error_msg = "New passwords do not match. Please try again.";
    } elseif (strlen($new_password) < 6) {
        $error_msg = "New password must be at least 6 characters long.";
    } else {
        // Fetch current password from database to verify
        $query = "SELECT password FROM users WHERE id = $user_id LIMIT 1";
        $result = mysqli_query($conn, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify if the entered current password matches the database hash
            if (password_verify($current_password, $user['password'])) {
                
                // Securely hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                
                // Update password in the database
                $update_query = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";
                if (mysqli_query($conn, $update_query)) {
                    $success_msg = "Your password has been successfully updated!";
                } else {
                    $error_msg = "An error occurred while updating. Please try again.";
                }
            } else {
                $error_msg = "The current password you entered is incorrect.";
            }
        } else {
            $error_msg = "User account not found.";
        }
    }
}

// Include Sidebar after PHP logic to prevent "headers already sent" errors
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="form-container-box">
        <div class="form-header">
            <h2><i class="bi bi-shield-lock-fill"></i> Change Account Password</h2>
            <p>Update your account password to ensure the ongoing security of your **REDE TOURS** data.</p>
        </div>

        <!-- FEEDBACK NOTIFICATIONS -->
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i> <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <!-- CHANGE PASSWORD FORM -->
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="password-form">
            
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <div class="input-wrapper">
                    <i class="bi bi-key input-icon"></i>
                    <input type="password" id="current_password" name="current_password" placeholder="Enter current password" required>
                </div>
            </div>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <div class="input-wrapper">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="input-wrapper">
                    <i class="bi bi-lock-check input-icon"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password" required>
                </div>
            </div>

            <button type="submit" class="submit-form-btn">
                <i class="bi bi-save"></i> Update Password
            </button>
        </form>
    </div>
</div>

<style>
    /* FORM CONTAINER STYLING */
    .form-container-box {
        max-width: 550px;
        margin: 40px auto;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 35px;
        backdrop-filter: blur(10px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
    }

    .form-header h2 {
        margin: 0 0 10px 0;
        font-size: 24px;
        color: #CFA58C;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .form-header p {
        margin: 0 0 25px 0;
        font-size: 14px;
        color: rgba(255, 255, 255, 0.6);
        line-height: 1.5;
    }

    /* ALERTS STYLE */
    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 14px;
        margin-bottom: 22px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    }

    .alert-danger {
        background-color: rgba(231, 76, 60, 0.15);
        border: 1px solid rgba(231, 76, 60, 0.3);
        color: #ff6666;
    }

    .alert-success {
        background-color: rgba(46, 204, 113, 0.15);
        border: 1px solid rgba(46, 204, 113, 0.3);
        color: #2ecc71;
    }

    /* FORM ELEMENTS */
    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-size: 14px;
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 8px;
        font-weight: 500;
    }

    .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-icon {
        position: absolute;
        left: 14px;
        color: rgba(255, 255, 255, 0.4);
        font-size: 16px;
    }

    .input-wrapper input {
        width: 100%;
        background: #111a36;
        border: 1px solid rgba(255, 255, 255, 0.15);
        padding: 12px 12px 12px 42px;
        border-radius: 8px;
        color: #ffffff;
        font-size: 15px;
        outline: none;
        box-sizing: border-box;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .input-wrapper input:focus {
        border-color: #CFA58C;
        box-shadow: 0 0 8px rgba(207, 165, 140, 0.2);
    }

    /* BUTTON STYLE */
    .submit-form-btn {
        width: 100%;
        background: #CFA58C;
        color: #0b2265;
        border: none;
        padding: 13px;
        font-size: 16px;
        font-weight: 600;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: background-color 0.3s, transform 0.2s;
        margin-top: 10px;
    }

    .submit-form-btn:hover {
        background: #bfa37c;
        transform: translateY(-2px);
    }

    .submit-form-btn:active {
        transform: translateY(0);
    }

    /* RESPONSIVENESS */
    @media (max-width: 576px) {
        .form-container-box {
            padding: 25px 20px;
            margin: 20px auto;
        }
        .form-header h2 { font-size: 20px; }
    }
</style>

</body>
</html>