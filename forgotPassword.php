<?php
session_start();

// Include database connection
include 'connect.php';

// LANGUAGE SYSTEM
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'bm';
}

if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] == 'en') ? 'en' : 'bm';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

$lang = $_SESSION['language'];

// Language texts
$text = [];

// Bahasa Malaysia
$text['bm'] = [
    'page_title' => 'Reset Kata Laluan',
    'system_name' => 'NEO V-TRACK - Sistem Pengurusan & Pemantauan Kenderaan',
    'reset_password' => 'Reset Kata Laluan',
    'step1_title' => 'Langkah 1: Masukkan Emel Anda',
    'step2_title' => 'Langkah 2: Tetapkan Kata Laluan Baharu',
    'email' => 'Emel',
    'new_password' => 'Kata Laluan Baharu',
    'confirm_password' => 'Sahkan Kata Laluan',
    'submit' => 'Hantar',
    'reset' => 'Reset Kata Laluan',
    'back_to_login' => 'Kembali ke Log Masuk',
    'reset_success' => 'Kata laluan berjaya ditukar! Anda akan diarahkan ke halaman log masuk.',
    'reset_error' => 'Ralat menukar kata laluan. Sila cuba lagi.',
    'email_not_found' => 'Emel tidak wujud dalam sistem.',
    'password_mismatch' => 'Kata laluan tidak sepadan.',
    'password_short' => 'Kata laluan mesti sekurang-kurangnya 6 aksara.',
    'email_placeholder' => 'Masukkan emel anda',
    'password_placeholder' => 'Masukkan kata laluan baharu',
    'confirm_placeholder' => 'Masukkan semula kata laluan baharu',
    'copyright' => '© Hak Cipta Universiti Teknologi MARA Cawangan Johor - Polis Bantuan | ICT Security',
    'user_login' => 'Log Masuk Pengguna',
    'success_title' => 'Kata Laluan Berjaya Direset!',
    'redirect_message' => 'Anda akan diarahkan ke halaman log masuk dalam 5 saat...',
    'show_password' => 'Tunjukkan kata laluan',
    'hide_password' => 'Sembunyikan kata laluan'
];

// English
$text['en'] = [
    'page_title' => 'Reset Password',
    'system_name' => 'NEO V-TRACK - Vehicle Management & Monitoring System',
    'reset_password' => 'Reset Password',
    'step1_title' => 'Step 1: Enter Your Email',
    'step2_title' => 'Step 2: Set New Password',
    'email' => 'Email',
    'new_password' => 'New Password',
    'confirm_password' => 'Confirm Password',
    'submit' => 'Submit',
    'reset' => 'Reset Password',
    'back_to_login' => 'Back to Login',
    'reset_success' => 'Password successfully changed! You will be redirected to login page.',
    'reset_error' => 'Error changing password. Please try again.',
    'email_not_found' => 'Email does not exist in the system.',
    'password_mismatch' => 'Passwords do not match.',
    'password_short' => 'Password must be at least 6 characters.',
    'email_placeholder' => 'Enter your email',
    'password_placeholder' => 'Enter new password',
    'confirm_placeholder' => 'Re-enter new password',
    'copyright' => '© Copyright Universiti Teknologi MARA Johor Branch - Auxiliary Police | ICT Security',
    'user_login' => 'User Login',
    'success_title' => 'Password Successfully Reset!',
    'redirect_message' => 'You will be redirected to login page in 5 seconds...',
    'show_password' => 'Show password',
    'hide_password' => 'Hide password'
];

$t = $text[$lang];

// Initialize variables
$message = "";
$message_type = ""; // success or danger
$step = 1; // 1 = email entry, 2 = password reset
$user_email = "";

// Check if email was submitted
if (isset($_POST['step1'])) {
    $email = mysqli_real_escape_string($con, $_POST['email']);
    
    // Check if user exists in user table
    $check_query = "SELECT * FROM user WHERE email = '$email'";
    $result = mysqli_query($con, $check_query);
    
    if (mysqli_num_rows($result) > 0) {
        // User exists, proceed to step 2
        $step = 2;
        $user_email = $email;
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_user_type'] = 'user';
    } else {
        // User doesn't exist
        $message = $t['email_not_found'];
        $message_type = "danger";
        $step = 1;
    }
}

