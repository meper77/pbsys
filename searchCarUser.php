<?php
session_start();

// ========== LOGOUT HANDLER ==========
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: roleSelection.php');
    exit();
}
// ========== END LOGOUT HANDLER ==========

if (!isset($_SESSION['email'])) {
    header('location:login.php');
    exit();
}

include('inc/header.php');
include 'connect.php';
include 'inc/search_backend.php';

$userName = $_SESSION['nama'] ?? 'Pengguna';

// LANGUAGE SYSTEM
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'bm';
}

if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] == 'en') ? 'en' : 'bm';
    // Preserve other GET parameters
    $queryParams = $_GET;
    unset($queryParams['lang']);
    $redirectUrl = $_SERVER['PHP_SELF'];
    if (!empty($queryParams)) {
        $redirectUrl .= '?' . http_build_query($queryParams);
    }
    header('Location: ' . $redirectUrl);
    exit();
}

$lang = $_SESSION['language'];

// Language texts
$text = [];

// Bahasa Malaysia
$text['bm'] = [
    // Header
    'title' => 'NEO V-TRACK',
    'subtitle' => 'Sistem Pengurusan & Pemantauan Kenderaan',

    // Back Button
    'backBtn' => 'Kembali ke Anjung',

    // Search Hero
    'searchTitle' => 'Cari Kenderaan',
    'searchSubtitle' => 'Cari kenderaan berdaftar mengikut nombor plat, nama pemilik atau nombor id',

    // Search Box
    'searchBtn' => 'Cari Sekarang',
    'quickFilters' => 'Carian Pantas:',
    'filterStaff' => 'Kenderaan Staf',
    'filterStudent' => 'Kenderaan Pelajar',
    'filterVisitor' => 'Kenderaan Pelawat',
    'filterContractor' => 'Kenderaan Kontraktor',
    'filterAll' => 'Semua Kenderaan',

    // Results
    'resultsTitle' => 'Hasil Carian',

    // Table Columns
    'colNo' => '#',
    'colStatus' => 'Status',
    'colID' => 'No. ID',
    'colName' => 'Nama Pemilik',
    'colPhone' => 'No. Telefon',
    'colPlate' => 'No. Plat Kenderaan',
    'colType' => 'Jenis Kenderaan',

    // Buttons
    'exportBtn' => 'Eksport ke Excel',
    'resetBtn' => 'Reset Carian',
    'resetBtn2' => 'Reset Carian',

    // No Results
    'noResultsTitle' => 'Tiada Rekod Ditemui',
    'noResultsText' => 'Tiada rekod yang sepadan dengan carian anda',

    // Start Search
    'startSearchTitle' => 'Mulakan Carian Anda',
    'startSearchText' => 'Gunakan borang di atas untuk mencari kenderaan berdaftar',
    'searchByPlate' => 'Cari dengan No. Plat',
    'searchByName' => 'Cari dengan Nama',
    'searchByID' => 'Cari dengan No. ID',
    'viewAll' => 'Lihat Semua',

    // Logout
    'logout_confirm' => 'Adakah anda pasti ingin log keluar?',

    // Filter Results Text
    'filtered_by_keyword' => 'Kata kunci:',
    'filtered_by_status' => 'Status:',
    'filtered_all' => 'Semua Kenderaan',
    'records_found' => 'ditemui',
    'records_matching' => 'rekod yang sepadan',
    'records_with_status' => 'rekod dengan status',
    'all_records' => 'semua rekod'
];

