<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /auth/role_selection.php');
    exit();
}

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

if (!isset($_SESSION['email_Admin'])) {
    header('location:/auth/login_admin.php');
    exit();
}

if (!isset($_SESSION['language'])) { $_SESSION['language'] = 'bm'; }
if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] == 'en') ? 'en' : 'bm';
    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . (isset($_GET['id']) ? (int)$_GET['id'] : 0));
    exit();
}
$lang = $_SESSION['language'];

$text = [];
$text['bm'] = [
    'eyebrow'         => 'Staf',
    'page_title'      => 'Kemaskini kenderaan',
    'sub'             => 'Kemaskini maklumat kenderaan staf.',
    'status'          => 'Status', 'name' => 'Nama', 'phone' => 'No. telefon',
    'ic_number'       => 'No. pekerja',
    'vehicle_type'    => 'Jenis kenderaan', 'plate_number' => 'Nombor plat',
    'save' => 'Simpan', 'cancel' => 'Batal',
    'update_success'  => 'Kenderaan staf berjaya dikemaskini.',
    'update_failed'   => 'Gagal mengemaskini kenderaan staf',
    'name_placeholder' => 'Nama staf', 'phone_placeholder' => 'cth. 012-3456789',
    'ic_placeholder' => 'No. pekerja', 'plate_placeholder' => 'cth. WPN 4421',
];
$text['en'] = [
    'eyebrow'         => 'Staff',
    'page_title'      => 'Update vehicle',
    'sub'             => 'Update staff vehicle details.',
    'status'          => 'Status', 'name' => 'Name', 'phone' => 'Phone',
    'ic_number'       => 'Staff number',
    'vehicle_type'    => 'Vehicle type', 'plate_number' => 'Plate number',
    'save' => 'Save', 'cancel' => 'Cancel',
    'update_success'  => 'Staff vehicle updated.',
    'update_failed'   => 'Failed to update staff vehicle',
    'name_placeholder' => 'Staff name', 'phone_placeholder' => 'e.g. 012-3456789',
    'ic_placeholder' => 'Staff number', 'plate_placeholder' => 'e.g. WPN 4421',
];
$t = $text[$lang];
$tf = ($lang === 'bm')
    ? ['model' => 'Model kenderaan', 'model_ph' => 'cth. PERODUA MYVI', 'date' => 'Tarikh ambil', 'serial' => 'No. siri']
    : ['model' => 'Vehicle model', 'model_ph' => 'e.g. PERODUA MYVI', 'date' => 'Date taken', 'serial' => 'Serial no.'];

