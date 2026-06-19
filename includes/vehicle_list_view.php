<?php
/**
 * Shared vehicle list view (active + inactive tables).
 *
 * Expects these to be set before include:
 *   $con       mysqli connection
 *   $t         language array (eyebrow,title,sub,add,col_*,empty_*)
 *   $lang      'bm' | 'en'
 *   $nv_slug   'staff' | 'student' | 'visitor' | 'contractor'
 *   $category  'Staf' | 'Pelajar' | 'Pelawat' | 'Kontraktor'
 *
 * Renders the <main> content (chrome/header are included by the caller).
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/contact_links.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/bulk_delete_component.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/vehicle_helpers.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/auth_guard.php';

$nv_admin = nv_is_admin();  // mutating actions are admin-only (view permission)

$cat = mysqli_real_escape_string($con, $category);

$active = [];
$inactive = [];
$res = mysqli_query($con, "SELECT * FROM `owner` WHERE status='$cat' AND " . NV_ACTIVE_WHERE . " ORDER BY id DESC");
if ($res) { while ($r = mysqli_fetch_assoc($res)) { $active[] = $r; } }
$res = mysqli_query($con, "SELECT * FROM `owner` WHERE status='$cat' AND " . NV_INACTIVE_WHERE . " ORDER BY id DESC");
if ($res) { while ($r = mysqli_fetch_assoc($res)) { $inactive[] = $r; } }

$L = ($lang === 'bm')
    ? ['active' => 'Aktif', 'inactive' => 'Tidak aktif (lebih setahun)', 'search' => 'Cari kenderaan']
    : ['active' => 'Active', 'inactive' => 'Inactive (over a year)', 'search' => 'Search vehicles'];

$total = count($active) + count($inactive);

/** Render one <table> of rows. */
$nv_render_table = function (array $rows, $table_id, $t, $nv_slug, $show_select_all) use ($nv_admin) {
    if (empty($rows)) {
        echo '<div class="text-muted" style="padding:16px;">—</div>';
        return;
    }
    echo '<table class="table" id="'.htmlspecialchars($table_id).'"><thead><tr>';
    if ($nv_admin) {
        echo $show_select_all ? bulk_delete_checkbox_header() : '<th style="width:40px;"></th>';
    }
    echo '<th>'.$t['col_plate'].'</th><th>'.$t['col_owner'].'</th><th>'.$t['col_phone'].'</th>';
    echo '<th>'.$t['col_type'].'</th><th>'.$t['col_updated'].'</th><th class="text-right"></th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $id    = (int)$r['id'];
        $plate = htmlspecialchars($r['platenum']);
        $name  = htmlspecialchars($r['name']);
        $idnum = htmlspecialchars($r['idnumber']);
        $phone = htmlspecialchars($r['phone'] ?? '');
        $type  = htmlspecialchars($r['type'] ?? '');
        $ts    = $r['reactivated_at'] ?? $r['updated_at'] ?? $r['created_at'] ?? null;
        echo '<tr>';
        if ($nv_admin) { echo bulk_delete_checkbox($id); }
        echo '<td><span class="plate">'.$plate.'</span></td>';
        echo '<td><div class="owner"><span class="name">'.$name.'</span><span class="id">'.$idnum.'</span></div></td>';
        if ($phone !== '') {
            echo '<td><div class="text-mono" style="font-size:14px;">'.$phone.'</div><div style="margin-top:2px;">'.format_contact_links($r['phone']).'</div></td>';
        } else {
            echo '<td><span class="text-muted">—</span></td>';
        }
        echo '<td><span class="pill neutral"><span class="dot"></span> '.$type.'</span></td>';
        echo '<td class="meta">'.($ts ? htmlspecialchars(date('d M Y, H:i', strtotime($ts))) : '—').'</td>';
        if ($nv_admin) {
            echo '<td class="text-right"><a class="btn btn-quiet" href="/vehicles/'.$nv_slug.'/update.php?id='.$id.'" title="Edit"><i data-lucide="pencil"></i></a></td>';
        } else {
            echo '<td class="text-right"></td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
};
?>
<main class="page">
    <div class="page-head">
        <div>
            <span class="eyebrow"><?php echo htmlspecialchars($t['eyebrow']); ?></span>
            <h1><?php echo htmlspecialchars($t['title']); ?></h1>
            <p class="sub"><?php echo htmlspecialchars($t['sub']); ?></p>
        </div>
        <div class="actions">
            <?php if ($nv_admin): ?>
            <a class="btn btn-primary" href="/vehicles/<?php echo $nv_slug; ?>/add.php"><i data-lucide="plus"></i> <?php echo htmlspecialchars($t['add']); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="flash ok mb-4"><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>

    <?php if ($total === 0): ?>
        <div class="card flat"><div class="text-center" style="padding:48px 24px;">
            <h3 style="margin-bottom:6px;"><?php echo htmlspecialchars($t['empty_title']); ?></h3>
            <p class="text-muted"><?php echo htmlspecialchars($t['empty_sub']); ?></p>
            <?php if ($nv_admin): ?>
            <a class="btn btn-primary mt-4" href="/vehicles/<?php echo $nv_slug; ?>/add.php"><i data-lucide="plus"></i> <?php echo htmlspecialchars($t['add']); ?></a>
            <?php endif; ?>
        </div></div>
    <?php else: ?>
        <form class="card nv-stack" onsubmit="return false;">
            <div class="field" style="position: relative;">
                <label class="field-label" for="plateInput"><?php echo htmlspecialchars($L['search']); ?></label>
                <input class="input mono" id="plateInput" type="text" placeholder="Type to search…" autocomplete="off" autofocus>
            </div>
        </form>

        <form id="bulkDeleteForm" method="POST">
            <input type="hidden" name="vehicle_type" value="<?php echo htmlspecialchars($nv_slug); ?>">
            <?php if ($nv_admin): ?>
            <div style="margin:16px 0;">
                <?php echo bulk_delete_button([
                    'endpoint' => '/api/bulk_delete_api.php',
                    'confirm_message' => 'Delete selected vehicles? This cannot be undone.'
                ]); ?>
            </div>
            <?php endif; ?>

            <div class="card flat mt-2">
                <div class="nv-row between" style="padding:12px 16px;align-items:center;">
                    <h3 style="margin:0;"><?php echo htmlspecialchars($L['active']); ?> <span class="text-muted">(<?php echo count($active); ?>)</span></h3>
                    <span class="pill ok"><span class="dot"></span> <?php echo htmlspecialchars($L['active']); ?></span>
                </div>
                <?php $nv_render_table($active, 'vehicleTable', $t, $nv_slug, true); ?>
            </div>

            <div class="card flat mt-6">
                <div class="nv-row between" style="padding:12px 16px;align-items:center;">
                    <h3 style="margin:0;"><?php echo htmlspecialchars($L['inactive']); ?> <span class="text-muted">(<?php echo count($inactive); ?>)</span></h3>
                    <span class="pill warn"><span class="dot"></span> <?php echo htmlspecialchars($L['inactive']); ?></span>
                </div>
                <?php $nv_render_table($inactive, 'vehicleTableInactive', $t, $nv_slug, false); ?>
            </div>
        </form>
    <?php endif; ?>
</main>
<script src="/assets/vehicle-autocomplete.js"></script>
<script>
(function(){
  var input = document.getElementById('plateInput');
  if (!input) return;
  var rows = document.querySelectorAll('#vehicleTable tbody tr, #vehicleTableInactive tbody tr');
  function applyFilter(){
    var q = input.value.trim().toLowerCase();
    rows.forEach(function (tr) {
      tr.style.display = q === '' || tr.textContent.toLowerCase().indexOf(q) >= 0 ? '' : 'none';
    });
  }
  input.addEventListener('input', applyFilter);
  // Click-to-fill autocomplete (suggestions from owner via API); keep-typing still filters.
  if (window.vehicleAutocomplete) {
    window.vehicleAutocomplete('plateInput', '/api/vehicle_search_api.php', function(){ applyFilter(); });
  }
})();
</script>
<?php echo bulk_delete_select_all_script(); ?>
