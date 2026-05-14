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
    'page_title' => 'Log Masuk Pentadbir',
    'system_name' => 'NEO V-TRACK - Sistem Pengurusan & Pemantauan Kenderaan',
    'tagline' => 'Akses Pentadbir Sistem',
    'email_placeholder' => 'Emel Admin',
    'password_placeholder' => 'Kata Laluan',
    'login_button' => 'Log Masuk Admin',
    'forgot_password' => 'Lupa Kata Laluan? Klik di sini',
    'switch_to_user' => 'Log masuk sebagai pengguna',
    'copyright' => '© 2026 NEO V-TRACK. Semua hak cipta terpelihara.',
    'invalid_credentials' => 'EMEL ATAU KATA LALUAN YANG TIDAK SAH!'
];

// English
$text['en'] = [
    'page_title' => 'Admin Login',
    'system_name' => 'NEO V-TRACK - Vehicle Management & Monitoring System',
    'tagline' => 'Admin System Access',
    'email_placeholder' => 'Admin Email',
    'password_placeholder' => 'Password',
    'login_button' => 'Admin Login',
    'forgot_password' => 'Forgot Password? Click here',
    'switch_to_user' => 'Login as user',
    'copyright' => '© 2026 NEO V-TRACK. All rights reserved.',
    'invalid_credentials' => 'INVALID EMAIL OR PASSWORD!'
];

$t = $text[$lang];

include($_SERVER['DOCUMENT_ROOT'].'/includes/header.php');

$invalid = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
    $email = $_POST['email_Admin'];
    $password = $_POST['password_Admin'];

    $sql = "SELECT * FROM admin WHERE email='$email' AND password='$password'";
    $result = mysqli_query($con, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $_SESSION['email_Admin'] = $email;
        header("Location: index.php");
        $_SESSION['user_type'] = 'admin';

// Track session
$session_id = session_id();
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// Get admin ID
$admin_id_query = mysqli_query($con, "SELECT userid FROM admin WHERE email = '$email'");
$admin_id_data = mysqli_fetch_assoc($admin_id_query);
$admin_id = $admin_id_data['userid'] ?? NULL;

// Update last_login in admin table
mysqli_query($con, "UPDATE admin SET last_login = NOW() WHERE email = '$email'");

// Insert or update session
$session_query = "INSERT INTO user_sessions (user_id, session_id, email, user_type, login_time, last_activity, ip_address, user_agent) 
                  VALUES ('$admin_id', '$session_id', '$email', 'admin', NOW(), NOW(), '$ip_address', '$user_agent')
                  ON DUPLICATE KEY UPDATE last_activity = NOW()";
mysqli_query($con, $session_query);
        exit();
    } else {
        $invalid = $t['invalid_credentials'];
    }
}

// Build forgot password link with language parameter
$forgot_link = "/auth/forgot_password_admin.php" . (isset($_SESSION['language']) ? "?lang=" . $_SESSION['language'] : "");
// Build user login link with language parameter
$user_login_link = "/auth/login.php" . (isset($_SESSION['language']) ? "?lang=" . $_SESSION['language'] : "");
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
        body.admin-bg {
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background: url('/assets/images/neon-purple-bg.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            color: #fff;
        }

        body.admin-bg::before {
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
            background: #1c1c1c;
            color: #fff;
            margin: 40px auto;
            padding: 28px;
            border-radius: 12px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.4);
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
            border: none;
            color: #000;
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
        }

        .btn-main:hover {
            background: #8e24aa;
        }

        .small-links {
            margin-top: 12px;
            font-size: 13px;
        }

        .small-links a {
            color: #FFD700;
            text-decoration: none;
            display: block;
            margin-top: 6px;
        }

        .small-links a:hover {
            text-decoration: underline;
        }

        .forgot-link {
            color: #FFD700;
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
            color: #ff6b6b;
            background-color: rgba(255, 107, 107, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #ff6b6b;
            font-size: 14px;
        }
        
        .admin-badge {
            display: inline-block;
            background: #FFD700;
            color: #000;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 5px;
        }
    </style>
</head>
<body class="admin-bg">

    <!-- Language Selector -->
    <select id="langSelect" class="lang-select">
        <option value="bm" <?php echo ($lang == 'bm') ? 'selected' : ''; ?>>BM</option>
        <option value="en" <?php echo ($lang == 'en') ? 'selected' : ''; ?>>EN</option>
    </select>

    <div class="top-logo">
        <img src="/assets/images/uitm.png" alt="UITM Logo">
    </div>

    <div class="login-card">
        <img src="/assets/images/kik2.png" alt="NEO V-TRACK Logo" class="neo">
        <h2>NEO V-TRACK <span class="admin-badge">ADMIN</span></h2>
        <p><?php echo $t['tagline']; ?></p>

        <?php if ($invalid) { ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $invalid; ?>
            </div>
        <?php } ?>

        <form method="POST">
            <input type="email" name="email_Admin" placeholder="<?php echo $t['email_placeholder']; ?>" required>
            <input type="password" name="password_Admin" placeholder="<?php echo $t['password_placeholder']; ?>" required>
            <button type="submit" class="btn-main">
                <i class="fas fa-sign-in-alt"></i> <?php echo $t['login_button']; ?>
            </button>
        </form>

        <a href="<?php echo $forgot_link; ?>" class="forgot-link">
            <i class="fas fa-question-circle"></i> <?php echo $t['forgot_password']; ?>
        </a>

        <div class="small-links">
            <a href="<?php echo $user_login_link; ?>">
                <i class="fas fa-user"></i> <?php echo $t['switch_to_user']; ?>
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


