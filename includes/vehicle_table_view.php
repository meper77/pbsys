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
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/nv_chart.php';

nv_schema_autoprovision_once($con);          // ensure model/date_taken/serial_no exist on live
$nv_admin = nv_is_admin();                    // delete stays admin-only
$nv_can_manage = nv_is_admin() || nv_is_user();  // anyone on this permission-guarded page may register/edit/import/export

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
    'import' => 'Import', 'export' => 'Eksport', 'template' => 'Templat', 'print' => 'Cetak',
    'all_years' => 'Semua tahun',
] : [
    'bil' => 'No.', 'plate' => 'PLATE NO.', 'type' => 'VEHICLE TYPE', 'model' => 'MODEL',
    'date' => 'DATE TAKEN', 'name' => 'NAME', 'phone' => 'PHONE', 'serial' => 'SERIAL NO.',
    'search' => 'Search vehicles', 'search_ph' => 'Type plate, name, ID or phone…',
    'year' => 'Year', 'month' => 'Month', 'all' => 'All', 'apply' => 'Filter', 'showing' => 'Showing',
    'records' => 'records', 'del' => 'Delete selected',
    'import' => 'Import', 'export' => 'Export', 'template' => 'Template', 'print' => 'Print',
    'all_years' => 'All years',
];
$months = ($lang === 'bm')
    ? [1=>'Jan',2=>'Feb',3=>'Mac',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Ogo',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Dis']
    : [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'];

// Human label for the active filter scope (used in the print header).
$scopeLabel = trim(($fm > 0 ? ($months[$fm] . ' ') : '') . ($fy > 0 ? $fy : $H['all_years']));

// Column config. Default = the 9-column staff/student shape; a page may set $nv_cols
// before including to render another shape (contractor 12-col, alumni 10-col). Each
// entry is [render-type, header-label]; render-type maps to nv_table_cell().
if (!isset($nv_cols)) {
    $nv_cols = [
        ['plate',  $H['plate']],
        ['type',   $H['type']],
        ['model',  $H['model']],
        ['date',   $H['date']],
        ['idnum',  $id_label],
        ['name',   $H['name']],
        ['phone',  $H['phone']],
        ['serial', $H['serial']],
    ];
}
if (!function_exists('nv_table_cell')) {
    /** Render one <td> for a render-type from an owner row (all cells uppercased via CSS). */
    function nv_table_cell($type, $r) {
        switch ($type) {
            case 'plate':   return '<td><span class="plate">' . htmlspecialchars($r['platenum'] ?? '') . '</span></td>';
            case 'type':    return '<td>' . htmlspecialchars($r['type'] ?? '') . '</td>';
            case 'model':   $m = (($r['model'] ?? '') !== '' && ($r['model'] ?? '') !== 'N/A') ? $r['model'] : '—'; return '<td>' . htmlspecialchars($m) . '</td>';
            case 'date':    $d = $r['date_taken'] ?? null; $dd = ($d && $d !== '0000-00-00') ? date('d M Y', strtotime($d)) : '—'; return '<td class="meta">' . htmlspecialchars($dd) . '</td>';
            case 'idnum':   return '<td class="text-mono">' . htmlspecialchars($r['idnumber'] ?? '') . '</td>';
            case 'name':    return '<td>' . htmlspecialchars($r['name'] ?? '') . '</td>';
            case 'phone':   $p = htmlspecialchars($r['phone'] ?? ''); return '<td class="lower">' . ($p !== '' ? '<span class="text-mono">' . $p . '</span> ' . format_contact_links($r['phone']) : '<span class="text-muted">—</span>') . '</td>';
            case 'serial':  return '<td class="text-mono">' . htmlspecialchars(nv_serial_label($r['serial_no'] ?? null)) . '</td>';
            case 'company': $c = ($r['company'] ?? '') !== '' ? $r['company'] : '—'; return '<td>' . htmlspecialchars($c) . '</td>';
            case 'email':   $e = htmlspecialchars($r['ownerEmail'] ?? ''); return '<td class="lower">' . ($e !== '' ? '<span class="text-mono">' . $e . '</span>' : '<span class="text-muted">—</span>') . '</td>';
            case 'note':    $n = ($r['note'] ?? '') !== '' ? $r['note'] : '—'; return '<td>' . htmlspecialchars($n) . '</td>';
        }
        return '<td></td>';
    }
}
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
  /* Print: the chart only (with its title), for the selected scope. */
  .nv-print-only { display:none; }
  @media print {
    .nv-header, .nv-nav, .nv-footer, .nv-no-print, #filterForm, .page-head,
    #bulkDeleteForm, .nv-sg-box, .flash { display:none !important; }
    .nv-print-only { display:block !important; margin-bottom:14px; }
    body, .page { background:#fff !important; }
    .card { box-shadow:none !important; border:1px solid #ddd !important; }
    @page { margin:12mm; }
  }
</style>
<main class="page">
    <div class="nv-print-only" style="text-align:center;">
        <div style="font-weight:700;">POLIS BANTUAN · UiTM CAWANGAN JOHOR (SEGAMAT)</div>
        <div style="font-size:15px;margin-top:2px;">
            <?php echo htmlspecialchars(strtoupper($t['title'])); ?> — <?php echo htmlspecialchars(strtoupper($scopeLabel)); ?>
            (<?php echo count($rows); ?> <?php echo htmlspecialchars($H['records']); ?>)
        </div>
    </div>
    <div class="page-head">
        <div>
            <span class="eyebrow"><?php echo htmlspecialchars($t['eyebrow']); ?></span>
            <h1><?php echo htmlspecialchars($t['title']); ?></h1>
            <p class="sub"><?php echo htmlspecialchars($t['sub']); ?></p>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-ghost" onclick="window.print()" title="<?php echo htmlspecialchars($H['print']); ?>"><i data-lucide="printer"></i> <?php echo htmlspecialchars($H['print']); ?></button>
            <?php if ($nv_can_manage): ?>
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

    <?php
    // Statistical chart by vehicle type, scoped to the same filter as the table:
    //   year + month -> a single bar for that month
    //   year only    -> 12 monthly bars for that year
    //   month only   -> that month, one bar per year (all years)
    //   neither      -> one bar per year (all years)
    if ($fy > 0 && $fm > 0) {
        $chartTitle = ($lang === 'bm' ? 'Statistik — ' : 'Statistics — ') . $months[$fm] . ' ' . $fy;
    } elseif ($fy > 0) {
        $chartTitle = ($lang === 'bm' ? 'Statistik bulanan — ' : 'Monthly statistics — ') . $fy;
    } elseif ($fm > 0) {
        $chartTitle = ($lang === 'bm' ? 'Statistik tahunan — ' : 'Yearly statistics — ') . $months[$fm]
                    . ' (' . ($lang === 'bm' ? 'semua tahun' : 'all years') . ')';
    } else {
        $chartTitle = ($lang === 'bm' ? 'Statistik tahunan — Semua tahun' : 'Yearly statistics — All years');
    }
    echo nv_owner_chart_card($con, [
        'status'   => $category,
        'year'     => $fy,   // 0 = all years
        'month'    => $fm,   // 0 = all months
        'seriesBy' => 'type',
        'series'   => [
            'KERETA'    => ['label' => 'KERETA',    'color' => '#6b21a8'],
            'MOTOSIKAL' => ['label' => 'MOTOSIKAL', 'color' => '#f5c518'],
        ],
        'months'   => $months,
        'title'    => $chartTitle,
        'sub'      => ($lang === 'bm' ? 'Mengikut jenis kenderaan' : 'By vehicle type'),
        'empty'    => ($lang === 'bm' ? 'Tiada data.' : 'No data.'),
    ]);
    ?>

    <?php if (empty($rows)): ?>
        <div class="card flat nv-no-print"><div class="text-center" style="padding:48px 24px;">
            <h3 style="margin-bottom:6px;"><?php echo htmlspecialchars($t['empty_title']); ?></h3>
            <p class="text-muted"><?php echo htmlspecialchars($t['empty_sub']); ?></p>
            <?php if ($nv_can_manage): ?>
            <a class="btn btn-primary mt-4" href="/vehicles/<?php echo $nv_slug; ?>/add.php"><i data-lucide="plus"></i> <?php echo htmlspecialchars($t['add']); ?></a>
            <?php endif; ?>
        </div></div>
    <?php else: ?>
        <form id="bulkDeleteForm" method="POST">
            <input type="hidden" name="vehicle_type" value="<?php echo htmlspecialchars($nv_slug); ?>">

            <div class="nv-row between mt-4" style="align-items:center;">
                <p class="text-muted" style="margin:0;"><?php echo htmlspecialchars($H['showing']); ?> <strong><?php echo count($rows); ?></strong> <?php echo htmlspecialchars($H['records']); ?></p>
                <?php if ($nv_admin): ?>
                    <?php echo bulk_delete_button(['endpoint' => '/api/bulk_delete_api.php', 'confirm_message' => 'Padam kenderaan dipilih? Tindakan ini tidak boleh dibatalkan.']); ?>
                <?php endif; ?>
            </div>

            <div class="card flat mt-2" style="overflow-x:auto;">
                <table class="table" id="vehicleTable">
                    <thead><tr>
                        <?php if ($nv_admin) { echo bulk_delete_checkbox_header(); } ?>
                        <th style="width:50px;"><?php echo htmlspecialchars($H['bil']); ?></th>
                        <?php foreach ($nv_cols as $c): ?><th><?php echo htmlspecialchars($c[1]); ?></th><?php endforeach; ?>
                        <?php if ($nv_can_manage) { echo '<th class="text-right nv-no-print"></th>'; } ?>
                    </tr></thead>
                    <tbody>
                    <?php
                    $bil = 1;
                    foreach ($rows as $r):
                        $id = (int) $r['id'];
                    ?>
                        <tr>
                            <?php if ($nv_admin) { echo bulk_delete_checkbox($id); } ?>
                            <td class="meta"><?php echo $bil++; ?></td>
                            <?php foreach ($nv_cols as $c) { echo nv_table_cell($c[0], $r); } ?>
                            <?php if ($nv_can_manage) { echo '<td class="text-right nv-no-print"><a class="btn btn-quiet" href="/vehicles/'.$nv_slug.'/update.php?id='.$id.'" title="Kemaskini"><i data-lucide="pencil"></i></a></td>'; } ?>
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
