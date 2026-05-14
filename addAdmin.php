<?php
session_start();

// ========== LOGOUT HANDLER ==========
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: roleSelection.php');
    exit();
}
// ========== END LOGOUT HANDLER ==========

include('inc/header.php');
include 'connect.php';

if (!isset($_SESSION['email_Admin'])) {
    header('location:loginAdmin.php');
    exit();
}

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
    'page_title' => 'Tambah Admin',
    'system_name' => 'NEO V-TRACK - Sistem Pengurusan & Pemantauan Kenderaan',
    'admin_role' => 'Administrator',
    'logout' => 'Log Keluar',
    'logout_confirm' => 'Adakah anda pasti ingin log keluar?',
    'add_admin' => 'Tambah Admin',
    'back_to_list' => 'Kembali ke Senarai',
    'email' => 'Emel',
    'password' => 'Kata Laluan',
    'admin_name' => 'Nama Admin',
    'save' => 'Simpan',
    'cancel' => 'Batal',
    'email_placeholder' => 'Isi emel',
    'password_placeholder' => 'Isi kata laluan',
    'name_placeholder' => 'Isi nama penuh',
    'registration_success' => 'Admin berjaya didaftar!',
    'email_exists' => 'Emel sudah wujud',
    'copyright' => '© Hak Cipta Universiti Teknologi MARA Cawangan Johor - Polis Bantuan | ICT Security',
    // Navigation items
    'nav_dashboard' => 'Anjung',
    'nav_search' => 'Carian Kenderaan',
    'nav_staff' => 'Staf',
    'nav_student' => 'Pelajar',
    'nav_visitor' => 'Pelawat',
    'nav_contractor' => 'Kontraktor',
    'nav_user_mgmt' => 'Pengguna',
    'nav_admin' => 'Admin'
];

// English
$text['en'] = [
    'page_title' => 'Add Admin',
    'system_name' => 'NEO V-TRACK - Vehicle Management & Monitoring System',
    'admin_role' => 'Administrator',
    'logout' => 'Log Out',
    'logout_confirm' => 'Are you sure you want to log out?',
    'add_admin' => 'Add Admin',
    'back_to_list' => 'Back to List',
    'email' => 'Email',
    'password' => 'Password',
    'admin_name' => 'Admin Name',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'email_placeholder' => 'Enter email',
    'password_placeholder' => 'Enter password',
    'name_placeholder' => 'Enter full name',
    'registration_success' => 'Admin registered successfully!',
    'email_exists' => 'Email already exists',
    'copyright' => '© Copyright Universiti Teknologi MARA Johor Branch - Auxiliary Police | ICT Security',
    // Navigation items
    'nav_dashboard' => 'Dashboard',
    'nav_search' => 'Vehicle Search',
    'nav_staff' => 'Staff',
    'nav_student' => 'Student',
    'nav_visitor' => 'Visitor',
    'nav_contractor' => 'Contractor',
    'nav_user_mgmt' => 'User',
    'nav_admin' => 'Admin'
];

$t = $text[$lang];

// Get admin display name
$admin_email = $_SESSION['email_Admin'];
$admin_display = $admin_email;

$admin_query = @mysqli_query($con, "SELECT name FROM admin WHERE email = '$admin_email'");
if ($admin_query && mysqli_num_rows($admin_query) > 0) {
    $admin_data = mysqli_fetch_assoc($admin_query);
    if (!empty($admin_data['name'])) {
        $admin_display = $admin_data['name'];
    } else {
        $admin_display = strstr($admin_email, '@', true) ?: $admin_email;
    }
}

