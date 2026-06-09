<?php
session_start();

// ========== LOGOUT HANDLER ==========
if (isset($_GET['logout'])) {
    header('Location: /auth/logout.php');
    exit();
}
// ========== END LOGOUT HANDLER ==========

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
include $_SERVER['DOCUMENT_ROOT'].'/includes/permission_check.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/vehicle_helpers.php';

// Admin only
requireAdmin();

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
    'import_desc' => 'Muat naik fail XLSX untuk menambah banyak kenderaan sekaligus',
    'download_template' => 'Muat Turun Template',
    'choose_file' => 'Pilih Fail XLSX',
    'upload' => 'Muat Naik',
    'back' => 'Kembali',
    'success' => 'Berjaya',
    'error' => 'Ralat',
    'instructions' => 'Arahan',
    'step1' => 'Langkah 1: Muat turun template XLSX',
    'step2' => 'Langkah 2: Isi data dalam Excel',
    'step3' => 'Langkah 3: Simpan sebagai fail XLSX',
    'step4' => 'Langkah 4: Muat naik fail di bawah',
    'xlsx_format' => 'Format XLSX:',
    'xlsx_columns' => 'Plate Number, Owner Name, Owner Phone, Type, Category',
    'example' => 'Contoh:',
    'example_row' => 'ABC1234, Ali Ahmad, 0123456789, KERETA, staff',
    'category_options' => 'Kategori: visitor, staff, student, contractor',
    'file_required' => 'Sila pilih fail XLSX',
    'upload_success' => 'Data berjaya diimport!',
    'upload_error' => 'Ralat semasa mengimport data',
    'rows_imported' => 'rekod berjaya diimport',
    'rows_failed' => 'rekod gagal',
    'invalid_format' => 'Format fail tidak sah. Sila gunakan fail XLSX sahaja.',
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
    'import_desc' => 'Upload XLSX file to add multiple vehicles at once',
    'download_template' => 'Download Template',
    'choose_file' => 'Choose XLSX File',
    'upload' => 'Upload',
    'back' => 'Back',
    'success' => 'Success',
    'error' => 'Error',
    'instructions' => 'Instructions',
    'step1' => 'Step 1: Download XLSX template',
    'step2' => 'Step 2: Fill data in Excel',
    'step3' => 'Step 3: Save as XLSX file',
    'step4' => 'Step 4: Upload file below',
    'xlsx_format' => 'XLSX Format:',
    'xlsx_columns' => 'Plate Number, Owner Name, Owner Phone, Type, Category',
    'example' => 'Example:',
    'example_row' => 'ABC1234, Ali Ahmad, 0123456789, KERETA, staff',
    'category_options' => 'Categories: visitor, staff, student, contractor',
    'file_required' => 'Please select XLSX file',
    'upload_success' => 'Data imported successfully!',
    'upload_error' => 'Error importing data',
    'rows_imported' => 'records imported successfully',
    'rows_failed' => 'records failed',
    'invalid_format' => 'Invalid file format. Please use XLSX file only.',
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

$message = null;
$message_type = null;

