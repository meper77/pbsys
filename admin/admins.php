<?php
session_start();

if (isset($_GET['logout'])) {
    header('Location: /auth/logout.php');
    exit();
}

include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';
include $_SERVER['DOCUMENT_ROOT'] . '/includes/permission_check.php';
requireAdmin();

if (!isset($_SESSION['language'])) { $_SESSION['language'] = 'bm'; }
if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] == 'en') ? 'en' : 'bm';
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit();
}
$lang = $_SESSION['language'];

$t = $lang === 'bm' ? [
    'eyebrow' => 'Pentadbir', 'admins_list' => 'Senarai admin',
    'email' => 'Emel', 'phone' => 'Telefon', 'admin_name' => 'Nama', 'created' => 'Dibuat',
    'no' => 'No.', 'export' => 'Eksport', 'delete' => 'Padam', 'delete_selected' => 'Padam terpilih',
    'no_records' => 'Tiada rekod admin', 'delete_confirm' => 'Padam admin terpilih?',
    'protected' => 'Dilindungi', 'you' => 'Anda', 'search' => 'Cari pentadbir',
    'allowlist' => 'Senarai dibenarkan (staf admin)',
    'allowlist_help' => 'Hanya emel UiTM dalam senarai ini boleh log masuk sebagai admin. Emel yang mempunyai akaun tidak boleh dipadam.',
    'add_email' => 'Tambah emel', 'add' => 'Tambah', 'remove' => 'Buang', 'locked' => 'Dikunci',
    'no_account' => 'Tiada akaun lagi', 'has_account' => 'Mempunyai akaun', 'no_selected' => 'Tiada admin dipilih.',
    'selected' => 'dipilih',
] : [
    'eyebrow' => 'Administration', 'admins_list' => 'Admins',
    'email' => 'Email', 'phone' => 'Phone', 'admin_name' => 'Name', 'created' => 'Created',
    'no' => 'No.', 'export' => 'Export', 'delete' => 'Delete', 'delete_selected' => 'Delete selected',
    'no_records' => 'No admin records yet', 'delete_confirm' => 'Delete the selected admins?',
    'protected' => 'Protected', 'you' => 'You', 'search' => 'Search admins',
    'allowlist' => 'Allowlist (admin staff)',
    'allowlist_help' => 'Only UiTM emails on this list can sign in as admins. An allow-listed email that already has an account cannot be deleted.',
    'add_email' => 'Add email', 'add' => 'Add', 'remove' => 'Remove', 'locked' => 'Locked',
    'no_account' => 'No account yet', 'has_account' => 'Has account', 'no_selected' => 'No admins selected.',
    'selected' => 'selected',
];

$me = strtolower($_SESSION['email_Admin'] ?? '');

function nvtbl_exists($con, $name) {
    $n = $con->real_escape_string($name);
    $r = @$con->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$n' LIMIT 1");
    return $r && $r->num_rows > 0;
}
$hasAllow = nvtbl_exists($con, 'admin_allowlist');

/* ---------------- POST actions ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $flash = ''; $flashType = 'ok';

    if ($action === 'allowlist_add' && $hasAllow) {
        $em = strtolower(trim($_POST['email'] ?? ''));
        if (!filter_var($em, FILTER_VALIDATE_EMAIL) || !preg_match('/@(student\.)?uitm\.edu\.my$/', $em)) {
            $flash = ($lang === 'bm' ? 'Gunakan emel UiTM yang sah.' : 'Use a valid UiTM email.'); $flashType = 'bad';
        } else {
            $st = $con->prepare("INSERT INTO admin_allowlist (email, is_locked, added_by) VALUES (?, 0, ?)
                                 ON DUPLICATE KEY UPDATE email = email");
            $st->bind_param('ss', $em, $me);
            $st->execute(); $st->close();
            $flash = ($lang === 'bm' ? 'Emel ditambah ke senarai dibenarkan.' : 'Email added to allowlist.');
        }
    } elseif ($action === 'allowlist_remove' && $hasAllow) {
        $id = (int)($_POST['id'] ?? 0);
        $st = $con->prepare("DELETE FROM admin_allowlist WHERE id = ? AND is_locked = 0");
        $st->bind_param('i', $id); $st->execute(); $st->close();
        $flash = ($lang === 'bm' ? 'Emel dibuang dari senarai.' : 'Email removed from allowlist.');
    } elseif ($action === 'delete_admins') {
        $allow = [];
        if ($hasAllow && ($r = $con->query("SELECT LOWER(email) e FROM admin_allowlist"))) {
            while ($x = $r->fetch_assoc()) { $allow[$x['e']] = 1; }
        }
        $ids = $_POST['admin_ids'] ?? [];
        $deleted = 0; $skipped = 0;
        foreach ((array)$ids as $raw) {
            $id = (int)$raw;
            if ($id <= 0) continue;
            $row = ($r = $con->query("SELECT email FROM admin WHERE userid = $id LIMIT 1")) ? $r->fetch_assoc() : null;
            if (!$row) continue;
            $em = strtolower($row['email']);
            if ($em === $me || isset($allow[$em])) { $skipped++; continue; } // self / allowlisted are protected
            $con->query("DELETE FROM admin WHERE userid = $id");
            $deleted++;
        }
        $flash = ($lang === 'bm' ? "Dipadam: $deleted admin." : "Deleted $deleted admin(s).");
        if ($skipped) { $flash .= ($lang === 'bm' ? " $skipped dilindungi/dilangkau." : " $skipped protected/skipped."); }
    }

    $_SESSION['admins_flash'] = $flash;
    $_SESSION['admins_flash_type'] = $flashType;
    header('Location: /admin/admins.php');
    exit();
}

$flash = $_SESSION['admins_flash'] ?? '';
$flashType = $_SESSION['admins_flash_type'] ?? 'ok';
unset($_SESSION['admins_flash'], $_SESSION['admins_flash_type']);

/* ---------------- data ---------------- */
$admins = [];
if ($r = $con->query("SELECT * FROM `admin` ORDER BY userid ASC")) {
    while ($row = $r->fetch_assoc()) { $admins[] = $row; }
}
$allowSet = [];
$allowRows = [];
if ($hasAllow && ($r = $con->query("SELECT * FROM admin_allowlist ORDER BY is_locked DESC, email ASC"))) {
    while ($row = $r->fetch_assoc()) { $allowRows[] = $row; $allowSet[strtolower($row['email'])] = 1; }
}

