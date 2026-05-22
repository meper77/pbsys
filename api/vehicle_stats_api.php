<?php
// Vehicle count statistics — same source as the web dashboard (`owner` table,
// keyed on `status` column). Public read-only so the Flutter dashboard can
// fetch counts without an established session.
//
// GET  ?action=get_stats         → { success, stats: { staff, student, visitor, contractor, total, total_users } }
// GET  ?action=get_vehicles_by_type&type=staff|student|visitor|contractor[&limit&offset]

header('Content-Type: application/json');
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if ($action === 'get_stats') {
    $statuses = [
        'staff'      => 'Staf',
        'student'    => 'Pelajar',
        'visitor'    => 'Pelawat',
        'contractor' => 'Kontraktor',
    ];

    $stats = [];
    $total = 0;
    $stmt = $con->prepare("SELECT COUNT(*) AS c FROM `owner` WHERE status = ?");
    foreach ($statuses as $key => $val) {
        $stmt->bind_param('s', $val);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $count = (int)($row['c'] ?? 0);
        $stats[$key] = $count;
        $total += $count;
    }
    $stats['total'] = $total;

    $u = mysqli_query($con, "SELECT COUNT(*) AS c FROM `user`");
    $stats['total_users'] = (int)(mysqli_fetch_assoc($u)['c'] ?? 0);

    echo json_encode(['success' => true, 'stats' => $stats]);
    exit;
}

if ($action === 'get_vehicles_by_type') {
    $type   = trim($_GET['type'] ?? '');
    $limit  = max(1, min(500, (int)($_GET['limit'] ?? 50)));
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    $map = ['staff' => 'Staf', 'student' => 'Pelajar', 'visitor' => 'Pelawat', 'contractor' => 'Kontraktor'];
    if (!isset($map[$type])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid type']);
        exit;
    }
    $status = $map[$type];

    $cstmt = $con->prepare("SELECT COUNT(*) AS c FROM `owner` WHERE status = ?");
    $cstmt->bind_param('s', $status);
    $cstmt->execute();
    $total = (int)($cstmt->get_result()->fetch_assoc()['c'] ?? 0);

    $qstmt = $con->prepare(
        "SELECT id, name, phone, idnumber, type, status, brand, platenum, sticker, stickerno
         FROM `owner` WHERE status = ? ORDER BY id DESC LIMIT ? OFFSET ?"
    );
    $qstmt->bind_param('sii', $status, $limit, $offset);
    $qstmt->execute();
    $res = $qstmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) { $rows[] = $r; }

    echo json_encode([
        'success'  => true,
        'type'     => $type,
        'total'    => $total,
        'limit'    => $limit,
        'offset'   => $offset,
        'vehicles' => $rows,
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action']);
