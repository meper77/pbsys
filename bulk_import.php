<?php
session_start();

// ========== LOGOUT HANDLER ==========
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: roleSelection.php');
    exit();
}
// ========== END LOGOUT HANDLER ==========

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
    'page_title' => 'Import Kenderaan',
    'system_name' => 'NEO V-TRACK - Sistem Pengurusan & Pemantauan Kenderaan',
    'nav_import' => 'Import Kenderaan',
    'import_title' => 'Import Kenderaan (Banyak)',
    'import_desc' => 'Muat naik fail CSV untuk menambah banyak kenderaan sekaligus',
    'download_template' => 'Muat Turun Template',
    'choose_file' => 'Pilih Fail CSV',
    'upload' => 'Muat Naik',
    'back' => 'Kembali',
    'success' => 'Berjaya',
    'error' => 'Ralat',
    'instructions' => 'Arahan',
    'step1' => 'Langkah 1: Muat turun template CSV',
    'step2' => 'Langkah 2: Isi data dalam Excel',
    'step3' => 'Langkah 3: Simpan sebagai fail CSV',
    'step4' => 'Langkah 4: Muat naik fail di bawah',
    'csv_format' => 'Format CSV:',
    'csv_columns' => 'nama,telefon,id number,jenis,status,nombor plat',
    'example' => 'Contoh:',
    'example_row' => 'Ali Ahmad,0123456789,12345,KERETA,Staf,ABC1234',
    'status_options' => 'Status: Staf, Pelajar, Pelawat, Kontraktor',
    'type_options' => 'Jenis: KERETA, MOTOSIKAL, LORI, 4WD, VAN, MPV',
    'file_required' => 'Sila pilih fail CSV',
    'upload_success' => 'Data berjaya diimport!',
    'upload_error' => 'Ralat semasa mengimport data',
    'rows_imported' => 'rekod berjaya diimport',
    'rows_failed' => 'rekod gagal',
    'invalid_format' => 'Format fail tidak sah',
    'duplicate_plate' => 'Nombor plat sudah wujud: ',
    'logout' => 'Log Keluar',
    'logout_confirm' => 'Adakah anda pasti ingin log keluar?',
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
    'page_title' => 'Import Vehicles',
    'system_name' => 'NEO V-TRACK - Vehicle Management & Monitoring System',
    'nav_import' => 'Import Vehicles',
    'import_title' => 'Import Vehicles (Multiple)',
    'import_desc' => 'Upload CSV file to add multiple vehicles at once',
    'download_template' => 'Download Template',
    'choose_file' => 'Choose CSV File',
    'upload' => 'Upload',
    'back' => 'Back',
    'success' => 'Success',
    'error' => 'Error',
    'instructions' => 'Instructions',
    'step1' => 'Step 1: Download CSV template',
    'step2' => 'Step 2: Fill data in Excel',
    'step3' => 'Step 3: Save as CSV file',
    'step4' => 'Step 4: Upload file below',
    'csv_format' => 'CSV Format:',
    'csv_columns' => 'name,phone,id number,type,status,plate number',
    'example' => 'Example:',
    'example_row' => 'Ali Ahmad,0123456789,12345,CAR,Staff,ABC1234',
    'status_options' => 'Status: Staff, Student, Visitor, Contractor',
    'type_options' => 'Type: CAR, MOTORCYCLE, LORRY, 4WD, VAN, MPV',
    'file_required' => 'Please select CSV file',
    'upload_success' => 'Data imported successfully!',
    'upload_error' => 'Error importing data',
    'rows_imported' => 'records imported successfully',
    'rows_failed' => 'records failed',
    'invalid_format' => 'Invalid file format',
    'duplicate_plate' => 'Plate number already exists: ',
    'logout' => 'Log Out',
    'logout_confirm' => 'Are you sure you want to log out?',
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

