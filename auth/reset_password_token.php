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
        $result = $stmt->execute();
        
        if ($result) {
            // Delete used token
            $delete_stmt = $con->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
            $delete_stmt->bind_param("s", $token);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            $success = true;
            $message = 'Password reset successfully! You can now log in with your new password.';
        } else {
            $message = 'Error updating password. Please try again.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - NEO V-TRACK</title>
    <link rel="stylesheet" href="/assets/css/neo-vtrack-tokens.css">
    <link rel="stylesheet" href="/assets/css/neo-vtrack-components.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <style>
        /* Auth page button styles */
        .btn {
            display: inline-block;
            padding: var(--space-3) var(--space-6);
            font-size: var(--text-md);
            font-weight: 600;
            border: 2px solid transparent;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 200ms var(--ease-out);
            font-family: var(--font-sans);
            text-decoration: none;
            text-align: center;
        }
        
        .btn-primary {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }
        
        .btn-primary:hover {
            background: var(--accent-hover);
            border-color: var(--accent-hover);
        }
        
        .btn-ghost {
            background: transparent;
            color: var(--accent);
            border-color: var(--accent);
        }
        
        .btn-ghost:hover {
            background: rgba(var(--accent-rgb), 0.1);
            border-color: var(--accent-hover);
            color: var(--accent-hover);
        }
    </style>
    <style>
        .auth-hero {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--brand-purple-deep) 0%, var(--brand-purple) 100%);
            padding: var(--space-4);
        }
        
        .auth-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: var(--space-8);
            box-shadow: var(--shadow-3);
            width: 100%;
            max-width: 420px;
            animation: slideUp 400ms var(--ease-out);
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .auth-brand {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            margin-bottom: var(--space-6);
        }
        
        .auth-brand .uitm {
            height: 32px;
            width: auto;
        }
        
        .auth-brand .divider {
            width: 1px;
            height: 40px;
            background: var(--border);
        }
        
        .auth-brand .neo {
            height: 32px;
            width: auto;
            background: var(--brand-white);
            border-radius: var(--radius-xs);
            padding: 2px 4px;
        }
        
        .auth-brand .word {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .auth-brand .name {
            font-family: var(--font-display);
            font-weight: 800;
            font-size: var(--text-lg);
            letter-spacing: 0.02em;
            color: var(--fg-1);
        }
        
        .auth-brand .name .y {
            color: var(--brand-yellow);
        }
        
        .auth-brand .sub {
            font-size: var(--text-xs);
            letter-spacing: 0.1em;
            color: var(--fg-3);
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .auth-head {
            margin-bottom: var(--space-6);
            border-bottom: 1px solid var(--border);
            padding-bottom: var(--space-4);
        }
        
        .auth-head h2 {
            font-size: var(--text-2xl);
            font-weight: 700;
            color: var(--fg-1);
        }
        
        .auth-form-group {
            margin-bottom: var(--space-4);
        }
        
        .auth-form-group label {
            display: block;
            font-size: var(--text-sm);
            font-weight: 600;
            color: var(--fg-1);
            margin-bottom: var(--space-2);
        }
        
        .auth-form-group input {
            width: 100%;
            padding: var(--space-3) var(--space-4);
            font-size: var(--text-md);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--surface);
            color: var(--fg-1);
            font-family: var(--font-sans);
            transition: border-color 200ms var(--ease-out), box-shadow 200ms var(--ease-out);
        }
        
        .auth-form-group input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--brand-purple-tint);
        }
        
        .auth-form-group input::placeholder {
            color: var(--fg-4);
        }
        
        .auth-message {
            padding: var(--space-4);
            border-radius: var(--radius-sm);
            margin-bottom: var(--space-4);
            font-size: var(--text-sm);
            font-weight: 500;
        }
        
        .auth-message.error {
            background: var(--status-bad-bg);
            color: var(--status-bad);
            border-left: 4px solid var(--status-bad);
        }
        
        .auth-message.success {
            background: var(--status-ok-bg);
            color: var(--status-ok);
            border-left: 4px solid var(--status-ok);
        }
        
        .auth-actions {
            display: flex;
            flex-direction: column;
            gap: var(--space-3);
            margin-top: var(--space-6);
        }
        
        .auth-text-center {
            text-align: center;
            color: var(--fg-2);
            font-size: var(--text-sm);
        }
        
        .auth-link {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            transition: color 120ms var(--ease-out);
        }
        
        .auth-link:hover {
            color: var(--accent-hover);
        }
    </style>
</head>
<body>
<div class="auth-hero">
    <form class="auth-card" method="post" action="">
        <div class="auth-brand">
            <img class="uitm" src="/assets/images/uitm.png" alt="UiTM">
            <div class="divider"></div>
            <img class="neo" src="/assets/images/neo-vtrack-logo.png" alt="NEO V-TRACK">
            <div class="word">
                <span class="name">NEO <span class="y">V-TRACK</span></span>
                <span class="sub">Password Reset</span>
            </div>
        </div>
        
        <div class="auth-head">
            <h2><?php echo $success ? 'Password Reset Successful' : 'Reset Your Password'; ?></h2>
        </div>

        <?php if (!empty($message)): ?>
            <div class="auth-message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($show_form): ?>
            <div class="auth-form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" placeholder="At least 8 characters" required>
            </div>

            <div class="auth-form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
            </div>

            <div class="auth-actions">
                <button type="submit" class="btn btn-primary">
                    Reset Password
                </button>
            </div>
        <?php elseif ($success): ?>
            <div class="auth-actions">
                <a href="/auth/login.php" class="btn btn-primary" style="text-align: center;">
                    Go to Login
                </a>
            </div>
        <?php else: ?>
            <div class="auth-text-center" style="margin-top: var(--space-6);">
                <p style="margin-bottom: var(--space-4);">Invalid or expired reset link. Please request a new password reset.</p>
                <p>
                    <a href="/auth/forgot_password_smtp.php" class="auth-link">Request Password Reset</a>
                </p>
            </div>
        <?php endif; ?>
    </form>
</div>
</body>
</html>
