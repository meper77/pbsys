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
    'eyebrow' => 'KATEGORI', 'title' => 'Kontraktor', 'sub' => 'Rekod kenderaan kontraktor.',
    'add' => 'Daftar kenderaan', 'empty_title' => 'Tiada rekod', 'empty_sub' => 'Belum ada kenderaan kontraktor didaftarkan.',
] : [
    'eyebrow' => 'CATEGORY', 'title' => 'Contractor', 'sub' => 'Contractor vehicle records.',
    'add' => 'Register vehicle', 'empty_title' => 'No records', 'empty_sub' => 'No contractor vehicles have been registered yet.',
];

$nv_slug  = 'contractor';
$category = 'Kontraktor';

// 12-column contractor table (foundation/contractor).
$nv_cols = ($lang === 'bm') ? [
    ['serial','NO SIRI'], ['name','NAMA'], ['idnum','NO. IC'], ['plate','NO KENDERAAN'],
    ['type','KENDERAAN'], ['model','MODEL KENDERAAN'], ['company','SYARIKAT'], ['phone','NO TELEFON'],
    ['date','TARIKH KELUAR PELEKAT'], ['email','EMAIL'], ['note','CATATAN'],
] : [
    ['serial','SERIAL NO.'], ['name','NAME'], ['idnum','IC NO.'], ['plate','PLATE NO.'],
    ['type','VEHICLE'], ['model','VEHICLE MODEL'], ['company','COMPANY'], ['phone','PHONE'],
    ['date','STICKER ISSUE DATE'], ['email','EMAIL'], ['note','NOTE'],
];

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<body>
<div class="nv-shell">
<?php $nv_active = 'contractor'; include $_SERVER['DOCUMENT_ROOT'].'/includes/nv_chrome.php'; ?>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/vehicle_table_view.php'; ?>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
