<?php
session_start();

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
    'page_title' => 'Pendaftaran Pengguna Baru',
    'system_name' => 'NEO V-TRACK - Sistem Pengurusan & Pemantauan Kenderaan',
    'tagline' => 'Sistem Pengurusan & Pemantauan Kenderaan',
    'name_placeholder' => 'Nama Penuh',
    'email_placeholder' => 'Emel',
    'password_placeholder' => 'Kata Laluan',
    'confirm_password' => 'Sahkan Kata Laluan',
    'register_button' => 'Daftar',
    'back_to_login' => 'Kembali ke Log Masuk',
    'copyright' => '© 2026 NEO V-TRACK. Semua hak cipta terpelihara.',
    'registration_success' => 'Pendaftaran berjaya! Sila log masuk dengan emel dan kata laluan anda.',
    'registration_error' => 'Ralat pendaftaran. Sila cuba lagi atau hubungi pentadbir.',
    'email_exists' => 'Emel ini sudah didaftarkan. Sila gunakan emel lain.',
    'passwords_mismatch' => 'Kata laluan tidak sepadan!',
    'password_requirements' => 'Kata laluan mesti sekurang-kurangnya 6 aksara',
    'fill_all_fields' => 'Sila isi semua ruangan!'
];

// English
$text['en'] = [
    'page_title' => 'New User Registration',
    'system_name' => 'NEO V-TRACK - Vehicle Management & Monitoring System',
    'tagline' => 'Vehicle Management & Monitoring System',
    'name_placeholder' => 'Full Name',
    'email_placeholder' => 'Email',
    'password_placeholder' => 'Password',
    'confirm_password' => 'Confirm Password',
    'register_button' => 'Register',
    'back_to_login' => 'Back to Login',
    'copyright' => '© 2026 NEO V-TRACK. All rights reserved.',
    'registration_success' => 'Registration successful! Please login with your email and password.',
    'registration_error' => 'Registration error. Please try again or contact administrator.',
    'email_exists' => 'Email already registered. Please use another email.',
    'passwords_mismatch' => 'Passwords do not match!',
    'password_requirements' => 'Password must be at least 6 characters',
    'fill_all_fields' => 'Please fill in all fields!'
];

$t = $text[$lang];

include($_SERVER['DOCUMENT_ROOT'].'/includes/header.php');

$message = "";
$message_type = ""; // 'success' or 'error'

// Handle Registration
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
    
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($con, $_POST['confirm_password']);
    
    // Validate all fields are filled
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = $t['fill_all_fields'];
        $message_type = 'error';
    }
    // Validate password length
    elseif (strlen($password) < 6) {
        $message = $t['password_requirements'];
        $message_type = 'error';
    } 
    // Check if passwords match
    elseif ($password !== $confirm_password) {
        $message = $t['passwords_mismatch'];
        $message_type = 'error';
    } 
    // Check if email already exists
    else {
        $check_email = mysqli_query($con, "SELECT * FROM user WHERE email='$email'");
        
        if (mysqli_num_rows($check_email) > 0) {
            $message = $t['email_exists'];
            $message_type = 'error';
        } else {
            // Insert new user
            $sql = "INSERT INTO user (name, email, password) VALUES ('$name', '$email', '$password')";
            
            if (mysqli_query($con, $sql)) {
                $message = $t['registration_success'];
                $message_type = 'success';
            } else {
                $message = $t['registration_error'];
                $message_type = 'error';
            }
        }
    }
}

