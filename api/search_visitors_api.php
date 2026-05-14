<?php
// search_visitors_api.php
// JSON API for Visitor vehicles
header('Content-Type: application/json; charset=utf-8');

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
include $_SERVER['DOCUMENT_ROOT'].'/includes/search_backend.php';

$search = '';
$showAll = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search = isset($_POST['search']) ? trim($_POST['search']) : '';
    $showAll = isset($_POST['showAll']) && ($_POST['showAll'] === 'true' || $_POST['showAll'] === '1');
} else {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $showAll = isset($_GET['showAll']) && ($_GET['showAll'] === 'true' || $_GET['showAll'] === '1');
}

try {
    $result = searchVehicleRecords($con, $search, 'Pelawat', $showAll);
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => 0,
        'count' => 0,
        'data' => [],
        'message' => 'Server error: ' . $e->getMessage(),
    ]);
}
