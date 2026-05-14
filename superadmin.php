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

// Check if user is admin
if (!isset($_SESSION['email_Admin'])) {
    header('location:loginAdmin.php');
    exit();
}

// Check if admin is superadmin (first 10 admins are considered superadmin)
$admin_email = $_SESSION['email_Admin'];
$admin_query = mysqli_query($con, "SELECT userid, name FROM admin WHERE email = '$admin_email' AND userid <= 10");
$admin = mysqli_fetch_assoc($admin_query);

if (!$admin) {
    header('Location: admin.php'); // Redirect regular admins
    exit();
}

// Language system
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
    'page_title' => 'Dashboard Superadmin',
    'system_name' => 'NEO V-TRACK - Sistem Pengurusan & Pemantauan Kenderaan',
    'superadmin_role' => 'Superadministrator',
    'logout' => 'Log Keluar',
    'logout_confirm' => 'Adakah anda pasti ingin log keluar?',
    'dashboard_title' => 'Dashboard Superadmin',
    'statistics' => 'Statistik Kenderaan',
    'total_vehicles' => 'Jumlah Kenderaan',
    'staff_vehicles' => 'Kenderaan Staf',
    'student_vehicles' => 'Kenderaan Pelajar',
    'visitor_vehicles' => 'Kenderaan Pelawat',
    'contractor_vehicles' => 'Kenderaan Kontraktor',
    'removed_stickers' => 'Stiker Dibuang',
    'admin_management' => 'Pengurusan Admin',
    'add_new_admin' => 'Tambah Admin Baru',
    'add_new_user' => 'Tambah Pengguna Baru',
    'email' => 'Emel',
    'name' => 'Nama',
    'password' => 'Kata Laluan',
    'confirm_password' => 'Sahkan Kata Laluan',
    'add' => 'Tambah',
    'cancel' => 'Batal',
    'list_admins' => 'Senarai Admin',
    'list_users' => 'Senarai Pengguna',
    'admin_email' => 'Emel Admin',
    'admin_name' => 'Nama Admin',
    'last_login' => 'Log Masuk Terakhir',
    'role' => 'Peranan',
    'status' => 'Status',
    'created_at' => 'Dibuat Pada',
    'user_email' => 'Emel Pengguna',
    'user_name' => 'Nama Pengguna',
    'active' => 'Aktif',
    'inactive' => 'Tidak Aktif',
    'success_message' => 'Operasi berjaya!',
    'error_message' => 'Terdapat ralat',
    'copyright' => '© Hak Cipta Universiti Teknologi MARA Cawangan Johor - Polis Bantuan | ICT Security',
    'nav_dashboard' => 'Anjung',
    'nav_search' => 'Carian Kenderaan',
    'nav_staff' => 'Staf',
    'nav_student' => 'Pelajar',
    'nav_visitor' => 'Pelawat',
    'nav_contractor' => 'Kontraktor',
    'nav_user_mgmt' => 'Pengguna',
    'nav_admin' => 'Admin',
    'nav_superadmin' => 'Superadmin'
];

