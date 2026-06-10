<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /auth/role_selection.php');
    exit();
}

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/auth_guard.php';
nv_require_login();

if (!isset($_SESSION['language'])) { $_SESSION['language'] = 'bm'; }
if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] == 'en') ? 'en' : 'bm';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
$lang = $_SESSION['language'];

$t = ($lang === 'bm') ? [
    'eyebrow' => 'KATEGORI', 'title' => 'Pelajar', 'sub' => 'Rekod kenderaan pelajar UiTM.',
    'add' => 'Daftar kenderaan', 'col_plate' => 'No. Plat', 'col_owner' => 'Pemilik',
    'col_idnum' => 'No. Matrik', 'col_phone' => 'Telefon', 'col_type' => 'Jenis',
    'col_updated' => 'Kemaskini', 'empty_title' => 'Tiada rekod', 'empty_sub' => 'Belum ada kenderaan pelajar didaftarkan.',
] : [
    'eyebrow' => 'CATEGORY', 'title' => 'Student', 'sub' => 'Vehicle records for UiTM students.',
    'add' => 'Register vehicle', 'col_plate' => 'Plate', 'col_owner' => 'Owner',
    'col_idnum' => 'Matric no.', 'col_phone' => 'Phone', 'col_type' => 'Type',
    'col_updated' => 'Last updated', 'empty_title' => 'No records', 'empty_sub' => 'No student vehicles have been registered yet.',
];

$nv_slug = 'student';
$category = 'Pelajar';

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<body>
<div class="nv-shell">
<?php $nv_active = 'student'; include $_SERVER['DOCUMENT_ROOT'].'/includes/nv_chrome.php'; ?>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/vehicle_table_view.php'; ?>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
