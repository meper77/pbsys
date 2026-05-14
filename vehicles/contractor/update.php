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
    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $_GET['id']);
    exit();
}

$lang = $_SESSION['language'];

// Language texts
$text = [];

// Bahasa Malaysia
$text['bm'] = [
    'page_title' => 'Kemaskini Kenderaan Kontraktor',
    'system_name' => 'NEO V-TRACK - Sistem Pengurusan & Pemantauan Kenderaan',
    'status' => 'Status',
    'name' => 'Nama',
    'phone' => 'No. Telefon',
    'contractor_no' => 'No. Kontraktor',
    'vehicle_type' => 'Jenis Kenderaan',
    'plate_number' => 'Nombor Plat',
    'sticker_number' => 'No. Stiker',
    'sticker_status' => 'Status Stiker',
    'save' => 'Simpan',
    'back' => 'Kembali',
    'update_success' => 'Kenderaan kontraktor berjaya dikemaskini!',
    'update_failed' => 'Gagal mengemaskini kenderaan kontraktor',
    'logout' => 'Log Keluar',
    'logout_confirm' => 'Adakah anda pasti ingin log keluar?',
    'nav_dashboard' => 'Anjung',
    'nav_search' => 'Carian Kenderaan',
    'nav_staff' => 'Staf',
    'nav_student' => 'Pelajar',
    'nav_visitor' => 'Pelawat',
    'nav_contractor' => 'Kontraktor',
    'nav_user_mgmt' => 'Pengguna',
    'nav_admin' => 'Admin',
    'status_student' => 'PELAJAR',
    'status_staff' => 'STAF',
    'status_visitor' => 'PELAWAT',
    'status_contractor' => 'KONTRAKTOR',
    'car' => 'KERETA',
    'motorcycle' => 'MOTOSIKAL',
    'lori' => 'LORI',
    'fourwd' => '4WD',
    'van' => 'VAN',
    'mpv' => 'MPV',
    'name_placeholder' => 'Isi nama kontraktor',
    'phone_placeholder' => 'Isi nombor telefon kontraktor',
    'contractor_placeholder' => 'Isi nombor kontraktor',
    'plate_placeholder' => 'Isi nombor plat kenderaan',
    'sticker_placeholder' => 'Masukkan No. Stiker',
    'sticker_ada' => 'ADA',
    'sticker_tiada' => 'TIADA'
];

