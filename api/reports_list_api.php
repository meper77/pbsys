<?php
// GET → JSON list of vehicle_reports (admin only).
// Returns: { success: 1, count: N, data: [ {id, ...}, ... ] }

header('Content-Type: application/json');
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

if (empty($_SESSION['email_Admin'])) {
    http_response_code(401);
    echo json_encode(['success' => 0, 'message' => 'Admin authentication required']);
    exit;
}

$limit  = max(1, min(500, (int)($_GET['limit']  ?? 100)));
$offset = max(0, (int)($_GET['offset'] ?? 0));

$stmt = $con->prepare(
    "SELECT id, reporter_name, reporter_role, plate_number, owner_name,
            vehicle_type, vehicle_status, offense_details,
            latitude, longitude, photo_paths, created_at
     FROM `vehicle_reports`
     ORDER BY created_at DESC
     LIMIT ? OFFSET ?"
);
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($r = $res->fetch_assoc()) {
    $r['photo_paths'] = json_decode($r['photo_paths'] ?? '[]', true) ?: [];
    $rows[] = $r;
}

echo json_encode(['success' => 1, 'count' => count($rows), 'data' => $rows]);