// English
$text['en'] = [
    // Header
    'title' => 'NEO V-TRACK',
    'subtitle' => 'Vehicle Management & Monitoring System',

    // Back Button
    'backBtn' => 'Back to Dashboard',

    // Search Hero
    'searchTitle' => 'Search Vehicle',
    'searchSubtitle' => 'Search registered vehicles by plate number, owner name or id number',

    // Search Box
    'searchBtn' => 'Search Now',
    'quickFilters' => 'Quick Filters:',
    'filterStaff' => 'Staff Vehicles',
    'filterStudent' => 'Student Vehicles',
    'filterVisitor' => 'Visitor Vehicles',
    'filterContractor' => 'Contractor Vehicles',
    'filterAll' => 'All Vehicles',

    // Results
    'resultsTitle' => 'Search Results',

    // Table Columns
    'colNo' => '#',
    'colStatus' => 'Status',
    'colID' => 'ID Number',
    'colName' => 'Owner Name',
    'colPhone' => 'Phone Number',
    'colPlate' => 'Vehicle Plate',
    'colType' => 'Vehicle Type',

    // Buttons
    'exportBtn' => 'Export to Excel',
    'resetBtn' => 'Reset Search',
    'resetBtn2' => 'Reset Search',

    // No Results
    'noResultsTitle' => 'No Records Found',
    'noResultsText' => 'No records match your search',

    // Start Search
    'startSearchTitle' => 'Start Your Search',
    'startSearchText' => 'Use the form above to search for registered vehicles',
    'searchByPlate' => 'Search by Plate',
    'searchByName' => 'Search by Name',
    'searchByID' => 'Search by ID',
    'viewAll' => 'View All',

    // Logout
    'logout_confirm' => 'Are you sure you want to log out?',

    // Filter Results Text
    'filtered_by_keyword' => 'Keyword:',
    'filtered_by_status' => 'Status:',
    'filtered_all' => 'All Vehicles',
    'records_found' => 'found',
    'records_matching' => 'matching records',
    'records_with_status' => 'records with status',
    'all_records' => 'all records'
];

$t = $text[$lang];

$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$showAll = isset($_GET['showAll']) && $_GET['showAll'] == 'true';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>NEO V-TRACK | <?php echo $t['searchTitle']; ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="css/dataTables.bootstrap.min.css" />

<style>
:root{
    --purple-main:#6a1b9a;
    --purple-dark:#4a148c;
    --purple-light:#9c4dcc;
    --purple-bg:#f4effa;
}

/* ===== FOOTER FIX ===== */
html, body {
    height: 100%;
}

body {
    background: var(--purple-bg);
    min-height: 100vh;
    font-family:'Segoe UI',sans-serif;
    display: flex;
    flex-direction: column;
}

.dashboard {
    flex: 1;
    padding: 30px;
    max-width: 1400px;
    margin: auto;
    width: 100%;
}

/* ===== HEADER ===== */
.header-bar{
    background:linear-gradient(135deg,var(--purple-dark),var(--purple-main));
    color:#fff;
    padding:15px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.header-left{
    display:flex;
    align-items:center;
    gap:20px;
}

.logo-container{
    display:flex;
    align-items:center;
    gap:15px;
}

.uitm-logo, .neo-logo{
    height:45px;
    width:auto;
    object-fit:contain;
}

.uitm-logo{
    border-right:2px solid rgba(255,255,255,0.3);
    padding-right:15px;
}

.header-title{
    border-left:2px solid rgba(255,255,255,0.3);
    padding-left:15px;
}

.header-title h4{
    margin:0;
    font-weight:700;
}

.header-title p{
    margin:0;
    font-size:13px;
    opacity:.9;
}

.user-box{
    display:flex;
    align-items:center;
    gap:12px;
}

.user-avatar{
    width:42px;
    height:42px;
    background:#fff;
    color:var(--purple-main);
    border-radius:50%;
    font-weight:bold;
    display:flex;
    align-items:center;
    justify-content:center;
}

.lang-btn-group {
    display: flex;
    align-items: center;
    gap: 5px;
    background: rgba(255,255,255,0.15);
    padding: 3px;
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.2);
}

.lang-btn {
    background: transparent;
    border: none;
    color: white;
    padding: 5px 15px;
    border-radius: 15px;
    font-size: 13px;
    text-decoration: none;
    transition: all 0.3s;
    cursor: pointer;
}

.lang-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    color: white;
    text-decoration: none;
}

.lang-btn.active {
    background: rgba(255, 255, 255, 0.3);
    font-weight: 600;
}