// English
$text['en'] = [
    'page_title' => 'Update Contractor Vehicle',
    'system_name' => 'NEO V-TRACK - Vehicle Management & Monitoring System',
    'status' => 'Status',
    'name' => 'Name',
    'phone' => 'Phone Number',
    'contractor_no' => 'Contractor Number',
    'vehicle_type' => 'Vehicle Type',
    'plate_number' => 'Plate Number',
    'sticker_number' => 'Sticker No.',
    'sticker_status' => 'Sticker Status',
    'save' => 'Save',
    'back' => 'Back',
    'update_success' => 'Contractor vehicle updated successfully!',
    'update_failed' => 'Failed to update contractor vehicle',
    'logout' => 'Log Out',
    'logout_confirm' => 'Are you sure you want to log out?',
    'nav_dashboard' => 'Dashboard',
    'nav_search' => 'Vehicle Search',
    'nav_staff' => 'Staff',
    'nav_student' => 'Student',
    'nav_visitor' => 'Visitor',
    'nav_contractor' => 'Contractor',
    'nav_user_mgmt' => 'User',
    'nav_admin' => 'Admin',
    'status_student' => 'STUDENT',
    'status_staff' => 'STAFF',
    'status_visitor' => 'VISITOR',
    'status_contractor' => 'CONTRACTOR',
    'car' => 'CAR',
    'motorcycle' => 'MOTORCYCLE',
    'lori' => 'LORRY',
    'fourwd' => '4WD',
    'van' => 'VAN',
    'mpv' => 'MPV',
    'name_placeholder' => 'Enter contractor name',
    'phone_placeholder' => 'Enter contractor phone number',
    'contractor_placeholder' => 'Enter contractor number',
    'plate_placeholder' => 'Enter vehicle plate number',
    'sticker_placeholder' => 'Enter Sticker Number',
    'sticker_ada' => 'AVAILABLE',
    'sticker_tiada' => 'NOT AVAILABLE'
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

// Handle form submission
if (isset($_POST['submit'])) {
    $id = $_GET['id'];
    $contractname = mysqli_real_escape_string($con, $_POST['name']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $pass = mysqli_real_escape_string($con, $_POST['idnumber']);
    $type = mysqli_real_escape_string($con, $_POST['type']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    $contractplate = mysqli_real_escape_string($con, $_POST['platenum']);
    $stickerno = mysqli_real_escape_string($con, $_POST['stickerno']);
    $sticker = mysqli_real_escape_string($con, $_POST['sticker']);

    $sql = "UPDATE `owner` SET 
            name='$contractname', 
            phone='$phone', 
            idnumber='$pass', 
            type='$type', 
            status='$status', 
            platenum='$contractplate',
            stickerno='$stickerno',
            sticker='$sticker' 
            WHERE id=$id";

    $result = mysqli_query($con, $sql);
    if ($result) {
        echo "<script>alert('{$t['update_success']}'); window.location.href='/vehicles/contractor/list.php';</script>";
        exit();
    } else {
        $error_msg = $t['update_failed'] . ": " . mysqli_error($con);
        echo "<script>alert('$error_msg');</script>";
    }
}

// Get vehicle data for editing
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$vehicle_data = null;

if ($id > 0) {
    $sql = "SELECT * FROM `owner` WHERE id = $id";
    $result = mysqli_query($con, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $vehicle_data = mysqli_fetch_assoc($result);
    } else {
        echo "<script>alert('Rekod kenderaan tidak ditemui!'); window.location.href='/vehicles/contractor/list.php';</script>";
        exit();
    }
} else {
    echo "<script>alert('ID Kenderaan tidak sah!'); window.location.href='/vehicles/contractor/list.php';</script>";
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
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 3px;
            color: white;
        }
        
        .system-title p {
            opacity: 0.9;
            font-size: 12px;
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
            margin-bottom: 30px;
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
            max-width: 800px;
            margin: 0 auto;
            padding: 0 15px 40px;
        }
        
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: none;
            margin-bottom: 30px;
        }
        
        .card-header {
            background: transparent;
            border-bottom: 2px solid #f0f2ff;
            padding: 0 0 20px 0;
            margin-bottom: 25px;
        }
        
        .card-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-control, .form-select {
            border: 2px solid #e1e5f1;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(26, 41, 128, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--uitm-blue) 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 41, 128, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 65, 108, 0.3);
        }
        
        /* Status Badge - UPDATED COLORS to match carian kenderaan */
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }
        
        /* STAFF - Blue (#667eea) */
        .badge-staff, .status-staff { 
            background-color: #667eea !important; 
            color: white !important; 
        }
        
        /* STUDENT - Purple (#f093fb) */
        .badge-student, .status-student { 
            background-color: #f093fb !important; 
            color: white !important; 
        }
        
        /* VISITOR - Green (#43e97b) */
        .badge-visitor, .status-visitor { 
            background-color: #43e97b !important; 
            color: white !important; 
        }
        
        /* CONTRACTOR - Orange (#ff9966) */
        .badge-contractor, .status-contractor { 
            background-color: #ff9966 !important; 
            color: white !important; 
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
            
            .form-card {
                padding: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .main-content {
                padding: 0 10px 30px;
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
                <h1><i class="fas fa-hard-hat"></i> <?php echo $t['page_title']; ?></h1>
                <p><?php echo $t['system_name']; ?></p>
            </div>
        </div>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon" style="filter: invert(1);">☰</span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/index.php">
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
                    <a class="nav-link active" href="/vehicles/contractor/list.php">
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
                        <div style="font-size: 11px; opacity: 0.8;">Administrator</div>
                    </div>
                </div>
                
                <!-- Language Switcher -->
                <div class="language-switcher">
                    <a href="?lang=bm&id=<?php echo $id; ?>" class="lang-btn <?php echo ($lang == 'bm') ? 'active' : ''; ?>">
                        <i class="fas fa-language me-1"></i>BM
                    </a>
                    <a href="?lang=en&id=<?php echo $id; ?>" class="lang-btn <?php echo ($lang == 'en') ? 'active' : ''; ?>">
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
    <div class="form-card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-edit me-2"></i><?php echo $t['page_title']; ?></h2>
            <?php if ($vehicle_data): ?>
            <div class="d-flex align-items-center gap-3 mt-2">
                <span class="text-muted">Status Semasa:</span>
                <?php 
                $status_class = '';
                switch($vehicle_data['status']) {
                    case 'Staf': $status_class = 'status-staff'; break;
                    case 'Pelajar': $status_class = 'status-student'; break;
                    case 'Pelawat': $status_class = 'status-visitor'; break;
                    case 'Kontraktor': $status_class = 'status-contractor'; break;
                    default: $status_class = 'status-' . strtolower($vehicle_data['status']);
                }
                ?>
                <span class="status-badge <?php echo $status_class; ?>">
                    <?php echo $vehicle_data['status']; ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        
        <form method="POST">
            <div class="mb-4">
                <label for="status" class="form-label"><?php echo $t['status']; ?>:</label>
                <select id="status" name="status" class="form-select" required>
                    <option value="Kontraktor" <?php echo ($vehicle_data['status'] == 'Kontraktor') ? 'selected' : ''; ?>>
                        <?php echo $t['status_contractor']; ?>
                    </option>
                    <option value="Staf" <?php echo ($vehicle_data['status'] == 'Staf') ? 'selected' : ''; ?>>
                        <?php echo $t['status_staff']; ?>
                    </option>
                    <option value="Pelajar" <?php echo ($vehicle_data['status'] == 'Pelajar') ? 'selected' : ''; ?>>
                        <?php echo $t['status_student']; ?>
                    </option>
                    <option value="Pelawat" <?php echo ($vehicle_data['status'] == 'Pelawat') ? 'selected' : ''; ?>>
                        <?php echo $t['status_visitor']; ?>
                    </option>
                </select>
            </div>
            
            <div class="mb-4">
                <label for="name" class="form-label"><?php echo $t['name']; ?>:</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="<?php echo $t['name_placeholder']; ?>" value="<?php echo htmlspecialchars($vehicle_data['name'] ?? ''); ?>" required>
            </div>
            
            <div class="mb-4">
                <label for="phone" class="form-label"><?php echo $t['phone']; ?>:</label>
                <input type="text" id="phone" name="phone" class="form-control" placeholder="<?php echo $t['phone_placeholder']; ?>" value="<?php echo htmlspecialchars($vehicle_data['phone'] ?? ''); ?>" required>
            </div>
            
            <div class="mb-4">
                <label for="idnumber" class="form-label"><?php echo $t['contractor_no']; ?>:</label>
                <input type="text" id="idnumber" name="idnumber" class="form-control" placeholder="<?php echo $t['contractor_placeholder']; ?>" value="<?php echo htmlspecialchars($vehicle_data['idnumber'] ?? ''); ?>" required>
            </div>
            
            <div class="mb-4">
                <label for="type" class="form-label"><?php echo $t['vehicle_type']; ?>:</label>
                <select id="type" name="type" class="form-select" required>
                    <option value="KERETA" <?php echo ($vehicle_data['type'] == 'KERETA') ? 'selected' : ''; ?>>
                        <?php echo $t['car']; ?>
                    </option>
                    <option value="MOTOSIKAL" <?php echo ($vehicle_data['type'] == 'MOTOSIKAL') ? 'selected' : ''; ?>>
                        <?php echo $t['motorcycle']; ?>
                    </option>
                    <option value="LORI" <?php echo ($vehicle_data['type'] == 'LORI') ? 'selected' : ''; ?>>
                        <?php echo $t['lori']; ?>
                    </option>
                    <option value="4WD" <?php echo ($vehicle_data['type'] == '4WD') ? 'selected' : ''; ?>>
                        <?php echo $t['fourwd']; ?>
                    </option>
                    <option value="VAN" <?php echo ($vehicle_data['type'] == 'VAN') ? 'selected' : ''; ?>>
                        <?php echo $t['van']; ?>
                    </option>
                    <option value="MPV" <?php echo ($vehicle_data['type'] == 'MPV') ? 'selected' : ''; ?>>
                        <?php echo $t['mpv']; ?>
                    </option>
                </select>
            </div>
            
            <div class="mb-4">
                <label for="platenum" class="form-label"><?php echo $t['plate_number']; ?>:</label>
                <input type="text" id="platenum" name="platenum" class="form-control" placeholder="<?php echo $t['plate_placeholder']; ?>" value="<?php echo htmlspecialchars($vehicle_data['platenum'] ?? ''); ?>" required>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="stickerno" class="form-label">
                            <i class="fas fa-sticky-note me-2"></i><?php echo $t['sticker_number']; ?>:
                        </label>
                        <input type="text" id="stickerno" name="stickerno" class="form-control" 
                               placeholder="<?php echo $t['sticker_placeholder']; ?>" 
                               value="<?php echo htmlspecialchars($vehicle_data['stickerno'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="sticker" class="form-label">
                            <i class="fas fa-tag me-2"></i><?php echo $t['sticker_status']; ?>:
                        </label>
                        <select id="sticker" name="sticker" class="form-select">
                            <option value="ADA" <?php echo (isset($vehicle_data['sticker']) && $vehicle_data['sticker'] == 'ADA') ? 'selected' : ''; ?>>
                                <?php echo $t['sticker_ada']; ?>
                            </option>
                            <option value="TIADA" <?php echo (isset($vehicle_data['sticker']) && $vehicle_data['sticker'] == 'TIADA') ? 'selected' : ''; ?>>
                                <?php echo $t['sticker_tiada']; ?>
                            </option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-3">
                <button type="submit" name="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i><?php echo $t['save']; ?>
                </button>
                <a href="/vehicles/contractor/list.php" class="btn btn-danger">
                    <i class="fas fa-arrow-left me-2"></i><?php echo $t['back']; ?>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Auto-uppercase for plate number
    $('#platenum').on('input', function() {
        this.value = this.value.toUpperCase();
    });
    
    // Auto-uppercase for sticker number
    $('#stickerno').on('input', function() {
        this.value = this.value.toUpperCase();
    });
    
    // Phone number validation
    $('#phone').on('input', function() {
        this.value = this.value.replace(/[^0-9+]/g, '');
    });
    
    // Contractor number validation (alphanumeric, uppercase)
    $('#idnumber').on('input', function() {
        this.value = this.value.toUpperCase();
    });
});
</script>

</body>
</html>