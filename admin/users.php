<?php
/**
 * Users — allowlisted staff who may sign in as regular users (foundation/users).
 *
 * The admin_allowlist (role='user') is the source of truth for "who can sign in
 * as a user"; profile fields (name/position/last online) come from the `user`
 * table by email. Admins add allowed emails, toggle access via PERMISSION
 * CONTROL (is_active), and select-to-delete. No auto-delete of inactive users.
 */
session_start();

if (isset($_GET['logout'])) { header('Location: /auth/logout.php'); exit; }

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
include $_SERVER['DOCUMENT_ROOT'].'/includes/permission_check.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/otp_auth.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/schema_guard.php';
requireAdmin();
nv_schema_autoprovision_once($con);

if (!isset($_SESSION['language'])) { $_SESSION['language'] = 'bm'; }
if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] == 'en') ? 'en' : 'bm';
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}
$lang = $_SESSION['language'];
$me   = strtolower($_SESSION['email_Admin'] ?? '');

$t = $lang === 'bm' ? [
    'eyebrow' => 'Pengguna', 'title' => 'Pengguna',
    'help' => 'Hanya emel UiTM dalam senarai ini boleh log masuk sebagai pengguna. Buka/tutup akses melalui kawalan kebenaran.',
    'add_email' => 'Tambah emel pengguna', 'add' => 'Tambah',
    'no' => 'No.', 'name' => 'Nama Penuh', 'position' => 'Jawatan', 'last_online' => 'Dalam Talian Terakhir',
    'permission' => 'Kawalan Kebenaran', 'no_records' => 'Tiada pengguna dibenarkan lagi.',
    'delete_selected' => 'Padam terpilih', 'no_selected' => 'Tiada pengguna dipilih.', 'selected' => 'dipilih',
    'delete_confirm' => 'Padam pengguna terpilih? Akses dan akaun mereka akan dibuang.',
    'search' => 'Cari pengguna', 'export' => 'Eksport', 'locked' => 'Dikunci', 'never' => 'Tidak pernah',
    'bad_email' => 'Gunakan emel UiTM yang sah.', 'added' => 'Emel pengguna ditambah.',
    'removed' => 'Pengguna dibuang.', 'updated' => 'Kebenaran dikemaskini.',
] : [
    'eyebrow' => 'Users', 'title' => 'Users',
    'help' => 'Only UiTM emails on this list can sign in as users. Open/close access with the permission control.',
    'add_email' => 'Add user email', 'add' => 'Add',
    'no' => 'No.', 'name' => 'Full Name', 'position' => 'Position', 'last_online' => 'Last Online',
    'permission' => 'Permission Control', 'no_records' => 'No allowed users yet.',
    'delete_selected' => 'Delete selected', 'no_selected' => 'No users selected.', 'selected' => 'selected',
    'delete_confirm' => 'Delete the selected users? Their access and account will be removed.',
    'search' => 'Search users', 'export' => 'Export', 'locked' => 'Locked', 'never' => 'Never',
    'bad_email' => 'Use a valid UiTM email.', 'added' => 'User email added.',
    'removed' => 'User removed.', 'updated' => 'Permission updated.',
];

