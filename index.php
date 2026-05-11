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
    'dashboard_title' => 'Dashboard Admin',
    'system_name' => 'NEO V-TRACK - Sistem Pengurusan & Pemantauan Kenderaan',
    'admin_role' => 'Administrator',
    'logout' => 'Log Keluar',
    'logout_confirm' => 'Adakah anda pasti ingin log keluar?',
    'vehicle_stats' => 'Statistik Kenderaan',
    'staff_vehicles' => 'Kenderaan Staf',
    'student_vehicles' => 'Kenderaan Pelajar',
    'visitor_vehicles' => 'Kenderaan Pelawat',
    'contractor_vehicles' => 'Kenderaan Kontraktor',
    'total_vehicles' => 'Total Kenderaan',
    'from_total' => 'dari total',
    'all_categories' => 'Semua kategori',
    'distribution' => 'Taburan Kenderaan',
    'recent_activity' => 'Aktiviti Terkini',
    'system_info' => 'Maklumat Sistem',
    'system_version' => 'Versi Sistem',
    'last_update' => 'Kemaskini Terakhir',
    'server_status' => 'Status Server',
    'all_systems_operational' => 'Semua sistem beroperasi',
    'activity_latest_vehicle' => 'Kenderaan terkini didaftarkan',
    'activity_total_vehicles' => 'Jumlah kenderaan terkini',
    'activity_main_category' => 'Kategori utama',
    'active_admin' => 'Admin aktif',
    'system_accessed' => 'Sistem diakses',
    'no_data' => 'Tiada data',
    // Navigation items
    'nav_dashboard' => 'Anjung',
    'nav_search' => 'Carian Kenderaan',
    'nav_staff' => 'Staf',
    'nav_student' => 'Pelajar',
    'nav_visitor' => 'Pelawat',
    'nav_contractor' => 'Kontraktor',
    'nav_user_mgmt' => 'Pengguna',
    'nav_admin' => 'Admin',
    // New translations
    'time' => 'Masa',
    'date' => 'Tarikh',
    'active_users' => 'Pengguna Aktif',
    'total_users' => 'Jumlah Pengguna'
];

// English
$text['en'] = [
    'dashboard_title' => 'Admin Dashboard',
    'system_name' => 'NEO V-TRACK - Vehicle Management & Monitoring System',
    'admin_role' => 'Administrator',
    'logout' => 'Log Out',
    'logout_confirm' => 'Are you sure you want to log out?',
    'vehicle_stats' => 'Vehicle Statistics',
    'staff_vehicles' => 'Staff Vehicles',
    'student_vehicles' => 'Student Vehicles',
    'visitor_vehicles' => 'Visitor Vehicles',
    'contractor_vehicles' => 'Contractor Vehicles',
    'total_vehicles' => 'Total Vehicles',
    'from_total' => 'of total',
    'all_categories' => 'All categories',
    'distribution' => 'Vehicle Distribution',
    'recent_activity' => 'Recent Activity',
    'system_info' => 'System Information',
    'system_version' => 'System Version',
    'last_update' => 'Last Updated',
    'server_status' => 'Server Status',
    'all_systems_operational' => 'All systems operational',
    'activity_latest_vehicle' => 'Latest vehicle registered',
    'activity_total_vehicles' => 'Current total vehicles',
    'activity_main_category' => 'Main category',
    'active_admin' => 'Active admin',
    'system_accessed' => 'System accessed',
    'no_data' => 'No data',
    // Navigation items
    'nav_dashboard' => 'Dashboard',
    'nav_search' => 'Vehicle Search',
    'nav_staff' => 'Staff',
    'nav_student' => 'Student',
    'nav_visitor' => 'Visitor',
    'nav_contractor' => 'Contractor',
    'nav_user_mgmt' => 'User',
    'nav_admin' => 'Admin',
    // New translations
    'time' => 'Time',
    'date' => 'Date',
    'active_users' => 'Active Users',
    'total_users' => 'Total Users'
];

$t = $text[$lang];

// Get admin display name - SAFE QUERY
$admin_email = $_SESSION['email_Admin'];
$admin_display = $admin_email; // Default to email

// Try to get admin name safely
$admin_query = @mysqli_query($con, "SELECT name FROM admin WHERE email = '$admin_email'");
if ($admin_query && mysqli_num_rows($admin_query) > 0) {
    $admin_data = mysqli_fetch_assoc($admin_query);
    if (!empty($admin_data['name'])) {
        $admin_display = $admin_data['name'];
    } else {
        // Extract username from email
        $admin_display = strstr($admin_email, '@', true) ?: $admin_email;
    }
}