// Handle XLSX upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['xlsx_file'])) {
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    
    if ($_FILES['xlsx_file']['error'] == UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['xlsx_file']['tmp_name'];
        $name = $_FILES['xlsx_file']['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);
        
        if ($ext !== 'xlsx' || !in_array($mime, ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])) {
            $message = $t['invalid_format'];
            $message_type = 'error';
        } else {
            // Process XLSX file
            try {
                require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
                
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                $spreadsheet = $reader->load($tmp_name);
                $worksheet = $spreadsheet->getActiveSheet();
                
                $result = import_vehicles_from_xlsx($con, $worksheet, $admin_email);
                
                if ($result['imported'] > 0 || $result['skipped'] > 0) {
                    $message = "{$result['imported']} {$t['rows_imported']}";
                    if ($result['skipped'] > 0) {
                        $message .= ", {$result['skipped']} {$t['rows_failed']}";
                    }
                    $message_type = 'success';
                    
                    if (!empty($result['errors'])) {
                        $message .= "\n\n" . implode("\n", array_slice($result['errors'], 0, 5));
                    }
                } else {
                    $message = $t['upload_error'];
                    $message_type = 'error';
                }
            } catch (Exception $e) {
                $message = $t['upload_error'] . ': ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    } else {
        $message = $t['upload_error'];
        $message_type = 'error';
    }
}

// Generate template XLSX file
if (isset($_GET['download_template'])) {
    try {
        require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Header row (Brand removed; Type + Category are dropdowns).
        $headers = ['Plate Number', 'Owner Name', 'Owner Phone', 'Type', 'Category'];
        $sheet->fromArray([$headers], null, 'A1');

        $headerStyle = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

        // Example rows (no Brand).
        $examples = [
            ['ABC1234', 'Ali Ahmad', '0123456789', 'KERETA', 'staff'],
            ['DEF5678', 'Siti Sarah', '0134567890', 'MOTOSIKAL', 'student'],
            ['GHI9012', 'John Doe', '0145678901', 'VAN', 'visitor'],
            ['JKL3456', 'Ahmad Kontraktor', '0156789012', 'LORI', 'contractor'],
        ];
        $row = 2;
        foreach ($examples as $example) {
            $sheet->fromArray([$example], null, 'A' . $row);
            $row++;
        }

        // Dropdown (data validation) for Type (col D) and Category (col E).
        $typeOpts = ['KERETA', 'MOTOSIKAL', 'LORI', '4WD', 'VAN', 'MPV', 'LAIN-LAIN'];
        $catOpts  = ['staff', 'student', 'visitor', 'contractor'];
        for ($r = 2; $r <= 200; $r++) {
            $dvT = $sheet->getCell('D' . $r)->getDataValidation();
            $dvT->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $dvT->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
            $dvT->setAllowBlank(false);
            $dvT->setShowDropDown(true);
            $dvT->setShowErrorMessage(true);
            $dvT->setErrorTitle('Invalid type');
            $dvT->setError('Choose a vehicle type from the list.');
            $dvT->setFormula1('"' . implode(',', $typeOpts) . '"');

            $dvC = $sheet->getCell('E' . $r)->getDataValidation();
            $dvC->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $dvC->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
            $dvC->setAllowBlank(false);
            $dvC->setShowDropDown(true);
            $dvC->setShowErrorMessage(true);
            $dvC->setErrorTitle('Invalid category');
            $dvC->setError('Choose a category from the list.');
            $dvC->setFormula1('"' . implode(',', $catOpts) . '"');
        }

        // Column widths (A-E).
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(14);
        
        // Output XLSX file
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="template_vehicles_' . date('Y-m-d') . '.xlsx"');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
    } catch (\Throwable $e) {
        $message = 'Error generating template: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Import vehicles from an XLSX worksheet into the unified `owner` table.
// Template columns: Plate Number, Owner Name, Owner Phone, Brand, Type, Category.
// Rule: plate must be UNIQUE among ACTIVE records. An inactive record with the same
// plate+phone is reactivated (lifecycle clock reset) instead of failing.
function import_vehicles_from_xlsx($con, $worksheet, $admin_email) {
  $inserted = 0;
  $skipped = 0;
  $errors = [];
  $seen_plates = [];

  $cat_map = [
    'staff' => 'Staf', 'student' => 'Pelajar',
    'visitor' => 'Pelawat', 'contractor' => 'Kontraktor',
  ];

  foreach ($worksheet->getRowIterator(2) as $row) {
    $cells = $row->getCellIterator();
    $cells->setIterateOnlyExistingCells(false);

    $data = [];
    $col_index = 0;
    foreach ($cells as $cell) {
      $data[$col_index++] = $cell->getValue();
      if ($col_index >= 5) break;
    }

    if (empty($data[0])) continue;

    try {
      // Template columns: Plate Number, Owner Name, Owner Phone, Type, Category (Brand removed).
      $plate_number = strtoupper(trim((string)($data[0] ?? '')));
      $owner_name   = trim((string)($data[1] ?? ''));
      $owner_phone  = trim((string)($data[2] ?? ''));
      $type         = strtoupper(trim((string)($data[3] ?? '')));
      $category     = strtolower(trim((string)($data[4] ?? '')));
      $brand        = 'N/A';

      if ($plate_number === '' || $owner_name === '' || $owner_phone === '' || $category === '') {
        throw new Exception('Missing required fields');
      }
      if (!isset($cat_map[$category])) {
        throw new Exception('Invalid category');
      }
      if (!preg_match('/^\d{10,15}$/', preg_replace('/[\s\-\+\(\)]/', '', $owner_phone))) {
        throw new Exception('Invalid phone number');
      }
      if (in_array($plate_number, $seen_plates, true)) {
        throw new Exception('Duplicate plate in file');
      }
      $seen_plates[] = $plate_number;

      $status     = $cat_map[$category];
      $plate_esc  = $con->real_escape_string($plate_number);
      $phone_esc  = $con->real_escape_string($owner_phone);
      $name_esc   = $con->real_escape_string($owner_name);
      $brand_esc  = $con->real_escape_string($brand !== '' ? $brand : 'N/A');
      $type_esc   = $con->real_escape_string($type);
      $status_esc = $con->real_escape_string($status);

      // Uniqueness is enforced only among ACTIVE records.
      $active = $con->query("SELECT id FROM `owner` WHERE platenum='$plate_esc' AND " . NV_ACTIVE_WHERE . " LIMIT 1");
      if ($active && $active->num_rows > 0) {
        throw new Exception('Active plate already exists');
      }

      // Inactive record with same plate+phone => reactivate.
      $inactive = $con->query("SELECT id FROM `owner` WHERE platenum='$plate_esc' AND phone='$phone_esc' LIMIT 1");
      if ($inactive && $inactive->num_rows > 0) {
        $rid = (int)$inactive->fetch_assoc()['id'];
        $con->query("UPDATE `owner` SET name='$name_esc', brand='$brand_esc', type='$type_esc',
                     status='$status_esc', reactivated_at=NOW() WHERE id=$rid");
        $inserted++;
        continue;
      }

      $insert_sql = "INSERT INTO `owner` (name, phone, idnumber, type, status, brand, platenum)
                     VALUES ('$name_esc', '$phone_esc', '', '$type_esc', '$status_esc', '$brand_esc', '$plate_esc')";
      if (!$con->query($insert_sql)) {
        throw new Exception($con->error);
      }
      $inserted++;

    } catch (Exception $e) {
      $skipped++;
      $errors[] = "Row " . ($row->getRowIndex()) . ": " . $e->getMessage();
    }
  }

  return [
    'success'  => true,
    'imported' => $inserted,
    'skipped'  => $skipped,
    'errors'   => $errors,
  ];
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

    <?php if ($message): ?>
    <div class="flash <?= $message_type === 'success' ? 'ok' : 'error' ?> mb-6">
        <i data-lucide="<?= $message_type === 'success' ? 'check-circle' : 'alert-circle' ?>"></i>
        <div style="white-space:pre-wrap;"><?= htmlspecialchars($message) ?></div>
    </div>
    <?php endif; ?>

    <div class="nv-grid cols-2" style="align-items:start;">
        <div class="card">
            <span class="eyebrow"><?= htmlspecialchars($t['instructions']) ?></span>
            <h3 class="text-display mt-2 mb-4">XLSX import steps</h3>
            <div class="nv-stack gap-4">
                <div class="nv-row gap-3"><span class="plate" style="min-width:32px;text-align:center;">1</span><div><strong><?= htmlspecialchars($t['step1']) ?></strong></div></div>
                <div class="nv-row gap-3"><span class="plate" style="min-width:32px;text-align:center;">2</span><div><strong><?= htmlspecialchars($t['step2']) ?></strong><div class="text-mono text-muted mt-2" style="font-size:12px;"><?= htmlspecialchars($t['xlsx_columns']) ?></div></div></div>
                <div class="nv-row gap-3"><span class="plate" style="min-width:32px;text-align:center;">3</span><div><strong><?= htmlspecialchars($t['step3']) ?></strong></div></div>
                <div class="nv-row gap-3"><span class="plate" style="min-width:32px;text-align:center;">4</span><div><strong><?= htmlspecialchars($t['step4']) ?></strong></div></div>
            </div>
            <div class="card flat mt-6" style="background:var(--surface-tint);">
                <span class="eyebrow"><?= htmlspecialchars($t['example']) ?></span>
                <div class="text-mono mt-2" style="font-size:12px;line-height:1.7;color:var(--brand-purple-deep);">
                    <?= htmlspecialchars($t['xlsx_columns']) ?><br>
                    <?= htmlspecialchars($t['example_row']) ?><br>
                    DEF5678, Siti Sarah, 0134567890, MOTOSIKAL, student<br>
                    GHI9012, John Doe, 0145678901, VAN, visitor
                </div>
                <div class="text-muted mt-4" style="font-size:12px;">
                    <strong><?= htmlspecialchars($t['category_options']) ?></strong>
                </div>
            </div>
        </div>

        <form class="card nv-stack gap-6" method="POST" enctype="multipart/form-data" id="uploadForm">
            <div>
                <span class="eyebrow">Upload</span>
                <h3 class="text-display mt-2"><?= htmlspecialchars($t['choose_file']) ?></h3>
            </div>
            <div class="field">
                <label class="field-label" for="xlsx_file">XLSX file (required)</label>
                <input class="input" id="xlsx_file" name="xlsx_file" type="file" accept=".xlsx" required onchange="updateFileName()">
                <span class="field-hint" id="file-name">No file chosen</span>
            </div>
            <div class="nv-row end gap-2">
                <a class="btn btn-ghost" href="/admin/admins.php"><i data-lucide="arrow-left"></i> <?= htmlspecialchars($t['back']) ?></a>
                <button class="btn btn-primary" type="submit"><i data-lucide="upload"></i> <?= htmlspecialchars($t['upload']) ?></button>
            </div>
        </form>
    </div>
</main>
</div>
<script>
function updateFileName() {
    var fi = document.getElementById('xlsx_file');
    var fn = document.getElementById('file-name');
    if (fi.files.length > 0) { 
        fn.textContent = fi.files[0].name; 
        fn.style.color = 'var(--status-ok)'; 
    }
    else { 
        fn.textContent = 'No file chosen'; 
        fn.style.color = 'var(--fg-3)'; 
    }
}
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    var fi = document.getElementById('xlsx_file');
    if (!fi.files.length) { 
        e.preventDefault(); 
        alert('<?php echo addslashes($t['file_required']); ?>'); 
        return false; 
    }
    var ext = fi.files[0].name.split('.').pop().toLowerCase();
    if (ext !== 'xlsx') { 
        e.preventDefault(); 
        alert('<?php echo addslashes($t['invalid_format']); ?>'); 
        return false; 
    }
    var btn = this.querySelector('button[type="submit"]');
    btn.innerHTML = '<i data-lucide="loader"></i> ' + '<?php echo addslashes($t['upload']); ?>';
    btn.disabled = true;
});
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