/* ===== BACK BUTTON ===== */
.back-button-container {
    margin-bottom: 25px;
}

.back-btn {
    background: var(--purple-main);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    transition: all 0.3s;
}

.back-btn:hover {
    background: var(--purple-dark);
    color: white;
    text-decoration: none;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(106, 27, 154, 0.2);
}

/* ===== SEARCH HERO SECTION ===== */
.search-hero{
    background:linear-gradient(135deg,var(--purple-main),var(--purple-light));
    border-radius:16px;
    padding:35px;
    color:white;
    margin-bottom:30px;
    box-shadow:0 10px 25px rgba(106,27,154,0.3);
}

.search-title{
    font-size:28px;
    font-weight:700;
    margin-bottom:10px;
}

.search-subtitle{
    font-size:16px;
    opacity:0.9;
    margin-bottom:25px;
}

/* ===== SEARCH BOX ===== */
.search-container{
    background:white;
    border-radius:12px;
    padding:25px;
    box-shadow:0 8px 20px rgba(0,0,0,0.1);
    margin-bottom:30px;
}

.search-form{
    display:flex;
    gap:15px;
    align-items:center;
}

.search-input{
    flex:1;
    padding:12px 20px;
    border:2px solid #e0d6e9;
    border-radius:10px;
    font-size:16px;
    transition:all 0.3s;
}

.search-input:focus{
    border-color:var(--purple-main);
    box-shadow:0 0 0 3px rgba(106,27,154,0.2);
    outline:none;
}

.search-btn{
    background:var(--purple-main);
    color:white;
    border:none;
    padding:12px 25px;
    border-radius:10px;
    font-weight:600;
    font-size:16px;
    cursor:pointer;
    transition:all 0.3s;
    display:flex;
    align-items:center;
    gap:8px;
}

.search-btn:hover{
    background:var(--purple-dark);
    transform:translateY(-2px);
}

.quick-filters{
    margin-top:20px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.filter-btn{
    background:#f8f5fc;
    border:1px solid #e0d6e9;
    border-radius:20px;
    padding:8px 16px;
    font-size:14px;
    color:#555;
    text-decoration:none;
    transition:all 0.3s;
}

.filter-btn:hover{
    background:var(--purple-main);
    color:white;
    border-color:var(--purple-main);
    text-decoration:none;
}

.filter-btn.active{
    background:var(--purple-main);
    color:white;
    border-color:var(--purple-main);
}

/* ===== RESULTS SECTION ===== */
.results-section{
    background:white;
    border-radius:12px;
    padding:25px;
    box-shadow:0 8px 20px rgba(0,0,0,0.1);
}

.results-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
    padding-bottom:15px;
    border-bottom:2px solid #f0f0f0;
}

.results-title{
    color:var(--purple-dark);
    font-weight:700;
    font-size:20px;
    margin:0;
}

.results-count{
    background:var(--purple-bg);
    color:var(--purple-dark);
    padding:6px 15px;
    border-radius:20px;
    font-weight:600;
    font-size:14px;
}

/* ===== TABLE STYLES ===== */
.vehicle-table{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
}

.vehicle-table th{
    background:var(--purple-main);
    color:white;
    padding:16px;
    text-align:left;
    font-weight:600;
    font-size:14px;
}

.vehicle-table td{
    padding:16px;
    border-bottom:1px solid #f0f0f0;
    vertical-align:middle;
}

.vehicle-table tr:hover{
    background:#faf6ff;
}

.status-badge{
    padding:6px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
    display:inline-block;
}

.status-staf{
    background:#e3f2fd;
    color:#1565c0;
}

.status-pelajar{
    background:#e8f5e9;
    color:#2e7d32;
}

.status-pelawat{
    background:#fff3e0;
    color:#ef6c00;
}

.status-kontraktor{
    background:#f3e5f5;
    color:#7b1fa2;
}

/* ===== NO RESULTS ===== */
.no-results{
    text-align:center;
    padding:50px 20px;
    color:#666;
}

