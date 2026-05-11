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
    'page_title' => 'Selamat Datang - NEO V-TRACK',
    'system_name' => 'NEO V-TRACK - Sistem Pengurusan & Pemantauan Kenderaan',
    'tagline' => 'Sistem Pengurusan & Pemantauan Kenderaan',
    'title' => 'Selamat Datang ke NEO V-TRACK',
    'subtitle' => 'Sila pilih peranan anda untuk teruskan',
    'user_title' => 'Pengguna',
    'user_desc' => 'Akses akaun peribadi dan urus data anda',
    'admin_title' => 'Pentadbir',
    'admin_desc' => 'Urus tetapan sistem dan akses kawalan pentadbiran',
    'user_button' => 'Log Masuk Pengguna',
    'admin_button' => 'Log Masuk Pentadbir',
    'copyright' => '© 2026 NEO V-TRACK. Semua hak cipta terpelihara.',
];

// English
$text['en'] = [
    'page_title' => 'Welcome - NEO V-TRACK',
    'system_name' => 'NEO V-TRACK - Vehicle Management & Monitoring System',
    'tagline' => 'Vehicle Management & Monitoring System',
    'title' => 'Welcome to NEO V-TRACK',
    'subtitle' => 'Please select your role to continue',
    'user_title' => 'User',
    'user_desc' => 'Access your personal account and manage your data',
    'admin_title' => 'Administrator',
    'admin_desc' => 'Manage system settings and access administrative controls',
    'user_button' => 'User Login',
    'admin_button' => 'Admin Login',
    'copyright' => '© 2026 NEO V-TRACK. All rights reserved.',
];

$t = $text[$lang];

include('inc/header.php');

// Build links with language parameter
$user_login_link = "login.php" . (isset($_SESSION['language']) ? "?lang=" . $_SESSION['language'] : "");
$admin_login_link = "loginAdmin.php" . (isset($_SESSION['language']) ? "?lang=" . $_SESSION['language'] : "");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['page_title']; ?></title>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body.role-bg {
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background: url('inc/images/neon-purple-bg.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            color: #fff;
            padding-bottom: 80px; 
        }

        body.role-bg::before {
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

        .role-card {
            width: 420px;
            background: #fff;
            color: #000;
            margin: 40px auto;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            text-align: center;
        }

        .role-card img.neo {
            width: 100px;
            margin-bottom: 15px;
        }

        .role-header h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 8px;
        }

        .role-header p {
            color: #666;
            font-size: 14px;
            margin-bottom: 25px;
            opacity: 0.8;
        }

        .role-options {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin: 30px 0;
        }

        .role-btn {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 22px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            display: flex;
            align-items: center;
            gap: 20px;
            text-decoration: none;
            color: #333;
        }

        .role-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.15);
        }

        .role-btn.user-btn:hover {
            border-color: #6a0dad;
        }

        .role-btn.admin-btn:hover {
            border-color: #1c1c1c;
        }

        .role-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .user-btn .role-icon {
            background: #6a0dad;
            color: white;
        }

        .admin-btn .role-icon {
            background: #1c1c1c;
            color: #FFD700;
        }

        .role-info {
            text-align: left;
            flex: 1;
        }

        .role-info h3 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 18px;
        }

        .role-info p {
            margin: 0;
            color: #666;
            font-size: 13px;
            line-height: 1.5;
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

        .back-link {
            margin-top: 25px;
            color: #6a0dad;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #8e24aa;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .role-card {
                width: 90%;
                margin: 20px auto;
                padding: 25px;
            }
            
            .top-logo img {
                width: 150px;
            }
            
            .role-btn {
                padding: 18px 15px;
            }
            
            .role-icon {
                width: 45px;
                height: 45px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body class="role-bg">

    <!-- Language Selector -->
    <select id="langSelect" class="lang-select">
        <option value="bm" <?php echo ($lang == 'bm') ? 'selected' : ''; ?>>BM</option>
        <option value="en" <?php echo ($lang == 'en') ? 'selected' : ''; ?>>EN</option>
    </select>

    <div class="top-logo">
        <img src="inc/images/uitm.png" alt="UITM Logo">
    </div>

    <div class="role-card">
        <img src="inc/images/kik2.png" alt="NEO V-TRACK Logo" class="neo">
        
        <div class="role-header">
            <h1><?php echo $t['title']; ?></h1>
            <p><?php echo $t['subtitle']; ?></p>
        </div>

        <div class="role-options">
            <a href="<?php echo $user_login_link; ?>" class="role-btn user-btn">
                <div class="role-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="role-info">
                    <h3><?php echo $t['user_title']; ?></h3>
                    <p><?php echo $t['user_desc']; ?></p>
                </div>
                <div>
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>

            <a href="<?php echo $admin_login_link; ?>" class="role-btn admin-btn">
                <div class="role-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="role-info">
                    <h3><?php echo $t['admin_title']; ?> <span class="admin-badge">ADMIN</span></h3>
                    <p><?php echo $t['admin_desc']; ?></p>
                </div>
                <div>
                    <i class="fas fa-chevron-right"></i>
                </div>
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

        // Add loading animation for better UX
        document.querySelectorAll('.role-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                // Add loading effect
                const icon = this.querySelector('.role-icon i');
                const originalIcon = icon.className;
                icon.className = 'fas fa-spinner fa-spin';
                
                // Revert icon after delay if page doesn't redirect
                setTimeout(() => {
                    icon.className = originalIcon;
                }, 1000);
            });
        });
    </script>

</body>
</html>