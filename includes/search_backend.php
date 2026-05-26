<?php
/**
 * Search Backend - Vehicle Search with Caching
 * Provides vehicle search functions with 300-second cache TTL
 */

// Cache file path
define('SEARCH_CACHE_DIR', $_SERVER['DOCUMENT_ROOT'] . '/uploads/.cache');
define('SEARCH_CACHE_TTL', 300); // 5 minutes

// Ensure cache directory exists
if (!is_dir(SEARCH_CACHE_DIR)) {
    @mkdir(SEARCH_CACHE_DIR, 0755, true);
}

/**
 * Search vehicle records across multiple tables
 * @param mysqli $con Database connection
 * @param string $search Search query (plate, name, ID)
 * @param string $status Filter by status
 * @param bool $showAll Show all records
 * @return array Results array
 */
function searchVehicleRecords(mysqli $con, string $search = '', string $status = '', bool $showAll = false): array
{
    $search = trim($search);
    $status = trim($status);

    if ($search !== '') {
        return searchByTerm($con, $search);
    } elseif ($status !== '') {
        return searchByStatus($con, $status);
    } else {
        return searchAll($con);
    }
}

/**
 * Search vehicles by term with caching
 * @param mysqli $con Database connection
 * @param string $term Search term (min 2 chars)
 * @return array Results
 */
function searchByTerm(mysqli $con, string $term): array
{
    $term = trim($term);
    
    if (strlen($term) < 2) {
        return [
            'success' => 1,
            'count' => 0,
            'data' => [],
            'message' => 'Search term too short',
        ];
    }

    // Generate cache key
    $cacheKey = 'search_' . md5($term);
    $cached = getCachedResult($cacheKey);
    
    if ($cached !== null) {
        return $cached;
    }

    $like = '%' . $con->real_escape_string($term) . '%';
    
    $stmt = $con->prepare(
        "SELECT * FROM owner
         WHERE platenum LIKE ? OR name LIKE ? OR idnumber LIKE ?
         ORDER BY id DESC
         LIMIT 100"
    );
    
    if (!$stmt) {
        return [
            'success' => 0,
            'count' => 0,
            'data' => [],
            'message' => 'Query failed: ' . $con->error,
        ];
    }

    $stmt->bind_param('sss', $like, $like, $like);
    
    if (!$stmt->execute()) {
        $stmt->close();
        return [
            'success' => 0,
            'count' => 0,
            'data' => [],
            'message' => 'Query execution failed',
        ];
    }

    $result = $stmt->get_result();
    if (!$result) {
        $stmt->close();
        return [
            'success' => 0,
            'count' => 0,
            'data' => [],
            'message' => 'Failed to fetch results',
        ];
    }

    $vehicles = [];
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = normalizeVehicleRow($row);
    }
    $stmt->close();

    $response = [
        'success' => 1,
        'count' => count($vehicles),
        'data' => $vehicles,
        'message' => count($vehicles) === 0 ? 'No vehicles found' : '',
    ];
    
    // Cache the result
    setCachedResult($cacheKey, $response);
    
    return $response;
}

/**
 * Search vehicles by status with caching
 * @param mysqli $con Database connection
 * @param string $status Status value
 * @return array Results
 */
function searchByStatus(mysqli $con, string $status): array
{
    $status = trim($status);
    
    // Generate cache key
    $cacheKey = 'status_' . md5($status);
    $cached = getCachedResult($cacheKey);
    
    if ($cached !== null) {
        return $cached;
    }

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
    
    if (!$stmt->execute()) {
        $stmt->close();
        return [
            'success' => 0,
            'count' => 0,
            'data' => [],
            'message' => 'Query execution failed',
        ];
    }

    $result = $stmt->get_result();
    if (!$result) {
        $stmt->close();
        return [
            'success' => 0,
            'count' => 0,
            'data' => [],
            'message' => 'Failed to fetch results',
        ];
    }

    $vehicles = [];
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = normalizeVehicleRow($row);
    }
    $stmt->close();

    $response = [
        'success' => 1,
        'count' => count($vehicles),
        'data' => $vehicles,
        'message' => count($vehicles) === 0 ? 'No vehicles found' : '',
    ];
    
    // Cache the result
    setCachedResult($cacheKey, $response);
    
    return $response;
}

/**
 * Get all vehicles
 * @param mysqli $con Database connection
 * @return array All vehicles
 */
function searchAll(mysqli $con): array
{
    // Generate cache key for all vehicles
    $cacheKey = 'search_all';
    $cached = getCachedResult($cacheKey);
    
    if ($cached !== null) {
        return $cached;
    }

    $result = $con->query("SELECT * FROM owner ORDER BY id DESC");
    
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

    $response = [
        'success' => 1,
        'count' => count($vehicles),
        'data' => $vehicles,
        'message' => count($vehicles) === 0 ? 'No vehicles found' : '',
    ];
    
    // Cache the result
    setCachedResult($cacheKey, $response);
    
    return $response;
}

/**
 * Get vehicle by plate number (exact match)
 * @param mysqli $con Database connection
 * @param string $plate Plate number
 * @return array Vehicle data or error
 */