.no-results-icon{
    font-size:60px;
    color:#ddd;
    margin-bottom:20px;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px){
    .search-form{
        flex-direction:column;
    }

    .search-input, .search-btn{
        width:100%;
    }

    .vehicle-table{
        display:block;
        overflow-x:auto;
    }
}
</style>
</head>

<body>

<!-- HEADER -->
<div class="header-bar">
    <div class="header-left">
        <div class="logo-container">
            <!-- UiTM Logo -->
            <img src="inc/images/uitm.png" alt="UiTM Logo" class="uitm-logo">
            <!-- NEO V-TRACK Logo -->
            <img src="inc/images/kik2.png" alt="NEO V-TRACK Logo" class="neo-logo">
        </div>
        <div class="header-title">
            <h4><?php echo $t['title']; ?></h4>
            <p><?php echo $t['subtitle']; ?></p>
        </div>
    </div>

    <div class="user-box">
        <!-- Language Switcher - Using Links Like Admin Pages -->
        <div class="lang-btn-group">
            <?php
            // Preserve current GET parameters when switching language
            $queryParams = $_GET;
            $bmParams = $queryParams;
            $bmParams['lang'] = 'bm';
            $enParams = $queryParams;
            $enParams['lang'] = 'en';
            ?>
            <a href="?<?php echo http_build_query($bmParams); ?>" class="lang-btn <?php echo ($lang == 'bm') ? 'active' : ''; ?>">
                <i class="fas fa-language me-1"></i>BM
            </a>
            <a href="?<?php echo http_build_query($enParams); ?>" class="lang-btn <?php echo ($lang == 'en') ? 'active' : ''; ?>">
                <i class="fas fa-language me-1"></i>EN
            </a>
        </div>

        <div class="user-avatar">
            <?php echo strtoupper(substr($userName,0,1)); ?>
        </div>
        <div>
            <div><?php echo htmlspecialchars($userName); ?></div>
            <small><?php echo $_SESSION['email']; ?></small>
        </div>
        <!-- UPDATED LOGOUT BUTTON -->
        <a href="?logout=1" class="btn btn-sm btn-light" onclick="return confirm('<?php echo $t['logout_confirm']; ?>')">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</div>

<!-- BACK TO ANJUNG BUTTON -->
<div class="dashboard">
    <div class="back-button-container">
        <a href="indexUser.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> <?php echo $t['backBtn']; ?>
        </a>
    </div>

<!-- SEARCH HERO -->
<div class="search-hero">
    <div class="search-title">
        <i class="fas fa-search me-2"></i><?php echo $t['searchTitle']; ?>
    </div>
    <div class="search-subtitle">
        <?php echo $t['searchSubtitle']; ?>
    </div>
</div>

<!-- SEARCH BOX -->
<div class="search-container">
    <form method="POST" action="">
        <div class="search-form">
            <input type="text"
                   class="search-input"
                   name="search"
                   placeholder="<?php echo $lang == 'bm' ? 'Masukkan No. Plat Kenderaan (contoh: ABC1234), Nama Pemilik Atau Nombor ID (contoh: Pelajar - 202*******)' : 'Enter Vehicle Plate Number (e.g.: ABC1234), Owner Name Or ID Number (e.g.: Student - 202*******)'; ?>"
                   value="<?php echo htmlspecialchars($search); ?>"
                   required>
            <button type="submit" name="submit" class="search-btn">
                <i class="fas fa-search"></i><?php echo $t['searchBtn']; ?>
            </button>
        </div>
    </form>

    <div class="quick-filters">
        <span style="color:#666; font-size:14px; margin-right:10px;"><?php echo $t['quickFilters']; ?></span>
        <a href="searchCarUser.php?status=Staf<?php echo $lang != 'bm' ? '&lang=en' : ''; ?>" class="filter-btn <?php echo ($status == 'Staf') ? 'active' : ''; ?>"><?php echo $t['filterStaff']; ?></a>
        <a href="searchCarUser.php?status=Pelajar<?php echo $lang != 'bm' ? '&lang=en' : ''; ?>" class="filter-btn <?php echo ($status == 'Pelajar') ? 'active' : ''; ?>"><?php echo $t['filterStudent']; ?></a>
        <a href="searchCarUser.php?status=Pelawat<?php echo $lang != 'bm' ? '&lang=en' : ''; ?>" class="filter-btn <?php echo ($status == 'Pelawat') ? 'active' : ''; ?>"><?php echo $t['filterVisitor']; ?></a>
        <a href="searchCarUser.php?status=Kontraktor<?php echo $lang != 'bm' ? '&lang=en' : ''; ?>" class="filter-btn <?php echo ($status == 'Kontraktor') ? 'active' : ''; ?>"><?php echo $t['filterContractor']; ?></a>
        <a href="searchCarUser.php?showAll=true<?php echo $lang != 'bm' ? '&lang=en' : ''; ?>" class="filter-btn <?php echo $showAll ? 'active' : ''; ?>"><?php echo $t['filterAll']; ?></a>
    </div>
