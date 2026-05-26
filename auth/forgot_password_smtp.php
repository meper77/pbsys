<?php
// Load PHPMailer first (before use statements)
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/PHPMailer/PHPMailer/src/PHPMailer.php';
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/PHPMailer/PHPMailer/src/SMTP.php';
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/PHPMailer/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';

$success = false;
$message = '';
$email_sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Check if email exists in user or admin table
        $stmt = $con->prepare("SELECT id, email FROM user WHERE email = ? UNION SELECT id, email FROM admin WHERE email = ?");
        $stmt->bind_param("ss", $email, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_exists = $result->num_rows > 0;
        $stmt->close();
        
        // Always show success for security (don't reveal if email exists)
        $email_sent = true;
        
        if ($user_exists) {
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $stmt = $con->prepare("
                INSERT INTO password_reset_tokens (email, token, expires_at) 
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("sss", $email, $token, $expires_at);
            $stmt->execute();
            $stmt->close();
            
            // Build reset link
            $reset_link = "http://{$_SERVER['HTTP_HOST']}/auth/reset_password_token.php?token=" . urlencode($token);
            
            // Send email via PHPMailer
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'localhost';
                $mail->SMTPAuth = defined('SMTP_AUTH') ? SMTP_AUTH : false;
                if (defined('SMTP_USER')) $mail->Username = SMTP_USER;
                if (defined('SMTP_PASS')) $mail->Password = SMTP_PASS;
                $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
                
                $mail->setFrom(defined('SMTP_FROM') ? SMTP_FROM : 'noreply@neovtrack.uitm.edu.my', 'NEO V-TRACK');
                $mail->addAddress($email);
                $mail->Subject = 'Password Reset Request - NEO V-TRACK';
                
                $mail->isHTML(true);
                $mail->Body = "
                    <h2>Password Reset Request</h2>
                    <p>You requested a password reset for your account.</p>
                    <p>Click the link below to reset your password (valid for 1 hour):</p>
                    <p><a href='{$reset_link}'>{$reset_link}</a></p>
                    <p>If you did not request this, please ignore this email.</p>
                    <p>NEO V-TRACK System</p>
                ";
                
                $mail->send();
            } catch (Exception $e) {
                // Log error but don't show it to user
                error_log("Password reset email failed: " . $e->getMessage());
            }
        }
        
        $success = true;
        $message = 'If an account with that email exists, you will receive a password reset link.';
    } else {
        $message = 'Please enter a valid email address.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - NEO V-TRACK</title>
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
        
        .auth-message.success {
            background: var(--status-ok-bg);
            color: var(--status-ok);
            border-left: 4px solid var(--status-ok);
        }
        
        .auth-message.error {
            background: var(--status-bad-bg);
            color: var(--status-bad);
            border-left: 4px solid var(--status-bad);
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
                <span class="sub">Forgot Password</span>
            </div>
        </div>
        
        <div class="auth-head">
            <h2>Reset Password</h2>
        </div>

        <?php if ($email_sent): ?>
            <div class="auth-message success">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <div class="auth-text-center" style="margin-top: var(--space-6);">
                <p style="margin-bottom: var(--space-4);">Check your email for a password reset link.</p>
                <p>
                    <a href="/auth/login.php" class="auth-link">Back to Login</a>
                </p>
            </div>
        <?php else: ?>
            <?php if (!empty($message)): ?>
                <div class="auth-message error">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="auth-form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="your@email.com" 
                    required 
                    autocomplete="email"
                >
            </div>

            <div class="auth-actions">
                <button type="submit" class="btn btn-primary">
                    Send Reset Link
                </button>
                <a href="/auth/login.php" class="btn btn-ghost" style="text-align: center;">
                    Back to Login
                </a>
            </div>

            <div class="auth-text-center" style="margin-top: var(--space-6);">
                <p>Don't have an account? 
                    <a href="/auth/register.php" class="auth-link">Sign Up</a>
                </p>
            </div>
        <?php endif; ?>
    </form>
</div>
</body>
</html>
