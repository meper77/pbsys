<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /auth/role_selection.php');
    exit();
}

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/includes/contact_links.php';

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
    'eyebrow'   => 'KATEGORI',
    'title'     => 'Pelajar',
    'sub'       => 'Rekod kenderaan pelajar UiTM.',
    'add'       => 'Daftar kenderaan',
    'col_plate' => 'No. Plat',
    'col_owner' => 'Pemilik',
    'col_phone' => 'Telefon',
    'col_type'  => 'Jenis',
    'col_sticker'=> 'Pas Kenderaan',
    'col_updated'=> 'Kemaskini',
    'sticker_ok' => 'Sah',
    'sticker_bad'=> 'Tiada',
    'empty_title'=> 'Tiada rekod',
    'empty_sub'  => 'Belum ada kenderaan pelajar didaftarkan.',
    'delete_confirm' => 'Padam rekod ini?',
] : [
    'eyebrow'   => 'CATEGORY',
    'title'     => 'Student',
    'sub'       => 'Vehicle records for UiTM students.',
    'add'       => 'Register vehicle',
    'col_plate' => 'Plate',
    'col_owner' => 'Owner',
    'col_phone' => 'Phone',
    'col_type'  => 'Type',
    'col_sticker'=> 'Sticker',
    'col_updated'=> 'Last updated',
    'sticker_ok' => 'Valid',
    'sticker_bad'=> 'None',
    'empty_title'=> 'No records',
    'empty_sub'  => 'No student vehicles have been registered yet.',
    'delete_confirm' => 'Delete this record?',
];

$rows = [];
$result = mysqli_query($con, "SELECT * FROM `owner` WHERE status = 'Pelajar' ORDER BY id DESC");
if ($result) {
    while ($r = mysqli_fetch_assoc($result)) {
        $rows[] = $r;
    }
}

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<body>
<div class="nv-shell">
<?php $nv_active = 'student'; include $_SERVER['DOCUMENT_ROOT'].'/includes/nv_chrome.php'; ?>
<main class="page">
    <div class="page-head">
        <div>
            <span class="eyebrow"><?php echo $t['eyebrow']; ?></span>
            <h1><?php echo htmlspecialchars($t['title']); ?></h1>
            <p class="sub"><?php echo htmlspecialchars($t['sub']); ?></p>
        </div>
        <div class="actions">
            <a class="btn btn-primary" href="/vehicles/student/add.php"><i data-lucide="plus"></i> <?php echo htmlspecialchars($t['add']); ?></a>
        </div>
    </div>

    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="flash ok mb-4"><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>

    <?php if (count($rows) > 0): ?>
    <form class="card nv-stack" onsubmit="return false;">
        <div class="field">
            <label class="field-label" for="listSearch">Cari kenderaan</label>
            <input class="input mono" id="listSearch" type="text" placeholder="Plate, name, matric, sticker…" autofocus>
        </div>
    </form>
    <?php endif; ?>

    <div class="card flat mt-6">
        <?php if (count($rows) === 0): ?>
            <div class="text-center" style="padding:48px 24px;">
                <h3 style="margin-bottom:6px;"><?php echo htmlspecialchars($t['empty_title']); ?></h3>
                <p class="text-muted"><?php echo htmlspecialchars($t['empty_sub']); ?></p>
                <a class="btn btn-primary mt-4" href="/vehicles/student/add.php"><i data-lucide="plus"></i> <?php echo htmlspecialchars($t['add']); ?></a>
            </div>
        <?php else: ?>
        <table class="table" id="vehicleTable">
            <thead>
                <tr>
                    <th><?php echo $t['col_plate']; ?></th>
                    <th><?php echo $t['col_owner']; ?></th>
                    <th><?php echo $t['col_phone']; ?></th>
                    <th><?php echo $t['col_type']; ?></th>
                    <th><?php echo $t['col_sticker']; ?></th>
                    <th><?php echo $t['col_updated']; ?></th>
                    <th class="text-right"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r):
                $id = (int)$r['id'];
                $plate = htmlspecialchars($r['platenum']);
                $name = htmlspecialchars($r['name']);
                $idnum = htmlspecialchars($r['idnumber']);
                $phone = htmlspecialchars($r['phone'] ?? '');
                $type = htmlspecialchars($r['type'] ?? '');
                $sticker = $r['sticker'] ?? '';
                $stickerno = htmlspecialchars($r['stickerno'] ?? '');
                $ts = $r['updated_at'] ?? $r['created_at'] ?? null;
            ?>
                <tr>
                    <td><span class="plate"><?php echo $plate; ?></span></td>
                    <td>
                        <div class="owner">
                            <span class="name"><?php echo $name; ?></span>
                            <span class="id"><?php echo $idnum; ?></span>
                        </div>
                    </td>
                    <td>
                        <?php if (!empty($phone)): ?>
                            <div class="text-mono" style="font-size:13px;"><?php echo $phone; ?></div>
                            <div style="margin-top:2px;"><?php echo format_contact_links($r['phone']); ?></div>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="pill neutral"><span class="dot"></span> <?php echo $type; ?></span></td>
                    <td>
                        <?php if ($sticker === 'ADA' && $stickerno !== ''): ?>
                            <span class="pill ok"><span class="dot"></span> <?php echo $stickerno; ?></span>
                        <?php elseif ($sticker === 'ADA'): ?>
                            <span class="pill ok"><span class="dot"></span> <?php echo htmlspecialchars($t['sticker_ok']); ?></span>
                        <?php elseif ($sticker === 'TIADA'): ?>
                            <span class="pill bad"><span class="dot"></span> <?php echo htmlspecialchars($t['sticker_bad']); ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="meta"><?php echo $ts ? htmlspecialchars(date('d M Y, H:i', strtotime($ts))) : '—'; ?></td>
                    <td class="text-right">
                        <a class="btn btn-quiet" href="/vehicles/student/update.php?id=<?php echo $id; ?>" title="Edit"><i data-lucide="pencil"></i></a>
                        <a class="btn btn-quiet" href="/vehicles/student/delete.php?id=<?php echo $id; ?>" title="Delete" onclick="return confirm('<?php echo addslashes($t['delete_confirm']); ?>')"><i data-lucide="trash-2"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
<script>
(function(){
  var input = document.getElementById('listSearch');
  if (!input) return;
  var rows = document.querySelectorAll('#vehicleTable tbody tr');
  input.addEventListener('input', function () {
    var q = this.value.trim().toLowerCase();
    rows.forEach(function (tr) {
      tr.style.display = q === '' || tr.textContent.toLowerCase().indexOf(q) >= 0 ? '' : 'none';
    });
  });
})();
</script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