/* ---------------- POST actions ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $flash = ''; $flashType = 'ok';

    if ($action === 'add') {
        $em = strtolower(trim($_POST['email'] ?? ''));
        if (!nv_valid_uitm_email($em)) {
            $flash = $t['bad_email']; $flashType = 'bad';
        } else {
            $st = $con->prepare("INSERT INTO admin_allowlist (email, role, is_active, added_by)
                                 VALUES (?, 'user', 1, ?)
                                 ON DUPLICATE KEY UPDATE role='user', is_active=1");
            $st->bind_param('ss', $em, $me); $st->execute(); $st->close();
            $flash = $t['added'];
        }
    } elseif ($action === 'set_active') {
        $id  = (int)($_POST['id'] ?? 0);
        $val = (int)($_POST['val'] ?? 0) === 1 ? 1 : 0;
        $st = $con->prepare("UPDATE admin_allowlist SET is_active = ? WHERE id = ? AND is_locked = 0 AND role='user'");
        $st->bind_param('ii', $val, $id); $st->execute(); $st->close();
        $flash = $t['updated'];
    } elseif ($action === 'delete_users') {
        $ids = (array)($_POST['allow_ids'] ?? []);
        $deleted = 0;
        foreach ($ids as $raw) {
            $id = (int)$raw; if ($id <= 0) continue;
            // Fetch the email of this unlocked user-allowlist row, then remove access + account.
            $em = null;
            if ($r = $con->query("SELECT email FROM admin_allowlist WHERE id=$id AND role='user' AND is_locked=0 LIMIT 1")) {
                if ($x = $r->fetch_assoc()) { $em = strtolower($x['email']); }
            }
            if ($em === null) continue;
            $con->query("DELETE FROM admin_allowlist WHERE id=$id AND is_locked=0");
            if ($d = $con->prepare("DELETE FROM `user` WHERE LOWER(email) = ?")) { $d->bind_param('s', $em); $d->execute(); $d->close(); }
            $deleted++;
        }
        $flash = ($lang === 'bm' ? "Dipadam: $deleted pengguna." : "Deleted $deleted user(s).");
    }

    $_SESSION['users_flash'] = $flash;
    $_SESSION['users_flash_type'] = $flashType;
    header('Location: /admin/users.php');
    exit;
}

$flash = $_SESSION['users_flash'] ?? '';
$flashType = $_SESSION['users_flash_type'] ?? 'ok';
unset($_SESSION['users_flash'], $_SESSION['users_flash_type']);

/* ---------------- data: allowlisted users + their profile ---------------- */
$rows = [];
$sql = "SELECT a.id AS allow_id, a.email, a.is_active, a.is_locked,
               u.name, u.position, u.last_login, u.deletion_requested
        FROM admin_allowlist a
        LEFT JOIN `user` u ON LOWER(u.email) = LOWER(a.email)
        WHERE a.role = 'user'
        ORDER BY a.email ASC";
