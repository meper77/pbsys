<?php
/**
 * 9-column vehicle list view (staff / student) per foundation.md.
 *
 * Columns: Bil. | NO KENDERAAN | JENIS KENDERAAN | MODEL KENDERAAN | TARIKH AMBIL |
 *          NO PEKERJA/PELAJAR | NAMA | NO TELEFON | NO SIRI
 *
 * - All cells uppercase, single table (no active/inactive split).
 * - Filter by month / year / all years (server-side, GET ?y=&m=).
 * - do-report-style autosuggest search (scoped to this category) that filters rows.
 * - Select-to-delete (admin only).
 *
 * Expects before include:  $con, $lang, $nv_slug ('staff'|'student'),
 *                          $category ('Staf'|'Pelajar'), $t (eyebrow/title/sub/add/empty_*).
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/contact_links.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/bulk_delete_component.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/vehicle_helpers.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/auth_guard.php';

nv_schema_autoprovision_once($con);          // ensure model/date_taken/serial_no exist on live
$nv_admin = nv_is_admin();                    // mutating actions are admin-only

$cat = mysqli_real_escape_string($con, $category);

// ---- Filters (month / year / all years) ----
$fy = (isset($_GET['y']) && ctype_digit($_GET['y'])) ? (int) $_GET['y'] : 0;        // 0 = all years
$fm = (isset($_GET['m']) && ctype_digit($_GET['m']) && $_GET['m'] >= 1 && $_GET['m'] <= 12) ? (int) $_GET['m'] : 0; // 0 = all months

$where = "status='$cat'";
$eff   = "COALESCE(`date_taken`, `created_at`)";
if ($fy > 0) { $where .= " AND YEAR($eff) = $fy"; }
if ($fm > 0) { $where .= " AND MONTH($eff) = $fm"; }

$rows = [];
$res = mysqli_query($con, "SELECT * FROM `owner` WHERE $where ORDER BY $eff DESC, id DESC");
if ($res) { while ($r = mysqli_fetch_assoc($res)) { $rows[] = $r; } }

// Distinct years for the year picker.
$years = [];
$yr = mysqli_query($con, "SELECT DISTINCT YEAR($eff) y FROM `owner` WHERE status='$cat' AND $eff IS NOT NULL ORDER BY y DESC");
if ($yr) { while ($y = mysqli_fetch_assoc($yr)) { if ($y['y']) { $years[] = (int) $y['y']; } } }
if ($fy > 0 && !in_array($fy, $years, true)) { $years[] = $fy; rsort($years); }

// ---- Labels ----
$id_label = ($nv_slug === 'student')
    ? ($lang === 'bm' ? 'NO PELAJAR' : 'STUDENT NO.')
    : ($lang === 'bm' ? 'NO PEKERJA' : 'STAFF NO.');

$H = ($lang === 'bm') ? [
    'bil' => 'BIL.', 'plate' => 'NO KENDERAAN', 'type' => 'JENIS KENDERAAN', 'model' => 'MODEL KENDERAAN',
    'date' => 'TARIKH AMBIL', 'name' => 'NAMA', 'phone' => 'NO TELEFON', 'serial' => 'NO SIRI',
    'search' => 'Cari kenderaan', 'search_ph' => 'Taip plat, nama, no. ID atau telefon…',
    'year' => 'Tahun', 'month' => 'Bulan', 'all' => 'Semua', 'apply' => 'Tapis', 'showing' => 'Memaparkan',
    'records' => 'rekod', 'del' => 'Padam dipilih',
    'import' => 'Import', 'export' => 'Eksport', 'template' => 'Templat',
] : [
    'bil' => 'No.', 'plate' => 'PLATE NO.', 'type' => 'VEHICLE TYPE', 'model' => 'MODEL',
    'date' => 'DATE TAKEN', 'name' => 'NAME', 'phone' => 'PHONE', 'serial' => 'SERIAL NO.',
    'search' => 'Search vehicles', 'search_ph' => 'Type plate, name, ID or phone…',
    'year' => 'Year', 'month' => 'Month', 'all' => 'All', 'apply' => 'Filter', 'showing' => 'Showing',
    'records' => 'records', 'del' => 'Delete selected',
    'import' => 'Import', 'export' => 'Export', 'template' => 'Template',
];
$months = ($lang === 'bm')
    ? [1=>'Jan',2=>'Feb',3=>'Mac',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Ogo',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Dis']
    : [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'];
?>
<style>
  #vehicleTable td, #vehicleTable th { text-transform: uppercase; }
  #vehicleTable td.meta, #vehicleTable td.lower { text-transform: none; }
  .nv-filter-bar { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
  .nv-filter-bar .field { margin:0; }
  .nv-sg-wrap { position:relative; flex:1 1 280px; min-width:240px; }
  .nv-sg-box { position:absolute; left:0; right:0; top:100%; z-index:60; background:#fff;
    border:1px solid var(--border,#d9d9e3); border-top:0; border-radius:0 0 8px 8px;
    max-height:280px; overflow-y:auto; box-shadow:0 8px 24px rgba(0,0,0,.12); display:none; }
  .nv-sg-item { padding:8px 12px; cursor:pointer; border-bottom:1px solid #f0f0f3; font-size:14px; }
  .nv-sg-item:hover, .nv-sg-item.active { background:var(--surface-tint,#f5f3ff); }
  .nv-sg-item .muted { color:#777; font-size:12px; }
</style>
<main class="page">
    <div class="page-head">
        <div>
            <span class="eyebrow"><?php echo htmlspecialchars($t['eyebrow']); ?></span>
            <h1><?php echo htmlspecialchars($t['title']); ?></h1>
            <p class="sub"><?php echo htmlspecialchars($t['sub']); ?></p>
        </div>
        <div class="actions">
            <?php if ($nv_admin): ?>
            <?php $qf = ($fy > 0 ? '&y=' . $fy : '') . ($fm > 0 ? '&m=' . $fm : ''); ?>
            <a class="btn btn-ghost" href="/api/vehicle_export_xlsx.php?category=<?php echo urlencode($category); ?><?php echo $qf; ?>" title="<?php echo htmlspecialchars($H['export']); ?>"><i data-lucide="download"></i> <?php echo htmlspecialchars($H['export']); ?></a>
            <a class="btn btn-ghost" href="/api/vehicle_export_xlsx.php?category=<?php echo urlencode($category); ?>&template=1" title="<?php echo htmlspecialchars($H['template']); ?>"><i data-lucide="file-spreadsheet"></i> <?php echo htmlspecialchars($H['template']); ?></a>
            <button type="button" class="btn btn-ghost" onclick="document.getElementById('nvImportFile').click()"><i data-lucide="upload"></i> <?php echo htmlspecialchars($H['import']); ?></button>
            <form id="nvImportForm" method="POST" action="/api/vehicle_import_xlsx.php" enctype="multipart/form-data" style="display:none;">
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                <input type="file" id="nvImportFile" name="xlsx_file" accept=".xlsx" onchange="if(this.files.length){this.form.submit();}">
            </form>
            <a class="btn btn-primary" href="/vehicles/<?php echo $nv_slug; ?>/add.php"><i data-lucide="plus"></i> <?php echo htmlspecialchars($t['add']); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="flash ok mb-4"><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>

    <form class="card nv-stack" method="GET" id="filterForm">
        <div class="nv-filter-bar">
            <div class="nv-sg-wrap">
                <label class="field-label" for="rowSearch"><?php echo htmlspecialchars($H['search']); ?></label>
                <input class="input mono" id="rowSearch" type="text" autocomplete="off"
                       placeholder="<?php echo htmlspecialchars($H['search_ph']); ?>"
                       data-status="<?php echo htmlspecialchars($category); ?>"
                       onkeydown="if(event.key==='Enter'){event.preventDefault();}">
                <div class="nv-sg-box" id="rowSearchBox"></div>
            </div>
            <div class="field">
                <label class="field-label" for="y"><?php echo htmlspecialchars($H['year']); ?></label>
                <select class="select" id="y" name="y" onchange="document.getElementById('filterForm').submit()">
                    <option value="0"><?php echo htmlspecialchars($H['all']); ?></option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo $fy === $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label class="field-label" for="m"><?php echo htmlspecialchars($H['month']); ?></label>
                <select class="select" id="m" name="m" onchange="document.getElementById('filterForm').submit()">
                    <option value="0"><?php echo htmlspecialchars($H['all']); ?></option>
                    <?php foreach ($months as $mn => $ml): ?>
                        <option value="<?php echo $mn; ?>" <?php echo $fm === $mn ? 'selected' : ''; ?>><?php echo htmlspecialchars($ml); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <button class="btn btn-ghost" type="submit"><i data-lucide="filter"></i> <?php echo htmlspecialchars($H['apply']); ?></button>
            </div>
        </div>
    </form>

    <?php if (empty($rows)): ?>
        <div class="card flat"><div class="text-center" style="padding:48px 24px;">
            <h3 style="margin-bottom:6px;"><?php echo htmlspecialchars($t['empty_title']); ?></h3>
            <p class="text-muted"><?php echo htmlspecialchars($t['empty_sub']); ?></p>
            <?php if ($nv_admin): ?>
            <a class="btn btn-primary mt-4" href="/vehicles/<?php echo $nv_slug; ?>/add.php"><i data-lucide="plus"></i> <?php echo htmlspecialchars($t['add']); ?></a>
            <?php endif; ?>
        </div></div>
    <?php else: ?>
        <form id="bulkDeleteForm" method="POST">
            <input type="hidden" name="vehicle_type" value="<?php echo htmlspecialchars($nv_slug); ?>">

            <div class="nv-row between mt-4" style="align-items:center;">
                <p class="text-muted" style="margin:0;"><?php echo htmlspecialchars($H['showing']); ?> <strong><?php echo count($rows); ?></strong> <?php echo htmlspecialchars($H['records']); ?></p>
                <?php if ($nv_admin): ?>
                    <?php echo bulk_delete_button(['endpoint' => '/api/bulk_delete_api.php', 'confirm_message' => 'Delete selected vehicles? This cannot be undone.']); ?>
                <?php endif; ?>
            </div>

            <div class="card flat mt-2" style="overflow-x:auto;">
                <table class="table" id="vehicleTable">
                    <thead><tr>
                        <?php if ($nv_admin) { echo bulk_delete_checkbox_header(); } ?>
                        <th style="width:50px;"><?php echo htmlspecialchars($H['bil']); ?></th>
                        <th><?php echo htmlspecialchars($H['plate']); ?></th>
                        <th><?php echo htmlspecialchars($H['type']); ?></th>
                        <th><?php echo htmlspecialchars($H['model']); ?></th>
                        <th><?php echo htmlspecialchars($H['date']); ?></th>
                        <th><?php echo htmlspecialchars($id_label); ?></th>
                        <th><?php echo htmlspecialchars($H['name']); ?></th>
                        <th><?php echo htmlspecialchars($H['phone']); ?></th>
                        <th><?php echo htmlspecialchars($H['serial']); ?></th>
                        <?php if ($nv_admin) { echo '<th class="text-right"></th>'; } ?>
                    </tr></thead>
                    <tbody>
                    <?php
                    $bil = 1;
                    foreach ($rows as $r):
                        $id     = (int) $r['id'];
                        $plate  = htmlspecialchars($r['platenum'] ?? '');
                        $type   = htmlspecialchars($r['type'] ?? '');
                        $model  = htmlspecialchars(($r['model'] ?? '') !== '' ? $r['model'] : '—');
                        $dateR  = $r['date_taken'] ?? null;
                        $dateD  = ($dateR && $dateR !== '0000-00-00') ? date('d M Y', strtotime($dateR)) : '—';
                        $idnum  = htmlspecialchars($r['idnumber'] ?? '');
                        $name   = htmlspecialchars($r['name'] ?? '');
                        $phone  = htmlspecialchars($r['phone'] ?? '');
                        $serial = (isset($r['serial_no']) && $r['serial_no'] !== null && $r['serial_no'] !== '')
                                  ? str_pad((string) (int) $r['serial_no'], 4, '0', STR_PAD_LEFT) : '—';
                    ?>
                        <tr>
                            <?php if ($nv_admin) { echo bulk_delete_checkbox($id); } ?>
                            <td class="meta"><?php echo $bil++; ?></td>
                            <td><span class="plate"><?php echo $plate; ?></span></td>
                            <td><?php echo $type; ?></td>
                            <td><?php echo $model; ?></td>
                            <td class="meta"><?php echo htmlspecialchars($dateD); ?></td>
                            <td class="mono"><?php echo $idnum; ?></td>
                            <td><?php echo $name; ?></td>
                            <td class="lower"><?php echo $phone !== '' ? '<span class="text-mono">'.$phone.'</span> '.format_contact_links($r['phone']) : '<span class="text-muted">—</span>'; ?></td>
                            <td class="mono"><?php echo $serial; ?></td>
                            <?php if ($nv_admin) { echo '<td class="text-right"><a class="btn btn-quiet" href="/vehicles/'.$nv_slug.'/update.php?id='.$id.'" title="Edit"><i data-lucide="pencil"></i></a></td>'; } ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
    <?php endif; ?>
</main>
<script>
(function () {
  var input = document.getElementById('rowSearch');
  if (!input) return;
  var box  = document.getElementById('rowSearchBox');
  var rows = function () { return document.querySelectorAll('#vehicleTable tbody tr'); };
  var status = input.getAttribute('data-status') || '';
  var timer = null, items = [], active = -1;

  function applyFilter() {
    var q = input.value.trim().toLowerCase();
    rows().forEach(function (tr) {
      tr.style.display = (q === '' || tr.textContent.toLowerCase().indexOf(q) >= 0) ? '' : 'none';
    });
  }
  function esc(s) { return s == null ? '' : String(s).replace(/[&<>"']/g, function (c) {
    return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]; }); }
  function close() { box.style.display = 'none'; active = -1; }
  function render() {
    if (!items.length) { close(); return; }
    box.innerHTML = items.map(function (m, i) {
      return '<div class="nv-sg-item" data-i="' + i + '"><strong>' + esc(m.plate) + '</strong> &mdash; ' +
        esc(m.name || '(no name)') + ' <span class="muted">' + esc(m.idnumber || '') +
        (m.phone ? ' · ' + esc(m.phone) : '') + '</span></div>';
    }).join('');
    box.style.display = 'block';
  }
  function choose(i) {
    var m = items[i]; if (!m) return;
    input.value = m.plate || ''; close(); applyFilter();
  }
  box.addEventListener('mousedown', function (e) {
    var el = e.target.closest('.nv-sg-item');
    if (el) { e.preventDefault(); choose(parseInt(el.dataset.i, 10)); }
  });
  input.addEventListener('input', function () {
    applyFilter();
    var q = input.value.trim();
    clearTimeout(timer);
    if (q.length < 2) { close(); return; }
    timer = setTimeout(function () {
      fetch('/api/vehicle_suggest_api.php?by=any&status=' + encodeURIComponent(status) + '&q=' + encodeURIComponent(q))
        .then(function (r) { return r.json(); })
        .then(function (data) { items = Array.isArray(data) ? data : []; active = -1; render(); })
        .catch(close);
    }, 200);
  });
  input.addEventListener('keydown', function (e) {
    if (box.style.display !== 'block') return;
    if (e.key === 'ArrowDown') { active = Math.min(active + 1, items.length - 1); e.preventDefault(); }
    else if (e.key === 'ArrowUp') { active = Math.max(active - 1, 0); e.preventDefault(); }
    else if (e.key === 'Enter') { if (active >= 0) { e.preventDefault(); choose(active); } return; }
    else { return; }
    Array.prototype.forEach.call(box.children, function (c, i) { c.classList.toggle('active', i === active); });
  });
  input.addEventListener('blur', function () { setTimeout(close, 150); });
})();
</script>
<?php echo bulk_delete_select_all_script(); ?>
