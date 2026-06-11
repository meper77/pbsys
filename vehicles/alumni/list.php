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
    'eyebrow' => 'KATEGORI', 'title' => 'Pesara', 'sub' => 'Rekod kenderaan pesara (alumni).',
    'add' => 'Daftar kenderaan', 'empty_title' => 'Tiada rekod', 'empty_sub' => 'Belum ada kenderaan pesara didaftarkan.',
] : [
    'eyebrow' => 'CATEGORY', 'title' => 'Alumni', 'sub' => 'Retiree (alumni) vehicle records.',
    'add' => 'Register vehicle', 'empty_title' => 'No records', 'empty_sub' => 'No alumni vehicles have been registered yet.',
];

$nv_slug  = 'alumni';
$category = 'Pesara';

// 10-column alumni table (foundation/alumni).
$nv_cols = ($lang === 'bm') ? [
    ['serial','NO SIRI PELEKAT'], ['plate','NO KENDERAAN'], ['type','JENIS KENDERAAN'], ['model','MODEL KENDERAAN'],
    ['date','TARIKH AMBIL PELEKAT'], ['name','NAMA'], ['idnum','NO. KP'], ['phone','NO. TELEFON'], ['note','CATATAN'],
] : [
    ['serial','STICKER SERIAL NO.'], ['plate','PLATE NO.'], ['type','VEHICLE TYPE'], ['model','VEHICLE MODEL'],
    ['date','STICKER DATE'], ['name','NAME'], ['idnum','IC NO.'], ['phone','PHONE'], ['note','NOTE'],
];

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<body>
<div class="nv-shell">
<?php $nv_active = 'alumni'; include $_SERVER['DOCUMENT_ROOT'].'/includes/nv_chrome.php'; ?>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/vehicle_table_view.php'; ?>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
