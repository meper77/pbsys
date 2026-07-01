<?php
// Autocomplete endpoint for vehicle plate lookup.
// GET ?q=<prefix>  →  JSON [{plate, name, idnumber, phone, type, status}, ...]

header('Content-Type: application/json');
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/auth_guard.php';
nv_api_require_login();   // owner rows carry PII (name, IC, phone) — signed-in callers only

$q = trim($_GET['q'] ?? '');
if ($q === '' || strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $con->prepare(
    "SELECT platenum, name, idnumber, phone, type, status
     FROM `owner`
     WHERE platenum LIKE CONCAT('%', ?, '%')
     ORDER BY platenum ASC
     LIMIT 20"
);
$stmt->bind_param('s', $q);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($row = $res->fetch_assoc()) {
    $out[] = [
        'plate'    => $row['platenum'],
        'name'     => $row['name'],
        'idnumber' => $row['idnumber'],
        'phone'    => $row['phone'],
        'type'     => $row['type'],
        'status'   => $row['status'],
    ];
}

echo json_encode($out);
