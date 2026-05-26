<?php
session_start();

// ========== LOGOUT HANDLER ==========
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /auth/role_selection.php');
    exit();
}
// ========== END LOGOUT HANDLER ==========

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
include $_SERVER['DOCUMENT_ROOT'].'/includes/permission_check.php';

require_admin();

if (!can_view_import()) {
  redirect_unauthorized();
}

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
                        window.location.href='/admin/bulk_import.php';
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
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>
<body>
<div class="nv-shell">
<?php $nv_active = 'bulk'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php'; ?>
<main class="page">
    <div class="page-head">
        <div>
            <span class="eyebrow">Pentadbir</span>
            <h1><?= htmlspecialchars($t['import_title']) ?></h1>
            <p class="sub"><?= htmlspecialchars($t['import_desc']) ?></p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="?download_template=1"><i data-lucide="download"></i> <?= htmlspecialchars($t['download_template']) ?></a>
        </div>
    </div>

    <div class="nv-grid cols-2" style="align-items:start;">
        <div class="card">
            <span class="eyebrow"><?= htmlspecialchars($t['instructions']) ?></span>
            <h3 class="text-display mt-2 mb-4">CSV import steps</h3>
            <div class="nv-stack gap-4">
                <div class="nv-row gap-3"><span class="plate" style="min-width:32px;text-align:center;">1</span><div><strong><?= htmlspecialchars($t['step1']) ?></strong></div></div>
                <div class="nv-row gap-3"><span class="plate" style="min-width:32px;text-align:center;">2</span><div><strong><?= htmlspecialchars($t['step2']) ?></strong><div class="text-mono text-muted mt-2" style="font-size:12px;"><?= htmlspecialchars($t['csv_columns']) ?></div></div></div>
                <div class="nv-row gap-3"><span class="plate" style="min-width:32px;text-align:center;">3</span><div><strong><?= htmlspecialchars($t['step3']) ?></strong></div></div>
                <div class="nv-row gap-3"><span class="plate" style="min-width:32px;text-align:center;">4</span><div><strong><?= htmlspecialchars($t['step4']) ?></strong></div></div>
            </div>
            <div class="card flat mt-6" style="background:var(--surface-tint);">
                <span class="eyebrow"><?= htmlspecialchars($t['example']) ?></span>
                <div class="text-mono mt-2" style="font-size:12px;line-height:1.7;color:var(--brand-purple-deep);">
                    <?= htmlspecialchars($t['csv_columns']) ?><br>
                    <?= htmlspecialchars($t['example_row']) ?><br>
                    Siti Sarah,0134567890,2023001,MOTOSIKAL,Pelajar,DEF5678<br>
                    John Doe,0145678901,IC123456,VAN,Pelawat,GHI9012
                </div>
                <div class="text-muted mt-4" style="font-size:12px;">
                    <strong><?= htmlspecialchars($t['status_options']) ?></strong><br>
                    <strong><?= htmlspecialchars($t['type_options']) ?></strong>
                </div>
            </div>
        </div>

        <form class="card nv-stack gap-6" method="POST" enctype="multipart/form-data" id="uploadForm">
            <div>
                <span class="eyebrow">Upload</span>
                <h3 class="text-display mt-2"><?= htmlspecialchars($t['choose_file']) ?></h3>
            </div>
            <div class="field">
                <label class="field-label" for="csv_file">CSV file</label>
                <input class="input" id="csv_file" name="csv_file" type="file" accept=".csv" required onchange="updateFileName()">
                <span class="field-hint" id="file-name">No file chosen</span>
            </div>
            <div class="nv-row end gap-2">
                <a class="btn btn-ghost" href="/admin/dashboard.php"><i data-lucide="arrow-left"></i> <?= htmlspecialchars($t['back']) ?></a>
                <button class="btn btn-primary" type="submit"><i data-lucide="upload"></i> <?= htmlspecialchars($t['upload']) ?></button>
            </div>
        </form>
    </div>
</main>
</div>
<script>
function updateFileName() {
    var fi = document.getElementById('csv_file');
    var fn = document.getElementById('file-name');
    if (fi.files.length > 0) { fn.textContent = fi.files[0].name; fn.style.color = 'var(--status-ok)'; }
    else { fn.textContent = 'No file chosen'; fn.style.color = 'var(--fg-3)'; }
}
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    var fi = document.getElementById('csv_file');
    if (!fi.files.length) { e.preventDefault(); alert('<?php echo addslashes($t['file_required']); ?>'); return false; }
    var ext = fi.files[0].name.split('.').pop().toLowerCase();
    if (ext !== 'csv') { e.preventDefault(); alert('<?php echo addslashes($t['invalid_format']); ?>'); return false; }
    var btn = this.querySelector('button[type="submit"]');
    btn.innerHTML = '<i data-lucide="loader"></i> ' + '<?php echo addslashes($t['upload']); ?>';
    btn.disabled = true;
});
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
