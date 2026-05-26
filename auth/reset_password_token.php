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
    $stmt = $conn->prepare("
        SELECT email FROM password_reset_tokens 
        WHERE token = ? AND expires_at > NOW() 
        LIMIT 1
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $token_valid = true;
        $token_email = $row['email'];
        $show_form = true;
    } else {
        $message = 'Reset link expired or invalid. Please request a new one.';
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate passwords
    if (empty($new_password) || empty($confirm_password)) {
        $message = 'Please enter password in both fields.';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $message = 'Password must be at least 6 characters.';
    } else {
        // Verify token again (prevent token reuse)
        $stmt = $conn->prepare("
            SELECT email FROM password_reset_tokens 
            WHERE token = ? AND expires_at > NOW()
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $reset_email = $row['email'];
            
            // Hash password
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            
            // Check if email is in user or admin table and update
            $updated = false;
            
            // Try user table first
            $stmt = $conn->prepare("UPDATE user SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $reset_email);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $updated = true;
            }
            $stmt->close();
            
            // If not found in user, try admin
            if (!$updated) {
                $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE email = ?");
                $stmt->bind_param("ss", $hashed_password, $reset_email);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $updated = true;
                }
                $stmt->close();
            }
            
            if ($updated) {
                // Delete used token
                $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $stmt->close();
                
                $success = true;
                $show_form = false;
                $message = 'Password reset successfully. You can now log in with your new password.';
            } else {
                $message = 'Error updating password. Please try again.';
            }
        } else {
            $message = 'Reset link expired. Please request a new one.';
        }
        $stmt->close();
    }
}

$page_title = 'Reset Password';
include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="card-title mb-4 text-center">Reset Your Password</h2>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <strong>Success!</strong> <?php echo htmlspecialchars($message); ?>
                        </div>
                        <p class="text-center mt-3">
                            <a href="/auth/login.php" class="btn btn-primary">Go to Login</a>
                        </p>
                    <?php else: ?>
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo ($token_valid ? 'info' : 'danger'); ?>" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($show_form && $token_valid): ?>
                            <form method="POST" action="/auth/reset_password_token.php">
                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" 
                                           name="new_password" placeholder="Enter new password" 
                                           required minlength="6" autofocus>
                                    <small class="form-text text-muted">
                                        Minimum 6 characters.
                                    </small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" placeholder="Confirm new password" 
                                           required minlength="6">
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">
                                    Reset Password
                                </button>
                            </form>
                        <?php else: ?>
                            <p class="text-center text-muted">
                                <a href="/auth/forgot_password_smtp.php" class="text-decoration-none">Request a new reset link</a>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
