<?php
/**
 * General vehicle autosuggest for the unified `owner` table.
 * GET ?q=<term>&by=plate|name|idnumber|phone|any  (default any)
 * -> JSON [{plate,name,idnumber,phone,type,status}, ...]
 *
 * Used by the do-report-style autosuggest on search + register/update pages.
 */
header('Content-Type: application/json');
include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$by   = strtolower($_GET['by'] ?? 'any');
$map  = ['plate' => 'platenum', 'name' => 'name', 'idnumber' => 'idnumber', 'phone' => 'phone'];
$like = '%' . $q . '%';

// Optional category scope (whitelisted) so list-page suggestions stay within their tab.
$catWhitelist = ['Staf', 'Pelajar', 'Pelawat', 'Kontraktor'];
$status = trim($_GET['status'] ?? '');
$catSql = in_array($status, $catWhitelist, true) ? " AND `status` = ? " : '';

if (isset($map[$by])) {
    $col = $map[$by]; // from a fixed whitelist — safe to interpolate
    $stmt = $con->prepare("SELECT platenum,name,idnumber,phone,type,status FROM `owner`
                           WHERE `$col` LIKE ? $catSql ORDER BY platenum ASC LIMIT 15");
    if ($catSql) { $stmt->bind_param('ss', $like, $status); }
    else         { $stmt->bind_param('s', $like); }
} else {
    $stmt = $con->prepare("SELECT platenum,name,idnumber,phone,type,status FROM `owner`
                           WHERE (platenum LIKE ? OR name LIKE ? OR idnumber LIKE ? OR phone LIKE ?) $catSql
                           ORDER BY platenum ASC LIMIT 15");
    if ($catSql) { $stmt->bind_param('sssss', $like, $like, $like, $like, $status); }
    else         { $stmt->bind_param('ssss', $like, $like, $like, $like); }
}

$out = [];
if ($stmt && $stmt->execute()) {
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $out[] = [
            'plate'    => strtoupper($r['platenum']),
            'name'     => $r['name'],
            'idnumber' => $r['idnumber'],
            'phone'    => $r['phone'],
            'type'     => $r['type'],
            'status'   => $r['status'],
        ];
    }
}
echo json_encode($out);
