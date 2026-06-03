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
    'eyebrow'         => 'Pelawat',
    'page_title'      => 'Kemaskini kenderaan',
    'sub'             => 'Kemaskini maklumat kenderaan pelawat.',
    'status'          => 'Status',
    'name'            => 'Nama',
    'phone'           => 'No. telefon',
    'ic_number'       => 'No. IC',
    'vehicle_type'    => 'Jenis kenderaan',
    'plate_number'    => 'Nombor plat',
    'save'            => 'Simpan',
    'cancel'          => 'Batal',
    'update_success'  => 'Kenderaan pelawat berjaya dikemaskini.',
    'update_failed'   => 'Gagal mengemaskini kenderaan pelawat',
    'name_placeholder'    => 'Nama pelawat',
    'phone_placeholder'   => 'cth. 012-3456789',
    'ic_placeholder'      => 'cth. 990101-01-1234',
    'plate_placeholder'   => 'cth. WPN 4421',
];
$text['en'] = [
    'eyebrow'         => 'Visitor',
    'page_title'      => 'Update vehicle',
    'sub'             => 'Update visitor vehicle details.',
    'status'          => 'Status',
    'name'            => 'Name',
    'phone'           => 'Phone',
    'ic_number'       => 'IC number',
    'vehicle_type'    => 'Vehicle type',
    'plate_number'    => 'Plate number',
    'save'            => 'Save',
    'cancel'          => 'Cancel',
    'update_success'  => 'Visitor vehicle updated.',
    'update_failed'   => 'Failed to update visitor vehicle',
    'name_placeholder'    => 'Visitor name',
    'phone_placeholder'   => 'e.g. 012-3456789',
    'ic_placeholder'      => 'e.g. 990101-01-1234',
    'plate_placeholder'   => 'e.g. WPN 4421',
];
$t = $text[$lang];

if (isset($_POST['submit'])) {
    $id           = (int)($_GET['id'] ?? 0);
    $visitorname  = mysqli_real_escape_string($con, $_POST['name']);
    $phone        = mysqli_real_escape_string($con, $_POST['phone']);
    $type         = mysqli_real_escape_string($con, $_POST['type']);
    $status       = mysqli_real_escape_string($con, $_POST['status']);
    $visitorplate = mysqli_real_escape_string($con, $_POST['platenum']);

    // Visitors have no IC; identity is the phone number.
    $sql = "UPDATE `owner` SET name='$visitorname', phone='$phone', type='$type', status='$status', platenum='$visitorplate' WHERE id=$id";

    if (mysqli_query($con, $sql)) {
        echo "<script>alert('" . addslashes($t['update_success']) . "'); window.location.href='/vehicles/visitor/list.php';</script>";
        exit();
    } else {
        $update_error = $t['update_failed'] . ': ' . mysqli_error($con);
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$vehicle_data = null;
if ($id > 0) {
    $res = mysqli_query($con, "SELECT * FROM `owner` WHERE id = $id");
    if ($res && mysqli_num_rows($res) > 0) {
        $vehicle_data = mysqli_fetch_assoc($res);
    } else {
        echo "<script>alert('Rekod tidak ditemui.'); window.location.href='/vehicles/visitor/list.php';</script>";
        exit();
    }
} else {
    echo "<script>alert('ID tidak sah.'); window.location.href='/vehicles/visitor/list.php';</script>";
    exit();
}

include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>
<body>
<div class="nv-shell">
<?php $nv_active = 'visitor'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php'; ?>
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
                    <option value="Pelawat" <?= ($vehicle_data['status']=='Pelawat')?'selected':'' ?>>Pelawat</option>
                    <option value="Staf" <?= ($vehicle_data['status']=='Staf')?'selected':'' ?>>Staf</option>
                    <option value="Pelajar" <?= ($vehicle_data['status']=='Pelajar')?'selected':'' ?>>Pelajar</option>
                    <option value="Kontraktor" <?= ($vehicle_data['status']=='Kontraktor')?'selected':'' ?>>Kontraktor</option>
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
            <div class="field">
                <label class="field-label" for="name"><?= htmlspecialchars($t['name']) ?></label>
                <input class="input" id="name" name="name" type="text" required placeholder="<?= htmlspecialchars($t['name_placeholder']) ?>" value="<?= htmlspecialchars($vehicle_data['name'] ?? '') ?>">
            </div>
            <div class="field">
                <label class="field-label" for="phone"><?= htmlspecialchars($t['phone']) ?></label>
                <input class="input mono" id="phone" name="phone" type="tel" required placeholder="<?= htmlspecialchars($t['phone_placeholder']) ?>" value="<?= htmlspecialchars($vehicle_data['phone'] ?? '') ?>">
            </div>
            <div class="field">
                <label class="field-label" for="platenum"><?= htmlspecialchars($t['plate_number']) ?></label>
                <input class="input mono plate-input" id="platenum" name="platenum" type="text" required placeholder="<?= htmlspecialchars($t['plate_placeholder']) ?>" value="<?= htmlspecialchars($vehicle_data['platenum'] ?? '') ?>">
            </div>
        </div>

        <div class="nv-row end gap-2">
            <a class="btn btn-ghost" href="/vehicles/visitor/list.php"><i data-lucide="arrow-left"></i> <?= htmlspecialchars($t['cancel']) ?></a>
            <button class="btn btn-primary" type="submit" name="submit"><i data-lucide="check"></i> <?= htmlspecialchars($t['save']) ?></button>
        </div>
    </form>
</main>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var plate = document.getElementById('platenum');
    var idn = document.getElementById('idnumber');
    var ph = document.getElementById('phone');
    if (plate) plate.addEventListener('input', function(){ this.value = this.value.toUpperCase(); });
    if (idn) idn.addEventListener('input', function(){ this.value = this.value.toUpperCase(); });
    if (ph) ph.addEventListener('input', function(){ this.value = this.value.replace(/[^0-9+\-]/g,''); });
});
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
