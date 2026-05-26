<?php
include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';

$token = $_GET['token'] ?? null;
$token_valid = false;
$token_email = '';
$message = '';
$success = false;
$show_form = false;

if (!empty($token)) {
    // Verify token
    $stmt = $con->prepare("
        SELECT email FROM password_reset_tokens 
        WHERE token = ? AND expires_at > NOW() 
        LIMIT 1
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $token_email = $row['email'];
        $token_valid = true;
        $show_form = true;
    } else {
        $message = 'Token invalid or expired. Please request a new password reset.';
    }
    $stmt->close();
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid && !empty($token_email)) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $message = 'Both password fields are required.';
    } elseif (strlen($new_password) < 8) {
        $message = 'Password must be at least 8 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Passwords do not match.';
    } else {
        // Hash password using bcrypt
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        
        // Update password in user or admin table
        $stmt = $con->prepare("UPDATE user SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $token_email);
        $stmt->execute();
        $user_updated = $stmt->affected_rows > 0;
        $stmt->close();
        
        if (!$user_updated) {
            // Try admin table
            $stmt = $con->prepare("UPDATE admin SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $token_email);
            $stmt->execute();
            $admin_updated = $stmt->affected_rows > 0;
            $stmt->close();
            
            if (!$admin_updated) {
                $message = 'Failed to update password. User not found.';
            }
        }
        
        // Delete used token
        if ($user_updated || $admin_updated) {
            $stmt = $con->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $stmt->close();
            
            $success = true;
            $show_form = false;
            $message = 'Password successfully reset. You can now log in with your new password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - NEO V-TRACK</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
<div class="auth-hero">
    <form class="auth-card" method="post" action="">
        <div class="auth-brand">
            <img class="uitm" src="/assets/images/uitm.png" alt="UiTM">
            <div class="divider"></div>
            <img class="neo" src="/assets/images/neo-vtrack-logo.png" alt="NEO V-TRACK">
            <div class="word"><span class="name">NEO <span class="y">V-TRACK</span></span><span class="sub">Password Reset</span></div>
        </div>
        <div class="auth-head">
            <h2><?php echo $success ? 'Password Reset Successful' : 'Reset Your Password'; ?></h2>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($show_form): ?>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" placeholder="At least 8 characters" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; padding: 12px; border: none; border-radius: 8px; background-color: #007bff; color: white; font-weight: bold; cursor: pointer;">
                Reset Password
            </button>
        <?php elseif ($success): ?>
            <p style="text-align: center; margin-top: 20px;">
                <a href="/auth/login.php" class="btn-primary" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 8px;">
                    Go to Login
                </a>
            </p>
        <?php else: ?>
            <p style="text-align: center; color: #dc3545; margin-top: 20px;">
                Invalid or expired reset link. Please request a new password reset.
            </p>
            <p style="text-align: center; margin-top: 20px;">
                <a href="/auth/forgot_password_smtp.php" style="color: #007bff; text-decoration: none;">Request Password Reset</a>
            </p>
        <?php endif; ?>
    </form>
</div>
</body>
</html>
