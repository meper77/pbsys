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
include $_SERVER['DOCUMENT_ROOT'].'/includes/search_backend.php';

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

$t = $lang === 'bm' ? [
    'eyebrow'         => 'Carian Kenderaan',
    'page_title'      => 'Cari kenderaan',
    'sub'             => 'Cari mengikut plat, nama pemilik, atau nombor ID.',
    'placeholder'     => 'Masukkan no. plat, nama, atau no. ID',
    'search_button'   => 'Cari',
    'clear'           => 'Kosongkan',
    'col_no'          => 'No.',
    'col_status'      => 'Status',
    'col_id'          => 'No. ID',
    'col_name'        => 'Nama',
    'col_phone'       => 'Telefon',
    'col_plate'       => 'Plat',
    'col_type'        => 'Jenis',
    'no_results'      => 'Tiada padanan',
    'no_results_sub'  => 'Cuba sebahagian plat atau nama.',
    'results_eyebrow' => 'Hasil Carian',
    'records'         => 'rekod',
    'export'          => 'Eksport Excel',
    'search_again'    => 'Cari lagi',
    'idle_eyebrow'    => 'Sedia untuk carian',
    'idle_title'      => 'Mulakan carian anda',
    'idle_sub'        => 'Maklumat kenderaan akan dipaparkan di sini.',
] : [
    'eyebrow'         => 'Vehicle Search',
    'page_title'      => 'Search vehicles',
    'sub'             => 'Look up by plate, owner name, or ID number.',
    'placeholder'     => 'Enter plate, name, or ID',
    'search_button'   => 'Search',
    'clear'           => 'Clear',
    'col_no'          => 'No.',
    'col_status'      => 'Status',
    'col_id'          => 'ID No.',
    'col_name'        => 'Name',
    'col_phone'       => 'Phone',
    'col_plate'       => 'Plate',
    'col_type'        => 'Type',
    'no_results'      => 'No match',
    'no_results_sub'  => 'Check the plate or try a partial name.',
    'results_eyebrow' => 'Search Results',
    'records'         => 'records',
    'export'          => 'Export Excel',
    'search_again'    => 'Search again',
    'idle_eyebrow'    => 'Ready to search',
    'idle_title'      => 'Start your search',
    'idle_sub'        => 'Vehicle details will appear here.',
];

// Initialize variables
$search = '';
$results = [];
$hasResults = false;
$searched = false;

