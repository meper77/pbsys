<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

if (!isset($_SESSION['email_Admin'])) {
    header('Location: /auth/login_admin.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = mysqli_prepare($con, "SELECT * FROM vehicle_reports WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$report = mysqli_fetch_assoc($result);

if (!$report) {
    echo '<div style="padding:40px;text-align:center;font-family:sans-serif">Report not found. <a href="/admin/reports.php">Back to list</a></div>';
    exit;
}

$photos = json_decode($report['photo_paths'] ?? '[]', true) ?: [];
$mapEmbed = 'https://maps.google.com/maps?q=' . urlencode($report['latitude'] . ',' . $report['longitude']) . '&z=17&output=embed';
$mapLink  = 'https://www.google.com/maps?q=' . urlencode($report['latitude'] . ',' . $report['longitude']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Report #<?php echo (int)$report['id']; ?> | NEO V-TRACK Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body { background:#f4f6f9; padding:20px; }
    .card-wrap { background:#fff; border-radius:10px; padding:28px; box-shadow:0 4px 18px rgba(0,0,0,.05); max-width:1100px; margin:0 auto; }
    .meta-grid { display:grid; grid-template-columns: 1fr 1fr; gap:12px 24px; margin:16px 0; }
    .meta-grid .label { color:#666; font-size:13px; }
    .meta-grid .val { font-weight:500; }
    .gallery img { width:180px; height:180px; object-fit:cover; border-radius:8px; margin:6px; border:1px solid #ddd; cursor:pointer; }
    iframe.map { width:100%; height:380px; border:0; border-radius:8px; }
</style>
</head>
<body>
<div class="card-wrap">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="m-0"><i class="fas fa-file-alt me-2"></i>Report #<?php echo (int)$report['id']; ?></h3>
        <a href="/admin/reports.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back to list</a>
    </div>

    <div class="meta-grid">
        <div><div class="label">Submitted</div><div class="val"><?php echo htmlspecialchars(date('d M Y, H:i', strtotime($report['created_at']))); ?></div></div>
        <div><div class="label">Plate Number</div><div class="val"><?php echo htmlspecialchars($report['plate_number']); ?></div></div>
        <div><div class="label">Reporter</div><div class="val"><?php echo htmlspecialchars($report['reporter_name']); ?> <small class="text-muted">(<?php echo htmlspecialchars($report['reporter_role']); ?>)</small></div></div>
        <div><div class="label">Reporter Email</div><div class="val"><?php echo htmlspecialchars($report['reporter_email'] ?: '-'); ?></div></div>
        <div><div class="label">Vehicle Type</div><div class="val"><?php echo htmlspecialchars($report['vehicle_type'] ?: '-'); ?></div></div>
        <div><div class="label">Sticker</div><div class="val"><?php echo htmlspecialchars($report['sticker'] ?: '-'); ?></div></div>
        <div><div class="label">Owner Name</div><div class="val"><?php echo htmlspecialchars($report['owner_name'] ?: '-'); ?></div></div>
        <div><div class="label">Phone</div><div class="val"><?php echo htmlspecialchars($report['phone'] ?: '-'); ?></div></div>
    </div>

    <h5 class="mt-4">Offense Details</h5>
    <p class="border rounded p-3 bg-light"><?php echo nl2br(htmlspecialchars($report['offense_details'])); ?></p>

    <h5 class="mt-4">Location</h5>
    <p><a href="<?php echo $mapLink; ?>" target="_blank" rel="noopener"><i class="fas fa-external-link-alt me-1"></i><?php echo htmlspecialchars($report['latitude'] . ', ' . $report['longitude']); ?></a></p>
    <iframe class="map" src="<?php echo $mapEmbed; ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>

    <h5 class="mt-4">Photos (<?php echo count($photos); ?>)</h5>
    <div class="gallery">
        <?php if (empty($photos)): ?>
            <p class="text-muted">No photos attached.</p>
        <?php else: foreach ($photos as $p):
            $src = htmlspecialchars($p);
        ?>
            <a href="<?php echo $src; ?>" target="_blank" rel="noopener"><img src="<?php echo $src; ?>" alt="Report photo"></a>
        <?php endforeach; endif; ?>
    </div>
</div>
</body>
</html>
