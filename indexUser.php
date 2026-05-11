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

$userName = $_SESSION['nama'] ?? 'Pengguna';

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

// Language texts
$text = [];

// Bahasa Malaysia
$text['bm'] = [
    'title' => 'NEO V-TRACK',
    'subtitle' => 'Sistem Pengurusan & Pemantauan Kenderaan',
    'welcome' => 'Selamat Datang',
    'staff' => 'Kenderaan Staf',
    'student' => 'Kenderaan Pelajar',
    'visitor' => 'Kenderaan Pelawat',
    'contractor' => 'Kenderaan Kontraktor',
    'view' => 'Lihat Maklumat',
    'search' => 'Cari Kenderaan',
    'timeLabel' => 'Masa',
    'dateLabel' => 'Tarikh',
    'footer' => '© Hak Cipta Universiti Teknologi MARA Cawangan Johor - Polis Bantuan | ICT Security',
    'logout_confirm' => 'Adakah anda pasti ingin log keluar?'
];

// English
$text['en'] = [
    'title' => 'NEO V-TRACK',
    'subtitle' => 'Vehicle Management & Monitoring System',
    'welcome' => 'Welcome',
    'staff' => 'Staff Vehicles',
    'student' => 'Student Vehicles',
    'visitor' => 'Visitor Vehicles',
    'contractor' => 'Contractor Vehicles',
    'view' => 'View Details',
    'search' => 'Search Vehicle',
    'timeLabel' => 'Time',
    'dateLabel' => 'Date',
    'footer' => '© Copyright Universiti Teknologi MARA Johor Branch - Auxiliary Police | ICT Security',
    'logout_confirm' => 'Are you sure you want to log out?'
];

$t = $text[$lang];
?>
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<title>NEO V-TRACK | Anjung Pengguna</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
:root{
    --purple-main:#6a1b9a;
    --purple-dark:#4a148c;
}

html, body {
    height: 100%;
}

body{
    background:#f4effa;
    min-height:100vh;
    font-family:'Segoe UI',sans-serif;
    display: flex;
    flex-direction: column;
}

/* ===== HEADER ===== */
.header-bar{
    background:linear-gradient(135deg,var(--purple-dark),var(--purple-main));
    color:#fff;
    padding:15px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-shrink: 0;
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

/* ===== REAL TIME CLOCK & DATE STYLES ===== */
.realtime-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 26px;
    margin-bottom: 26px;
}

@media (max-width: 768px) {
    .realtime-info {
        grid-template-columns: 1fr;
    }
}

.clock-box, .date-box {
    background: #fff;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 12px 25px rgba(0,0,0,0.12);
    border-top: 5px solid var(--purple-main);
    transition: transform 0.3s;
    min-height: 140px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.clock-box:hover, .date-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(106,27,154,0.15);
}