function getVehicleByPlate(mysqli $con, string $plate): array
{
    $plate = trim($plate);
    
    if (strlen($plate) < 2) {
        return [
            'success' => 0,
            'count' => 0,
            'data' => [],
            'message' => 'Invalid plate number',
        ];
    }

    // Generate cache key
    $cacheKey = 'plate_' . md5($plate);
    $cached = getCachedResult($cacheKey);
    
    if ($cached !== null) {
        return $cached;
    }

    $stmt = $con->prepare("SELECT * FROM owner WHERE platenum = ? LIMIT 1");
    
    if (!$stmt) {
        return [
            'success' => 0,
            'count' => 0,
            'data' => [],
            'message' => 'Query failed',
        ];
    }

    $stmt->bind_param('s', $plate);
    
    if (!$stmt->execute()) {
        $stmt->close();
        return [
            'success' => 0,
            'count' => 0,
            'data' => [],
            'message' => 'Query execution failed',
        ];
    }

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        return [
            'success' => 0,
            'count' => 0,
            'data' => [],
            'message' => 'Vehicle not found',
        ];
    }

    $row = $result->fetch_assoc();
    $stmt->close();

    $response = [
        'success' => 1,
        'count' => 1,
        'data' => [normalizeVehicleRow($row)],
        'message' => '',
    ];
    
    // Cache the result
    setCachedResult($cacheKey, $response);
    
    return $response;
}

/**
 * Get vehicle by ID and type
 * @param mysqli $con Database connection
 * @param int $id Vehicle ID
 * @param string $type Vehicle type (staff, student, visitor, contractor)
 * @return array Vehicle data or error
 */
function getVehicleById(mysqli $con, int $id, string $type): array
{
    $id = (int)$id;
    $type = strtolower(trim($type));
    
    if ($id <= 0 || !in_array($type, ['staff', 'student', 'visitor', 'contractor'])) {
        return [
            'success' => 0,
            'count' => 0,
            'data' => [],
            'message' => 'Invalid parameters',
        ];
    }

    // Generate cache key
    $cacheKey = 'id_' . md5($id . '_' . $type);
    $cached = getCachedResult($cacheKey);
    
    if ($cached !== null) {
        return $cached;
    }

    $stmt = $con->prepare("SELECT * FROM owner WHERE id = ? LIMIT 1");
    
    if (!$stmt) {
        return [
            'success' => 0,
            'count' => 0,
            'data' => [],
            'message' => 'Query failed',
        ];
    }

    $stmt->bind_param('i', $id);
    
    if (!$stmt->execute()) {
        $stmt->close();
        return [
            'success' => 0,
            'count' => 0,
            'data' => [],
            'message' => 'Query execution failed',
        ];
    }

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        return [
            'success' => 0,
            'count' => 0,
            'data' => [],
            'message' => 'Vehicle not found',
        ];
    }

    $row = $result->fetch_assoc();
    $stmt->close();

    $response = [
        'success' => 1,
        'count' => 1,
        'data' => [normalizeVehicleRow($row)],
        'message' => '',
    ];
    
    // Cache the result
    setCachedResult($cacheKey, $response);
    
    return $response;
}

/**
 * Normalize vehicle row for consistent output
 * @param array $row Database row
 * @return array Normalized vehicle data
 */
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
    ];
}

/**
 * Get cached result if not expired
 * @param string $key Cache key
 * @return array|null Cached data or null if expired/not found
 */
function getCachedResult(string $key): ?array
{
    $cacheFile = SEARCH_CACHE_DIR . '/' . $key . '.cache';
    
    if (!file_exists($cacheFile)) {
        return null;
    }
    
    $fileTime = filemtime($cacheFile);
    if ($fileTime === false) {
        return null;
    }
    
    // Check if cache is expired
    if (time() - $fileTime > SEARCH_CACHE_TTL) {
        @unlink($cacheFile);
        return null;
    }
    
    $cached = @file_get_contents($cacheFile);
    if ($cached === false) {
        return null;
    }
    
    $data = json_decode($cached, true);
    return is_array($data) ? $data : null;
}

/**
 * Set cached result
 * @param string $key Cache key
 * @param array $data Data to cache
 * @return bool Success
 */
function setCachedResult(string $key, array $data): bool
{
    $cacheFile = SEARCH_CACHE_DIR . '/' . $key . '.cache';
    $json = json_encode($data);
    
    if ($json === false) {
        return false;
    }
    
    return @file_put_contents($cacheFile, $json) !== false;
}

/**
 * Clear all cached search results
 * @return int Number of files deleted
 */
function clearSearchCache(): int
{
    $count = 0;
    $files = @glob(SEARCH_CACHE_DIR . '/*.cache');
    
    if ($files === false) {
        return 0;
    }
    
    foreach ($files as $file) {
        if (@unlink($file)) {
            $count++;
        }
    }
    
    return $count;
}

/**
 * Get cache statistics
 * @return array Cache stats
 */
function getCacheStats(): array
{
    $files = @glob(SEARCH_CACHE_DIR . '/*.cache');
    $count = is_array($files) ? count($files) : 0;
    $size = 0;
    
    if ($count > 0) {
        foreach ($files as $file) {
            $size += filesize($file);
        }
    }
    
    return [
        'total_files' => $count,
        'total_size' => $size,
        'cache_ttl' => SEARCH_CACHE_TTL,
    ];
}
