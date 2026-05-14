<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['email_Admin'])) {
    header('location:/auth/login_admin.php');
    exit();
}

include($_SERVER['DOCUMENT_ROOT'].'/includes/header.php');
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

// Language system
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'bm';
}

if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] == 'en') ? 'en' : 'bm';
    header('Location: ' . $_SERVER['PHP_SELF'] . '?type=' . ($_GET['type'] ?? ''));
    exit();
}

$lang = $_SESSION['language'];

// Language texts
$text = [];

// Bahasa Malaysia
$text['bm'] = [
    'page_title' => 'Senarai Kenderaan',
    'system_name' => 'NEO V-TRACK - Sistem Pengurusan & Pemantauan Kenderaan',
    'back' => 'Kembali',
    'staff' => 'Staf',
    'student' => 'Pelajar',
    'visitor' => 'Pelawat',
    'contractor' => 'Kontraktor',
    'no' => 'Bil.',
    'name' => 'Nama',
    'phone' => 'No. Telefon',
    'id_number' => 'No. Pengenalan',
    'model' => 'Model Kenderaan',
    'plate_number' => 'No. Plat',
    'sticker' => 'Stiker',
    'sticker_status' => 'Status Stiker',
    'created_at' => 'Dibuat Pada',
    'actions' => 'Tindakan',
    'edit' => 'Ubah',
    'delete' => 'Padam',
    'active' => 'Aktif',
    'removed' => 'Dibuang',
    'no_records' => 'Tiada rekod',
    'company_name' => 'Nama Syarikat',
    'staff_no' => 'No. Staf',
    'matric_no' => 'No. Matrik',
    'ic_passport' => 'No. IC/Pasport'
];

// English
$text['en'] = [
    'page_title' => 'Vehicle List',
    'system_name' => 'NEO V-TRACK - Vehicle Management & Monitoring System',
    'back' => 'Back',
    'staff' => 'Staff',
    'student' => 'Student',
    'visitor' => 'Visitor',
    'contractor' => 'Contractor',
    'no' => 'No.',
    'name' => 'Name',
    'phone' => 'Phone',
    'id_number' => 'ID Number',
    'model' => 'Vehicle Model',
    'plate_number' => 'Plate Number',
    'sticker' => 'Sticker',
    'sticker_status' => 'Sticker Status',
    'created_at' => 'Created At',
    'actions' => 'Actions',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'active' => 'Active',
    'removed' => 'Removed',
    'no_records' => 'No records',
    'company_name' => 'Company Name',
    'staff_no' => 'Staff No.',
    'matric_no' => 'Matric No.',
    'ic_passport' => 'IC/Passport'
];

$t = $text[$lang];
$type = mysqli_real_escape_string($con, $_GET['type'] ?? '');

if (empty($type) || !in_array($type, ['staff', 'student', 'visitor', 'contractor'])) {
    header('Location: /admin/superadmin.php');
    exit();
}

