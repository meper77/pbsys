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
include $_SERVER['DOCUMENT_ROOT'].'/includes/search_backend.php';

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
    'page_title' => 'Pencarian Kenderaan',
    'system_name' => 'NEO V-TRACK - Sistem Pengurusan & Pemantauan Kenderaan',
    'admin_role' => 'Administrator',
    'logout' => 'Log Keluar',
    'logout_confirm' => 'Adakah anda pasti ingin log keluar?',
    'search_vehicle' => 'Pencarian Kenderaan',
    'search_placeholder' => 'Masukkan No. Plat Kenderaan',
    'search_button' => 'Cari',
    'search_vehicle_help' => 'Cari kenderaan dengan memasukkan nombor plat',
    'no_results' => 'Tiada rekod dijumpai',
    'all_fields' => 'Semua maklumat kenderaan akan dipaparkan di sini',
    'search_results' => 'Keputusan Carian',
    'vehicle_details' => 'Butiran Kenderaan',
    'no' => 'No.',
    'status' => 'Status',
    'id_number' => 'No. Staf/Matriks/Pelawat',
    'name' => 'Nama',
    'phone' => 'No. Telefon',
    'plate_number' => 'No. Plat Kenderaan',
    'vehicle_type' => 'Jenis Kenderaan',
    'sticker' => 'Stiker',
    'search_again' => 'Cari Lagi',
    'export_excel' => 'Eksport ke Excel',
    'sticker_ada' => 'ADA',
    'sticker_tiada' => 'TIADA',
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
    'page_title' => 'Vehicle Search',
    'system_name' => 'NEO V-TRACK - Vehicle Management & Monitoring System',
    'admin_role' => 'Administrator',
    'logout' => 'Log Out',
    'logout_confirm' => 'Are you sure you want to log out?',
    'search_vehicle' => 'Vehicle Search',
    'search_placeholder' => 'Enter Vehicle Plate Number',
    'search_button' => 'Search',
    'search_vehicle_help' => 'Search vehicles by entering plate number',
    'no_results' => 'No records found',
    'all_fields' => 'All vehicle information will be displayed here',
    'search_results' => 'Search Results',
    'vehicle_details' => 'Vehicle Details',
    'no' => 'No.',
    'status' => 'Status',
    'id_number' => 'Staff/Matrix/Visitor No.',
    'name' => 'Name',
    'phone' => 'Phone Number',
    'plate_number' => 'Plate Number',
    'vehicle_type' => 'Vehicle Type',
    'sticker' => 'Sticker',
    'search_again' => 'Search Again',
    'export_excel' => 'Export to Excel',
    'sticker_ada' => 'AVAILABLE',
    'sticker_tiada' => 'NOT AVAILABLE',
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

// Initialize variables
$search = '';
$results = [];
$hasResults = false;
$no = 1;

