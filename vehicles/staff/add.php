<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /auth/role_selection.php');
    exit();
}

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

require_once $_SERVER['DOCUMENT_ROOT'].'/includes/auth_guard.php';
nv_guard_page($con, 'staff');   // admins, or users granted this category, may manage

if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'bm';
}
if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] == 'en') ? 'en' : 'bm';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
$lang = $_SESSION['language'];

$t = ($lang === 'bm') ? [
    'eyebrow' => 'STAF',
    'title'   => 'Daftar kenderaan',
    'sub'     => 'Tambah rekod kenderaan baharu untuk kakitangan UiTM.',
    'name'    => 'Nama staf',
    'name_ph' => 'Isi nama staf',
    'phone'   => 'No. telefon',
    'phone_ph'=> 'Isi nombor telefon staf',
    'idnum'   => 'No. Pekerja',
    'idnum_ph'=> 'Isi nombor pekerja',
    'type'    => 'Jenis kenderaan',
    'plate'   => 'No. plat kenderaan',
    'plate_ph'=> 'Isi nombor plat',

    'select_type'=> 'Sila pilih',
    'save'    => 'Simpan',
    'cancel'  => 'Batal',
    'plate_exists' => 'Nombor plat sudah wujud untuk pemilik lain.',
    'success' => 'Kenderaan staf berjaya didaftar.',
    'required' => 'Sila isi nombor plat dan telefon.',
] : [
    'eyebrow' => 'STAFF',
    'title'   => 'Register vehicle',
    'sub'     => 'Add a new vehicle record for UiTM staff.',
    'name'    => 'Owner name',
    'name_ph' => 'Enter staff name',
    'phone'   => 'Phone number',
    'phone_ph'=> 'Enter phone number',
    'idnum'   => 'Staff no.',
    'idnum_ph'=> 'Enter staff number',
    'type'    => 'Vehicle type',
    'plate'   => 'Plate number',
    'plate_ph'=> 'Enter plate number',

    'select_type'=> 'Please select',
    'save'    => 'Save',
    'cancel'  => 'Cancel',
    'plate_exists' => 'Plate number already belongs to another owner.',
    'success' => 'Staff vehicle registered successfully.',
    'required' => 'Plate number and phone are required.',
];

$tf = ($lang === 'bm') ? [
    'model' => 'Model kenderaan', 'model_ph' => 'cth. PERODUA MYVI',
    'date' => 'Tarikh ambil', 'serial' => 'No. siri',
    'serial_hint' => 'Biar kosong untuk auto (set semula setiap tahun).',
] : [
    'model' => 'Vehicle model', 'model_ph' => 'e.g. PERODUA MYVI',
    'date' => 'Date taken', 'serial' => 'Serial no.',
    'serial_hint' => 'Leave blank to auto-assign (resets each year).',
];

$error = '';

require_once $_SERVER['DOCUMENT_ROOT'].'/includes/vehicle_helpers.php';

if (isset($_POST['submit'])) {
    $result = nv_vehicle_register($con, 'Staf', $error);
    if ($result !== false) {
        $_SESSION['success_message'] = $t['success'];
        header('location:/vehicles/staff/list.php');
        exit();
    } elseif ($error === 'plate_exists') {
        $error = $t['plate_exists'];
    } elseif ($error === 'required') {
        $error = $t['required'];
    }
}

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<body>
<div class="nv-shell">
<?php $nv_active = 'staff'; include $_SERVER['DOCUMENT_ROOT'].'/includes/nv_chrome.php'; ?>
<main class="page">
    <div class="page-head">
        <div>
            <span class="eyebrow"><?php echo $t['eyebrow']; ?></span>
            <h1><?php echo htmlspecialchars($t['title']); ?></h1>
            <p class="sub"><?php echo htmlspecialchars($t['sub']); ?></p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="/vehicles/staff/list.php"><i data-lucide="arrow-left"></i> <?php echo htmlspecialchars($t['cancel']); ?></a>
        </div>
    </div>

    <form class="card nv-stack gap-6" method="post" id="vehicleForm">
        <?php if (!empty($error)): ?>
            <div class="flash bad"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <input type="hidden" name="status" value="Staf">

        <div class="nv-grid cols-2">
            <div class="field">
                <label class="field-label" for="platenum"><?php echo $t['plate']; ?></label>
                <input class="input mono" id="platenum" name="platenum" type="text" required placeholder="<?php echo htmlspecialchars($t['plate_ph']); ?>">
            </div>
            <div class="field">
                <label class="field-label" for="type"><?php echo $t['type']; ?></label>
                <select class="select" id="type" name="type" required>
                    <option value="" disabled selected><?php echo htmlspecialchars($t['select_type']); ?></option>
                    <option value="KERETA">KERETA</option>
                    <option value="MOTOSIKAL">MOTOSIKAL</option>
                </select>
            </div>
            <div class="field">
                <label class="field-label" for="model"><?php echo $tf['model']; ?></label>
                <input class="input" id="model" name="model" type="text" placeholder="<?php echo htmlspecialchars($tf['model_ph']); ?>">
            </div>
            <div class="field">
                <label class="field-label" for="date_taken"><?php echo $tf['date']; ?></label>
                <input class="input mono" id="date_taken" name="date_taken" type="date" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="field">
                <label class="field-label" for="idnumber"><?php echo $t['idnum']; ?></label>
                <input class="input mono" id="idnumber" name="idnumber" type="text" required placeholder="<?php echo htmlspecialchars($t['idnum_ph']); ?>">
            </div>
            <div class="field">
                <label class="field-label" for="name"><?php echo $t['name']; ?></label>
                <input class="input" id="name" name="name" type="text" required placeholder="<?php echo htmlspecialchars($t['name_ph']); ?>">
            </div>
            <div class="field">
                <label class="field-label" for="phone"><?php echo $t['phone']; ?></label>
                <input class="input mono" id="phone" name="phone" type="tel" required placeholder="<?php echo htmlspecialchars($t['phone_ph']); ?>">
            </div>
            <div class="field">
                <label class="field-label" for="serial_no"><?php echo $tf['serial']; ?></label>
                <input class="input mono" id="serial_no" name="serial_no" type="number" min="1" inputmode="numeric" placeholder="—">
                <small class="text-muted"><?php echo htmlspecialchars($tf['serial_hint']); ?></small>
            </div>
        </div>

        <div class="nv-row end gap-2">
            <a class="btn btn-ghost" href="/vehicles/staff/list.php"><?php echo htmlspecialchars($t['cancel']); ?></a>
            <button class="btn btn-primary" type="submit" name="submit"><i data-lucide="check"></i> <?php echo htmlspecialchars($t['save']); ?></button>
        </div>
    </form>
</main>
<script>
(function(){
    var up = ['platenum','idnumber','model','name'];
    up.forEach(function(id){ var el = document.getElementById(id);
        if (el) el.addEventListener('input', function(){ this.value = this.value.toUpperCase(); }); });
    var ph = document.getElementById('phone');
    if (ph) ph.addEventListener('input', function(){ this.value = this.value.replace(/[^0-9+]/g,''); });
})();
</script>
<script src="/assets/js/nv-autofill.js?v=<?php echo @filemtime($_SERVER['DOCUMENT_ROOT'].'/assets/js/nv-autofill.js'); ?>"></script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
