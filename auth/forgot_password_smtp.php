<?php
include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';

$success = false;
$message = '';
$email_sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Check if email exists in user or admin table
        $stmt = $conn->prepare("SELECT id, email FROM user WHERE email = ? UNION SELECT id, email FROM admin WHERE email = ?");
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
            $stmt = $conn->prepare("
                INSERT INTO password_reset_tokens (email, token, expires_at) 
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("sss", $email, $token, $expires_at);
            $stmt->execute();
            $stmt->close();
            
            // Build reset link
            $reset_link = "http://{$_SERVER['HTTP_HOST']}/auth/reset_password_token.php?token=" . urlencode($token);
            
            // Send email via PHPMailer
            require $_SERVER['DOCUMENT_ROOT'] . '/vendor/PHPMailer/PHPMailer.php';
            require $_SERVER['DOCUMENT_ROOT'] . '/vendor/PHPMailer/SMTP.php';
            require $_SERVER['DOCUMENT_ROOT'] . '/vendor/PHPMailer/Exception.php';
            
            use PHPMailer\PHPMailer\PHPMailer;
            use PHPMailer\PHPMailer\Exception;
            
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USER;
                $mail->Password = SMTP_PASS;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = SMTP_PORT;
                
                $mail->setFrom(SMTP_FROM, 'NEO V-TRACK');
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

// Set page title and include header
$page_title = 'Forgot Password';
include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="card-title mb-4 text-center">Reset Password</h2>
                    
                    <?php if ($success && $email_sent): ?>
                        <div class="alert alert-info" role="alert">
                            <strong>Success!</strong> <?php echo htmlspecialchars($message); ?>
                        </div>
                        <p class="text-muted text-center mt-3">
                            Remember your password? <a href="/auth/login.php">Back to login</a>
                        </p>
                    <?php else: ?>
                        <?php if (!empty($message) && !$success): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="/auth/forgot_password_smtp.php">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="Enter your email" required autofocus>
                                <small class="form-text text-muted">
                                    Enter the email address associated with your account.
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                Send Reset Link
                            </button>
                            
                            <hr>
                            
                            <p class="text-center text-muted">
                                <a href="/auth/login.php" class="text-decoration-none">Back to login</a>
                            </p>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