// Handle search
if (isset($_POST['submit'])) {
    $search = trim($_POST['search']);
    if (!empty($search)) {
        $payload = searchVehicleRecords($con, $search);
        $results = $payload['data'];
        $hasResults = true;
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

        /* Logo container styling - SAME AS INDEX.PHP */
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

        /* Main Content */
        .main-content {
            max-width: 1300px;
            margin: 20px auto;
            padding: 0 15px;
        }

        /* Search Section */
        .search-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border: 1px solid #eaeaea;
        }

        .search-title {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
        }

        .search-form {
            max-width: 700px;
            margin: 0 auto;
        }

        .search-input-group {
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }

        .search-input-group:focus-within {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 41, 128, 0.1);
        }

        .search-input {
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            flex: 1;
        }

        .search-input:focus {
            box-shadow: none;
            outline: none;
        }

        .search-btn {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--uitm-blue) 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .search-btn:hover {
            background: linear-gradient(135deg, var(--uitm-blue) 0%, var(--primary-color) 100%);
        }

        .search-help {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-top: 12px;
        }

        /* Results Section */
        .results-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border: 1px solid #eaeaea;
            overflow: hidden;
        }

        .results-title {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .results-count {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--uitm-blue) 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .vehicle-table {
            width: 100%;
            border-collapse: collapse;
        }

        .vehicle-table thead {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--uitm-blue) 100%);
            color: white;
        }

        .vehicle-table th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            border: none;
            white-space: nowrap;
        }

        .vehicle-table tbody tr {
            border-bottom: 1px solid #eee;
            transition: all 0.3s;
        }

        .vehicle-table tbody tr:hover {
            background-color: #f8f9ff;
        }

        .vehicle-table td {
            padding: 12px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
        }

        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .no-results-icon {
            font-size: 50px;
            color: #ddd;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .action-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .export-btn {
            background: linear-gradient(135deg, var(--success-color) 0%, #00d2b9 100%);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .export-btn:hover {
            background: linear-gradient(135deg, #00d2b9 0%, var(--success-color) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 176, 155, 0.3);
        }

        .search-again-btn {
            background: linear-gradient(135deg, var(--warning-color) 0%, #ff9966 100%);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .search-again-btn:hover {
            background: linear-gradient(135deg, #ff9966 0%, var(--warning-color) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 153, 102, 0.3);
            text-decoration: none;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-staff {
            background-color: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }

        .status-student {
            background-color: #f3e5f5;
            color: #7b1fa2;
            border: 1px solid #e1bee7;
        }

        .status-visitor {
            background-color: #e8f5e9;
            color: #388e3c;
            border: 1px solid #c8e6c9;
        }

        .status-contractor {
            background-color: #fff3e0;
            color: #f57c00;
            border: 1px solid #ffe0b2;
        }

        .vehicle-number {
            font-family: 'Courier New', monospace;
            font-size: 15px;
            font-weight: bold;
            color: var(--primary-color);
            background: #f5f7ff;
            padding: 5px 10px;
            border-radius: 4px;
            border: 1px solid #e0e5ff;
        }

        .sticker-badge-ada {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            text-align: center;
            min-width: 80px;
        }

        .sticker-badge-tiada {
            background: linear-gradient(135deg, #dc3545 0%, #ff6b6b 100%);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            text-align: center;
            min-width: 80px;
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

            .logo-container {
                justify-content: center;
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

            .search-input-group {
                flex-direction: column;
            }

            .search-input {
                border-radius: 8px 8px 0 0;
                border-bottom: 1px solid #ddd;
            }

            .search-btn {
                border-radius: 0 0 8px 8px;
                width: 100%;
            }

            .results-title {
                flex-direction: column;
                align-items: flex-start;
            }

            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .search-again-btn, .export-btn {
                width: 100%;
                justify-content: center;
            }

            .vehicle-table th,
            .vehicle-table td {
                padding: 10px;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 0 10px;
            }

            .search-container,
            .results-container {
                padding: 20px 15px;
            }

            .search-title {
                font-size: 1.3rem;
            }

            .uitm-logo, .neo-logo {
                height: 35px;
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
            <img src="/assets/images/uitm.png" alt="UITM Logo" class="uitm-logo">

            <!-- NEO V-TRACK Logo -->
            <img src="/assets/images/kik2.png" alt="NEO V-TRACK Logo" class="neo-logo">

            <div class="system-title">
                <h1><i class="fas fa-search"></i> <?php echo $t['page_title']; ?></h1>
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
                    <a class="nav-link active" href="/search/car_admin.php">
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
                    <a class="nav-link" href="/admin/users.php">
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
    <!-- Search Section -->
    <div class="search-container">
        <h2 class="search-title"><i class="fas fa-car me-2"></i><?php echo $t['search_vehicle']; ?></h2>

        <form method="POST" class="search-form">
            <div class="d-flex search-input-group">
                <input type="text" class="form-control search-input"
                       placeholder="<?php echo $t['search_placeholder']; ?>"
                       name="search" value="<?php echo htmlspecialchars($search); ?>"
                       required autofocus>
                <button class="btn search-btn" type="submit" name="submit">
                    <i class="fas fa-search me-2"></i><?php echo $t['search_button']; ?>
                </button>
            </div>
            <p class="search-help">
                <i class="fas fa-info-circle me-2"></i><?php echo $t['search_vehicle_help']; ?>
            </p>
        </form>
    </div>

    <!-- Results Section -->
    <?php if ($hasResults || isset($_POST['submit'])): ?>
    <div class="results-container">
        <div class="results-title">
            <span><i class="fas fa-list-alt me-2"></i><?php echo $t['search_results']; ?></span>
            <?php if (count($results) > 0): ?>
            <span class="results-count">
                <i class="fas fa-database me-1"></i>
                <?php echo count($results); ?> <?php echo $lang == 'bm' ? (count($results) == 1 ? 'rekod' : 'rekod') : (count($results) == 1 ? 'record' : 'records'); ?>
            </span>
            <?php endif; ?>
        </div>

        <?php if (count($results) > 0): ?>
        <div class="table-responsive">
            <table class="vehicle-table table-striped" id="vehicleTable">
                <thead>
                    <tr>
                        <th width="50"><?php echo $t['no']; ?></th>
                        <th width="100"><?php echo $t['status']; ?></th>
                        <th width="150"><?php echo $t['id_number']; ?></th>
                        <th><?php echo $t['name']; ?></th>
                        <th width="120"><?php echo $t['phone']; ?></th>
                        <th width="150"><?php echo $t['plate_number']; ?></th>
                        <th width="120"><?php echo $t['vehicle_type']; ?></th>
                        <th width="100"><?php echo $t['sticker']; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $counter = 1;
                    foreach ($results as $row):
                        $status_class = '';
                        $status_text = '';

                        // Map database status to language and CSS class
                        if (in_array(strtolower($row['status']), ['staf', 'staff'])) {
                            $status_class = 'status-staff';
                            $status_text = $lang == 'bm' ? 'Staf' : 'Staff';
                        } elseif (in_array(strtolower($row['status']), ['pelajar', 'student'])) {
                            $status_class = 'status-student';
                            $status_text = $lang == 'bm' ? 'Pelajar' : 'Student';
                        } elseif (in_array(strtolower($row['status']), ['pelawat', 'visitor'])) {
                            $status_class = 'status-visitor';
                            $status_text = $lang == 'bm' ? 'Pelawat' : 'Visitor';
                        } elseif (in_array(strtolower($row['status']), ['kontraktor', 'contractor'])) {
                            $status_class = 'status-contractor';
                            $status_text = $lang == 'bm' ? 'Kontraktor' : 'Contractor';
                        } else {
                            $status_class = 'status-staff';
                            $status_text = htmlspecialchars($row['status']);
                        }

                        $sticker_status = isset($row['sticker']) ? $row['sticker'] : '';
                        $sticker_number = isset($row['stickerno']) ? $row['stickerno'] : '';
                    ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                        <td><?php echo htmlspecialchars($row['idnumber']); ?></td>
                        <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><span class="vehicle-number"><?php echo htmlspecialchars($row['platenum']); ?></span></td>
                        <td><?php echo htmlspecialchars($row['type']); ?></td>
                        <td>
                            <?php
                            if ($sticker_status == 'ADA' && !empty($sticker_number)):
                            ?>
                                <span class="sticker-badge-ada">
                                    <?php echo htmlspecialchars($sticker_number); ?>
                                </span>
                            <?php elseif ($sticker_status == 'TIADA'): ?>
                                <span class="sticker-badge-tiada">
                                    <?php echo $t['sticker_tiada']; ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="action-buttons">
            <a href="/search/car_admin.php" class="search-again-btn">
                <i class="fas fa-redo me-2"></i><?php echo $t['search_again']; ?>
            </a>
            <button class="btn export-btn" id="export-btn">
                <i class="fas fa-file-excel me-2"></i><?php echo $t['export_excel']; ?>
            </button>
        </div>

        <?php else: ?>
        <div class="no-results">
            <div class="no-results-icon">
                <i class="fas fa-search-minus"></i>
            </div>
            <h4><?php echo $t['no_results']; ?></h4>
            <p><?php echo ($lang == 'bm' ? 'Tiada kenderaan dijumpai dengan nombor plat "' : 'No vehicle found with plate number "') . htmlspecialchars($search) . '"'; ?></p>
            <a href="/search/car_admin.php" class="btn btn-primary mt-3">
                <i class="fas fa-redo me-2"></i><?php echo $t['search_again']; ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <!-- Initial State - No Search Yet -->
    <div class="results-container">
        <div class="results-title">
            <span><i class="fas fa-info-circle me-2"></i><?php echo $t['vehicle_details']; ?></span>
        </div>
        <div class="no-results">
            <div class="no-results-icon">
                <i class="fas fa-car"></i>
            </div>
            <h4><?php echo $t['search_vehicle']; ?></h4>
            <p><?php echo $t['all_fields']; ?></p>
        </div>
    </div>
    <?php endif; ?>
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
    var table = $('#vehicleTable');
    if (table.length) {
        table.DataTable({
            "language": {
                "search": "<?php echo $t['search_button']; ?>:",
                "lengthMenu": "<?php echo $lang == 'bm' ? 'Paparkan _MENU_ rekod setiap halaman' : 'Show _MENU_ entries per page'; ?>",
                "zeroRecords": "<?php echo $t['no_results']; ?>",
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
            "autoWidth": false,
            "columnDefs": [
                { "orderable": false, "targets": 7 }
            ]
        });
    }

    // Export to Excel
    $('#export-btn').on('click', function() {
        var table = $('#vehicleTable');
        if (table.length) {
            // Clone table to avoid affecting DataTable
            var tableClone = table.clone();

            // Remove DataTable classes and attributes
            tableClone.removeClass('dataTable').find('.dataTables_empty').remove();

            // Create workbook and download
            var wb = XLSX.utils.table_to_book(tableClone[0], {sheet: "Vehicle Search Results"});
            XLSX.writeFile(wb, "vehicle-search-results-<?php echo date('Y-m-d'); ?>.xlsx");
        }
    });
});
</script>

</body>
</html>