if ($r = @$con->query($sql)) { while ($row = $r->fetch_assoc()) { $rows[] = $row; } }

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<style>
  .nv-switch{position:relative;display:inline-block;width:42px;height:22px;}
  .nv-switch input{opacity:0;width:0;height:0;}
  .nv-switch .sl{position:absolute;cursor:pointer;inset:0;background:#cfcfd6;border-radius:22px;transition:.2s;}
  .nv-switch .sl:before{content:"";position:absolute;height:16px;width:16px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.2s;}
  .nv-switch input:checked + .sl{background:var(--status-ok,#16a34a);}
  .nv-switch input:checked + .sl:before{transform:translateX(20px);}
  .nv-switch input:disabled + .sl{opacity:.5;cursor:not-allowed;}
</style>
<body>
<div class="nv-shell">
<?php $nv_active = 'users'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php'; ?>
<main class="page">
  <div class="page-head">
    <div>
      <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?></span>
      <h1><?= htmlspecialchars($t['title']) ?></h1>
      <p class="sub"><?= htmlspecialchars($t['help']) ?></p>
    </div>
    <div class="actions">
      <button class="btn btn-ghost" id="export-btn"><i data-lucide="download"></i> <?= htmlspecialchars($t['export']) ?></button>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="flash <?= $flashType === 'bad' ? 'bad' : 'ok' ?>"><i data-lucide="<?= $flashType === 'bad' ? 'alert-triangle' : 'check-circle' ?>"></i> <?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>

  <form method="POST" class="card nv-row gap-2" style="flex-wrap:wrap;align-items:flex-end;">
    <input type="hidden" name="action" value="add">
    <div class="field" style="flex:1;min-width:260px;margin:0;">
      <label class="field-label" for="newUser"><?= htmlspecialchars($t['add_email']) ?></label>
      <input class="input" id="newUser" name="email" type="email" placeholder="name@uitm.edu.my" required>
    </div>
    <button class="btn btn-primary" type="submit"><i data-lucide="user-plus"></i> <?= htmlspecialchars($t['add']) ?></button>
  </form>

  <?php if (count($rows) > 0): ?>
  <form class="card nv-stack mt-4" onsubmit="return false;">
    <div class="field">
      <label class="field-label" for="usersSearch"><?= htmlspecialchars($t['search']) ?></label>
      <input class="input mono" id="usersSearch" type="text" placeholder="<?= htmlspecialchars($t['name']) ?>, email…">
    </div>
  </form>
  <?php endif; ?>

  <form id="bulkForm" method="POST" class="mt-4" onsubmit="return confirm('<?= addslashes($t['delete_confirm']) ?>');">
    <input type="hidden" name="action" value="delete_users">
    <div class="nv-row between mb-4">
      <span class="text-muted" id="bulkCount" style="font-size:13px;"><?= htmlspecialchars($t['no_selected']) ?></span>
      <button type="submit" class="btn btn-ghost text-danger" id="bulkDeleteBtn" disabled>
        <i data-lucide="trash-2"></i> <?= htmlspecialchars($t['delete_selected']) ?>
      </button>
    </div>

    <div class="card flat">
      <?php if (count($rows) > 0): ?>
      <table class="table" id="userTable">
        <thead>
          <tr>
            <th style="width:36px;"><input type="checkbox" id="selectAll" aria-label="Select all"></th>
            <th style="width:60px;"><?= htmlspecialchars($t['no']) ?></th>
            <th><?= htmlspecialchars($t['name']) ?></th>
            <th><?= htmlspecialchars($t['position']) ?></th>
            <th><?= htmlspecialchars($t['last_online']) ?></th>
            <th style="width:150px;"><?= htmlspecialchars($t['permission']) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php $counter = 1; foreach ($rows as $row):
            $aid    = (int)$row['allow_id'];
            $locked = !empty($row['is_locked']);
            $active = (int)($row['is_active'] ?? 1) === 1 || $locked;
            $lastlg = $row['last_login'] ?? null;
          ?>
          <tr>
            <td>
              <?php if ($locked): ?>
                <span title="<?= htmlspecialchars($t['locked']) ?>"><i data-lucide="lock" style="width:14px;height:14px;color:var(--fg-3);"></i></span>
              <?php else: ?>
                <input type="checkbox" name="allow_ids[]" value="<?= $aid ?>" class="user-cb" aria-label="Select <?= htmlspecialchars($row['email']) ?>">
              <?php endif; ?>
            </td>
            <td class="meta"><?= $counter++ ?></td>
            <td>
              <strong><?= htmlspecialchars($row['name'] ?: '—') ?></strong>
              <div class="text-mono text-muted" style="font-size:12px;"><?= htmlspecialchars($row['email']) ?>
                <?php if (!empty($row['deletion_requested'])): ?><span class="pill warn" style="margin-left:6px;"><span class="dot"></span> <?= $lang === 'bm' ? 'Mohon padam' : 'Deletion requested' ?></span><?php endif; ?>
              </div>
            </td>
            <td><?= htmlspecialchars($row['position'] ?: '—') ?></td>
            <td class="meta"><?= $lastlg ? htmlspecialchars(date('d M Y, H:i', strtotime($lastlg))) : htmlspecialchars($t['never']) ?></td>
            <td>
              <form method="POST" style="margin:0;">
                <input type="hidden" name="action" value="set_active">
                <input type="hidden" name="id" value="<?= $aid ?>">
                <input type="hidden" name="val" value="0" class="perm-val">
                <label class="nv-switch" title="<?= htmlspecialchars($t['permission']) ?>">
                  <input type="checkbox" class="perm-cb" <?= $active ? 'checked' : '' ?> <?= $locked ? 'disabled' : '' ?>
                         onchange="this.closest('form').querySelector('.perm-val').value=this.checked?1:0;this.closest('form').submit();">
                  <span class="sl"></span>
                </label>
              </form>
            </td>
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
      var table = $('#userTable'), dt = null;
      if (table.length) {
          dt = table.DataTable({ "pageLength": 25, "order": [[1, "asc"]], "autoWidth": false, "dom": "rtip",
              "columnDefs": [{ "orderable": false, "targets": [0, 5] }] });
          $('#usersSearch').on('input', function () { dt.search(this.value).draw(); });
      }
      $('#export-btn').on('click', function(){
          if (!table.length) return;
          var clone = table.clone();
          clone.find('input,button,svg,.nv-switch').remove();
          var wb = XLSX.utils.table_to_book(clone[0], {sheet: "Users"});
          XLSX.writeFile(wb, "users-<?= date('Y-m-d') ?>.xlsx");
      });
      var $selectAll = $('#selectAll'), $btn = $('#bulkDeleteBtn'), $count = $('#bulkCount');
      function refresh(){
        var checked = $('.user-cb:checked').length;
        $btn.prop('disabled', checked === 0);
        $count.text(checked === 0 ? '<?= addslashes($t["no_selected"]) ?>' : checked + ' <?= addslashes($t["selected"]) ?>');
        var total = $('.user-cb').length;
        $selectAll.prop('checked', total > 0 && checked === total);
        $selectAll.prop('indeterminate', checked > 0 && checked < total);
      }
      $(document).on('change', '.user-cb', refresh);
      $selectAll.on('change', function(){ $('.user-cb').prop('checked', this.checked); refresh(); });
      if (dt) dt.on('draw', refresh);
      refresh();
  });
  </script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