// English
$text['en'] = [
    'page_title' => 'Superadmin Dashboard',
    'system_name' => 'NEO V-TRACK - Vehicle Management & Monitoring System',
    'superadmin_role' => 'Superadministrator',
    'logout' => 'Log Out',
    'logout_confirm' => 'Are you sure you want to log out?',
    'dashboard_title' => 'Superadmin Dashboard',
    'statistics' => 'Vehicle Statistics',
    'total_vehicles' => 'Total Vehicles',
    'staff_vehicles' => 'Staff Vehicles',
    'student_vehicles' => 'Student Vehicles',
    'visitor_vehicles' => 'Visitor Vehicles',
    'contractor_vehicles' => 'Contractor Vehicles',
    'removed_stickers' => 'Removed Stickers',
    'admin_management' => 'Admin Management',
    'add_new_admin' => 'Add New Admin',
    'add_new_user' => 'Add New User',
    'email' => 'Email',
    'name' => 'Name',
    'password' => 'Password',
    'confirm_password' => 'Confirm Password',
    'add' => 'Add',
    'cancel' => 'Cancel',
    'list_admins' => 'Admin List',
    'list_users' => 'User List',
    'admin_email' => 'Admin Email',
    'admin_name' => 'Admin Name',
    'last_login' => 'Last Login',
    'role' => 'Role',
    'status' => 'Status',
    'created_at' => 'Created At',
    'user_email' => 'User Email',
    'user_name' => 'User Name',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'success_message' => 'Operation successful!',
    'error_message' => 'An error occurred',
    'copyright' => '© Copyright UiTM Johor Branch - Traffic Police | ICT Security',
    'nav_dashboard' => 'Dashboard',
    'nav_search' => 'Search Vehicle',
    'nav_staff' => 'Staff',
    'nav_student' => 'Student',
    'nav_visitor' => 'Visitor',
    'nav_contractor' => 'Contractor',
    'nav_user_mgmt' => 'Users',
    'nav_admin' => 'Admin',
    'nav_superadmin' => 'Superadmin'
];

$t = $text[$lang];
$output = '';

// Get vehicle statistics
$staff_query = mysqli_query($con, "SELECT COUNT(*) as count FROM staffcar");
$staff_row = mysqli_fetch_assoc($staff_query);
$staff_count = $staff_row['count'];

$student_query = mysqli_query($con, "SELECT COUNT(*) as count FROM studentcar");
$student_row = mysqli_fetch_assoc($student_query);
$student_count = $student_row['count'];

// Try to get visitor count (table might not exist yet)
$visitor_count = 0;
$visitor_query = @mysqli_query($con, "SELECT COUNT(*) as count FROM visitorcar");
if ($visitor_query) {
    $visitor_row = mysqli_fetch_assoc($visitor_query);
    $visitor_count = $visitor_row['count'];
}

// Try to get contractor count (table might not exist yet)
$contractor_count = 0;
$contractor_query = @mysqli_query($con, "SELECT COUNT(*) as count FROM contractorcar");
if ($contractor_query) {
    $contractor_row = mysqli_fetch_assoc($contractor_query);
    $contractor_count = $contractor_row['count'];
}

$total_count = $staff_count + $student_count + $visitor_count + $contractor_count;

// Get admin list
$admins_query = mysqli_query($con, "SELECT userid, email, name, last_login FROM admin ORDER BY userid DESC LIMIT 10");
$admins = [];
while ($row = mysqli_fetch_assoc($admins_query)) {
    $admins[] = $row;
}