// Handle CSV upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    
    if ($_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['csv_file']['tmp_name'];
        $name = $_FILES['csv_file']['name'];
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        
        if (strtolower($ext) == 'csv') {
            if (($handle = fopen($tmp_name, 'r')) !== FALSE) {
                // Skip header row if exists
                $header = fgetcsv($handle);
                
                while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    // Check if we have enough columns
                    if (count($data) >= 6) {
                        $name = mysqli_real_escape_string($con, trim($data[0]));
                        $phone = mysqli_real_escape_string($con, trim($data[1]));
                        $idnumber = mysqli_real_escape_string($con, trim($data[2]));
                        $type = mysqli_real_escape_string($con, trim($data[3]));
                        $status = mysqli_real_escape_string($con, trim($data[4]));
                        $platenum = mysqli_real_escape_string($con, trim($data[5]));
                        
                        // Validate required fields
                        if (!empty($name) && !empty($platenum) && !empty($status)) {
                            // Check if vehicle already exists
                            $check_sql = "SELECT id FROM owner WHERE platenum = '$platenum'";
                            $check_result = mysqli_query($con, $check_sql);
                            
                            if (mysqli_num_rows($check_result) == 0) {
                                // Insert new vehicle
                                $sql = "INSERT INTO owner (name, phone, idnumber, type, status, platenum) 
                                        VALUES ('$name', '$phone', '$idnumber', '$type', '$status', '$platenum')";
                                
                                if (mysqli_query($con, $sql)) {
                                    $success_count++;
                                } else {
                                    $error_count++;
                                    $errors[] = "Baris " . ($success_count + $error_count) . ": " . mysqli_error($con);
                                }
                            } else {
                                $error_count++;
                                $errors[] = $t['duplicate_plate'] . $platenum;
                            }
                        } else {
                            $error_count++;
                            $errors[] = "Baris " . ($success_count + $error_count) . ": Data tidak lengkap";
                        }
                    } else {
                        $error_count++;
                        $errors[] = "Baris " . ($success_count + $error_count) . ": Format tidak sah";
                    }
                }
                fclose($handle);
                
                if ($success_count > 0) {
                    $message = "$success_count {$t['rows_imported']}";
                    if ($error_count > 0) {
                        $message .= ", $error_count {$t['rows_failed']}";
                    }
                    
                    echo "<script>
                        alert('{$t['upload_success']}\\n$message');
                        window.location.href='bulk_import.php';
                    </script>";
                } else {
                    $error_msg = implode('\\n', $errors);
                    echo "<script>
                        alert('{$t['upload_error']}\\n$error_msg');
                    </script>";
                }
                exit();
            } else {
                echo "<script>alert('{$t['upload_error']}: Tidak dapat membaca fail');</script>";
            }
        } else {
            echo "<script>alert('{$t['invalid_format']}: Sila gunakan fail CSV');</script>";
        }
    } else {
        echo "<script>alert('{$t['upload_error']}: Ralat muat naik fail');</script>";
    }
}

