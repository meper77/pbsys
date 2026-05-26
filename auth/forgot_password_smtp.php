     1|<?php
     2|// Load PHPMailer first (before use statements)
     3|require $_SERVER['DOCUMENT_ROOT'] . '/vendor/PHPMailer/PHPMailer/src/PHPMailer.php';
     4|require $_SERVER['DOCUMENT_ROOT'] . '/vendor/PHPMailer/PHPMailer/src/SMTP.php';
     5|require $_SERVER['DOCUMENT_ROOT'] . '/vendor/PHPMailer/PHPMailer/src/Exception.php';
     6|
     7|use PHPMailer\PHPMailer\PHPMailer;
     8|use PHPMailer\PHPMailer\Exception;
     9|
    10|include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';
    11|
    12|$success = false;
    13|$message = '';
    14|$email_sent = false;
    15|
    16|if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    17|    $email = trim($_POST['email'] ?? '');
    18|    
    19|    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    20|        // Check if email exists in user or admin table
    21|        $stmt = $con->prepare("SELECT id, email FROM user WHERE email = ? UNION SELECT id, email FROM admin WHERE email = ?");
    22|        $stmt->bind_param("ss", $email, $email);
    23|        $stmt->execute();
    24|        $result = $stmt->get_result();
    25|        $user_exists = $result->num_rows > 0;
    26|        $stmt->close();
    27|        
    28|        // Always show success for security (don't reveal if email exists)
    29|        $email_sent = true;
    30|        
    31|        if ($user_exists) {
    32|            // Generate secure token
    33|            $token = bin2hex(random_bytes(32));
    34|            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    35|            
    36|            // Store token in database
    37|            $stmt = $con->prepare("
    38|                INSERT INTO password_reset_tokens (email, token, expires_at) 
    39|                VALUES (?, ?, ?)
    40|            ");
    41|            $stmt->bind_param("sss", $email, $token, $expires_at);
    42|            $stmt->execute();
    43|            $stmt->close();
    44|            
    45|            // Build reset link
    46|            $reset_link = "http://{$_SERVER['HTTP_HOST']}/auth/reset_password_token.php?token=*** . urlencode($token);
    47|            
    48|            // Send email via PHPMailer
    49|            try {
    50|                $mail = new PHPMailer(true);
    51|                $mail->isSMTP();
    52|                $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'localhost';
    53|                $mail->SMTPAuth = defined('SMTP_AUTH') ? SMTP_AUTH : false;
    54|                if (defined('SMTP_USER')) $mail->Username = SMTP_USER;
    55|                if (defined('SMTP_PASS')) $mail->Password = SMTP_PASS;
    56|                $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : PHPMailer::ENCRYPTION_STARTTLS;
    57|                $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
    58|                
    59|                $mail->setFrom(defined('SMTP_FROM') ? SMTP_FROM : 'noreply@neovtrack.uitm.edu.my', 'NEO V-TRACK');
    60|                $mail->addAddress($email);
    61|                $mail->Subject = 'Password Reset Request - NEO V-TRACK';
    62|                
    63|                $mail->isHTML(true);
    64|                $mail->Body = "
    65|                    <h2>Password Reset Request</h2>
    66|                    <p>You requested a password reset for your account.</p>
    67|                    <p>Click the link below to reset your password (valid for 1 hour):</p>
    68|                    <p><a href='{$reset_link}'>{$reset_link}</a></p>
    69|                    <p>If you did not request this, please ignore this email.</p>
    70|                    <p>NEO V-TRACK System</p>
    71|                ";
    72|                
    73|                $mail->send();
    74|            } catch (Exception $e) {
    75|                // Log error but don't show it to user
    76|                error_log("Password reset email failed: " . $e->getMessage());
    77|            }
    78|        }
    79|        
    80|        $success = true;
    81|        $message = 'If an account with that email exists, you will receive a password reset link.';
    82|    } else {
    83|        $message = 'Please enter a valid email address.';
    84|    }
    85|}
    86|
    87|// Set page title and include header
    88|$page_title = 'Forgot Password';
    89|include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
    90|?>
    91|
    92|<div class="container mt-5">
    93|    <div class="row">
    94|        <div class="col-md-6 mx-auto">
    95|            <div class="card shadow-sm">
    96|                <div class="card-body p-4">
    97|                    <h2 class="card-title mb-4 text-center">Reset Password</h2>
    98|                    
    99|                    <?php if ($success && $email_sent): ?>
   100|                        <div class="alert alert-info" role="alert">
   101|                            <strong>Success!</strong> <?php echo htmlspecialchars($message); ?>
   102|                        </div>
   103|                        <p class="text-muted text-center mt-3">
   104|                            Remember your password? <a href="/auth/login.php">Back to login</a>
   105|                        </p>
   106|                    <?php else: ?>
   107|                        <?php if (!empty($message) && !$success): ?>
   108|                            <div class="alert alert-danger" role="alert">
   109|                                <?php echo htmlspecialchars($message); ?>
   110|                            </div>
   111|                        <?php endif; ?>
   112|                        
   113|                        <form method="POST" action="/auth/forgot_password_smtp.php">
   114|                            <div class="mb-3">
   115|                                <label for="email" class="form-label">Email Address</label>
   116|                                <input type="email" class="form-control" id="email" name="email" 
   117|                                       placeholder="Enter your email" required autofocus>
   118|                                <small class="form-text text-muted">
   119|                                    Enter the email address associated with your account.
   120|                                </small>
   121|                            </div>
   122|                            
   123|                            <button type="submit" class="btn btn-primary w-100 mb-3">
   124|                                Send Reset Link
   125|                            </button>
   126|                            
   127|                            <hr>
   128|                            
   129|                            <p class="text-center text-muted">
   130|                                <a href="/auth/login.php" class="text-decoration-none">Back to login</a>
   131|                            </p>
   132|                        </form>
   133|                    <?php endif; ?>
   134|                </div>
   135|            </div>
   136|        </div>
   137|    </div>
   138|</div>
   139|
   140|<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
   141|