<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

include 'connect.php';

// ==================== GET INPUT ====================
$search  = isset($_POST['search']) ? trim($_POST['search']) : '';
$status  = isset($_POST['status']) ? trim($_POST['status']) : '';
$showAll = isset($_POST['showAll']) ? $_POST['showAll'] : '';

// ==================== BUILD QUERY ====================

if (!empty($search)) {

    $safe = mysqli_real_escape_string($con, $search);

    $sql = "SELECT * FROM owner 
            WHERE platenum LIKE '%$safe%' 
               OR name LIKE '%$safe%' 
               OR idnumber LIKE '%$safe%'";

} elseif (!empty($status)) {

    $safe = mysqli_real_escape_string($con, $status);

    $sql = "SELECT * FROM owner WHERE status='$safe'";

} else {

    // DEFAULT: SHOW ALL
    $sql = "SELECT * FROM owner ORDER BY id DESC";
}

// ==================== EXECUTE QUERY ====================
$result = mysqli_query($con, $sql);

if (!$result) {
    echo json_encode([
        "success" => 0,
        "message" => "Query failed"
    ]);
    exit;
}

// ==================== FETCH RESULTS ====================
$vehicles = [];

while ($row = mysqli_fetch_assoc($result)) {

    $vehicles[] = [
        'id'        => $row['id'],
        'name'      => $row['name'],
        'ownerEmail'=> $row['ownerEmail'],
        'phone'     => $row['phone'],
        'idnumber'  => $row['idnumber'],
        'type'      => $row['type'],
        'status'    => $row['status'],
        'brand'     => $row['brand'],
        'platenum'  => strtoupper($row['platenum']),
        'sticker'   => $row['sticker'],
        'stickerno' => $row['stickerno']
    ];
}

// ==================== RETURN JSON ====================
echo json_encode([
    "success" => 1,
    "count"   => count($vehicles),
    "data"    => $vehicles
]);

mysqli_close($con);