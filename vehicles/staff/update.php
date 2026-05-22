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

if (isset($_POST['submit'])) {
    $id          = (int)($_GET['id'] ?? 0);
    $staffname   = mysqli_real_escape_string($con, $_POST['name']);
    $phone       = mysqli_real_escape_string($con, $_POST['phone']);
    $staffno     = mysqli_real_escape_string($con, $_POST['idnumber']);
    $type        = mysqli_real_escape_string($con, $_POST['type']);
    $status      = mysqli_real_escape_string($con, $_POST['status']);
    $staffplate  = mysqli_real_escape_string($con, $_POST['platenum']);

    $sql = "UPDATE `owner` SET name='$staffname', phone='$phone', idnumber='$staffno', type='$type', status='$status', platenum='$staffplate' WHERE id=$id";
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
                <select class="select" id="type" name="type" required>
                    <?php foreach (['KERETA','MOTOSIKAL','LORI','4WD','VAN','MPV'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= ($vehicle_data['type']==$opt)?'selected':'' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field"><label class="field-label" for="name"><?= htmlspecialchars($t['name']) ?></label>
                <input class="input" id="name" name="name" type="text" required placeholder="<?= htmlspecialchars($t['name_placeholder']) ?>" value="<?= htmlspecialchars($vehicle_data['name'] ?? '') ?>"></div>
            <div class="field"><label class="field-label" for="phone"><?= htmlspecialchars($t['phone']) ?></label>
                <input class="input mono" id="phone" name="phone" type="tel" required placeholder="<?= htmlspecialchars($t['phone_placeholder']) ?>" value="<?= htmlspecialchars($vehicle_data['phone'] ?? '') ?>"></div>
            <div class="field"><label class="field-label" for="idnumber"><?= htmlspecialchars($t['ic_number']) ?></label>
                <input class="input mono" id="idnumber" name="idnumber" type="text" required placeholder="<?= htmlspecialchars($t['ic_placeholder']) ?>" value="<?= htmlspecialchars($vehicle_data['idnumber'] ?? '') ?>"></div>
            <div class="field"><label class="field-label" for="platenum"><?= htmlspecialchars($t['plate_number']) ?></label>
                <input class="input mono plate-input" id="platenum" name="platenum" type="text" required placeholder="<?= htmlspecialchars($t['plate_placeholder']) ?>" value="<?= htmlspecialchars($vehicle_data['platenum'] ?? '') ?>"></div>
            <div class="field"><label class="field-label" for="stickerno"><?= htmlspecialchars($t['sticker_number']) ?></label>
            <div class="field"><label class="field-label" for="sticker"><?= htmlspecialchars($t['sticker_status']) ?></label>
                <select class="select" id="sticker" name="sticker">
                </select>
            </div>
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
    var plate = document.getElementById('platenum'), sn = document.getElementById('stickerno'), idn = document.getElementById('idnumber'), ph = document.getElementById('phone');
    if (plate) plate.addEventListener('input', function(){ this.value = this.value.toUpperCase(); });
    if (idn) idn.addEventListener('input', function(){ this.value = this.value.toUpperCase(); });
    if (ph) ph.addEventListener('input', function(){ this.value = this.value.replace(/[^0-9+\-]/g,''); });
});
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
