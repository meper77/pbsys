<?php
session_start();

if (isset($_GET['logout'])) { header('Location: /auth/logout.php'); exit; }

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/auth_guard.php';
nv_require_login();

if (!isset($_SESSION['language'])) { $_SESSION['language'] = 'bm'; }
if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] == 'en') ? 'en' : 'bm';
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit();
}
$lang = $_SESSION['language'];

$t = ($lang === 'bm') ? [
    'eyebrow' => 'KATEGORI', 'title' => 'Pelawat', 'sub' => 'Rekod kenderaan pelawat.',
    'add' => 'Daftar kenderaan', 'empty_title' => 'Tiada rekod', 'empty_sub' => 'Belum ada kenderaan pelawat didaftarkan.',
] : [
    'eyebrow' => 'CATEGORY', 'title' => 'Visitor', 'sub' => 'Visitor vehicle records.',
    'add' => 'Register vehicle', 'empty_title' => 'No records', 'empty_sub' => 'No visitor vehicles have been registered yet.',
];

$nv_slug  = 'visitor';
$category = 'Pelawat';

// 9-column visitor table (foundation/visitor); ID column = NO PENGENALAN (NRIC/passport).
$nv_cols = ($lang === 'bm') ? [
    ['plate','NO KENDERAAN'], ['type','JENIS KENDERAAN'], ['model','MODEL KENDERAAN'], ['date','TARIKH AMBIL'],
    ['idnum','NO PENGENALAN'], ['name','NAMA'], ['phone','NO TELEFON'], ['serial','NO SIRI'],
] : [
    ['plate','PLATE NO.'], ['type','VEHICLE TYPE'], ['model','VEHICLE MODEL'], ['date','DATE TAKEN'],
    ['idnum','ID NO.'], ['name','NAME'], ['phone','PHONE'], ['serial','SERIAL NO.'],
];

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<body>
<div class="nv-shell">
<?php $nv_active = 'visitor'; include $_SERVER['DOCUMENT_ROOT'].'/includes/nv_chrome.php'; ?>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/vehicle_table_view.php'; ?>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
