<?php
// Autocomplete endpoint for vehicle plate lookup.
// GET ?q=<prefix>  →  JSON [{plate, name, idnumber, phone, type, status, sticker}, ...]

header('Content-Type: application/json');
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

$q = trim($_GET['q'] ?? '');
if ($q === '' || strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $con->prepare(
    "SELECT platenum, name, idnumber, phone, type, status, sticker
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
        'sticker'  => $row['sticker'],
    ];
}

echo json_encode($out);
