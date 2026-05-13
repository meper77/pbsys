<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

include 'connect.php';
include 'inc/search_backend.php';

$input = json_decode(file_get_contents('php://input'), true);

$search = trim($_POST['search'] ?? ($input['search'] ?? ''));
$status = trim($_POST['status'] ?? ($input['status'] ?? ''));
$showAllRaw = $_POST['showAll'] ?? ($input['showAll'] ?? '');
$showAll = filter_var($showAllRaw, FILTER_VALIDATE_BOOL);

$payload = searchVehicleRecords($con, $search, $status, $showAll);

echo json_encode($payload);

mysqli_close($con);
