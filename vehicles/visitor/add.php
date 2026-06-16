<?php
session_start();
if (isset($_GET['logout'])) { header('Location: /auth/logout.php'); exit; }

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/auth_guard.php';
nv_guard_page($con, 'visitor');   // admins, or users granted this category, may manage

if (!isset($_SESSION['language'])) { $_SESSION['language'] = 'bm'; }
if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] == 'en') ? 'en' : 'bm';
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?')); exit;
}
$lang = $_SESSION['language'];

$t = ($lang === 'bm') ? [
    'eyebrow' => 'PELAWAT', 'title' => 'Daftar kenderaan', 'sub' => 'Tambah rekod kenderaan pelawat baharu.',
    'plate' => 'No. Kenderaan', 'plate_ph' => 'Isi nombor plat', 'type' => 'Jenis kenderaan',
    'model' => 'Model kenderaan', 'model_ph' => 'cth. PERODUA MYVI', 'name' => 'Nama', 'name_ph' => 'Isi nama',
    'idnum' => 'No. Pengenalan', 'idnum_ph' => 'No. KP / pasport', 'phone' => 'No. Telefon', 'phone_ph' => 'cth. 012-3456789',
    'date' => 'Tarikh ambil', 'serial' => 'No. Siri', 'serial_hint' => 'Biar kosong untuk auto (set semula setiap tahun).',
    'select_type' => 'Sila pilih', 'save' => 'Simpan', 'cancel' => 'Batal',
    'plate_exists' => 'Nombor plat sudah wujud untuk pemilik lain.', 'success' => 'Kenderaan pelawat berjaya didaftar.',
    'required' => 'Sila isi nombor plat dan telefon.',
] : [
    'eyebrow' => 'VISITOR', 'title' => 'Register vehicle', 'sub' => 'Add a new visitor vehicle record.',
    'plate' => 'Plate number', 'plate_ph' => 'Enter plate number', 'type' => 'Vehicle type',
    'model' => 'Vehicle model', 'model_ph' => 'e.g. PERODUA MYVI', 'name' => 'Name', 'name_ph' => 'Enter name',
    'idnum' => 'ID number', 'idnum_ph' => 'NRIC / passport no.', 'phone' => 'Phone', 'phone_ph' => 'e.g. 012-3456789',
    'date' => 'Date taken', 'serial' => 'Serial no.', 'serial_hint' => 'Leave blank to auto-assign (resets each year).',
    'select_type' => 'Please select', 'save' => 'Save', 'cancel' => 'Cancel',
    'plate_exists' => 'Plate number already belongs to another owner.', 'success' => 'Visitor vehicle registered successfully.',
    'required' => 'Plate number and phone are required.',
];

$error = '';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/vehicle_helpers.php';

if (isset($_POST['submit'])) {
    $result = nv_vehicle_register($con, 'Pelawat', $error);
    if ($result !== false) {
        $_SESSION['success_message'] = $t['success'];
        header('location:/vehicles/visitor/list.php'); exit;
    } elseif ($error === 'plate_exists') { $error = $t['plate_exists']; }
    elseif ($error === 'required') { $error = $t['required']; }
}

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<body>
<div class="nv-shell">
<?php $nv_active = 'visitor'; include $_SERVER['DOCUMENT_ROOT'].'/includes/nv_chrome.php'; ?>
<main class="page">
    <div class="page-head">
        <div>
            <span class="eyebrow"><?php echo $t['eyebrow']; ?></span>
            <h1><?php echo htmlspecialchars($t['title']); ?></h1>
            <p class="sub"><?php echo htmlspecialchars($t['sub']); ?></p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="/vehicles/visitor/list.php"><i data-lucide="arrow-left"></i> <?php echo htmlspecialchars($t['cancel']); ?></a>
        </div>
    </div>
    <form class="card nv-stack gap-6" method="post" id="vehicleForm">
        <?php if (!empty($error)): ?><div class="flash bad"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <input type="hidden" name="status" value="Pelawat">
        <div class="nv-grid cols-2">
            <div class="field"><label class="field-label" for="platenum"><?php echo $t['plate']; ?></label>
                <input class="input mono" id="platenum" name="platenum" type="text" required placeholder="<?php echo htmlspecialchars($t['plate_ph']); ?>"></div>
            <div class="field"><label class="field-label" for="type"><?php echo $t['type']; ?></label>
                <select class="select" id="type" name="type" required>
                    <option value="" disabled selected><?php echo htmlspecialchars($t['select_type']); ?></option>
                    <option value="KERETA">KERETA</option>
                    <option value="MOTOSIKAL">MOTOSIKAL</option>
                </select></div>
            <div class="field"><label class="field-label" for="model"><?php echo $t['model']; ?></label>
                <input class="input" id="model" name="model" type="text" placeholder="<?php echo htmlspecialchars($t['model_ph']); ?>"></div>
            <div class="field"><label class="field-label" for="date_taken"><?php echo $t['date']; ?></label>
                <input class="input mono" id="date_taken" name="date_taken" type="date" value="<?php echo date('Y-m-d'); ?>"></div>
            <div class="field"><label class="field-label" for="idnumber"><?php echo $t['idnum']; ?></label>
                <input class="input mono" id="idnumber" name="idnumber" type="text" placeholder="<?php echo htmlspecialchars($t['idnum_ph']); ?>"></div>
            <div class="field"><label class="field-label" for="name"><?php echo $t['name']; ?></label>
                <input class="input" id="name" name="name" type="text" required placeholder="<?php echo htmlspecialchars($t['name_ph']); ?>"></div>
            <div class="field"><label class="field-label" for="phone"><?php echo $t['phone']; ?></label>
                <input class="input mono" id="phone" name="phone" type="tel" required placeholder="<?php echo htmlspecialchars($t['phone_ph']); ?>"></div>
            <div class="field"><label class="field-label" for="serial_no"><?php echo $t['serial']; ?></label>
                <input class="input mono" id="serial_no" name="serial_no" type="number" min="1" inputmode="numeric" placeholder="—">
                <small class="text-muted"><?php echo htmlspecialchars($t['serial_hint']); ?></small></div>
        </div>
        <div class="nv-row end gap-2">
            <a class="btn btn-ghost" href="/vehicles/visitor/list.php"><?php echo htmlspecialchars($t['cancel']); ?></a>
            <button class="btn btn-primary" type="submit" name="submit"><i data-lucide="check"></i> <?php echo htmlspecialchars($t['save']); ?></button>
        </div>
    </form>
</main>
</div>
<script>
(function(){
    ['platenum','idnumber','model','name'].forEach(function(id){ var el=document.getElementById(id);
        if (el) el.addEventListener('input', function(){ this.value = this.value.toUpperCase(); }); });
    var ph = document.getElementById('phone');
    if (ph) ph.addEventListener('input', function(){ this.value = this.value.replace(/[^0-9+]/g,''); });
})();
</script>
<script src="/assets/js/nv-autofill.js?v=<?php echo @filemtime($_SERVER['DOCUMENT_ROOT'].'/assets/js/nv-autofill.js'); ?>"></script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