// Get user list
$users_query = mysqli_query($con, "SELECT userid, email, name, last_login FROM user ORDER BY userid DESC LIMIT 10");
$users = [];
while ($row = mysqli_fetch_assoc($users_query)) {
    $users[] = $row;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $t['page_title']; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 18px;
        }
        .sidebar {
            background: white;
            border-right: 1px solid #e0e0e0;
            padding: 20px;
            border-radius: 8px;
        }
        .sidebar .nav-link {
            color: #333;
            padding: 10px 15px;
            margin-bottom: 8px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #667eea;
            color: white;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 15px;
        }
        .stat-card h3 {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-card p {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            border-radius: 6px;
            border: 1px solid #ddd;
            padding: 10px 12px;
            font-size: 14px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 6px;
            padding: 10px 20px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6a3a8f 100%);
        }
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #333;
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
        .badge {
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 12px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 12px;
        }
        .tab-pane {
            padding: 20px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <span class="navbar-brand">
                <i class="fas fa-shield-alt me-2"></i><?php echo $t['system_name']; ?>
            </span>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-language me-2"></i><?php echo ($lang == 'en') ? 'EN' : 'BM'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
                            <li><a class="dropdown-item" href="?lang=en">English</a></li>
                            <li><a class="dropdown-item" href="?lang=bm">Bahasa Melayu</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link text-white">
                            <i class="fas fa-user-shield me-2"></i><?php echo $admin['name']; ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="?logout=1" onclick="return confirm('<?php echo $t['logout_confirm']; ?>');">
                            <i class="fas fa-sign-out-alt me-2"></i><?php echo $t['logout']; ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2">
                <div class="sidebar">
                    <h5 class="mb-3"><i class="fas fa-list me-2"></i>Menu</h5>
                    <a href="admin.php" class="nav-link"><i class="fas fa-chart-line me-2"></i><?php echo $t['nav_dashboard']; ?></a>
                    <a href="staffcar.php" class="nav-link"><i class="fas fa-car me-2"></i><?php echo $t['nav_staff']; ?></a>
                    <a href="studentcar.php" class="nav-link"><i class="fas fa-car me-2"></i><?php echo $t['nav_student']; ?></a>
                    <a href="Visitorcar.php" class="nav-link"><i class="fas fa-car me-2"></i><?php echo $t['nav_visitor']; ?></a>
                    <a href="contractorcar.php" class="nav-link"><i class="fas fa-car me-2"></i><?php echo $t['nav_contractor']; ?></a>
                    <a href="user.php" class="nav-link"><i class="fas fa-users me-2"></i><?php echo $t['nav_user_mgmt']; ?></a>
                    <a href="admin.php" class="nav-link"><i class="fas fa-user-tie me-2"></i><?php echo $t['nav_admin']; ?></a>
                    <a href="superadmin.php" class="nav-link active"><i class="fas fa-crown me-2"></i><?php echo $t['nav_superadmin']; ?></a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <h1 class="mb-4"><i class="fas fa-crown me-2 text-warning"></i><?php echo $t['dashboard_title']; ?></h1>

                <!-- Statistics Section -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <a href="vehicle_list_drill_down.php?type=staff" style="text-decoration: none; color: inherit;">
                            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); cursor: pointer;">
                                <p><i class="fas fa-car me-2"></i><?php echo $t['total_vehicles']; ?></p>
                                <h3><?php echo $total_count; ?></h3>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="vehicle_list_drill_down.php?type=staff" style="text-decoration: none; color: inherit;">
                            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); cursor: pointer;">
                            <p><i class="fas fa-briefcase me-2"></i><?php echo $t['staff_vehicles']; ?></p>
                            <h3><?php echo $staff_count; ?></h3>
                        </div>
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="vehicle_list_drill_down.php?type=student" style="text-decoration: none; color: inherit;">
                            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); cursor: pointer;">
                                <p><i class="fas fa-book me-2"></i><?php echo $t['student_vehicles']; ?></p>
                                <h3><?php echo $student_count; ?></h3>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="vehicle_list_drill_down.php?type=visitor" style="text-decoration: none; color: inherit;">
                            <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); cursor: pointer;">
                                <p><i class="fas fa-user-tie me-2"></i><?php echo $t['visitor_vehicles']; ?></p>
                                <h3><?php echo $visitor_count; ?></h3>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="vehicle_list_drill_down.php?type=contractor" style="text-decoration: none; color: inherit;">
                            <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); cursor: pointer;">
                                <p><i class="fas fa-building me-2"></i><?php echo $t['contractor_vehicles']; ?></p>
                                <h3><?php echo $contractor_count; ?></h3>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Admin Management Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light border-0 pt-4 pb-2">
                        <h5><i class="fas fa-users-cog me-2 text-primary"></i><?php echo $t['admin_management']; ?></h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="add-admin-tab" data-bs-toggle="tab" data-bs-target="#add-admin" type="button" role="tab">
                                    <i class="fas fa-plus me-2"></i><?php echo $t['add_new_admin']; ?>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="add-user-tab" data-bs-toggle="tab" data-bs-target="#add-user" type="button" role="tab">
                                    <i class="fas fa-plus me-2"></i><?php echo $t['add_new_user']; ?>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="list-admins-tab" data-bs-toggle="tab" data-bs-target="#list-admins" type="button" role="tab">
                                    <i class="fas fa-list me-2"></i><?php echo $t['list_admins']; ?>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="list-users-tab" data-bs-toggle="tab" data-bs-target="#list-users" type="button" role="tab">
                                    <i class="fas fa-list me-2"></i><?php echo $t['list_users']; ?>
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- Add Admin Tab -->
                            <div class="tab-pane fade show active" id="add-admin" role="tabpanel">
                                <form id="addAdminForm" method="POST" action="admin_management_api.php">
                                    <input type="hidden" name="action" value="add_admin">
                                    <div class="mb-3">
                                        <label for="adminEmail" class="form-label"><?php echo $t['email']; ?></label>
                                        <input type="email" class="form-control" id="adminEmail" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="adminName" class="form-label"><?php echo $t['name']; ?></label>
                                        <input type="text" class="form-control" id="adminName" name="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="adminPassword" class="form-label"><?php echo $t['password']; ?></label>
                                        <input type="password" class="form-control" id="adminPassword" name="password" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i><?php echo $t['add']; ?></button>
                                </form>
                            </div>

                            <!-- Add User Tab -->
                            <div class="tab-pane fade" id="add-user" role="tabpanel">
                                <form id="addUserForm" method="POST" action="admin_management_api.php">
                                    <input type="hidden" name="action" value="add_user">
                                    <div class="mb-3">
                                        <label for="userEmail" class="form-label"><?php echo $t['email']; ?></label>
                                        <input type="email" class="form-control" id="userEmail" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="userName" class="form-label"><?php echo $t['name']; ?></label>
                                        <input type="text" class="form-control" id="userName" name="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="userPassword" class="form-label"><?php echo $t['password']; ?></label>
                                        <input type="password" class="form-control" id="userPassword" name="password" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i><?php echo $t['add']; ?></button>
                                </form>
                            </div>

                            <!-- List Admins Tab -->
                            <div class="tab-pane fade" id="list-admins" role="tabpanel">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><?php echo $t['admin_email']; ?></th>
                                            <th><?php echo $t['admin_name']; ?></th>
                                            <th><?php echo $t['last_login']; ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($admins as $admin_row): ?>
                                        <tr>
                                            <td><?php echo $admin_row['email']; ?></td>
                                            <td><?php echo $admin_row['name']; ?></td>
                                            <td><?php echo $admin_row['last_login'] ?? 'N/A'; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- List Users Tab -->
                            <div class="tab-pane fade" id="list-users" role="tabpanel">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><?php echo $t['user_email']; ?></th>
                                            <th><?php echo $t['user_name']; ?></th>
                                            <th><?php echo $t['last_login']; ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user_row): ?>
                                        <tr>
                                            <td><?php echo $user_row['email']; ?></td>
                                            <td><?php echo $user_row['name']; ?></td>
                                            <td><?php echo $user_row['last_login'] ?? 'N/A'; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer mt-5">
        <p><?php echo $t['copyright']; ?></p>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle form submissions with AJAX
        document.getElementById('addAdminForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('admin_management_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('<?php echo $t["success_message"]; ?>');
                    this.reset();
                    document.getElementById('list-admins-tab').click();
                } else {
                    alert(data.message || '<?php echo $t["error_message"]; ?>');
                }
            })
            .catch(error => alert('<?php echo $t["error_message"]; ?>: ' + error));
        });

        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('admin_management_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('<?php echo $t["success_message"]; ?>');
                    this.reset();
                    document.getElementById('list-users-tab').click();
                } else {
                    alert(data.message || '<?php echo $t["error_message"]; ?>');
                }
            })
            .catch(error => alert('<?php echo $t["error_message"]; ?>: ' + error));
        });
    </script>
</body>
</html>
