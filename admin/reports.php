<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

if (!isset($_SESSION['email_Admin'])) {
    header('Location: /auth/login_admin.php');
    exit;
}

$res = mysqli_query($con, "SELECT id, reporter_name, reporter_role, plate_number, owner_name,
        vehicle_type, offense_details, latitude, longitude, photo_paths, created_at
        FROM vehicle_reports ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vehicle Reports | NEO V-TRACK Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
    body { background:#f4f6f9; padding:20px; }
    .card-wrap { background:#fff; border-radius:10px; padding:24px; box-shadow:0 4px 18px rgba(0,0,0,.05); }
    .truncate { max-width: 280px; display:inline-block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; vertical-align:middle; }
</style>
</head>
<body>
<div class="container-fluid">
    <div class="card-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="m-0"><i class="fas fa-flag text-danger me-2"></i>Vehicle Reports</h3>
            <a href="/admin/dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back to Admin</a>
        </div>

        <div class="table-responsive">
            <table id="reportsTable" class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Submitted</th>
                        <th>Plate</th>
                        <th>Reporter</th>
                        <th>Offense</th>
                        <th>Location</th>
                        <th>Photos</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($res && $row = mysqli_fetch_assoc($res)):
                    $photos = json_decode($row['photo_paths'] ?? '[]', true) ?: [];
                    $mapUrl = 'https://www.google.com/maps?q=' . urlencode($row['latitude'] . ',' . $row['longitude']);
                ?>
                    <tr>
                        <td>#<?php echo (int)$row['id']; ?></td>
                        <td><small><?php echo htmlspecialchars(date('d M Y, H:i', strtotime($row['created_at']))); ?></small></td>
                        <td><strong><?php echo htmlspecialchars($row['plate_number']); ?></strong></td>
                        <td>
                            <?php echo htmlspecialchars($row['reporter_name']); ?>
                            <small class="text-muted d-block"><?php echo htmlspecialchars($row['reporter_role']); ?></small>
                        </td>
                        <td><span class="truncate" title="<?php echo htmlspecialchars($row['offense_details']); ?>"><?php echo htmlspecialchars($row['offense_details']); ?></span></td>
                        <td><a href="<?php echo $mapUrl; ?>" target="_blank" rel="noopener"><i class="fas fa-map-marker-alt me-1"></i>Map</a></td>
                        <td><?php echo count($photos); ?> <i class="fas fa-camera text-muted"></i></td>
                        <td><a href="/admin/report_view.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye me-1"></i>View</a></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(function(){ $('#reportsTable').DataTable({ order:[[1,'desc']], pageLength: 25 }); });
</script>
</body>
</html>
