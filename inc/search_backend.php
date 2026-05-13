<?php

function searchVehicleRecords(mysqli $con, string $search = '', string $status = '', bool $showAll = false): array
{
    $search = trim($search);
    $status = trim($status);

    if ($search !== '') {
        $like = '%' . $search . '%';
        $stmt = $con->prepare(
            "SELECT * FROM owner
             WHERE platenum LIKE ? OR name LIKE ? OR idnumber LIKE ?
             ORDER BY id DESC"
        );
        if (!$stmt) {
            return [
                'success' => 0,
                'count' => 0,
                'data' => [],
                'message' => 'Query failed',
            ];
        }

        $stmt->bind_param('sss', $like, $like, $like);
    } elseif ($status !== '') {
        $stmt = $con->prepare(
            "SELECT * FROM owner
             WHERE status = ?
             ORDER BY id DESC"
        );
        if (!$stmt) {
            return [
                'success' => 0,
                'count' => 0,
                'data' => [],
                'message' => 'Query failed',
            ];
        }

        $stmt->bind_param('s', $status);
    } else {
        $sql = $showAll
            ? "SELECT * FROM owner ORDER BY id DESC"
            : "SELECT * FROM owner ORDER BY id DESC";
        $result = $con->query($sql);
        if (!$result) {
            return [
                'success' => 0,
                'count' => 0,
                'data' => [],
                'message' => 'Query failed',
            ];
        }

        $vehicles = [];
        while ($row = $result->fetch_assoc()) {
            $vehicles[] = normalizeVehicleRow($row);
        }

        return [
            'success' => 1,
            'count' => count($vehicles),
            'data' => $vehicles,
            'message' => count($vehicles) === 0 ? 'No vehicles found' : '',
        ];
    }

    if (!$stmt->execute()) {
        $stmt->close();
        return [
            'success' => 0,
            'count' => 0,
            'data' => [],
            'message' => 'Query failed',
        ];
    }

    $result = $stmt->get_result();
    if (!$result) {
        $stmt->close();
        return [
            'success' => 0,
            'count' => 0,
            'data' => [],
            'message' => 'Query failed',
        ];
    }

    $vehicles = [];
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = normalizeVehicleRow($row);
    }

    $stmt->close();

    return [
        'success' => 1,
        'count' => count($vehicles),
        'data' => $vehicles,
        'message' => count($vehicles) === 0 ? 'No vehicles found' : '',
    ];
}

function normalizeVehicleRow(array $row): array
{
    return [
        'id' => $row['id'] ?? '',
        'name' => $row['name'] ?? '',
        'ownerEmail' => $row['ownerEmail'] ?? '',
        'phone' => $row['phone'] ?? '',
        'idnumber' => $row['idnumber'] ?? '',
        'type' => $row['type'] ?? '',
        'status' => $row['status'] ?? '',
        'brand' => $row['brand'] ?? '',
        'platenum' => strtoupper($row['platenum'] ?? ''),
        'sticker' => $row['sticker'] ?? '',
        'stickerno' => $row['stickerno'] ?? '',
    ];
}
