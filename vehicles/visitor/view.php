<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

if (!isset($_SESSION['email_Admin'])) {
    header('location:/auth/login_admin.php');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$row = null;
if ($id > 0) {
    $res = mysqli_query($con, "SELECT * FROM `owner` WHERE id = $id");
    if ($res && mysqli_num_rows($res) > 0) { $row = mysqli_fetch_assoc($res); }
}

include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>
<body>
<div class="nv-shell">
<?php $nv_active = 'visitor'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php'; ?>
<main class="page">
    <div class="page-head">
        <div>
            <span class="eyebrow">Pelawat</span>
            <h1>Vehicle record</h1>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="/vehicles/visitor/list.php"><i data-lucide="arrow-left"></i> Back</a>
            <?php if ($row): ?><a class="btn btn-primary" href="/vehicles/visitor/update.php?id=<?= (int)$row['id'] ?>"><i data-lucide="pencil"></i> Edit</a><?php endif; ?>
        </div>
    </div>
    <?php if ($row): ?>
    <div class="card nv-stack gap-4">
        <div class="nv-row gap-4"><span class="plate lg"><?= htmlspecialchars($row['platenum'] ?? '') ?></span>
            <div><div class="text-display" style="font-size:20px;font-weight:700;"><?= htmlspecialchars($row['name'] ?? '') ?></div><div class="text-mono text-muted" style="font-size:12px;"><?= htmlspecialchars($row['idnumber'] ?? '') ?></div></div>
        </div>
        <div class="kv">
            <div class="k">Status</div><div class="v"><?= htmlspecialchars($row['status'] ?? '') ?></div>
            <div class="k">Vehicle type</div><div class="v"><?= htmlspecialchars($row['type'] ?? '') ?></div>
            <div class="k">Phone</div><div class="v text-mono"><?= htmlspecialchars($row['phone'] ?? '') ?></div>
        </div>
    </div>
    <?php else: ?>
    <div class="flash bad">Vehicle record not found.</div>
    <?php endif; ?>
</main>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
