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
    'page_title' => 'Pengurusan Pengguna',
    'system_name' => 'NEO V-TRACK - Sistem Pengurusan & Pemantauan Kenderaan',
    'admin_role' => 'Administrator',
    'logout' => 'Log Keluar',
    'logout_confirm' => 'Adakah anda pasti ingin log keluar?',
    'users_list' => 'Senarai Pengguna',
    'add_user' => 'Tambah Pengguna',
    'email' => 'Email',
    'password' => 'Kata Laluan',
    'user_name' => 'Nama Pengguna',
    'action' => 'Tindakan',
    'no' => 'No.',
    'edit' => 'Kemaskini',
    'delete' => 'Padam',
    'export_excel' => 'Eksport ke Excel',
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
    'page_title' => 'User Management',
    'system_name' => 'NEO V-TRACK - Vehicle Management & Monitoring System',
    'admin_role' => 'Administrator',
    'logout' => 'Log Out',
    'logout_confirm' => 'Are you sure you want to log out?',
    'users_list' => 'Users List',
    'add_user' => 'Add User',
    'email' => 'Email',
    'password' => 'Password',
    'user_name' => 'User Name',
    'action' => 'Action',
    'no' => 'No.',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'export_excel' => 'Export to Excel',
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

// Get users data
$users = [];
$no = 1;
$sql = "SELECT * FROM `user`";
$result = mysqli_query($con, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
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
    <!-- DataTables CSS from CDN -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" />
    
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
        
        /* Content Container */
        .content-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border: 1px solid #eaeaea;
            min-height: 60vh;
        }
        
        .content-title {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .add-btn {
            background: linear-gradient(135deg, var(--success-color) 0%, #00d2b9 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .add-btn:hover {
            background: linear-gradient(135deg, #00d2b9 0%, var(--success-color) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 176, 155, 0.3);
            text-decoration: none;
            color: white;
        }
        
        .export-btn {
            background: linear-gradient(135deg, var(--warning-color) 0%, #ff9966 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            margin-bottom: 15px;
        }
        
        .export-btn:hover {
            background: linear-gradient(135deg, #ff9966 0%, var(--warning-color) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 153, 102, 0.3);
        }
        
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table thead {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--uitm-blue) 100%);
            color: white;
        }
        
        .data-table th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            border: none;
            white-space: nowrap;
        }
        
        .data-table tbody tr {
            border-bottom: 1px solid #eee;
            transition: all 0.3s;
        }
        
        .data-table tbody tr:hover {
            background-color: #f8f9ff;
        }
        
        .data-table td {
            padding: 12px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        
        .edit-btn {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--uitm-blue) 100%);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 12px;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .edit-btn:hover {
            background: linear-gradient(135deg, var(--uitm-blue) 0%, var(--primary-color) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(26, 41, 128, 0.3);
            text-decoration: none;
        }
        
        .delete-btn {
            background: linear-gradient(135deg, var(--danger-color) 0%, #ff6b88 100%);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 12px;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .delete-btn:hover {
            background: linear-gradient(135deg, #ff6b88 0%, var(--danger-color) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 65, 108, 0.3);
            text-decoration: none;
        }
        
        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .no-data-icon {
            font-size: 50px;
            color: #ddd;
            margin-bottom: 20px;
            opacity: 0.5;
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
            
            .content-title {
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
            
            .content-container {
                padding: 15px 10px;
                min-height: 50vh;
            }
            
            .data-table th, 
            .data-table td {
                padding: 10px;
                font-size: 13px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .edit-btn, .delete-btn {
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
            
            .add-btn, .export-btn {
                width: 100%;
                justify-content: center;
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
                <h1><i class="fas fa-users-cog"></i> <?php echo $t['page_title']; ?></h1>
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
    <div class="content-container">
        <div class="content-title">
            <span><i class="fas fa-list-alt me-2"></i><?php echo $t['users_list']; ?></span>
            <!-- Commented out as per original code -->
            <!-- <a href="/admin/add_user.php" class="add-btn">
                <i class="fas fa-plus-circle me-2"></i><?php echo $t['add_user']; ?>
            </a> -->
        </div>
        
        <button class="btn export-btn" id="export-btn">
            <i class="fas fa-file-excel me-2"></i><?php echo $t['export_excel']; ?>
        </button>
        
        <?php if (count($users) > 0): ?>
        <div class="table-responsive">
            <table class="data-table table-striped" id="userTable">
                <thead>
                    <tr>
                        <th width="50"><?php echo $t['no']; ?></th>
                        <th><?php echo $t['email']; ?></th>
                        <th width="120"><?php echo $t['password']; ?></th>
                        <th><?php echo $t['user_name']; ?></th>
                        <th width="120"><?php echo $t['action']; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    foreach ($users as $row): 
                        $id = htmlspecialchars($row['userid']);
                        $email = htmlspecialchars($row['email']);
                        $name = htmlspecialchars($row['name']);
                    ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><strong><?php echo $email; ?></strong></td>
                        <td><span class="text-muted"><i class="fas fa-lock me-1"></i>********</span></td>
                        <td><?php echo $name; ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="/admin/update_user.php?id=<?php echo $id; ?>" class="edit-btn">
                                    <i class="fas fa-edit me-1"></i><?php echo $t['edit']; ?>
                                </a>
                                <a href="/admin/delete_user.php?id=<?php echo $id; ?>" class="delete-btn" onclick="return confirm('<?php echo $lang == 'bm' ? 'Adakah anda pasti ingin memadam pengguna ini?' : 'Are you sure you want to delete this user?'; ?>')">
                                    <i class="fas fa-trash me-1"></i><?php echo $t['delete']; ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php else: ?>
        <div class="no-data">
            <div class="no-data-icon">
                <i class="fas fa-users-slash"></i>
            </div>
            <h4><?php echo $lang == 'bm' ? 'Tiada Pengguna' : 'No Users'; ?></h4>
            <p><?php echo $lang == 'bm' ? 'Tiada rekod pengguna dijumpai.' : 'No user records found.'; ?></p>
            <!-- Commented out as per original code -->
            <!-- <a href="/admin/add_user.php" class="btn btn-primary mt-3">
                <i class="fas fa-plus-circle me-2"></i><?php echo $t['add_user']; ?>
            </a> -->
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- JavaScript Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.1/xlsx.full.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable if table exists
    var table = $('#userTable');
    if (table.length) {
        table.DataTable({
            "language": {
                "search": "<?php echo $lang == 'bm' ? 'Carian:' : 'Search:'; ?>",
                "lengthMenu": "<?php echo $lang == 'bm' ? 'Paparkan _MENU_ rekod setiap halaman' : 'Show _MENU_ entries per page'; ?>",
                "zeroRecords": "<?php echo $lang == 'bm' ? 'Tiada data yang sepadan' : 'No matching records found'; ?>",
                "info": "<?php echo $lang == 'bm' ? 'Paparan halaman _PAGE_ dari _PAGES_' : 'Showing page _PAGE_ of _PAGES_'; ?>",
                "infoEmpty": "<?php echo $lang == 'bm' ? 'Tiada rekod' : 'No records available'; ?>",
                "infoFiltered": "<?php echo $lang == 'bm' ? '(disaring dari _MAX_ jumlah rekod)' : '(filtered from _MAX_ total records)'; ?>",
                "paginate": {
                    "first": "<?php echo $lang == 'bm' ? 'Pertama' : 'First'; ?>",
                    "last": "<?php echo $lang == 'bm' ? 'Terakhir' : 'Last'; ?>",
                    "next": "<?php echo $lang == 'bm' ? 'Seterusnya' : 'Next'; ?>",
                    "previous": "<?php echo $lang == 'bm' ? 'Sebelumnya' : 'Previous'; ?>"
                }
            },
            "pageLength": 10,
            "order": [[0, "asc"]],
            "responsive": true,
            "autoWidth": false
        });
    }
    
    // Export to Excel
    $('#export-btn').on('click', function() {
        var table = $('#userTable');
        if (table.length) {
            // Clone table to avoid affecting DataTable
            var tableClone = table.clone();
            
            // Remove DataTable classes and attributes
            tableClone.removeClass('dataTable').find('.dataTables_empty').remove();
            
            // Create workbook and download
            var wb = XLSX.utils.table_to_book(tableClone[0], {sheet: "Users"});
            XLSX.writeFile(wb, "users-<?php echo date('Y-m-d'); ?>.xlsx");
        }
    });
});
</script>

</body>
</html>