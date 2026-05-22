<?php
session_start();

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

$text = [];

$text['bm'] = [
    'page_title' => 'Pilih peranan',
    'eyebrow' => 'Polis Bantuan · UiTM',
    'heading' => 'Selamat datang ke NEO V-TRACK',
    'subhead' => 'Sila pilih peranan anda untuk meneruskan.',
    'user_title' => 'Pengguna',
    'user_desc' => 'Akses akaun peribadi dan urus rekod kenderaan anda.',
    'admin_title' => 'Pentadbir',
    'admin_desc' => 'Urus tetapan sistem dan kawalan pentadbiran.',
    'brand_sub' => 'Pilih peranan'
];

$text['en'] = [
    'page_title' => 'Select role',
    'eyebrow' => 'Auxiliary Police · UiTM',
    'heading' => 'Welcome to NEO V-TRACK',
    'subhead' => 'Please select your role to continue.',
    'user_title' => 'User',
    'user_desc' => 'Access your personal account and manage your vehicle records.',
    'admin_title' => 'Administrator',
    'admin_desc' => 'Manage system settings and administrative controls.',
    'brand_sub' => 'Select role'
];

$t = $text[$lang];

$user_login_link = "/auth/login.php" . (isset($_SESSION['language']) ? "?lang=" . $_SESSION['language'] : "");
$admin_login_link = "/auth/login_admin.php" . (isset($_SESSION['language']) ? "?lang=" . $_SESSION['language'] : "");

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<body>
<div class="auth-hero">
  <div class="auth-card wide">
    <div class="auth-brand">
      <img class="uitm" src="/assets/images/uitm.png" alt="UiTM">
      <div class="divider"></div>
      <img class="neo" src="/assets/images/neo-vtrack-logo.png" alt="NEO V-TRACK">
      <div class="word"><span class="name">NEO <span class="y">V-TRACK</span></span><span class="sub"><?= htmlspecialchars($t['brand_sub']) ?></span></div>
    </div>

    <div class="auth-head">
      <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?></span>
      <h1><?= htmlspecialchars($t['heading']) ?></h1>
      <p><?= htmlspecialchars($t['subhead']) ?></p>
    </div>

    <div class="role-tiles">
      <a class="role-tile" href="<?= htmlspecialchars($user_login_link) ?>">
        <span class="ico"><i data-lucide="user"></i></span>
        <span class="role-name"><?= htmlspecialchars($t['user_title']) ?></span>
        <span class="role-sub"><?= htmlspecialchars($t['user_desc']) ?></span>
      </a>
      <a class="role-tile" href="<?= htmlspecialchars($admin_login_link) ?>">
        <span class="ico" style="background:var(--brand-purple-deep);color:var(--brand-yellow);"><i data-lucide="shield-check"></i></span>
        <span class="role-name"><?= htmlspecialchars($t['admin_title']) ?></span>
        <span class="role-sub"><?= htmlspecialchars($t['admin_desc']) ?></span>
      </a>
    </div>

    <div class="auth-foot">
      <span class="text-mono" style="font-size:11px;">
        <a href="?lang=bm" style="<?= $lang == 'bm' ? 'font-weight:700;' : 'color:var(--fg-3);' ?>">BM</a>
        ·
        <a href="?lang=en" style="<?= $lang == 'en' ? 'font-weight:700;' : 'color:var(--fg-3);' ?>">EN</a>
      </span>
    </div>
  </div>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
