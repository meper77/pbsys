<?php
session_start();

// ========== LOGOUT HANDLER ==========
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /auth/role_selection.php');
    exit();
}
// ========== END LOGOUT HANDLER ==========

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

if (!isset($_SESSION['email_Admin'])) {
    header('location:/auth/login_admin.php');
    exit();
}

// LANGUAGE SYSTEM
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
    'eyebrow'   => 'KATEGORI',
    'title'     => 'Staf',
    'sub'       => 'Rekod kenderaan kakitangan UiTM.',
    'add'       => 'Daftar kenderaan',
    'col_plate' => 'No. Plat',
    'col_owner' => 'Pemilik',
    'col_idnum' => 'No. Pekerja',
    'col_phone' => 'Telefon',
    'col_type'  => 'Jenis',
    'col_updated'=> 'Kemaskini',
    'empty_title'=> 'Tiada rekod',
    'empty_sub'  => 'Belum ada kenderaan staf didaftarkan.',
] : [
    'eyebrow'   => 'CATEGORY',
    'title'     => 'Staff',
    'sub'       => 'Vehicle records for UiTM staff.',
    'add'       => 'Register vehicle',
    'col_plate' => 'Plate',
    'col_owner' => 'Owner',
    'col_idnum' => 'Staff no.',
    'col_phone' => 'Phone',
    'col_type'  => 'Type',
    'col_updated'=> 'Last updated',
    'empty_title'=> 'No records',
    'empty_sub'  => 'No staff vehicles have been registered yet.',
];

$nv_slug = 'staff';
$category = 'Staf';

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<body>
<div class="nv-shell">
<?php $nv_active = 'staff'; include $_SERVER['DOCUMENT_ROOT'].'/includes/nv_chrome.php'; ?>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/vehicle_list_view.php'; ?>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
