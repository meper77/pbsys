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
    'page_title' => 'Kemaskini Admin',
    'system_name' => 'NEO V-TRACK - Sistem Pengurusan & Pemantauan Kenderaan',
    'email' => 'Email',
    'password' => 'Kata Laluan',
    'name' => 'Nama',
    'email_placeholder' => 'Isi email',
    'password_placeholder' => 'Isi kata laluan',
    'name_placeholder' => 'Isi nama penuh',
    'save' => 'Simpan',
    'back' => 'Kembali',
    'update_success' => 'Admin berjaya dikemaskini!',
    'update_failed' => 'Gagal mengemaskini admin',
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
    'page_title' => 'Update Admin',
    'system_name' => 'NEO V-TRACK - Vehicle Management & Monitoring System',
    'email' => 'Email',
    'password' => 'Password',
    'name' => 'Name',
    'email_placeholder' => 'Enter email',
    'password_placeholder' => 'Enter password',
    'name_placeholder' => 'Enter full name',
    'save' => 'Save',
    'back' => 'Back',
    'update_success' => 'Admin updated successfully!',
    'update_failed' => 'Failed to update admin',
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

// First, let's check the column names in the admin table
$check_columns = mysqli_query($con, "SHOW COLUMNS FROM admin");
$columns = [];
if ($check_columns) {
    while ($col = mysqli_fetch_assoc($check_columns)) {
        $columns[] = $col['Field'];
    }
}

// Debug: Check what columns exist
// echo "<pre>Columns in admin table: ";
// print_r($columns);
// echo "</pre>";

// Determine the correct ID column name
$id_column = 'adminid'; // default
if (in_array('adminid', $columns)) {
    $id_column = 'adminid';
} elseif (in_array('id', $columns)) {
    $id_column = 'id';
} elseif (in_array('userid', $columns)) {
    $id_column = 'userid';
} elseif (in_array('admin_id', $columns)) {
    $id_column = 'admin_id';
}

// Get admin data based on correct column names
$admin_query = @mysqli_query($con, "SELECT * FROM admin WHERE email = '$admin_email'");
if ($admin_query && mysqli_num_rows($admin_query) > 0) {
    $admin_data = mysqli_fetch_assoc($admin_query);
    // Determine name column
    $name_column = 'name';
    if (isset($admin_data['name_Admin'])) {
        $name_column = 'name_Admin';
    } elseif (isset($admin_data['name'])) {
        $name_column = 'name';
    } elseif (isset($admin_data['admin_name'])) {
        $name_column = 'admin_name';
    }
    
    if (!empty($admin_data[$name_column])) {
        $admin_display = $admin_data[$name_column];
    } else {
        $admin_display = strstr($admin_email, '@', true) ?: $admin_email;
    }
}

// Handle form submission
if (isset($_POST['submit'])) {
    $id = $_GET['id'];
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $name = mysqli_real_escape_string($con, $_POST['name']);
    
    // Check what columns actually exist for updating
    $update_fields = [];
    if (in_array('email', $columns)) {
        $update_fields['email'] = $email;
    } elseif (in_array('email_Admin', $columns)) {
        $update_fields['email_Admin'] = $email;
    }
    
    if (in_array('password', $columns)) {
        $update_fields['password'] = $password;
    } elseif (in_array('password_Admin', $columns)) {
        $update_fields['password_Admin'] = $password;
    }
    
    if (in_array('name', $columns)) {
        $update_fields['name'] = $name;
    } elseif (in_array('name_Admin', $columns)) {
        $update_fields['name_Admin'] = $name;
    }
    
    // Check if it's the same email
    $check_query = mysqli_query($con, "SELECT email FROM admin WHERE $id_column = $id");
    if ($check_query && mysqli_num_rows($check_query) > 0) {
        $current_data = mysqli_fetch_assoc($check_query);
        $current_email = $current_data['email'] ?? $current_data['email_Admin'] ?? '';
        
        if ($email !== $current_email) {
            // Check if new email already exists
            $email_column = in_array('email', $columns) ? 'email' : 'email_Admin';
            $email_check = mysqli_query($con, "SELECT $id_column FROM admin WHERE $email_column = '$email'");
            if (mysqli_num_rows($email_check) > 0) {
                $error_message = ($lang == 'bm') ? "Email sudah wujud!" : "Email already exists!";
                echo "<script>alert('$error_message');</script>";
            } else {
                updateAdmin($con, $id, $update_fields, $id_column, $t);
            }
        } else {
            updateAdmin($con, $id, $update_fields, $id_column, $t);
        }
    } else {
        updateAdmin($con, $id, $update_fields, $id_column, $t);
    }
}