// Build back to login link with language parameter
$login_link = "/auth/login.php" . (isset($_SESSION['language']) ? "?lang=" . $_SESSION['language'] : "");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['page_title']; ?> - NEO V-TRACK</title>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body.user-bg {
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background: url('/assets/images/neon-purple-bg.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            color: #fff;
            padding-bottom: 60px; /* Add padding for footer */
        }

        body.user-bg::before {
            content: "";
            position: fixed;
            inset: 0;
            background-color: rgba(0,0,0,0.2);
            z-index: -1;
        }

        .top-logo {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
        }

        .top-logo img {
            width: 180px;
        }

        .register-container {
            min-height: calc(100vh - 200px); /* Ensure enough height */
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .register-card {
            width: 420px; /* Slightly wider */
            background: #fff;
            color: #000;
            margin: 30px auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
            text-align: center;
        }

        .register-card img.neo {
            width: 90px;
            margin-bottom: 10px;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            margin-bottom: 16px;
            border-radius: 6px;
            border: 1px solid #ccc;
            color: #000;
            box-sizing: border-box;
            font-size: 14px;
        }

        input::placeholder {
            color: #888;
        }

        .btn-main {
            background: #6a0dad;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            margin-bottom: 15px; /* Space before back button */
            font-size: 16px;
            transition: background 0.3s;
        }

        .btn-main:hover {
            background: #8e24aa;
        }

        .btn-secondary {
            background: #757575;
            color: white;
            border: none;
            padding: 10px;
            width: 100%;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .btn-secondary:hover {
            background: #616161;
        }

        .footer-text {
            text-align: center;
            padding: 12px;
            font-size: 14px;
            font-weight: bold;
            color: #FFD700;
            width: 100%;
            position: relative; /* Changed from fixed to relative */
            bottom: 0;
        }

        .lang-select {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 5px 10px;
            font-size: 14px;
            z-index: 1000;
        }
        
        .lang-select:focus {
            outline: none;
            border-color: #6a0dad;
        }
        
        .error-message {
            color: #ff0000;
            background-color: #ffe6e6;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #ff0000;
            font-size: 14px;
            text-align: left;
        }
        
        .success-message {
            color: #155724;
            background-color: #d4edda;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            font-size: 14px;
            text-align: left;
        }
        
        .password-hint {
            font-size: 12px;
            color: #666;
            text-align: left;
            margin-top: -10px;
            margin-bottom: 15px;
            padding-left: 5px;
        }
        
        .form-header {
            margin-bottom: 25px;
        }
        
        .form-header h2 {
            margin: 5px 0;
            color: #333;
            font-size: 24px;
        }
        
        .form-header p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        
        .button-container {
            margin-top: 10px;
        }
        
        @media (max-width: 480px) {
            .register-card {
                width: 90%;
                margin: 20px auto;
                padding: 20px;
            }
            
            .top-logo img {
                width: 150px;
            }
            
            .register-container {
                min-height: calc(100vh - 150px);
            }
            
            .form-header h2 {
                font-size: 20px;
            }
        }
        
        @media (max-height: 700px) {
            .register-card {
                margin: 20px auto;
                padding: 20px;
            }
            
            .form-group {
                margin-bottom: 12px;
            }
            
            input {
                padding: 10px;
                margin-bottom: 12px;
            }
        }
    </style>
</head>
<body class="user-bg">

    <!-- Language Selector -->
    <select id="langSelect" class="lang-select">
        <option value="bm" <?php echo ($lang == 'bm') ? 'selected' : ''; ?>>BM</option>
        <option value="en" <?php echo ($lang == 'en') ? 'selected' : ''; ?>>EN</option>
    </select>

    <div class="register-container">
        <div class="top-logo">
            <img src="/assets/images/uitm.png" alt="UITM Logo">
        </div>

        <div class="register-card">
            <img src="/assets/images/kik2.png" alt="NEO V-TRACK Logo" class="neo">
            <div class="form-header">
                <h2><?php echo $t['page_title']; ?></h2>
                <p><?php echo $t['system_name']; ?></p>
            </div>

            <?php 
            // Show any messages
            if ($message): 
                $message_class = ($message_type == 'success') ? 'success-message' : 'error-message';
                $message_icon = ($message_type == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle';
            ?>
                <div class="<?php echo $message_class; ?>">
                    <i class="fas <?php echo $message_icon; ?>"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> <?php echo $t['name_placeholder']; ?></label>
                    <input type="text" id="name" name="name" placeholder="<?php echo $t['name_placeholder']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> <?php echo $t['email_placeholder']; ?></label>
                    <input type="email" id="email" name="email" placeholder="<?php echo $t['email_placeholder']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> <?php echo $t['password_placeholder']; ?></label>
                    <input type="password" id="password" name="password" placeholder="<?php echo $t['password_placeholder']; ?>" required>
                    <div class="password-hint">
                        <i class="fas fa-info-circle"></i> <?php echo $t['password_requirements']; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> <?php echo $t['confirm_password']; ?></label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="<?php echo $t['confirm_password']; ?>" required>
                </div>
                
                <button type="submit" class="btn-main">
                    <i class="fas fa-user-plus"></i> <?php echo $t['register_button']; ?>
                </button>
            </form>

            <div class="button-container">
                <a href="<?php echo $login_link; ?>" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> <?php echo $t['back_to_login']; ?>
                </a>
            </div>
        </div>
    </div>

    <div class="footer-text">
        <?php echo $t['copyright']; ?>
    </div>

    <script>
        // Language switcher
        document.getElementById('langSelect').addEventListener('change', function() {
            const lang = this.value;
            window.location.href = '/auth/register.php?lang=' + lang;
        });
        
        // Password validation
        document.addEventListener('DOMContentLoaded', function() {
            const passwordField = document.getElementById('password');
            const confirmField = document.getElementById('confirm_password');
            
            if (passwordField && confirmField) {
                // Check password match on confirmation field change
                confirmField.addEventListener('input', function() {
                    if (passwordField.value !== confirmField.value) {
                        confirmField.style.borderColor = '#ff0000';
                        confirmField.style.borderWidth = '2px';
                    } else {
                        confirmField.style.borderColor = '#28a745';
                        confirmField.style.borderWidth = '2px';
                    }
                });
                
                // Also check when password field changes
                passwordField.addEventListener('input', function() {
                    if (passwordField.value !== confirmField.value && confirmField.value !== '') {
                        confirmField.style.borderColor = '#ff0000';
                        confirmField.style.borderWidth = '2px';
                    } else if (confirmField.value !== '') {
                        confirmField.style.borderColor = '#28a745';
                        confirmField.style.borderWidth = '2px';
                    }
                });
            }
            
            // Auto-scroll to top on page load for better visibility
            window.scrollTo(0, 0);
        });
        
        // Ensure form is visible on small screens
        function ensureFormVisibility() {
            const form = document.querySelector('.register-card');
            const viewportHeight = window.innerHeight;
            const formRect = form.getBoundingClientRect();
            
            if (formRect.bottom > viewportHeight) {
                window.scrollTo(0, formRect.top - 20);
            }
        }
        
        // Run on load and resize
        window.addEventListener('load', ensureFormVisibility);
        window.addEventListener('resize', ensureFormVisibility);
    </script>

</body>
</html>