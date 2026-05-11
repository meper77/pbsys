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
    'page_title' => 'Log Masuk Pengguna',
    'system_name' => 'NEO V-TRACK - Sistem Pengurusan & Pemantauan Kenderaan',
    'tagline' => 'Sistem Pengurusan & Pemantauan Kenderaan',
    'email_placeholder' => 'Emel',
    'password_placeholder' => 'Kata Laluan',
    'login_button' => 'Log Masuk',
    'forgot_password' => 'Lupa Kata Laluan? Klik di sini',
    'switch_to_admin' => 'Log masuk sebagai admin',
    'copyright' => '© 2026 NEO V-TRACK. Semua hak cipta terpelihara.',
    'invalid_credentials' => 'EMEL ATAU KATA LALUAN YANG TIDAK SAH!',
    'new_user_question' => 'Pengguna baru?',
    'register_here' => 'Daftar di sini'
];

// English
$text['en'] = [
    'page_title' => 'User Login',
    'system_name' => 'NEO V-TRACK - Vehicle Management & Monitoring System',
    'tagline' => 'Vehicle Management & Monitoring System',
    'email_placeholder' => 'Email',
    'password_placeholder' => 'Password',
    'login_button' => 'Login',
    'forgot_password' => 'Forgot Password? Click here',
    'switch_to_admin' => 'Login as admin',
    'copyright' => '© 2026 NEO V-TRACK. All rights reserved.',
    'invalid_credentials' => 'INVALID EMAIL OR PASSWORD!',
    'new_user_question' => 'New user?',
    'register_here' => 'Register here'
];

$t = $text[$lang];

include('inc/header.php');

$invalid = "";

// Handle Login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'connect.php';
    
    // Basic security - prevent SQL injection
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);

    $sql = "SELECT * FROM user WHERE email='$email' AND password='$password'";
    $result = mysqli_query($con, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $user_data = mysqli_fetch_assoc($result);
        
        // Set session variables for login
        $_SESSION['email'] = $email;
        $_SESSION['nama'] = $user_data['name'] ?? $email;
        $_SESSION['user_type'] = 'user';
        $_SESSION['userid'] = $user_data['userid'] ?? null;

        // Optional: Update last_login (keep if you want)
        mysqli_query($con, "UPDATE user SET last_login = NOW() WHERE email = '$email'");

        header("Location: indexUser.php");
        exit();
    } else {
        $invalid = $t['invalid_credentials'];
    }
}

// Build links with language parameter
$lang_param = isset($_SESSION['language']) ? "?lang=" . $_SESSION['language'] : "";
$forgot_link = "forgotPassword.php" . $lang_param;
$admin_login_link = "loginAdmin.php" . $lang_param;
$register_link = "register.php" . $lang_param;
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
            background: url('inc/images/neon-purple-bg.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            color: #fff;
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
        }

        .top-logo img {
            width: 180px;
        }

        .login-card {
            width: 380px;
            background: #fff;
            color: #000;
            margin: 40px auto;
            padding: 28px;
            border-radius: 12px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
            text-align: center;
        }

        .login-card img.neo {
            width: 90px;
            margin-bottom: 10px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            margin-bottom: 16px;
            border-radius: 6px;
            border: 1px solid #ccc;
            color: #000;
            box-sizing: border-box;
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
        }

        .btn-main:hover {
            background: #8e24aa;
        }

        .small-links {
            margin-top: 12px;
            font-size: 13px;
        }

        .small-links a {
            color: #6a0dad;
            text-decoration: none;
            display: block;
            margin-top: 6px;
        }

        .small-links a:hover {
            text-decoration: underline;
        }

        .forgot-link {
            color: #6a0dad;
            text-decoration: none;
            font-size: 13px;
            display: block;
            margin-top: 10px;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .footer-text {
            text-align: center;
            padding: 12px;
            font-size: 14px;
            font-weight: bold;
            color: #FFD700;
            position: fixed;
            bottom: 0;
            width: 100%;
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
        }
        
        .lang-select:focus {
            outline: none;
            border-color: #6a0dad;
        }
        
        .error-message {
            color: #ff0000;
            background-color: #ffe6e6;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #ff0000;
            font-size: 14px;
            text-align: left;
        }
        
        .form-header {
            margin-bottom: 20px;
        }
        
        .form-header h2 {
            margin: 5px 0;
            color: #333;
        }
        
        .form-header p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        
        @media (max-width: 480px) {
            .login-card {
                width: 90%;
                margin: 20px auto;
                padding: 20px;
            }
            
            .top-logo img {
                width: 150px;
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

    <div class="top-logo">
        <img src="inc/images/uitm.png" alt="UITM Logo">
    </div>

    <div class="login-card">
        <img src="inc/images/kik2.png" alt="NEO V-TRACK Logo" class="neo">
        <div class="form-header">
            <h2>NEO V-TRACK</h2>
            <p><?php echo $t['tagline']; ?></p>
        </div>

        <?php if ($invalid) { ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $invalid; ?>
            </div>
        <?php } ?>

        <form method="POST">
            <input type="email" name="email" placeholder="<?php echo $t['email_placeholder']; ?>" required>
            <input type="password" name="password" placeholder="<?php echo $t['password_placeholder']; ?>" required>
            <button type="submit" class="btn-main">
                <i class="fas fa-sign-in-alt"></i> <?php echo $t['login_button']; ?>
            </button>
        </form>

        <a href="<?php echo $forgot_link; ?>" class="forgot-link">
            <i class="fas fa-question-circle"></i> <?php echo $t['forgot_password']; ?>
        </a>

        <div class="small-links">
            <a href="<?php echo $register_link; ?>">
                <i class="fas fa-user-plus"></i> <?php echo $t['new_user_question']; ?> <strong><?php echo $t['register_here']; ?></strong>
            </a>
            <a href="<?php echo $admin_login_link; ?>">
                <i class="fas fa-user-shield"></i> <?php echo $t['switch_to_admin']; ?>
            </a>
        </div>
    </div>

    <div class="footer-text">
        <?php echo $t['copyright']; ?>
    </div>

    <script>
        // Language switcher
        document.getElementById('langSelect').addEventListener('change', function() {
            const lang = this.value;
            window.location.href = '?lang=' + lang;
        });
    </script>

</body>
</html>

