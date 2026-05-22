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
    'plate_exists' => 'Nombor plat sudah wujud.',
    'success' => 'Kenderaan staf berjaya didaftar.',
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
    'plate_exists' => 'Plate number already exists.',
    'success' => 'Staff vehicle registered successfully.',
];

$error = '';

if (isset($_POST['submit'])) {
    $staffname = mysqli_real_escape_string($con, $_POST['name']);
    $phone     = mysqli_real_escape_string($con, $_POST['phone']);
    $staffno   = mysqli_real_escape_string($con, $_POST['idnumber']);
    $type      = mysqli_real_escape_string($con, $_POST['type']);
    $status    = mysqli_real_escape_string($con, $_POST['status']);
    $staffplate= mysqli_real_escape_string($con, $_POST['platenum']);


    $check = mysqli_query($con, "SELECT id FROM owner WHERE platenum = '$staffplate'");
    if ($check && mysqli_num_rows($check) > 0) {
        $error = $t['plate_exists'];
    } else {
        $sql = "INSERT INTO `owner` (`name`, `phone`, `idnumber`, `type`, `status`, `platenum`)
                VALUES('$staffname','$phone','$staffno','$type','$status','$staffplate')";
        if (mysqli_query($con, $sql)) {
            $_SESSION['success_message'] = $t['success'];
            header('location:/vehicles/staff/list.php');
            exit();
        } else {
            $error = mysqli_error($con);
        }
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
                    <option value="LORI">LORI</option>
                    <option value="4WD">4WD</option>
                    <option value="VAN">VAN</option>
                    <option value="MPV">MPV</option>
                </select>
            </div>
            <div class="field">
                <label class="field-label" for="name"><?php echo $t['name']; ?></label>
                <input class="input" id="name" name="name" type="text" required placeholder="<?php echo htmlspecialchars($t['name_ph']); ?>">
            </div>
            <div class="field">
                <label class="field-label" for="idnumber"><?php echo $t['idnum']; ?></label>
                <input class="input mono" id="idnumber" name="idnumber" type="text" required placeholder="<?php echo htmlspecialchars($t['idnum_ph']); ?>">
            </div>
            <div class="field">
                <label class="field-label" for="phone"><?php echo $t['phone']; ?></label>
                <input class="input mono" id="phone" name="phone" type="tel" required placeholder="<?php echo htmlspecialchars($t['phone_ph']); ?>">
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
    var plate = document.getElementById('platenum');
    var idn = document.getElementById('idnumber');
    var ph = document.getElementById('phone');
    if (plate) plate.addEventListener('input', function(){ this.value = this.value.toUpperCase(); });
    if (idn) idn.addEventListener('input', function(){ this.value = this.value.toUpperCase(); });
    if (ph) ph.addEventListener('input', function(){ this.value = this.value.replace(/[^0-9+]/g,''); });
})();
</script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
