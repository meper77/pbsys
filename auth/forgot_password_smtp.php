<?php
/**
 * SMTP-only Password Reset Page
 * 
 * Sends password reset link via SMTP email
 */

session_start();

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

// Check if database has password_reset_tokens table
$check_table = mysqli_query($con, "SHOW TABLES LIKE 'password_reset_tokens'");
$table_exists = mysqli_num_rows($check_table) > 0;

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($con, $_POST['email']);
    
    // Check if user exists
    $check_query = "SELECT * FROM user WHERE email = '$email'";
    $result = mysqli_query($con, $check_query);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Generate random token
        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);
        
        // Token expires in 1 hour
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        if ($table_exists) {
            // Store token hash
            $con->query("
                INSERT INTO password_reset_tokens (user_id, token_hash, email, expires_at)
                VALUES ({$user['userid']}, '$token_hash', '{$user['email']}', '$expires')
                ON DUPLICATE KEY UPDATE token_hash = '$token_hash', expires_at = '$expires'
            ");
            
            // Send email via PHPMailer
            try {
                require $_SERVER['DOCUMENT_ROOT'] . '/vendor/PHPMailer/PHPMailer/src/PHPMailer.php';
                require $_SERVER['DOCUMENT_ROOT'] . '/vendor/PHPMailer/PHPMailer/src/SMTP.php';
                require $_SERVER['DOCUMENT_ROOT'] . '/vendor/PHPMailer/PHPMailer/src/Exception.php';
                
                $mail = new \PHPMailer\PHPMailer\PHPMailer();
                $mail->IsSMTP();
                $mail->Host = 'mail.uitm.edu.my';
                $mail->SMTPAuth = true;
                $mail->Username = 'noreply@uitm.edu.my';
                $mail->Password = getenv('SMTP_PASSWORD') ?: '';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;
                
                $mail->From = 'noreply@uitm.edu.my';
                $mail->FromName = 'NEO V-TRACK';
                $mail->addAddress($user['email'], $user['name']);
                
                $reset_link = "https://neovtrack.uitm.edu.my/auth/reset_password.php?token=" . urlencode($token);
                
                $mail->Subject = 'Reset Your NEO V-TRACK Password';
                $mail->Body = "Hello {$user['name']},\n\n" .
                              "Click the link below to reset your password:\n" .
                              "$reset_link\n\n" .
                              "This link expires in 1 hour.\n\n" .
                              "If you did not request this, ignore this email.\n\n" .
                              "-- NEO V-TRACK";
                
                if ($mail->send()) {
                    $success = 'Reset link sent to ' . htmlspecialchars($email) . '. Check your email.';
                } else {
                    $error = 'Failed to send email. Please contact admin.';
                }
            } catch (Exception $e) {
                $error = 'Mail error: ' . $e->getMessage();
            }
        } else {
            $error = 'Password reset system is not configured. Please contact admin.';
        }
    } else {
        // Don't reveal if email exists (security best practice)
        $success = 'If this email exists in our system, a reset link has been sent.';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Forgot Password - NEO V-TRACK</title>
  <link rel="stylesheet" href="/assets/css/styles.css">
  <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
  <div class="container">
    <h1>Forgot Password</h1>
    
    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?php echo $success; ?></div>
    <?php elseif (!empty($error)): ?>
      <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" required placeholder="name@uitm.edu.my">
      </div>
      <button type="submit" class="btn btn-primary">Send Reset Link</button>
      <a href="/auth/login.php" class="btn btn-secondary">Back to Login</a>
    </form>
  </div>
</body>
</html>