// Check if password reset was submitted
if (isset($_POST['step2'])) {
    $email = $_SESSION['reset_email'];
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($con, $_POST['confirm_password']);
    
    // Validate passwords
    if (strlen($password) < 6) {
        $message = $t['password_short'];
        $message_type = "danger";
        $step = 2;
        $user_email = $email;
    } elseif ($password !== $confirm_password) {
        $message = $t['password_mismatch'];
        $message_type = "danger";
        $step = 2;
        $user_email = $email;
    } else {
        // Update password in database
        // Note: In a real system, you should hash the password!
        $update_query = "UPDATE user SET password = '$password' WHERE email = '$email'";
        
        if (mysqli_query($con, $update_query)) {
            $message = $t['reset_success'];
            $message_type = "success";
            $step = 3; // Success step
            
            // Clear session data
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_user_type']);
        } else {
            $message = $t['reset_error'] . ": " . mysqli_error($con);
            $message_type = "danger";
            $step = 2;
            $user_email = $email;
        }
    }
}

// Build login link with language parameter
$login_link = "login.php" . (isset($_SESSION['language']) ? "?lang=" . $_SESSION['language'] : "");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['page_title']; ?> - NEO V-TRACK</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #1a2980;
            --secondary-color: #2575fc;
            --success-color: #00b09b;
            --warning-color: #ff9966;
            --danger-color: #ff416c;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
            --uitm-red: #c30e2e;
            --uitm-blue: #003087;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fb 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 500px;
        }
        
        /* Header with logos */
        .header-container {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--uitm-blue) 100%);
            border-radius: 15px 15px 0 0;
            padding: 30px;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .logo-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 30px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .logo-img {
            height: 60px;
            width: auto;
            object-fit: contain;
        }
        
        .uitm-logo {
            border-right: 2px solid rgba(255, 255, 255, 0.3);
            padding-right: 30px;
        }
        
        .system-title {
            text-align: center;
            padding: 20px 0;
        }
        
        .system-title h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .system-title p {
            font-size: 16px;
            opacity: 0.9;
            margin: 0;
        }
        
        /* Form container */
        .form-container {
            background: white;
            border-radius: 0 0 15px 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .form-title {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .form-title h3 {
            margin: 0;
            font-size: 24px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(26, 41, 128, 0.15);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--success-color) 0%, #00d2b9 100%);
            color: white;
            border: none;
            padding: 14px 35px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            cursor: pointer;
            width: 100%;
            justify-content: center;
        }
        
        .btn-submit:hover {
            background: linear-gradient(135deg, #00d2b9 0%, var(--success-color) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 176, 155, 0.3);
            color: white;
        }
        
        .btn-back {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            border: none;
            padding: 14px 35px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            text-decoration: none;
            width: 100%;
            justify-content: center;
        }
        
        .btn-back:hover {
            background: linear-gradient(135deg, #495057 0%, #343a40 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
            color: white;
            text-decoration: none;
        }
        
        .alert {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(0, 176, 155, 0.1) 0%, rgba(0, 210, 185, 0.1) 100%);
            border-left: 4px solid var(--success-color);
            color: #155724;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(255, 65, 108, 0.1) 0%, rgba(255, 107, 136, 0.1) 100%);
            border-left: 4px solid var(--danger-color);
            color: #721c24;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            display: flex;
            align-items: center;
            color: #666;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }
        
        .step.active .step-number {
            background: var(--primary-color);
            color: white;
        }
        
        .step.active .step-text {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .step-line {
            width: 50px;
            height: 2px;
            background: #ddd;
            margin: 0 10px;
        }
        
        .success-message {
            text-align: center;
            padding: 40px 20px;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--success-color) 0%, #00d2b9 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 36px;
        }
        
        .countdown-timer {
            font-size: 14px;
            color: #888;
            margin-top: 15px;
        }
        
        .countdown-number {
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .copyright {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 12px;
        }
        
        /* Language switcher */
        .language-switcher {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 5px;
            background: rgba(255, 255, 255, 0.15);
            padding: 8px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        
        .lang-btn {
            background: transparent;
            border: none;
            color: white;
            padding: 6px 15px;
            border-radius: 15px;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .lang-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            color: white;
            text-decoration: none;
        }
        
        .lang-btn.active {
            background: rgba(255, 255, 255, 0.3);
            font-weight: 600;
        }
        
        /* Password input with eye icon */
        .password-input-group {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 12px;
            cursor: pointer;
            color: #777;
            z-index: 10;
            background: transparent;
            border: none;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .header-container {
                padding: 20px;
            }
            
            .logo-row {
                gap: 15px;
            }
            
            .uitm-logo {
                border-right: none;
                padding-right: 0;
                border-bottom: 2px solid rgba(255, 255, 255, 0.3);
                padding-bottom: 15px;
                margin-bottom: 15px;
            }
            
            .logo-img {
                height: 50px;
            }
            
            .form-container {
                padding: 25px;
            }
            
            .system-title h1 {
                font-size: 24px;
            }
            
            .system-title p {
                font-size: 14px;
            }
            
            .language-switcher {
                position: relative;
                top: 0;
                right: 0;
                justify-content: center;
                margin-bottom: 15px;
            }
            
            .step-line {
                width: 30px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .header-container {
                padding: 15px;
            }
            
            .form-container {
                padding: 20px;
            }
            
            .logo-img {
                height: 40px;
            }
            
            .form-title h3 {
                font-size: 20px;
            }
            
            .step-line {
                width: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Language Switcher -->
        <div class="language-switcher">
            <a href="?lang=bm" class="lang-btn <?php echo ($lang == 'bm') ? 'active' : ''; ?>">
                <i class="fas fa-language me-1"></i>BM
            </a>
            <a href="?lang=en" class="lang-btn <?php echo ($lang == 'en') ? 'active' : ''; ?>">
                <i class="fas fa-language me-1"></i>EN
            </a>
        </div>
        
        <!-- Header with logos -->
        <div class="header-container">
            <div class="logo-row">
                <!-- UITM Logo -->
                <img src="inc/images/uitm.png" alt="UITM Logo" class="logo-img uitm-logo"
                     onerror="this.onerror=null; this.src='https://upload.wikimedia.org/wikipedia/ms/thumb/d/d7/Logo_UITM.svg/2560px-Logo_UITM.svg.png';">
                
                <!-- NEO V-TRACK Logo -->
                <img src="inc/images/kik2.png" alt="NEO V-TRACK Logo" class="logo-img neo-logo"
                     onerror="this.onerror=null; this.src='https://via.placeholder.com/220x50/003087/FFFFFF?text=NEO+V-TRACK';">
            </div>
            
            <div class="system-title">
                <h1><i class="fas fa-key me-2"></i><?php echo $t['reset_password']; ?></h1>
                <p><?php echo $t['system_name']; ?></p>
            </div>
        </div>
        
        <!-- Form Container -->
        <div class="form-container">
            <div class="form-title">
                <h3><i class="fas fa-lock me-2"></i><?php echo $t['reset_password']; ?></h3>
            </div>
            
            <!-- Step Indicator -->
            <?php if ($step < 3): ?>
            <div class="step-indicator">
                <div class="step <?php echo $step == 1 ? 'active' : ''; ?>">
                    <div class="step-number">1</div>
                    <div class="step-text"><?php echo $lang == 'bm' ? 'Emel' : 'Email'; ?></div>
                </div>
                <div class="step-line"></div>
                <div class="step <?php echo $step == 2 ? 'active' : ''; ?>">
                    <div class="step-number">2</div>
                    <div class="step-text"><?php echo $lang == 'bm' ? 'Kata Laluan' : 'Password'; ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($step == 1): ?>
                <!-- Step 1: Email Entry -->
                <form method="POST" id="step1Form">
                    <input type="hidden" name="step1" value="1">
                    
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-2"></i><?php echo $t['step1_title']; ?>
                        </label>
                        <input type="email" id="email" name="email" 
                               placeholder="<?php echo $t['email_placeholder']; ?>" 
                               class="form-control" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6 mb-3">
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-arrow-right me-2"></i><?php echo $t['submit']; ?>
                            </button>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="<?php echo $login_link; ?>" class="btn-back">
                                <i class="fas fa-arrow-left me-2"></i><?php echo $t['back_to_login']; ?>
                            </a>
                        </div>
                    </div>
                </form>
                
            <?php elseif ($step == 2): ?>
                <!-- Step 2: Password Reset -->
                <form method="POST" id="step2Form">
                    <input type="hidden" name="step2" value="1">
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user me-2"></i><?php echo $lang == 'bm' ? 'Emel:' : 'Email:'; ?>
                        </label>
                        <div class="form-control" style="background-color: #f8f9fa;">
                            <?php echo htmlspecialchars($user_email); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-key me-2"></i><?php echo $t['new_password']; ?> *
                        </label>
                        <div class="password-input-group">
                            <input type="password" id="password" name="password" 
                                   placeholder="<?php echo $t['password_placeholder']; ?>" 
                                   class="form-control" required minlength="6">
                            <button type="button" class="password-toggle" id="togglePassword" 
                                    aria-label="<?php echo $t['show_password']; ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            <?php echo $lang == 'bm' ? 'Kata laluan mesti sekurang-kurangnya 6 aksara.' : 'Password must be at least 6 characters.'; ?>
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-key me-2"></i><?php echo $t['confirm_password']; ?> *
                        </label>
                        <div class="password-input-group">
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   placeholder="<?php echo $t['confirm_placeholder']; ?>" 
                                   class="form-control" required minlength="6">
                            <button type="button" class="password-toggle" id="toggleConfirmPassword" 
                                    aria-label="<?php echo $t['show_password']; ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6 mb-3">
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-save me-2"></i><?php echo $t['reset']; ?>
                            </button>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="<?php echo $login_link; ?>" class="btn-back">
                                <i class="fas fa-arrow-left me-2"></i><?php echo $t['back_to_login']; ?>
                            </a>
                        </div>
                    </div>
                </form>
                
            <?php elseif ($step == 3): ?>
                <!-- Step 3: Success Message -->
                <div class="success-message">
                    <div class="success-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <h3 class="mb-3"><?php echo $t['success_title']; ?></h3>
                    <p class="mb-4"><?php echo $t['reset_success']; ?></p>
                    <p class="text-muted mb-4"><?php echo $t['redirect_message']; ?></p>
                    <div class="countdown-timer mb-4">
                        <?php echo $lang == 'bm' ? 'Mengarahkan dalam: ' : 'Redirecting in: '; ?>
                        <span class="countdown-number" id="redirectCountdown">5</span> 
                        <?php echo $lang == 'bm' ? 'saat' : 'seconds'; ?>
                    </div>
                    <a href="<?php echo $login_link; ?>" class="btn-back" style="width: auto; padding: 12px 30px;">
                        <i class="fas fa-arrow-left me-2"></i><?php echo $t['back_to_login']; ?>
                    </a>
                </div>
                
                <script>
                    // Start countdown for redirect
                    let countdown = 5;
                    const countdownElement = document.getElementById('redirectCountdown');
                    const countdownInterval = setInterval(() => {
                        countdown--;
                        countdownElement.textContent = countdown;
                        
                        if (countdown <= 0) {
                            clearInterval(countdownInterval);
                            window.location.href = '<?php echo $login_link; ?>';
                        }
                    }, 1000);
                </script>
            <?php endif; ?>
            
            <div class="copyright">
                <?php echo $t['copyright']; ?>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Toggle password visibility
        function togglePasswordVisibility(fieldId, toggleId) {
            const passwordField = document.getElementById(fieldId);
            const toggleButton = document.getElementById(toggleId);
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleButton.innerHTML = '<i class="fas fa-eye-slash"></i>';
                toggleButton.setAttribute('aria-label', '<?php echo $t['hide_password']; ?>');
            } else {
                passwordField.type = 'password';
                toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
                toggleButton.setAttribute('aria-label', '<?php echo $t['show_password']; ?>');
            }
        }
        
        // Attach event listeners to toggle buttons
        if (document.getElementById('togglePassword')) {
            document.getElementById('togglePassword').addEventListener('click', function() {
                togglePasswordVisibility('password', 'togglePassword');
            });
        }
        
        if (document.getElementById('toggleConfirmPassword')) {
            document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
                togglePasswordVisibility('confirm_password', 'toggleConfirmPassword');
            });
        }
        
        // Form validation for step 2
        $('#step2Form').on('submit', function(e) {
            let password = $('#password').val();
            let confirmPassword = $('#confirm_password').val();
            
            if (password.length < 6) {
                e.preventDefault();
                alert('<?php echo $t['password_short']; ?>');
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('<?php echo $t['password_mismatch']; ?>');
                return false;
            }
            
            return true;
        });
    });
    </script>
</body>
</html>