// Generate template CSV file
if (isset($_GET['download_template'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="template_kenderaan.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel compatibility
    fwrite($output, "\xEF\xBB\xBF");
    
    // Header row in both languages based on current language
    if ($lang == 'bm') {
        $header = ['nama', 'telefon', 'idnumber', 'jenis', 'status', 'platenum'];
    } else {
        $header = ['name', 'phone', 'idnumber', 'type', 'status', 'platenum'];
    }
    fputcsv($output, $header);
    
    // Example rows
    $examples = [
        ['Ali Ahmad', '0123456789', '12345', 'KERETA', 'Staf', 'ABC1234'],
        ['Siti Sarah', '0134567890', '2023001', 'MOTOSIKAL', 'Pelajar', 'DEF5678'],
        ['John Doe', '0145678901', 'IC123456', 'VAN', 'Pelawat', 'GHI9012'],
        ['Ahmad Kontraktor', '0156789012', 'K001', 'LORI', 'Kontraktor', 'JKL3456']
    ];
    
    foreach ($examples as $example) {
        fputcsv($output, $example);
    }
    
    fclose($output);
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
        
        /* Black Navbar */
        .navbar-black {
            background-color: #000000 !important;
            padding: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-bottom: 3px solid var(--uitm-red);
        }
        
        .navbar-black .container {
            max-width: 1400px;
        }
        
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
            max-width: 900px;
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
        
        .card-subtitle {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
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
        
        .btn-success {
            background: linear-gradient(135deg, var(--success-color) 0%, #96c93d 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 176, 155, 0.3);
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
        
        .instruction-box {
            background: #f8f9ff;
            border: 2px dashed #e1e5f1;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .instruction-step {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            margin-top: 10px;
        }
        
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-label {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--uitm-blue) 100%);
            color: white;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-input-label:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 41, 128, 0.3);
        }
        
        .file-name {
            margin-left: 15px;
            font-style: italic;
            color: #666;
        }
        
        .csv-preview {
            background: #f5f7fb;
            border: 1px solid #e1e5f1;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
        }
        
        @media (max-width: 768px) {
            .navbar-black .navbar-nav {
                flex-direction: column;
                width: 100%;
            }
            
            .navbar-black .nav-item {
                width: 100%;
                border-bottom: 1px solid rgba(255,255,255,0.1);
                border-right: none;
            }
            
            .form-card {
                padding: 20px;
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
                <h1><i class="fas fa-file-import"></i> <?php echo $t['page_title']; ?></h1>
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
                    <a class="nav-link" href="admin.php">
                        <i class="fas fa-user-shield me-1"></i><?php echo $t['nav_admin']; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="bulk_import.php">
                        <i class="fas fa-file-import me-1"></i><?php echo $t['nav_import']; ?>
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
    <div class="form-card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-file-import me-2"></i><?php echo $t['import_title']; ?></h2>
            <p class="card-subtitle"><?php echo $t['import_desc']; ?></p>
        </div>
        
        <!-- Instructions Section -->
        <div class="instruction-box">
            <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i><?php echo $t['instructions']; ?></h5>
            
            <div class="instruction-step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <strong><?php echo $t['step1']; ?></strong><br>
                    <a href="?download_template=1" class="btn btn-success btn-sm mt-2">
                        <i class="fas fa-download me-1"></i><?php echo $t['download_template']; ?>
                    </a>
                </div>
            </div>
            
            <div class="instruction-step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <strong><?php echo $t['step2']; ?></strong><br>
                    <small><strong><?php echo $t['csv_format']; ?></strong> <?php echo $t['csv_columns']; ?></small><br>
                    <small><strong><?php echo $t['example']; ?></strong> <?php echo $t['example_row']; ?></small><br>
                    <small><strong><?php echo $t['status_options']; ?></strong></small><br>
                    <small><strong><?php echo $t['type_options']; ?></strong></small>
                    
                    <div class="csv-preview mt-2">
                        <?php echo $t['csv_columns']; ?><br>
                        <?php echo $t['example_row']; ?><br>
                        Siti Sarah,0134567890,2023001,MOTOSIKAL,Pelajar,DEF5678<br>
                        John Doe,0145678901,IC123456,VAN,Pelawat,GHI9012
                    </div>
                </div>
            </div>
            
            <div class="instruction-step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <strong><?php echo $t['step3']; ?></strong><br>
                    <small>Dalam Excel: File → Save As → Pilih "CSV (Comma delimited) (*.csv)"</small>
                </div>
            </div>
            
            <div class="instruction-step">
                <div class="step-number">4</div>
                <div class="step-content">
                    <strong><?php echo $t['step4']; ?></strong>
                </div>
            </div>
        </div>
        
        <!-- Upload Form -->
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="mb-4">
                <label class="form-label d-block mb-3"><?php echo $t['choose_file']; ?>:</label>
                <div class="file-input-wrapper">
                    <input type="file" id="csv_file" name="csv_file" accept=".csv" required onchange="updateFileName()">
                    <label for="csv_file" class="file-input-label">
                        <i class="fas fa-upload me-2"></i><?php echo $t['choose_file']; ?>
                    </label>
                    <span id="file-name" class="file-name">Tiada fail dipilih</span>
                </div>
            </div>
            
            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload me-2"></i><?php echo $t['upload']; ?>
                </button>
                <a href="index.php" class="btn btn-danger">
                    <i class="fas fa-arrow-left me-2"></i><?php echo $t['back']; ?>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
function updateFileName() {
    const fileInput = document.getElementById('csv_file');
    const fileName = document.getElementById('file-name');
    
    if (fileInput.files.length > 0) {
        fileName.textContent = fileInput.files[0].name;
        fileName.style.color = '#28a745';
        fileName.style.fontWeight = '600';
    } else {
        fileName.textContent = 'Tiada fail dipilih';
        fileName.style.color = '#666';
        fileName.style.fontWeight = 'normal';
    }
}

// Form validation
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('csv_file');
    
    if (!fileInput.files.length) {
        e.preventDefault();
        alert('<?php echo $t['file_required']; ?>');
        return false;
    }
    
    const file = fileInput.files[0];
    const fileExt = file.name.split('.').pop().toLowerCase();
    
    if (fileExt !== 'csv') {
        e.preventDefault();
        alert('<?php echo $t['invalid_format']; ?>');
        return false;
    }
    
    // Show loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memuat naik...';
    submitBtn.disabled = true;
});
</script>

</body>
</html>