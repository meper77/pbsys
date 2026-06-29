<?php
/**
 * Grouped vehicle search results — one table per category (Staf, Pelajar,
 * Pelawat, Kontraktor, Pesara), each rendered with THAT category's own columns
 * (via nv_category_columns + nv_table_cell). Replaces the old single universal
 * results table. Shared by search/car_admin.php and search/car_user.php.
 *
 * Expects in scope before include:
 *   $results  array   normalized owner rows (search_backend::normalizeVehicleRow)
 *   $lang     string  'bm' | 'en'
 *
 * Each table carries class="table nv-search-table" so it gets the DataTables
 * treatment (initialised by the caller) and the app card-stack layout.
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/vehicle_helpers.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/nv_category.php';

// Group the flat result set by canonical category.
$nv_groups = [];
foreach ($results as $nv_r) {
    $nv_groups[nv_canonical_category($nv_r['status'] ?? '')][] = $nv_r;
}

// Display order: the five known categories first, then any unrecognised ones.
$nv_ordered = [];
foreach (['Staf', 'Pelajar', 'Pelawat', 'Kontraktor', 'Pesara'] as $nv_c) {
    if (!empty($nv_groups[$nv_c])) { $nv_ordered[$nv_c] = $nv_groups[$nv_c]; }
}
if (!empty($nv_groups[''])) { $nv_ordered[''] = $nv_groups['']; }

$NVL = ($lang === 'bm')
    ? ['no' => 'BIL.', 'records' => 'rekod', 'other' => 'Lain-lain']
    : ['no' => 'No.',  'records' => 'records', 'other' => 'Other'];

$nv_i = 0;
foreach ($nv_ordered as $nv_canon => $nv_rows):
    $nv_label = $nv_canon === '' ? $NVL['other'] : nv_category_info($nv_canon, $lang)['label'];
    $nv_cols  = nv_category_columns($nv_canon, $lang);
    $nv_i++;
?>
  <div class="card flat mt-4">
    <div class="nv-row between" style="padding:12px 16px;align-items:center;flex-wrap:wrap;gap:8px;">
      <h3 style="margin:0;"><?= nv_category_pill($nv_canon, $nv_label) ?></h3>
      <span class="text-muted" style="font-size:14px;"><?= count($nv_rows) ?> <?= htmlspecialchars($NVL['records']) ?></span>
    </div>
    <table class="table nv-search-table" id="searchTable<?= $nv_i ?>">
      <thead>
        <tr>
          <th style="width:50px;"><?= htmlspecialchars($NVL['no']) ?></th>
          <?php foreach ($nv_cols as $nv_col): ?><th><?= htmlspecialchars($nv_col[1]) ?></th><?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php $nv_bil = 1; foreach ($nv_rows as $nv_row): ?>
        <tr>
          <td class="meta"><?= $nv_bil++ ?></td>
          <?php foreach ($nv_cols as $nv_col) { echo nv_table_cell($nv_col[0], $nv_row); } ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endforeach; ?>
<style>
  /* Match the category list pages: uppercase data, except phone/email/dates. */
  .nv-search-table td, .nv-search-table th { text-transform: uppercase; }
  .nv-search-table td.meta, .nv-search-table td.lower { text-transform: none; }
</style>