.clock-title, .date-title {
    font-size: 16px;
    color: var(--purple-main);
    font-weight: 700;
    margin-bottom: 15px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

.clock-display {
    font-size: 40px;
    font-weight: 900;
    font-family: 'Courier New', monospace;
    color: var(--purple-dark);
    text-align: center;
    letter-spacing: 1.5px;
}

.date-display {
    font-size: 28px;
    font-weight: 700;
    color: var(--purple-dark);
    text-align: center;
    line-height: 1.4;
}

/* ===== CONTENT ===== */
.dashboard{
    padding:30px;
    max-width:1400px;
    margin:auto;
    flex: 1 0 auto;
    width: 100%;
}

.welcome{
    background:#fff;
    border-left:6px solid var(--purple-main);
    border-radius:10px;
    padding:22px;
    margin-bottom:35px;
    box-shadow:0 8px 20px rgba(0,0,0,.08);
}

/* ===== BIGGER STAT BOXES ===== */
.stats{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
    gap:26px;
}

@media (max-width: 768px) {
    .stats {
        grid-template-columns: 1fr;
    }
}

.stat-card{
    background:#fff;
    border-radius:16px;
    padding:28px;
    box-shadow:0 12px 25px rgba(0,0,0,.12);
    transition:.3s;
    min-height:210px;
    border-top:4px solid var(--purple-main);
}

.stat-card:hover{
    transform:translateY(-6px);
    box-shadow:0 15px 30px rgba(106,27,154,0.15);
}

.stat-icon{
    font-size:38px;
    color:var(--purple-main);
    margin-bottom:6px;
}

.stat-number{
    font-size:44px;
    font-weight:800;
    color:var(--purple-dark);
}

.stat-title{
    font-weight:600;
    font-size:17px;
}

/* ===== FOOTER ===== */
.footer{
    background:var(--purple-dark);
    color:#fff;
    padding:12px;
    text-align:center;
    font-size:14px;
    flex-shrink: 0;
    width: 100%;
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
            <a href="?lang=bm" class="lang-btn <?php echo ($lang == 'bm') ? 'active' : ''; ?>">
                <i class="fas fa-language me-1"></i>BM
            </a>
            <a href="?lang=en" class="lang-btn <?php echo ($lang == 'en') ? 'active' : ''; ?>">
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

<div class="dashboard">

<!-- REAL TIME CLOCK & DATE BOXES -->
<div class="realtime-info">
    <div class="clock-box">
        <div class="clock-title"><?php echo $t['timeLabel']; ?></div>
        <div id="realTimeClock" class="clock-display">00:00:00</div>
    </div>
    
    <div class="date-box">
        <div class="date-title"><?php echo $t['dateLabel']; ?></div>
        <div id="realTimeDate" class="date-display"><?php echo $lang == 'bm' ? 'Hari, 1 Januari 2024' : 'Day, 1 January 2024'; ?></div>
    </div>
</div>

<!-- WELCOME -->
<div class="welcome">
    <h4><?php echo $t['welcome'] . ', ' . htmlspecialchars($userName); ?></h4>
    <p class="text-muted mb-0">
        <?php
        date_default_timezone_set('Asia/Kuala_Lumpur');
        if ($lang == 'bm') {
            $hari=['Ahad','Isnin','Selasa','Rabu','Khamis','Jumaat','Sabtu'];
            $bulan=['Januari','Februari','Mac','April','Mei','Jun','Julai','Ogos','September','Oktober','November','Disember'];
        } else {
            $hari=['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            $bulan=['January','February','March','April','May','June','July','August','September','October','November','December'];
        }
        echo $hari[date('w')].', '.date('j').' '.$bulan[date('n')-1].' '.date('Y');
        ?>
    </p>
</div>

<!-- STATS -->
<div class="stats">
<?php
$dataStatus=[
    ['Staf','fas fa-user-tie','staff'],
    ['Pelajar','fas fa-user-graduate','student'],
    ['Pelawat','fas fa-users','visitor'],
    ['Kontraktor','fas fa-hard-hat','contractor']
];

foreach($dataStatus as $d){
    $q=mysqli_query($con,"SELECT * FROM owner WHERE status='$d[0]'");
    $count=mysqli_num_rows($q);
?>
<div class="stat-card">
    <div class="stat-icon"><i class="<?php echo $d[1]; ?>"></i></div>
    <div class="stat-number"><?php echo $count; ?></div>
    <div class="stat-title">
        <?php echo $t[$d[2]]; ?>
    </div>
    <a href="searchCarUser.php?status=<?php echo urlencode($d[0]); ?>" 
       class="btn btn-sm btn-outline-secondary mt-3">
       <?php echo $t['view']; ?>
    </a>
</div>
<?php } ?>
</div>

<div class="mt-4 text-end">
    <a href="searchCarUser.php" class="btn btn-primary">
        <i class="fas fa-search me-1"></i> <?php echo $t['search']; ?>
    </a>
</div>

</div>

<div class="footer">
    <?php echo $t['footer']; ?>
</div>

<script>
// REAL-TIME DIGITAL CLOCK FUNCTION
function updateRealTimeClock() {
    const now = new Date();
    
    // Time in 24-hour format
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    // Day names in Malay and English
    const daysBM = ['Ahad', 'Isnin', 'Selasa', 'Rabu', 'Khamis', 'Jumaat', 'Sabtu'];
    const daysEN = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    
    // Month names in Malay and English
    const monthsBM = ['Januari', 'Februari', 'Mac', 'April', 'Mei', 'Jun', 'Julai', 'Ogos', 'September', 'Oktober', 'November', 'Disember'];
    const monthsEN = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    
    // Get current language from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const currentLang = urlParams.get('lang') || '<?php echo $lang; ?>';
    
    // Update time display
    document.getElementById('realTimeClock').textContent = `${hours}:${minutes}:${seconds}`;
    
    // Update date display based on language
    if (currentLang === 'bm') {
        const dayName = daysBM[now.getDay()];
        const monthName = monthsBM[now.getMonth()];
        const date = now.getDate();
        const year = now.getFullYear();
        document.getElementById('realTimeDate').textContent = `${dayName}, ${date} ${monthName} ${year}`;
    } else {
        const dayName = daysEN[now.getDay()];
        const monthName = monthsEN[now.getMonth()];
        const date = now.getDate();
        const year = now.getFullYear();
        document.getElementById('realTimeDate').textContent = `${dayName}, ${date} ${monthName} ${year}`;
    }
}

// Initialize clock and update every second
updateRealTimeClock();
setInterval(updateRealTimeClock, 1000);
</script>

</body>
</html>

<?php mysqli_close($con); ?>