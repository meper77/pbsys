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
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
$lang = $_SESSION['language'];

$t = ($lang === 'bm') ? [
    'eyebrow' => 'KATEGORI', 'title' => 'Pelawat', 'sub' => 'Rekod kenderaan pelawat.',
    'add' => 'Daftar kenderaan', 'col_plate' => 'No. Plat', 'col_owner' => 'Pemilik',
    'col_idnum' => 'Telefon', 'col_phone' => 'Telefon', 'col_type' => 'Jenis',
    'col_updated' => 'Kemaskini', 'empty_title' => 'Tiada rekod', 'empty_sub' => 'Belum ada kenderaan pelawat didaftarkan.',
] : [
    'eyebrow' => 'CATEGORY', 'title' => 'Visitor', 'sub' => 'Visitor vehicle records.',
    'add' => 'Register vehicle', 'col_plate' => 'Plate', 'col_owner' => 'Owner',
    'col_idnum' => 'Phone', 'col_phone' => 'Phone', 'col_type' => 'Type',
    'col_updated' => 'Last updated', 'empty_title' => 'No records', 'empty_sub' => 'No visitor vehicles have been registered yet.',
];

$nv_slug = 'visitor';
$category = 'Pelawat';

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<link rel="stylesheet" href="/assets/vehicle-autocomplete.css">
<body>
<div class="nv-shell">
<?php $nv_active = 'visitor'; include $_SERVER['DOCUMENT_ROOT'].'/includes/nv_chrome.php'; ?>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/vehicle_list_view.php'; ?>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
