<?php
// GET ?id=N → JSON for a single vehicle_report (admin only).

header('Content-Type: application/json');
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

if (empty($_SESSION['email_Admin'])) {
    http_response_code(401);
    echo json_encode(['success' => 0, 'message' => 'Admin authentication required']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => 0, 'message' => 'Invalid id']);
    exit;
}

$stmt = $con->prepare("SELECT * FROM `vehicle_reports` WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => 0, 'message' => 'Not found']);
    exit;
}

$row['photo_paths'] = json_decode($row['photo_paths'] ?? '[]', true) ?: [];
echo json_encode(['success' => 1, 'data' => $row]);