include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>
<link rel="stylesheet" href="/assets/css/neo-vtrack-tokens.css">
<link rel="stylesheet" href="/assets/css/neo-vtrack-components.css">
<link rel="stylesheet" href="/assets/css/neo-vtrack-app.css">
<link rel="stylesheet" href="/assets/css/responsive.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<body>
<div class="nv-shell">
<?php $nv_active = 'admin'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php'; ?>
<main class="page">
  <div class="page-head">
    <div>
      <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?></span>
      <h1 class="h1-compact"><?= htmlspecialchars($t['admins_list']) ?></h1>
    </div>
    <div class="actions">
      <button class="btn btn-ghost" id="export-btn"><i data-lucide="download"></i> <?= htmlspecialchars($t['export']) ?></button>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="flash <?= $flashType === 'bad' ? 'bad' : 'ok' ?>"><i data-lucide="<?= $flashType === 'bad' ? 'alert-triangle' : 'check-circle' ?>"></i> <?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>

  <!-- Allowlist management -->
  <div class="card nv-stack">
    <div>
      <h3 style="margin:0 0 4px;"><?= htmlspecialchars($t['allowlist']) ?></h3>
      <p class="text-muted" style="margin:0;font-size:13px;"><?= htmlspecialchars($t['allowlist_help']) ?></p>
    </div>
    <?php if (!$hasAllow): ?>
      <div class="flash info"><i data-lucide="info"></i> Allowlist table not migrated yet.</div>
    <?php else: ?>
      <form method="POST" class="nv-row gap-2" style="flex-wrap:wrap;align-items:flex-end;">
        <input type="hidden" name="action" value="allowlist_add">
        <div class="field" style="flex:1;min-width:240px;margin:0;">
          <label class="field-label" for="newAllow"><?= htmlspecialchars($t['add_email']) ?></label>
          <input class="input" id="newAllow" name="email" type="email" placeholder="name@uitm.edu.my" required>
        </div>
        <button class="btn btn-primary" type="submit"><i data-lucide="plus"></i> <?= htmlspecialchars($t['add']) ?></button>
      </form>
      <div class="card flat" style="margin-top:6px;">
        <table class="table">
          <thead><tr><th><?= htmlspecialchars($t['email']) ?></th><th style="width:140px;"></th><th style="width:120px;"></th></tr></thead>
          <tbody>
          <?php foreach ($allowRows as $a):
              $em = strtolower($a['email']);
              $locked = !empty($a['is_locked']);
              $hasAcct = false;
              foreach ($admins as $ad) { if (strtolower($ad['email']) === $em) { $hasAcct = true; break; } }
          ?>
            <tr>
              <td><strong><?= htmlspecialchars($a['email']) ?></strong></td>
              <td>
                <?php if ($locked): ?><span class="pill neutral"><span class="dot"></span> <?= htmlspecialchars($t['locked']) ?></span>
                <?php elseif ($hasAcct): ?><span class="pill ok"><span class="dot"></span> <?= htmlspecialchars($t['has_account']) ?></span>
                <?php else: ?><span class="pill warn"><span class="dot"></span> <?= htmlspecialchars($t['no_account']) ?></span><?php endif; ?>
              </td>
              <td class="text-right">
                <?php if (!$locked): ?>
                  <form method="POST" style="display:inline" onsubmit="return confirm('<?= addslashes($t['remove']) ?>?')">
                    <input type="hidden" name="action" value="allowlist_remove">
                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                    <button class="btn btn-quiet text-danger" type="submit"><i data-lucide="x"></i> <?= htmlspecialchars($t['remove']) ?></button>
                  </form>
                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($allowRows)): ?><tr><td colspan="3" class="text-muted">—</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Admin accounts -->
  <?php if (count($admins) > 0): ?>
  <form class="card nv-stack mt-6" onsubmit="return false;">
    <div class="field">
      <label class="field-label" for="adminsSearch"><?= htmlspecialchars($t['search']) ?></label>
      <input class="input mono" id="adminsSearch" type="text" placeholder="Email, name…">
    </div>
  </form>
  <?php endif; ?>

  <form id="adminBulkForm" method="POST" class="mt-4" onsubmit="return confirm('<?= addslashes($t['delete_confirm']) ?>');">
    <input type="hidden" name="action" value="delete_admins">
    <div class="nv-row between mb-4">
      <span class="text-muted" id="bulkCount" style="font-size:13px;"><?= htmlspecialchars($t['no_selected']) ?></span>
      <button type="submit" class="btn btn-ghost text-danger" id="bulkDeleteBtn" disabled>
        <i data-lucide="trash-2"></i> <?= htmlspecialchars($t['delete_selected']) ?>
      </button>
    </div>

    <div class="card flat">
      <?php if (count($admins) > 0): ?>
      <table class="table" id="adminTable">
        <thead>
          <tr>
            <th style="width:36px;"><input type="checkbox" id="selectAll" aria-label="Select all"></th>
            <th style="width:60px;"><?= htmlspecialchars($t['no']) ?></th>
            <th><?= htmlspecialchars($t['email']) ?></th>
            <th><?= htmlspecialchars($t['phone']) ?></th>
            <th><?= htmlspecialchars($t['admin_name']) ?></th>
            <th><?= htmlspecialchars($t['created']) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php $counter = 1; foreach ($admins as $row):
              $id = (int)($row['userid'] ?? 0);
              $em = strtolower($row['email'] ?? '');
              $isSelf = ($em === $me);
              $isAllow = isset($allowSet[$em]);
              $protected = $isSelf || $isAllow;
              $cdate = $row['created_at'] ?? $row['last_login'] ?? null;
          ?>
          <tr>
            <td>
              <?php if ($protected): ?>
                <span title="<?= $isSelf ? htmlspecialchars($t['you']) : htmlspecialchars($t['protected']) ?>"><i data-lucide="lock" style="width:14px;height:14px;color:var(--fg-3);"></i></span>
              <?php else: ?>
                <input type="checkbox" name="admin_ids[]" value="<?= $id ?>" class="admin-cb" aria-label="Select <?= htmlspecialchars($row['email'] ?? '') ?>">
              <?php endif; ?>
            </td>
            <td class="meta"><?= $counter++ ?></td>
            <td><strong><?= htmlspecialchars($row['email'] ?? '') ?></strong>
              <?php if ($isSelf): ?><span class="pill info" style="margin-left:6px;"><span class="dot"></span> <?= htmlspecialchars($t['you']) ?></span><?php endif; ?>
            </td>
            <td class="meta"><?= htmlspecialchars($row['phone'] ?? '—') ?></td>
            <td><?= htmlspecialchars($row['name'] ?? '—') ?></td>
            <td class="meta"><?= $cdate ? htmlspecialchars(date('d M Y', strtotime($cdate))) : '—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="flash info"><i data-lucide="info"></i> <?= htmlspecialchars($t['no_records']) ?></div>
      <?php endif; ?>
    </div>
  </form>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.1/xlsx.full.min.js"></script>
  <script>
  $(function(){
      var table = $('#adminTable');
      var dt = null;
      if (table.length) {
          dt = table.DataTable({ "pageLength": 25, "order": [[1, "asc"]], "autoWidth": false, "dom": "rtip",
              "columnDefs": [{ "orderable": false, "targets": 0 }] });
          $('#adminsSearch').on('input', function () { dt.search(this.value).draw(); });
      }
      $('#export-btn').on('click', function(){
          if (!table.length) return;
          var clone = table.clone();
          clone.find('input,button,svg,.pill').remove();
          var wb = XLSX.utils.table_to_book(clone[0], {sheet: "Admins"});
          XLSX.writeFile(wb, "admins-<?= date('Y-m-d') ?>.xlsx");
      });

      var $selectAll = $('#selectAll'), $btn = $('#bulkDeleteBtn'), $count = $('#bulkCount');
      function refresh(){
        var checked = $('.admin-cb:checked').length;
        $btn.prop('disabled', checked === 0);
        $count.text(checked === 0 ? '<?= addslashes($t["no_selected"]) ?>' : checked + ' <?= addslashes($t["selected"]) ?>');
        var total = $('.admin-cb').length;
        $selectAll.prop('checked', total > 0 && checked === total);
        $selectAll.prop('indeterminate', checked > 0 && checked < total);
      }
      $(document).on('change', '.admin-cb', refresh);
      $selectAll.on('change', function(){ $('.admin-cb').prop('checked', this.checked); refresh(); });
      if (dt) dt.on('draw', refresh);
      refresh();
  });
  </script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