$output = "";
if (isset($_POST['submit'])) {
    $email = mysqli_real_escape_string($con, $_POST['email_Admin']);
    $password = mysqli_real_escape_string($con, $_POST['password_Admin']);
    $name = mysqli_real_escape_string($con, $_POST['name_Admin']);
    
    $error = array();
    
    // Check if email already exists - CORRECTED COLUMN NAME
    $check_email = mysqli_query($con, "SELECT * FROM admin WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        $error['1'] = $t['email_exists'];
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error['2'] = $lang == 'bm' ? 'Format emel tidak sah' : 'Invalid email format';
    }
    
    // Validate password strength
    if (strlen($password) < 6) {
        $error['3'] = $lang == 'bm' ? 'Kata laluan mesti sekurang-kurangnya 6 aksara' : 'Password must be at least 6 characters';
    }
    
    if (isset($error['1'])) {
        $output .= "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <i class='fas fa-exclamation-circle me-2'></i>" . $error['1'] . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";
    }
    
    if (isset($error['2'])) {
        $output .= "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <i class='fas fa-exclamation-circle me-2'></i>" . $error['2'] . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";
    }
    
    if (isset($error['3'])) {
        $output .= "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <i class='fas fa-exclamation-circle me-2'></i>" . $error['3'] . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";
    }

    if (count($error) < 1) {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // CORRECTED COLUMN NAMES FOR INSERT QUERY
        $sql = "INSERT INTO `admin` (`email`, `password`, `name`)
                VALUES('$email','$hashed_password','$name')";

        $result = mysqli_query($con, $sql);
        if ($result) {
            $_SESSION['success_message'] = $t['registration_success'];
            header('location:admin.php');
            exit();
        } else {
            $output .= "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                        <i class='fas fa-exclamation-circle me-2'></i>" . mysqli_error($con) . "
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                        </div>";
        }
    }
}
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
        
        html, body {
            height: 100%;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        /* Black Navbar - Enhanced visibility */
        .navbar-black {
            background-color: #000000 !important;
            padding: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-bottom: 3px solid var(--uitm-red);
        }
        
        .navbar-black .container {
            max-width: 1400px;
        }
        
        /* Logo container styling - SIMILAR TO USER INTERFACE */
        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px 0;
        }
        
        .uitm-logo, .neo-logo {
            height: 45px;
            width: auto;
            object-fit: contain;
        }
        
        .uitm-logo {
            border-right: 2px solid rgba(255,255,255,0.3);
            padding-right: 15px;
        }
        
        .system-title {
            border-left: 3px solid rgba(255,255,255,0.3);
            padding-left: 15px;
        }
        
        .system-title h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
            color: white;
        }
        
        .system-title p {
            opacity: 0.9;
            font-size: 14px;
            margin: 0;
            color: white;
        }
        
        .navbar-black .navbar-nav {
            flex-direction: row;
            justify-content: center;
            flex-wrap: nowrap;
        }
        
        .navbar-black .nav-item {
            border-right: 1px solid rgba(255,255,255,0.2);
        }
        
        .navbar-black .nav-item:last-child {
            border-right: none;
        }
        
        .navbar-black .nav-link {
            color: white !important;
            padding: 15px 18px !important;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s;
            text-align: center;
            position: relative;
        }
        
        .navbar-black .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        /* Enhanced active state for better visibility */
        .navbar-black .nav-link.active {
            background-color: var(--uitm-red);
            font-weight: 600;
            color: #ffffff !important;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        
        /* Add an indicator for active tab */
        .navbar-black .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            right: 0;
            height: 3px;
            background-color: #ffffff;
            border-radius: 2px 2px 0 0;
        }
        
        /* Main Header */
        .main-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--uitm-blue) 100%);
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.15);
            padding: 8px 15px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .admin-avatar {
            width: 35px;
            height: 35px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        
        .logout-btn {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }
        
        /* Language Switcher */
        .language-switcher {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-right: 15px;
            background: rgba(255,255,255,0.15);
            padding: 5px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .lang-btn {
            background: transparent;
            border: none;
            color: white;
            padding: 5px 15px;
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
        
        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 20px auto 40px auto;
            padding: 0 15px;
            flex: 1;
            width: 100%;
        }
        
        /* Form Container */
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border: 1px solid #eaeaea;
        }
        
        .form-title {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .form-title h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
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
            font-size: 14px;
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
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            text-decoration: none;
            cursor: pointer;
        }
        
        .btn-submit:hover {
            background: linear-gradient(135deg, #00d2b9 0%, var(--success-color) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 176, 155, 0.3);
            color: white;
        }
        
        .btn-cancel {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-cancel:hover {
            background: linear-gradient(135deg, #495057 0%, #343a40 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
            color: white;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .alert {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(255, 65, 108, 0.1) 0%, rgba(255, 107, 136, 0.1) 100%);
            border-left: 4px solid var(--danger-color);
            color: #721c24;
        }
        
        /* Password strength indicator */
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            background: #eee;
            overflow: hidden;
        }
        
        .password-strength-meter {
            height: 100%;
            width: 0;
            transition: all 0.3s;
        }
        
        .strength-weak { background-color: #ff416c; }
        .strength-medium { background-color: #ff9966; }
        .strength-strong { background-color: #00b09b; }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .navbar-black .nav-link {
                padding: 15px 14px !important;
                font-size: 13px;
            }
            
            .uitm-logo, .neo-logo {
                height: 40px;
            }
        }
        
        @media (max-width: 992px) {
            .navbar-black .navbar-nav {
                flex-wrap: wrap;
                justify-content: flex-start;
            }
            
            .navbar-black .nav-item {
                border-right: none;
                width: auto;
                border-bottom: none;
                flex: 1 0 33.33%;
                text-align: center;
            }
            
            .navbar-black .nav-link {
                padding: 12px 8px !important;
                font-size: 12px;
            }
            
            /* Update active indicator for mobile */
            .navbar-black .nav-link.active::after {
                bottom: -1px;
                height: 2px;
            }
            
            .logo-container {
                justify-content: center;
            }
            
            .form-title {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .logo-container {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .system-title {
                border-left: none;
                padding-left: 0;
                border-top: 2px solid rgba(255,255,255,0.3);
                padding-top: 10px;
                width: 100%;
            }
            
            .navbar-black .navbar-nav {
                flex-direction: column;
                width: 100%;
            }
            
            .navbar-black .nav-item {
                width: 100%;
                border-bottom: 1px solid rgba(255,255,255,0.1);
                flex: none;
            }
            
            .navbar-black .nav-item:last-child {
                border-bottom: none;
            }
            
            /* Update active indicator for vertical nav */
            .navbar-black .navbar-nav {
                flex-direction: column;
            }
            
            .navbar-black .nav-link.active::after {
                bottom: 0;
                right: 0;
                left: auto;
                width: 3px;
                height: 100%;
                border-radius: 0 2px 2px 0;
            }
            
            .form-container {
                padding: 20px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-submit, .btn-cancel {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .main-content {
                padding: 0 10px;
            }
            
            .uitm-logo, .neo-logo {
                height: 35px;
            }
            
            .form-container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>

<!-- Black Navigation Bar WITH LOGOS -->
<nav class="navbar navbar-expand-lg navbar-black">
    <div class="container">
        <div class="logo-container">
            <!-- UITM Logo -->
            <img src="inc/images/uitm.png" alt="UITM Logo" class="uitm-logo"
                 onerror="this.onerror=null; this.src='https://upload.wikimedia.org/wikipedia/ms/thumb/d/d7/Logo_UITM.svg/2560px-Logo_UITM.svg.png';">
            
            <!-- NEO V-TRACK Logo -->
            <img src="inc/images/kik2.png" alt="NEO V-TRACK Logo" class="neo-logo"
                 onerror="this.onerror=null; this.src='https://via.placeholder.com/220x50/003087/FFFFFF?text=NEO+V-TRACK';">
            
            <div class="system-title">
                <h1><i class="fas fa-plus-circle"></i> <?php echo $t['page_title']; ?></h1>
                <p><?php echo $t['system_name']; ?></p>
            </div>
        </div>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon" style="filter: invert(1);">☰</span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-home me-1"></i><?php echo $t['nav_dashboard']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="searchCar.php">
                        <i class="fas fa-search me-1"></i><?php echo $t['nav_search']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="staffcar.php">
                        <i class="fas fa-user-tie me-1"></i><?php echo $t['nav_staff']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="studentcar.php">
                        <i class="fas fa-user-graduate me-1"></i><?php echo $t['nav_student']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="Visitorcar.php">
                        <i class="fas fa-user-clock me-1"></i><?php echo $t['nav_visitor']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contractorcar.php">
                        <i class="fas fa-hard-hat me-1"></i><?php echo $t['nav_contractor']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="user.php">
                        <i class="fas fa-users-cog me-1"></i><?php echo $t['nav_user_mgmt']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="admin.php">
                        <i class="fas fa-user-shield me-1"></i><?php echo $t['nav_admin']; ?>
                    </a>
                </li>
                <li class="nav-item">
    <a class="nav-link" href="bulk_import.php">
        <i class="fas fa-file-import me-1"></i>Import Kenderaan
    </a>
</li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Header -->
<div class="main-header">
    <div class="header-content">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="admin-info">
                    <div class="admin-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div>
                        <div style="font-weight: 500; font-size: 14px;"><?php echo htmlspecialchars($admin_display); ?></div>
                        <div style="font-size: 11px; opacity: 0.8;"><?php echo $t['admin_role']; ?></div>
                    </div>
                </div>
                
                <!-- Language Switcher -->
                <div class="language-switcher">
                    <a href="?lang=bm" class="lang-btn <?php echo ($lang == 'bm') ? 'active' : ''; ?>">
                        <i class="fas fa-language me-1"></i>BM
                    </a>
                    <a href="?lang=en" class="lang-btn <?php echo ($lang == 'en') ? 'active' : ''; ?>">
                        <i class="fas fa-language me-1"></i>EN
                    </a>
                </div>
            </div>
            
            <!-- Logout Button -->
            <a href="?logout=1" class="logout-btn" onclick="return confirm('<?php echo $t['logout_confirm']; ?>')">
                <i class="fas fa-sign-out-alt me-2"></i> <?php echo $t['logout']; ?>
            </a>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="form-container">
        <div class="form-title">
            <h3><i class="fas fa-user-plus"></i> <?php echo $t['add_admin']; ?></h3>
            <a href="admin.php" class="btn-cancel">
                <i class="fas fa-arrow-left me-2"></i><?php echo $t['back_to_list']; ?>
            </a>
        </div>
        
        <?php echo $output; ?>
        
        <form method="POST" id="adminForm">
            <div class="mb-4">
                <label for="email_Admin" class="form-label">
                    <i class="fas fa-envelope me-2"></i><?php echo $t['email']; ?>
                </label>
                <input type="email" id="email_Admin" name="email_Admin" 
                       placeholder="<?php echo $t['email_placeholder']; ?>" 
                       class="form-control" required>
            </div>
            
            <div class="mb-4">
                <label for="password_Admin" class="form-label">
                    <i class="fas fa-lock me-2"></i><?php echo $t['password']; ?>
                </label>
                <input type="password" id="password_Admin" name="password_Admin" 
                       placeholder="<?php echo $t['password_placeholder']; ?>" 
                       class="form-control" required minlength="6">
                <div class="password-strength">
                    <div class="password-strength-meter" id="password-strength-meter"></div>
                </div>
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    <?php echo $lang == 'bm' ? 'Kata laluan mesti sekurang-kurangnya 6 aksara' : 'Password must be at least 6 characters'; ?>
                </small>
            </div>
            
            <div class="mb-4">
                <label for="name_Admin" class="form-label">
                    <i class="fas fa-user me-2"></i><?php echo $t['admin_name']; ?>
                </label>
                <input type="text" id="name_Admin" name="name_Admin" 
                       placeholder="<?php echo $t['name_placeholder']; ?>" 
                       class="form-control" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="submit" class="btn-submit">
                    <i class="fas fa-save me-2"></i><?php echo $t['save']; ?>
                </button>
                <a href="admin.php" class="btn-cancel">
                    <i class="fas fa-times me-2"></i><?php echo $t['cancel']; ?>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Password strength indicator
    $('#password_Admin').on('input', function() {
        var password = $(this).val();
        var strength = 0;
        var meter = $('#password-strength-meter');
        
        // Reset meter
        meter.removeClass('strength-weak strength-medium strength-strong');
        meter.css('width', '0%');
        
        if (password.length >= 6) {
            strength += 25;
        }
        if (password.length >= 8) {
            strength += 25;
        }
        if (/[A-Z]/.test(password)) {
            strength += 25;
        }
        if (/[0-9]/.test(password) || /[^A-Za-z0-9]/.test(password)) {
            strength += 25;
        }
        
        // Update meter
        meter.css('width', strength + '%');
        
        if (strength <= 25) {
            meter.addClass('strength-weak');
        } else if (strength <= 50) {
            meter.addClass('strength-medium');
        } else {
            meter.addClass('strength-strong');
        }
    });
    
    // Form validation
    $('#adminForm').on('submit', function() {
        let valid = true;
        
        // Clear previous error styles
        $('.form-control').removeClass('is-invalid');
        
        // Validate each required field
        $(this).find('[required]').each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass('is-invalid');
                valid = false;
            }
        });
        
        // Validate email format
        var email = $('#email_Admin').val().trim();
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            $('#email_Admin').addClass('is-invalid');
            alert('<?php echo $lang == "bm" ? "Format emel tidak sah." : "Invalid email format."; ?>');
            valid = false;
        }
        
        // Validate password strength
        var password = $('#password_Admin').val();
        if (password && password.length < 6) {
            $('#password_Admin').addClass('is-invalid');
            alert('<?php echo $lang == "bm" ? "Kata laluan mesti sekurang-kurangnya 6 aksara." : "Password must be at least 6 characters."; ?>');
            valid = false;
        }
        
        if (!valid) {
            alert('<?php echo $lang == "bm" ? "Sila isi semua maklumat yang diperlukan." : "Please fill in all required information."; ?>');
            return false;
        }
        
        return true;
    });
});
</script>

</body>
</html>