function updateAdmin($con, $id, $update_fields, $id_column, $t) {
    $set_clause = [];
    foreach ($update_fields as $field => $value) {
        $set_clause[] = "$field = '$value'";
    }
    
    $sql = "UPDATE `admin` SET " . implode(', ', $set_clause) . " WHERE $id_column = $id";
    $result = mysqli_query($con, $sql);
    
    if ($result) {
        echo "<script>alert('{$t['update_success']}'); window.location.href='/admin/dashboard.php';</script>";
        exit();
    } else {
        $error_msg = $t['update_failed'] . ": " . mysqli_error($con);
        echo "<script>alert('$error_msg');</script>";
    }
}

// Get admin data for editing
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$email = $password = $name = '';

if ($id > 0) {
    // Determine which columns to select
    $select_fields = [];
    if (in_array('email', $columns)) {
        $select_fields[] = 'email';
    } elseif (in_array('email_Admin', $columns)) {
        $select_fields[] = 'email_Admin as email';
    }
    
    if (in_array('password', $columns)) {
        $select_fields[] = 'password';
    } elseif (in_array('password_Admin', $columns)) {
        $select_fields[] = 'password_Admin as password';
    }
    
    if (in_array('name', $columns)) {
        $select_fields[] = 'name';
    } elseif (in_array('name_Admin', $columns)) {
        $select_fields[] = 'name_Admin as name';
    }
    
    $select_sql = "SELECT " . implode(', ', $select_fields) . " FROM `admin` WHERE $id_column = $id";
    $result = mysqli_query($con, $select_sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $email = $row['email'] ?? '';
        $password = $row['password'] ?? '';
        $name = $row['name'] ?? '';
    } else {
        echo "<script>alert('Admin tidak ditemui!'); window.location.href='/admin/dashboard.php';</script>";
        exit();
    }
} else {
    echo "<script>alert('ID Admin tidak sah!'); window.location.href='/admin/dashboard.php';</script>";
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
        
        .form-control {
            border: 2px solid #e1e5f1;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .form-control:focus {
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
                <h1><i class="fas fa-user-shield"></i> <?php echo $t['page_title']; ?></h1>
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
                    <a class="nav-link active" href="/admin/dashboard.php">
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
            <h2 class="card-title"><i class="fas fa-user-edit me-2"></i><?php echo $t['page_title']; ?></h2>
        </div>
        
        <form method="POST">
            <div class="mb-4">
                <label for="email" class="form-label"><?php echo $t['email']; ?>:</label>
                <input type="email" id="email" name="email" placeholder="<?php echo $t['email_placeholder']; ?>" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label"><?php echo $t['password']; ?>:</label>
                <input type="text" id="password" name="password" placeholder="<?php echo $t['password_placeholder']; ?>" class="form-control" value="<?php echo htmlspecialchars($password); ?>" required>
            </div>
            
            <div class="mb-4">
                <label for="name" class="form-label"><?php echo $t['name']; ?>:</label>
                <input type="text" id="name" name="name" placeholder="<?php echo $t['name_placeholder']; ?>" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            
            <div class="d-flex gap-3">
                <button type="submit" name="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i><?php echo $t['save']; ?>
                </button>
                <a href="/admin/dashboard.php" class="btn btn-danger">
                    <i class="fas fa-arrow-left me-2"></i><?php echo $t['back']; ?>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>