<?php
/**
 * API: Bulk Delete Vehicles
 * POST /api/bulk_delete_api.php
 *
 * Parameters:
 *   - action=bulk_delete            (required)
 *   - ids[]=1,2,3                   (required - array of owner.id values)
 *   - vehicle_type=staff|...        (optional - used only for the audit message)
 *
 * NOTE: All live vehicle records live in the unified `owner` table, keyed by `id`.
 * The legacy per-type *car tables are not used by the current UI, so deletion is
 * performed against `owner` directly.
 */

header('Content-Type: application/json');

session_start();

include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';

// Require admin session
if (!isset($_SESSION['email_Admin'])) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Admin access required']));
}

$action = $_POST['action'] ?? null;
$ids    = $_POST['ids'] ?? [];

if ($action !== 'bulk_delete') {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid action']));
}

if (!is_array($ids) || empty($ids)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'No IDs provided']));
}

// Sanitize IDs to positive integers
$safe_ids = array_values(array_filter(array_map('intval', $ids), function ($n) {
    return $n > 0;
}));

if (empty($safe_ids)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'No valid IDs provided']));
}

$id_list = implode(',', $safe_ids);

$con->begin_transaction();
try {
    // Clean up M:M links if the junction table exists (defensive).
    $has_uv = $con->query("SHOW TABLES LIKE 'user_vehicle'");
    if ($has_uv && $has_uv->num_rows > 0) {
        $con->query("DELETE FROM user_vehicle WHERE vehicle_id IN ($id_list)");
    }

    $con->query("DELETE FROM `owner` WHERE `id` IN ($id_list)");
    $count = $con->affected_rows;

    $con->commit();

    echo json_encode([
        'success' => true,
        'message' => "Deleted $count record(s)",
        'count'   => $count,
    ]);
} catch (Throwable $e) {
    $con->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
