<?php
session_start();

// ========== LOGOUT HANDLER ==========
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /auth/role_selection.php');
    exit();
}
// ========== END LOGOUT HANDLER ==========

include($_SERVER['DOCUMENT_ROOT'].'/includes/header.php');
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

if (!isset($_SESSION['email_Admin'])) {
    header('location:/auth/login_admin.php');
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
    'page_title' => 'Kemaskini Pengguna',
    'system_name' => 'NEO V-TRACK - Sistem Pengurusan & Pemantauan Kenderaan',
    'admin_role' => 'Administrator',
    'logout' => 'Log Keluar',
    'logout_confirm' => 'Adakah anda pasti ingin log keluar?',
    'update_user' => 'Kemaskini Pengguna',
    'back_to_list' => 'Kembali ke Senarai',
    'email' => 'Emel',
    'password' => 'Kata Laluan',
    'name' => 'Nama',
    'save' => 'Simpan',
    'cancel' => 'Batal',
    'email_placeholder' => 'Isi emel',
    'password_placeholder' => 'Isi kata laluan',
    'name_placeholder' => 'Isi nama penuh',
    'update_success' => 'Pengguna berjaya dikemaskini!',
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
    'page_title' => 'Update User',
    'system_name' => 'NEO V-TRACK - Vehicle Management & Monitoring System',
    'admin_role' => 'Administrator',
    'logout' => 'Log Out',
    'logout_confirm' => 'Are you sure you want to log out?',
    'update_user' => 'Update User',
    'back_to_list' => 'Back to List',
    'email' => 'Email',
    'password' => 'Password',
    'name' => 'Name',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'email_placeholder' => 'Enter email',
    'password_placeholder' => 'Enter password',
    'name_placeholder' => 'Enter full name',
    'update_success' => 'User updated successfully!',
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

// ========== SIMPLIFIED LOGO LOADING ==========
// Use direct paths like in the dashboard example
$uitm_logo_src = "/assets/images/uitm.png";
$neovtrack_logo_src = "/assets/images/kik2.png";
// ========== END LOGO LOADING ==========

// Handle form submission
$output = "";
if (isset($_POST['submit'])) {
    $id = $_GET['id'];
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $name = mysqli_real_escape_string($con, $_POST['name']);

    $sql = "UPDATE `user` SET email='$email', password='$password', name='$name' WHERE userid=$id";

    $result = mysqli_query($con, $sql);
    if ($result) {
        $_SESSION['success_message'] = $t['update_success'];
        header('location:/admin/users.php');
        exit();
    } else {
        $output .= "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <i class='fas fa-exclamation-circle me-2'></i>" . mysqli_error($con) . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";
    }
}

// Get current user data
$current_email = $current_password = $current_name = "";
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM `user` WHERE userid=$id";
    $result = mysqli_query($con, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $current_email = htmlspecialchars($row['email']);
        $current_password = htmlspecialchars($row['password']);
        $current_name = htmlspecialchars($row['name']);
    } else {
        header('location:/admin/users.php');
        exit();
    }
} else {
    header('location:/admin/users.php');
    exit();
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
        
        /* Logo container styling - SIMILAR TO DASHBOARD */
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

<!-- Black Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-black">
    <div class="container">
        <div class="logo-container">
            <!-- UITM Logo -->
            <img src="<?php echo $uitm_logo_src; ?>" alt="UITM Logo" class="uitm-logo">
            
            <!-- NEO V-TRACK Logo -->
            <img src="<?php echo $neovtrack_logo_src; ?>" alt="NEO V-TRACK Logo" class="neo-logo">
            
            <div class="system-title">
                <h1><i class="fas fa-user-edit"></i> <?php echo $t['page_title']; ?></h1>
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
                    <a class="nav-link" href="/search/car_admin.php">
                        <i class="fas fa-search me-1"></i><?php echo $t['nav_search']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/vehicles/staff/list.php">
                        <i class="fas fa-user-tie me-1"></i><?php echo $t['nav_staff']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/vehicles/student/list.php">
                        <i class="fas fa-user-graduate me-1"></i><?php echo $t['nav_student']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/vehicles/visitor/list.php">
                        <i class="fas fa-user-clock me-1"></i><?php echo $t['nav_visitor']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/vehicles/contractor/list.php">
                        <i class="fas fa-hard-hat me-1"></i><?php echo $t['nav_contractor']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="/admin/users.php">
                        <i class="fas fa-users-cog me-1"></i><?php echo $t['nav_user_mgmt']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/dashboard.php">
                        <i class="fas fa-user-shield me-1"></i><?php echo $t['nav_admin']; ?>
                    </a>
                </li>
                <li class="nav-item">
    <a class="nav-link" href="/admin/bulk_import.php">
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
            <h3><i class="fas fa-user-edit"></i> <?php echo $t['update_user']; ?></h3>
            <a href="/admin/users.php" class="btn-cancel">
                <i class="fas fa-arrow-left me-2"></i><?php echo $t['back_to_list']; ?>
            </a>
        </div>
        
        <?php echo $output; ?>
        
        <form method="POST" id="userUpdateForm">
            <div class="mb-4">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope me-2"></i><?php echo $t['email']; ?> *
                </label>
                <input type="email" id="email" name="email" 
                       placeholder="<?php echo $t['email_placeholder']; ?>" 
                       class="form-control" required
                       value="<?php echo $current_email; ?>">
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="fas fa-key me-2"></i><?php echo $t['password']; ?> *
                </label>
                <input type="text" id="password" name="password" 
                       placeholder="<?php echo $t['password_placeholder']; ?>" 
                       class="form-control" required
                       value="<?php echo $current_password; ?>">
            </div>
            
            <div class="mb-4">
                <label for="name" class="form-label">
                    <i class="fas fa-user me-2"></i><?php echo $t['name']; ?> *
                </label>
                <input type="text" id="name" name="name" 
                       placeholder="<?php echo $t['name_placeholder']; ?>" 
                       class="form-control" required
                       value="<?php echo $current_name; ?>">
            </div>
            
            <div class="form-actions">
                <button type="submit" name="submit" class="btn-submit">
                    <i class="fas fa-save me-2"></i><?php echo $t['save']; ?>
                </button>
                <a href="/admin/users.php" class="btn-cancel">
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
    // Form validation
    $('#userUpdateForm').on('submit', function() {
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
        var email = $('#email').val().trim();
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            $('#email').addClass('is-invalid');
            alert('<?php echo $lang == "bm" ? "Format emel tidak sah." : "Invalid email format."; ?>');
            valid = false;
        }
        
        // Validate password length (at least 6 characters)
        var password = $('#password').val();
        if (password && password.length < 6) {
            $('#password').addClass('is-invalid');
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