// Handle search
if (isset($_POST['submit'])) {
    $searched = true;
    $search = trim($_POST['search']);
    if (!empty($search)) {
        $payload = searchVehicleRecords($con, $search);
        $results = $payload['data'];
        $hasResults = count($results) > 0;
    }
}

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<body>
<div class="nv-shell">
<?php $nv_active = 'search'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php'; ?>
<main class="page">
  <div class="page-head">
    <div>
      <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?></span>
      <h1><?= htmlspecialchars($t['page_title']) ?></h1>
      <p class="sub"><?= htmlspecialchars($t['sub']) ?></p>
    </div>
  </div>

  <form class="card nv-stack" method="POST">
    <div class="field">
      <label class="field-label" for="search"><?= htmlspecialchars($t['col_plate']) ?> / <?= htmlspecialchars($t['col_name']) ?> / <?= htmlspecialchars($t['col_id']) ?></label>
      <input class="input mono" id="search" name="search" type="text"
             placeholder="<?= htmlspecialchars($t['placeholder']) ?>"
             value="<?= htmlspecialchars($search) ?>" required autofocus>
    </div>
    <div class="nv-row end gap-2">
      <a class="btn btn-ghost" href="/search/car_admin.php"><?= htmlspecialchars($t['clear']) ?></a>
      <button class="btn btn-primary" type="submit" name="submit"><i data-lucide="search"></i> <?= htmlspecialchars($t['search_button']) ?></button>
    </div>
  </form>

  <?php if ($hasResults): ?>
  <div class="page-head mt-6">
    <div>
      <span class="eyebrow"><?= htmlspecialchars($t['results_eyebrow']) ?></span>
      <h2 class="text-display"><?= count($results) ?> <?= htmlspecialchars($t['records']) ?></h2>
    </div>
    <div class="actions">
      <button class="btn btn-ghost" id="export-btn"><i data-lucide="download"></i> <?= htmlspecialchars($t['export']) ?></button>
      <a class="btn btn-ghost" href="/search/car_admin.php"><i data-lucide="rotate-ccw"></i> <?= htmlspecialchars($t['search_again']) ?></a>
    </div>
  </div>

  <div class="card flat">
    <table class="table" id="vehicleTable">
      <thead>
        <tr>
          <th style="width:50px;"><?= htmlspecialchars($t['col_no']) ?></th>
          <th style="width:120px;"><?= htmlspecialchars($t['col_status']) ?></th>
          <th><?= htmlspecialchars($t['col_id']) ?></th>
          <th><?= htmlspecialchars($t['col_name']) ?></th>
          <th><?= htmlspecialchars($t['col_phone']) ?></th>
          <th><?= htmlspecialchars($t['col_plate']) ?></th>
          <th><?= htmlspecialchars($t['col_type']) ?></th>
        </tr>
      </thead>
      <tbody>
        <?php
        $counter = 1;
        foreach ($results as $row):
            $status_raw = strtolower($row['status'] ?? '');
            $tone = 'info';
            if (in_array($status_raw, ['staf', 'staff']))            { $tone = 'info';    $status_text = ($lang === 'bm' ? 'Staf' : 'Staff'); }
            elseif (in_array($status_raw, ['pelajar', 'student']))   { $tone = 'info';    $status_text = ($lang === 'bm' ? 'Pelajar' : 'Student'); }
            elseif (in_array($status_raw, ['pelawat', 'visitor']))   { $tone = 'warn';    $status_text = ($lang === 'bm' ? 'Pelawat' : 'Visitor'); }
            elseif (in_array($status_raw, ['kontraktor', 'contractor'])) { $tone = 'ok'; $status_text = ($lang === 'bm' ? 'Kontraktor' : 'Contractor'); }
            else { $tone = 'neutral'; $status_text = htmlspecialchars($row['status'] ?? ''); }

        ?>
        <tr>
          <td class="meta"><?= $counter++ ?></td>
          <td><span class="pill <?= $tone ?>"><span class="dot"></span> <?= htmlspecialchars($status_text) ?></span></td>
          <td><?= htmlspecialchars($row['idnumber'] ?? '') ?></td>
          <td><strong><?= htmlspecialchars($row['name'] ?? '') ?></strong></td>
          <td><?= htmlspecialchars($row['phone'] ?? '') ?></td>
          <td><span class="plate"><?= htmlspecialchars($row['platenum'] ?? '') ?></span></td>
          <td><?= htmlspecialchars($row['type'] ?? '') ?></td>
          <td>
            <span class="text-muted">—</span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php elseif ($searched): ?>
    <div class="card flat mt-6 text-center" style="padding:40px;">
      <span class="eyebrow"><?= htmlspecialchars($t['no_results']) ?></span>
      <h3 class="text-display" style="margin-top:8px;"><?= htmlspecialchars($t['no_results']) ?>.</h3>
      <p class="text-muted"><?= htmlspecialchars($t['no_results_sub']) ?></p>
      <div class="nv-row" style="justify-content:center; margin-top:16px;">
        <a class="btn btn-primary" href="/search/car_admin.php"><i data-lucide="rotate-ccw"></i> <?= htmlspecialchars($t['search_again']) ?></a>
      </div>
    </div>
  <?php else: ?>
    <div class="card flat mt-6 text-center" style="padding:40px;">
      <span class="eyebrow"><?= htmlspecialchars($t['idle_eyebrow']) ?></span>
      <h3 class="text-display" style="margin-top:8px;"><?= htmlspecialchars($t['idle_title']) ?></h3>
      <p class="text-muted"><?= htmlspecialchars($t['idle_sub']) ?></p>
    </div>
  <?php endif; ?>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.1/xlsx.full.min.js"></script>
  <script>
  $(function(){
      var table = $('#vehicleTable');
      if (table.length) {
          table.DataTable({
              "pageLength": 10,
              "order": [[0, "asc"]],
              "autoWidth": false,
              "columnDefs": [{ "orderable": false, "targets": 7 }]
          });
      }
      $('#export-btn').on('click', function(){
          var t = $('#vehicleTable');
          if (!t.length) return;
          var clone = t.clone();
          clone.removeClass('dataTable').find('.dataTables_empty').remove();
          var wb = XLSX.utils.table_to_book(clone[0], {sheet: "Vehicle Search"});
          XLSX.writeFile(wb, "vehicle-search-<?= date('Y-m-d') ?>.xlsx");
      });
  });
  </script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