if (isset($_POST['submit'])) {
    require_once $_SERVER['DOCUMENT_ROOT'].'/includes/vehicle_helpers.php';
    nv_schema_autoprovision_once($con);

    $id          = (int)($_GET['id'] ?? 0);
    $staffname   = mysqli_real_escape_string($con, strtoupper(trim($_POST['name'])));
    $phone       = mysqli_real_escape_string($con, trim($_POST['phone']));
    $staffno     = mysqli_real_escape_string($con, strtoupper(trim($_POST['idnumber'])));
    $type        = mysqli_real_escape_string($con, nv_norm_vehicle_type($_POST['type']));
    $status      = mysqli_real_escape_string($con, $_POST['status']);
    $staffplate  = mysqli_real_escape_string($con, strtoupper(trim($_POST['platenum'])));

    $set = "name='$staffname', phone='$phone', idnumber='$staffno', type='$type', status='$status', platenum='$staffplate'";
    $cols = nv_owner_new_cols($con);
    if (isset($cols['model'])) {
        $model = mysqli_real_escape_string($con, strtoupper(trim($_POST['model'] ?? '')) ?: 'N/A');
        $set .= ", model='$model'";
    }
    if (isset($cols['date_taken'])) {
        $draw = trim($_POST['date_taken'] ?? '');
        $dts  = $draw !== '' ? strtotime($draw) : false;
        $set .= $dts !== false ? ", date_taken='" . mysqli_real_escape_string($con, date('Y-m-d', $dts)) . "'" : ", date_taken=NULL";
    }
    if (isset($cols['serial_no'])) {
        $sraw = trim($_POST['serial_no'] ?? '');
        $set .= ($sraw !== '' && ctype_digit($sraw)) ? ", serial_no=" . (int) $sraw : ", serial_no=NULL";
    }

    $sql = "UPDATE `owner` SET $set WHERE id=$id";
    if (mysqli_query($con, $sql)) {
        echo "<script>alert('" . addslashes($t['update_success']) . "'); window.location.href='/vehicles/staff/list.php';</script>";
        exit();
    } else {
        $update_error = $t['update_failed'] . ': ' . mysqli_error($con);
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$vehicle_data = null;
if ($id > 0) {
    $res = mysqli_query($con, "SELECT * FROM `owner` WHERE id = $id");
    if ($res && mysqli_num_rows($res) > 0) { $vehicle_data = mysqli_fetch_assoc($res); }
    else { echo "<script>alert('Rekod tidak ditemui.'); window.location.href='/vehicles/staff/list.php';</script>"; exit(); }
} else { echo "<script>alert('ID tidak sah.'); window.location.href='/vehicles/staff/list.php';</script>"; exit(); }

include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>
<body>
<div class="nv-shell">
<?php $nv_active = 'staff'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php'; ?>
<main class="page">
    <div class="page-head">
        <div>
            <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?></span>
            <h1><?= htmlspecialchars($t['page_title']) ?></h1>
            <p class="sub"><?= htmlspecialchars($t['sub']) ?></p>
        </div>
    </div>

    <?php if (!empty($update_error)): ?><div class="flash bad"><?= htmlspecialchars($update_error) ?></div><?php endif; ?>

    <form class="card nv-stack gap-6" method="POST">
        <div class="nv-grid cols-2">
            <div class="field">
                <label class="field-label" for="status"><?= htmlspecialchars($t['status']) ?></label>
                <select class="select" id="status" name="status" required>
                    <?php foreach (['Staf','Pelajar','Pelawat','Kontraktor'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= ($vehicle_data['status']==$opt)?'selected':'' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label class="field-label" for="type"><?= htmlspecialchars($t['vehicle_type']) ?></label>
                <?php
                    $type_opts = ['KERETA', 'MOTOSIKAL'];
                    $cur_type  = $vehicle_data['type'] ?? '';
                    if ($cur_type !== '' && !in_array($cur_type, $type_opts, true)) { $type_opts[] = $cur_type; }
                ?>
                <select class="select" id="type" name="type" required>
                    <?php foreach ($type_opts as $opt): ?>
                        <option value="<?= htmlspecialchars($opt) ?>" <?= ($cur_type==$opt)?'selected':'' ?>><?= htmlspecialchars($opt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label class="field-label" for="model"><?= htmlspecialchars($tf['model']) ?></label>
                <input class="input" id="model" name="model" type="text" placeholder="<?= htmlspecialchars($tf['model_ph']) ?>" value="<?= htmlspecialchars(($vehicle_data['model'] ?? '') === 'N/A' ? '' : ($vehicle_data['model'] ?? '')) ?>"></div>
            <div class="field"><label class="field-label" for="date_taken"><?= htmlspecialchars($tf['date']) ?></label>
                <?php $dt = $vehicle_data['date_taken'] ?? ''; $dt = ($dt && $dt !== '0000-00-00') ? date('Y-m-d', strtotime($dt)) : ''; ?>
                <input class="input mono" id="date_taken" name="date_taken" type="date" value="<?= htmlspecialchars($dt) ?>"></div>
            <div class="field"><label class="field-label" for="idnumber"><?= htmlspecialchars($t['ic_number']) ?></label>
                <input class="input mono" id="idnumber" name="idnumber" type="text" required placeholder="<?= htmlspecialchars($t['ic_placeholder']) ?>" value="<?= htmlspecialchars($vehicle_data['idnumber'] ?? '') ?>"></div>
            <div class="field"><label class="field-label" for="name"><?= htmlspecialchars($t['name']) ?></label>
                <input class="input" id="name" name="name" type="text" required placeholder="<?= htmlspecialchars($t['name_placeholder']) ?>" value="<?= htmlspecialchars($vehicle_data['name'] ?? '') ?>"></div>
            <div class="field"><label class="field-label" for="phone"><?= htmlspecialchars($t['phone']) ?></label>
                <input class="input mono" id="phone" name="phone" type="tel" required placeholder="<?= htmlspecialchars($t['phone_placeholder']) ?>" value="<?= htmlspecialchars($vehicle_data['phone'] ?? '') ?>"></div>
            <div class="field"><label class="field-label" for="platenum"><?= htmlspecialchars($t['plate_number']) ?></label>
                <input class="input mono plate-input" id="platenum" name="platenum" type="text" required placeholder="<?= htmlspecialchars($t['plate_placeholder']) ?>" value="<?= htmlspecialchars($vehicle_data['platenum'] ?? '') ?>"></div>
            <div class="field"><label class="field-label" for="serial_no"><?= htmlspecialchars($tf['serial']) ?></label>
                <input class="input mono" id="serial_no" name="serial_no" type="number" min="1" inputmode="numeric" value="<?= htmlspecialchars($vehicle_data['serial_no'] ?? '') ?>"></div>
        </div>
        <div class="nv-row end gap-2">
            <a class="btn btn-ghost" href="/vehicles/staff/list.php"><i data-lucide="arrow-left"></i> <?= htmlspecialchars($t['cancel']) ?></a>
            <button class="btn btn-primary" type="submit" name="submit"><i data-lucide="check"></i> <?= htmlspecialchars($t['save']) ?></button>
        </div>
    </form>
</main>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    ['platenum','idnumber','model','name'].forEach(function(id){ var el = document.getElementById(id);
        if (el) el.addEventListener('input', function(){ this.value = this.value.toUpperCase(); }); });
    var ph = document.getElementById('phone');
    if (ph) ph.addEventListener('input', function(){ this.value = this.value.replace(/[^0-9+\-]/g,''); });
});
</script>
<script src="/assets/js/nv-autofill.js"></script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
