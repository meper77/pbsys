<?php
// search_api.php
// JSON API wrapper around inc/search_backend.php
header('Content-Type: application/json; charset=utf-8');

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
include $_SERVER['DOCUMENT_ROOT'].'/includes/search_backend.php';

$search = '';
$status = '';
$showAll = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search = isset($_POST['search']) ? trim($_POST['search']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $showAll = isset($_POST['showAll']) && ($_POST['showAll'] === 'true' || $_POST['showAll'] === '1');
} else {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $showAll = isset($_GET['showAll']) && ($_GET['showAll'] === 'true' || $_GET['showAll'] === '1');
}

try {
    $result = searchVehicleRecords($con, $search, $status, $showAll);
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