// Get vehicle counts - SAFE QUERIES
$categories = [
    'Staf' => ['icon' => 'fas fa-briefcase', 'class' => 'stat-staff', 'label' => $t['staff_vehicles']],
    'Pelajar' => ['icon' => 'fas fa-graduation-cap', 'class' => 'stat-student', 'label' => $t['student_vehicles']],
    'Pelawat' => ['icon' => 'fas fa-user-clock', 'class' => 'stat-visitor', 'label' => $t['visitor_vehicles']],
    'Kontraktor' => ['icon' => 'fas fa-hard-hat', 'class' => 'stat-contractor', 'label' => $t['contractor_vehicles']]
];

$total_vehicles = 0;
$category_counts = [];

foreach ($categories as $category => $info) {
    $count = 0;
    // Safe query with error suppression
    $result = @mysqli_query($con, "SELECT COUNT(*) as count FROM owner WHERE status = '$category'");
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        $count = $data['count'] ?? 0;
    }
    
    $category_counts[$category] = $count;
    $total_vehicles += $count;
}

// Get total users count
$total_users_query = @mysqli_query($con, "SELECT COUNT(*) as total FROM user");
$total_users_count = 0;
if ($total_users_query && mysqli_num_rows($total_users_query) > 0) {
    $total_users_data = mysqli_fetch_assoc($total_users_query);
    $total_users_count = $total_users_data['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['dashboard_title']; ?> - NEO V-TRACK</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js for graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        /* Black Navbar - UPDATED with logos */
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
        }
        
        .navbar-black .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        .navbar-black .nav-link.active {
            background-color: var(--uitm-red);
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
        
        /* ===== REAL TIME CLOCK & DATE STYLES ===== */
        .realtime-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 26px;
            margin-bottom: 26px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            padding: 0 15px;
        }
        
        @media (max-width: 992px) {
            .realtime-info {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .realtime-info {
                grid-template-columns: 1fr;
            }
        }
        
        .clock-box, .date-box, .total-users-box {
            background: white;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 12px 25px rgba(0,0,0,0.12);
            border-top: 5px solid var(--uitm-red);
            transition: transform 0.3s;
            min-height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
        }
        
        .clock-box {
            border-top-color: #667eea;
        }
        
        .date-box {
            border-top-color: #f5576c;
        }
        
        .total-users-box {
            border-top-color: #43e97b;
        }
        
        .clock-box:hover, .date-box:hover, .total-users-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .realtime-title {
            font-size: 16px;
            color: #666;
            font-weight: 600;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .clock-display {
            font-size: 40px;
            font-weight: 900;
            font-family: 'Courier New', monospace;
            color: #2c3e50;
            text-align: center;
            letter-spacing: 1.5px;
        }
        
        .date-display {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            text-align: center;
            line-height: 1.4;
        }
        
        .users-count {
            font-size: 40px;
            font-weight: 900;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 5px;
        }
        
        .users-label {
            font-size: 14px;
            color: #666;
            text-align: center;
            font-weight: 500;
        }
        
        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 25px;
            box-shadow: 0 12px 25px rgba(0,0,0,0.12);
            border: none;
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            border-top: 4px solid;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .stat-staff { 
            border-top-color: #667eea; 
            background: white;
        }
        
        .stat-student { 
            border-top-color: #f5576c; 
            background: white;
        }
        
        .stat-visitor { 
            border-top-color: #4facfe; 
            background: white;
        }
        
        .stat-contractor { 
            border-top-color: #43e97b; 
            background: white;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 15px;
        }
        
        .stat-staff .stat-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-student .stat-icon { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-visitor .stat-icon { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-contractor .stat-icon { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        
        .stat-number {
            font-size: 44px;
            font-weight: 800;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 17px;
            color: #666;
            font-weight: 600;
        }
        
        .stat-change {
            font-size: 14px;
            margin-top: 10px;
        }
        
        .stat-change.positive {
            color: var(--success-color);
        }
        
        .dashboard-section {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 8px 20px rgba(0,0,0,.08);
            border-left: 6px solid var(--uitm-red);
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f2ff;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        /* Two column layout */
        .two-column-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        /* Mobile Optimization Enhancements */
        @media (max-width: 1200px) {
            .navbar-black .nav-link {
                padding: 15px 14px !important;
                font-size: 13px;
            }
            
            .uitm-logo, .neo-logo {
                height: 40px;
            }
            
            /* Better touch targets for mobile */
            .navbar-black .nav-link,
            .logout-btn,
            .lang-btn {
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
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
            
            .logo-container {
                justify-content: center;
            }
            
            .two-column-layout {
                grid-template-columns: 1fr;
            }
            
            /* Stack navigation items on mobile */
            .navbar-black .nav-item {
                flex: 1 0 50%;
            }
            
            /* Increase font sizes for better readability on mobile */
            .system-title h1 {
                font-size: 20px;
            }
            
            .system-title p {
                font-size: 12px;
            }
            
            .clock-display {
                font-size: 32px;
            }
            
            .date-display {
                font-size: 24px;
            }
            
            .users-count {
                font-size: 32px;
            }
            
            .stat-number {
                font-size: 32px;
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
            
            .stat-card {
                padding: 25px;
            }
            
            .stat-number {
                font-size: 32px;
            }
            
            /* Adjust padding for mobile */
            .main-content,
            .header-content {
                padding: 0 10px;
            }
            
            .dashboard-section,
            .stat-card,
            .clock-box,
            .date-box,
            .total-users-box {
                padding: 20px;
            }
            
            .clock-display {
                font-size: 28px;
            }
            
            .date-display {
                font-size: 20px;
            }
            
            .users-count {
                font-size: 28px;
            }
        }
        
        @media (max-width: 576px) {
            .realtime-info {
                gap: 20px;
            }
            
            .clock-box, .date-box, .total-users-box {
                min-height: 120px;
                padding: 20px;
            }
            
            .clock-display {
                font-size: 24px;
            }
            
            .date-display {
                font-size: 18px;
            }
            
            .users-count {
                font-size: 24px;
            }
            
            .realtime-title {
                font-size: 14px;
            }
            
            .users-label {
                font-size: 12px;
            }
        }
        
        @media (max-width: 480px) {
            .main-content {
                padding: 0 10px;
            }
            
            .uitm-logo, .neo-logo {
                height: 35px;
            }
            
            /* Single column for all content on very small screens */
            .row {
                margin-left: -5px;
                margin-right: -5px;
            }
            
            .col-lg-3, .col-md-6, .col-sm-6 {
                padding-left: 5px;
                padding-right: 5px;
            }
            
            .clock-box, .date-box, .total-users-box {
                padding: 15px;
            }
            
            .clock-display {
                font-size: 20px;
            }
            
            .date-display {
                font-size: 16px;
            }
            
            .users-count {
                font-size: 20px;
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
            <img src="inc/images/uitm.png" alt="UITM Logo" class="uitm-logo">
            
            <!-- NEO V-TRACK Logo -->
            <img src="inc/images/kik2.png" alt="NEO V-TRACK Logo" class="neo-logo">
            
            <div class="system-title">
                <h1><i class="fas fa-tachometer-alt"></i> <?php echo $t['dashboard_title']; ?></h1>
                <p><?php echo $t['system_name']; ?></p>
            </div>
        </div>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon" style="filter: invert(1);">☰</span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">
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
                    <a class="nav-link" href="admin.php">
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

<!-- REAL TIME CLOCK, DATE & USER INFO BOXES -->
<div class="realtime-info">
    <div class="clock-box">
        <div class="realtime-title" id="timeLabel"><?php echo $t['time']; ?></div>
        <div id="realTimeClock" class="clock-display">00:00:00</div>
    </div>
    
    <div class="date-box">
        <div class="realtime-title" id="dateLabel"><?php echo $t['date']; ?></div>
        <div id="realTimeDate" class="date-display">Hari, 1 Januari 2024</div>
    </div>
    
    <div class="total-users-box">
        <div class="realtime-title" id="totalUsersLabel"><?php echo $t['total_users']; ?></div>
        <div class="users-count"><?php echo $total_users_count; ?></div>
        <div class="users-label">Pengguna Berdaftar / User Registered</div>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    
    <!-- Statistics Section -->
    <div class="mb-4">
        <h3 class="mb-3"><?php echo $t['vehicle_stats']; ?></h3>
        <div class="row">
            <?php
            foreach ($categories as $category => $info) {
                $count = $category_counts[$category];
                $percentage = $total_vehicles > 0 ? round(($count / $total_vehicles) * 100, 1) : 0;
            ?>
            <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
                <div class="stat-card <?php echo $info['class']; ?>">
                    <div class="stat-icon">
                        <i class="<?php echo $info['icon']; ?>"></i>
                    </div>
                    <div class="stat-number"><?php echo $count; ?></div>
                    <div class="stat-label"><?php echo $info['label']; ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-chart-line"></i> <?php echo $percentage; ?>% <?php echo $t['from_total']; ?>
                    </div>
                </div>
            </div>
            <?php } ?>
            
            <!-- Total Vehicles Card -->
            <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
                <div class="stat-card" style="background: linear-gradient(135deg, #2c3e50 0%, #4a6491 100%); color: white; border-top-color: #2c3e50;">
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="stat-number" style="color: white;"><?php echo $total_vehicles; ?></div>
                    <div class="stat-label" style="color: rgba(255,255,255,0.9);"><?php echo $t['total_vehicles']; ?></div>
                    <div class="stat-change" style="color: rgba(255,255,255,0.8);">
                        <i class="fas fa-database"></i> <?php echo $t['all_categories']; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Two Column Layout -->
    <div class="two-column-layout">
        <!-- Left Column -->
        <div>
            <!-- Vehicle Distribution Section -->
            <div class="dashboard-section">
                <h4 class="section-title"><i class="fas fa-chart-pie"></i> <?php echo $t['distribution']; ?></h4>
                <div class="chart-container">
                    <canvas id="vehicleChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Right Column - SYSTEM INFO -->
        <div>
            <div class="dashboard-section">
                <h4 class="section-title"><i class="fas fa-info-circle"></i> <?php echo $t['system_info']; ?></h4>
                <div class="list-group">
                    <div class="list-group-item border-0">
                        <small class="text-muted"><?php echo $t['system_version']; ?></small>
                        <div class="fw-bold">NEO V-TRACK v2.0</div>
                    </div>
                    <div class="list-group-item border-0">
                        <small class="text-muted"><?php echo $t['last_update']; ?></small>
                        <div class="fw-bold"><?php echo date('d/m/Y'); ?></div>
                    </div>
                    <div class="list-group-item border-0">
                        <small class="text-muted"><?php echo $t['server_status']; ?></small>
                        <div>
                            <span class="badge bg-success">Online</span>
                            <small class="text-muted ms-2"><?php echo $t['all_systems_operational']; ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Charts and Real-time Clock -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Real-time clock function
    function updateRealTimeClock() {
        const now = new Date();
        
        // Time in 24-hour format
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        
        // Day names in Malay and English
        const daysBM = ['Ahad', 'Isnin', 'Selasa', 'Rabu', 'Khamis', 'Jumaat', 'Sabtu'];
        const daysEN = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        // Month names in Malay and English
        const monthsBM = ['Januari', 'Februari', 'Mac', 'April', 'Mei', 'Jun', 'Julai', 'Ogos', 'September', 'Oktober', 'November', 'Disember'];
        const monthsEN = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        
        // Get current language from URL or default
        const urlParams = new URLSearchParams(window.location.search);
        const currentLang = urlParams.get('lang') || '<?php echo $lang; ?>';
        
        // Update time display
        document.getElementById('realTimeClock').textContent = `${hours}:${minutes}:${seconds}`;
        
        // Update date display based on language
        if (currentLang === 'bm') {
            const dayName = daysBM[now.getDay()];
            const monthName = monthsBM[now.getMonth()];
            const date = now.getDate();
            const year = now.getFullYear();
            document.getElementById('realTimeDate').textContent = `${dayName}, ${date} ${monthName} ${year}`;
        } else {
            const dayName = daysEN[now.getDay()];
            const monthName = monthsEN[now.getMonth()];
            const date = now.getDate();
            const year = now.getFullYear();
            document.getElementById('realTimeDate').textContent = `${dayName}, ${date} ${monthName} ${year}`;
        }
    }
    
    // Initialize clock and update every second
    updateRealTimeClock();
    setInterval(updateRealTimeClock, 1000);
    
    // Chart.js initialization
    const ctx = document.getElementById('vehicleChart').getContext('2d');
    
    const staffCount = <?php echo $category_counts['Staf'] ?? 0; ?>;
    const studentCount = <?php echo $category_counts['Pelajar'] ?? 0; ?>;
    const visitorCount = <?php echo $category_counts['Pelawat'] ?? 0; ?>;
    const contractorCount = <?php echo $category_counts['Kontraktor'] ?? 0; ?>;
    
    // Get labels based on current language
    const currentLang = '<?php echo $lang; ?>';
    const labels = currentLang === 'bm' ? [
        '<?php echo $t['staff_vehicles']; ?>',
        '<?php echo $t['student_vehicles']; ?>', 
        '<?php echo $t['visitor_vehicles']; ?>',
        '<?php echo $t['contractor_vehicles']; ?>'
    ] : [
        '<?php echo $t['staff_vehicles']; ?>',
        '<?php echo $t['student_vehicles']; ?>', 
        '<?php echo $t['visitor_vehicles']; ?>',
        '<?php echo $t['contractor_vehicles']; ?>'
    ];
    
    const vehicleChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: [staffCount, studentCount, visitorCount, contractorCount],
                backgroundColor: [
                    '#667eea',
                    '#f5576c',
                    '#4facfe',
                    '#43e97b'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>