</div>

<!-- RESULTS SECTION -->
<div class="results-section">
    <div class="results-header">
        <h2 class="results-title"><?php echo $t['resultsTitle']; ?></h2>
        <?php
        $hasResults = false;
        $count = 0;
        $countText = '';
        $payload = ['data' => []];

        if (isset($_POST['submit']) && !empty($search)) {
            $payload = searchVehicleRecords($con, $search);
            $countText = $t['records_matching'];
            $hasResults = true;
        } elseif (!empty($status)) {
            $payload = searchVehicleRecords($con, '', $status, false);
            $countText = $t['records_with_status'] . " $status";
            $hasResults = true;
        } elseif ($showAll) {
            $payload = searchVehicleRecords($con, '', '', true);
            $countText = $t['all_records'];
            $hasResults = true;
        }

        $results = $payload['data'];
        $count = count($results);

        if ($hasResults) {
            echo '<div class="results-count">' . $count . ' ' . $countText . ' ' . $t['records_found'] . '</div>';
        }
        ?>
    </div>

    <?php
    if ($hasResults) {
        if ($count > 0) {
    ?>
    <div class="table-responsive">
        <table class="vehicle-table" id="vehicleTable">
            <thead>
                <tr>
                    <th><?php echo $t['colNo']; ?></th>
                    <th><?php echo $t['colStatus']; ?></th>
                    <th><?php echo $t['colID']; ?></th>
                    <th><?php echo $t['colName']; ?></th>
                    <th><?php echo $t['colPhone']; ?></th>
                    <th><?php echo $t['colPlate']; ?></th>
                    <th><?php echo $t['colType']; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                foreach ($results as $row) {
                    $statusClass = strtolower(str_replace(' ', '-', $row['status']));
                ?>
                <tr>
                    <td><?php echo $no; ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $statusClass; ?>">
                            <?php echo $row['status']; ?>
                        </span>
                    </td>
                    <td><strong><?php echo $row['idnumber']; ?></strong></td>
                    <td>
                        <div style="font-weight:600;"><?php echo $row['name']; ?></div>
                        <small class="text-muted">ID: <?php echo $row['id']; ?></small>
                    </td>
                    <td><?php echo $row['phone']; ?></td>
                    <td>
                        <span style="font-family:monospace; font-weight:bold; font-size:16px;">
                            <?php echo strtoupper($row['platenum']); ?>
                        </span>
                    </td>
                    <td><?php echo $row['type']; ?></td>
                </tr>
                <?php
                $no++;
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- EXPORT BUTTON -->
    <div class="text-end mt-4">
        <button class="btn btn-outline-purple" onclick="exportToExcel()">
            <i class="fas fa-file-excel me-2"></i><?php echo $t['exportBtn']; ?>
        </button>
        <a href="searchCarUser.php<?php echo $lang != 'bm' ? '?lang=en' : ''; ?>" class="btn btn-purple ms-2">
            <i class="fas fa-redo me-1"></i> <?php echo $t['resetBtn']; ?>
        </a>
    </div>

    <?php
        } else {
    ?>
    <div class="no-results">
        <div class="no-results-icon">
            <i class="fas fa-search-minus"></i>
        </div>
        <h4 style="color:#666; margin-bottom:10px;"><?php echo $t['noResultsTitle']; ?></h4>
        <p style="color:#888;"><?php echo $t['noResultsText']; ?></p>
        <a href="searchCarUser.php<?php echo $lang != 'bm' ? '?lang=en' : ''; ?>" class="btn btn-purple mt-3">
            <i class="fas fa-redo me-2"></i><?php echo $t['resetBtn2']; ?>
        </a>
    </div>
    <?php
        }
    } else {
    ?>
    <div class="no-results">
        <div class="no-results-icon">
            <i class="fas fa-search"></i>
        </div>
        <h4 style="color:#666; margin-bottom:10px;"><?php echo $t['startSearchTitle']; ?></h4>
        <p style="color:#888;"><?php echo $t['startSearchText']; ?></p>
        <div class="mt-4">
            <div class="row text-center">
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded">
                        <i class="fas fa-car fa-2x mb-2" style="color:var(--purple-main);"></i>
                        <div><?php echo $t['searchByPlate']; ?></div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded">
                        <i class="fas fa-user fa-2x mb-2" style="color:var(--purple-main);"></i>
                        <div><?php echo $t['searchByName']; ?></div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded">
                        <i class="fas fa-id-card fa-2x mb-2" style="color:var(--purple-main);"></i>
                        <div><?php echo $t['searchByID']; ?></div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="p-3 border rounded">
                        <i class="fas fa-list fa-2x mb-2" style="color:var(--purple-main);"></i>
                        <div><?php echo $t['viewAll']; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
</div>

</div> <!-- End .dashboard -->

<!-- INCLUDE YOUR FOOTER -->
<?php include('inc/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/jquery.dataTables.min.js"></script>
<script src="js/dataTables.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.1/xlsx.full.min.js"></script>

<script>
// Initialize DataTable with current language
$(document).ready(function() {
    const currentLang = '<?php echo $lang; ?>';

    if ($('#vehicleTable').length) {
        $('#vehicleTable').DataTable({
            "language": {
                "search": currentLang === 'bm' ? "Cari dalam jadual:" : "Search in table:",
                "lengthMenu": currentLang === 'bm' ? "Papar _MENU_ rekod setiap halaman" : "Show _MENU_ entries per page",
                "zeroRecords": currentLang === 'bm' ? "Tiada data yang sepadan" : "No matching records found",
                "info": currentLang === 'bm' ? "Papar halaman _PAGE_ dari _PAGES_" : "Showing page _PAGE_ of _PAGES_",
                "infoEmpty": currentLang === 'bm' ? "Tiada rekod" : "No records available",
                "infoFiltered": currentLang === 'bm' ? "(disaring dari _MAX_ jumlah rekod)" : "(filtered from _MAX_ total records)",
                "paginate": {
                    "first": currentLang === 'bm' ? "Pertama" : "First",
                    "last": currentLang === 'bm' ? "Terakhir" : "Last",
                    "next": currentLang === 'bm' ? "Seterusnya" : "Next",
                    "previous": currentLang === 'bm' ? "Sebelumnya" : "Previous"
                }
            },
            "pageLength": 10,
            "order": [[0, "asc"]]
        });
    }
});

// Export to Excel function
function exportToExcel() {
    var wb = XLSX.utils.table_to_book(document.getElementById('vehicleTable'));
    XLSX.writeFile(wb, "carian-kenderaan.xlsx");
}

// Add button styles
document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = `
        .btn-purple {
            background: var(--purple-main);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-purple:hover {
            background: var(--purple-dark);
            color: white;
        }
        .btn-outline-purple {
            color: var(--purple-main);
            border: 1px solid var(--purple-main);
            background: white;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
        }
        .btn-outline-purple:hover {
            background: var(--purple-main);
            color: white;
        }
    `;
    document.head.appendChild(style);
});
</script>

</body>
</html>

<?php
mysqli_close($con);
?>