$title = $t[$type];
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
        .header-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        .header-section h1 {
            margin: 0;
            color: #333;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 15px;
            padding: 8px 15px;
            background: #667eea;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            background: #5568d3;
            text-decoration: none;
        }
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 0;
            overflow: hidden;
        }
        .table {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
        }
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #333;
            padding: 15px;
            text-align: left;
        }
        .table tbody td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
        .badge {
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 12px;
        }
        .badge-success {
            background-color: #43e97b;
            color: white;
        }
        .badge-danger {
            background-color: #fa709a;
            color: white;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        .action-buttons a {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .action-buttons a.edit {
            background: #667eea;
            color: white;
        }
        .action-buttons a.edit:hover {
            background: #5568d3;
        }
        .action-buttons a.delete {
            background: #fa709a;
            color: white;
        }
        .action-buttons a.delete:hover {
            background: #f05281;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }
        .pagination {
            justify-content: center;
            padding: 20px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <span class="navbar-brand">
                <i class="fas fa-list me-2"></i><?php echo $t['system_name']; ?>
            </span>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <a href="/admin/superadmin.php" class="back-link">
            <i class="fas fa-arrow-left me-2"></i><?php echo $t['back']; ?>
        </a>

        <div class="header-section">
            <h1><i class="fas fa-car me-2"></i><?php echo $title; ?> - <?php echo $t['page_title']; ?></h1>
        </div>

        <div class="table-container">
            <?php
            $vehicles = [];
            $total = 0;
            
            if ($type === 'staff') {
                $query = "SELECT staffid, name, phone, staffno as id_number, model, platenum, sticker, sticker_status, created_at 
                         FROM staffcar ORDER BY created_at DESC";
                $result = mysqli_query($con, $query);
                if ($result) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $vehicles[] = $row;
                    }
                    $total = count($vehicles);
                }
            } elseif ($type === 'student') {
                $query = "SELECT studentid, name, phone, matric as id_number, model, platenum, sticker, sticker_status, created_at 
                         FROM studentcar ORDER BY created_at DESC";
                $result = mysqli_query($con, $query);
                if ($result) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $vehicles[] = $row;
                    }
                    $total = count($vehicles);
                }
            } elseif ($type === 'visitor') {
                $query = "SELECT visitorid, name, phone, ic_passport as id_number, model, platenum, sticker, sticker_status, created_at 
                         FROM visitorcar ORDER BY created_at DESC";
                $result = @mysqli_query($con, $query);
                if ($result) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $vehicles[] = $row;
                    }
                    $total = count($vehicles);
                }
            } elseif ($type === 'contractor') {
                $query = "SELECT contractorid, name, phone, ic_passport as id_number, company_name, model, platenum, sticker, sticker_status, created_at 
                         FROM contractorcar ORDER BY created_at DESC";
                $result = @mysqli_query($con, $query);
                if ($result) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $vehicles[] = $row;
                    }
                    $total = count($vehicles);
                }
            }
            
            if ($total > 0) {
                ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><?php echo $t['no']; ?></th>
                            <th><?php echo $t['name']; ?></th>
                            <th><?php echo $t['phone']; ?></th>
                            <th><?php echo $t['id_number']; ?></th>
                            <?php if ($type === 'contractor'): ?>
                            <th><?php echo $t['company_name']; ?></th>
                            <?php endif; ?>
                            <th><?php echo $t['model']; ?></th>
                            <th><?php echo $t['plate_number']; ?></th>
                            <th><?php echo $t['sticker']; ?></th>
                            <th><?php echo $t['sticker_status']; ?></th>
                            <th><?php echo $t['created_at']; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicles as $index => $vehicle): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo $vehicle['name']; ?></td>
                            <td><?php echo $vehicle['phone']; ?></td>
                            <td><?php echo $vehicle['id_number']; ?></td>
                            <?php if ($type === 'contractor'): ?>
                            <td><?php echo $vehicle['company_name'] ?? '-'; ?></td>
                            <?php endif; ?>
                            <td><?php echo $vehicle['model']; ?></td>
                            <td><?php echo $vehicle['platenum']; ?></td>
                            <td><?php echo $vehicle['sticker']; ?></td>
                            <td>
                                <?php if ($vehicle['sticker_status'] === 'removed'): ?>
                                    <span class="badge badge-danger"><i class="fas fa-times-circle me-1"></i><?php echo $t['removed']; ?></span>
                                <?php else: ?>
                                    <span class="badge badge-success"><i class="fas fa-check-circle me-1"></i><?php echo $t['active']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($vehicle['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php
            } else {
                ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p><?php echo $t['no_records']; ?></p>
                </div>
                <?php
            }
            ?>
        </div>

        <!-- Stats Summary -->
        <div class="mt-4 p-4 bg-white rounded-3 shadow-sm">
            <p class="text-muted">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo $t['total_vehicles'] ?? 'Total Records'; ?>: <strong><?php echo $total; ?></strong>
            </p>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p><?php echo $t['copyright'] ?? '© Copyright UiTM Johor - Traffic Police'; ?></p>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
