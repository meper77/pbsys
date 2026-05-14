<?php
header('Content-Type: application/json');
session_start();

// Check authentication
if (!isset($_SESSION['email_Admin']) && !isset($_SESSION['email_User'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include 'connect.php';

// Handle GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get_stats') {
        // Get vehicle statistics
        $stats = [];
        
        // Staff vehicles
        $staff_query = mysqli_query($con, "SELECT COUNT(*) as count FROM staffcar WHERE sticker_status != 'removed'");
        $staff_row = mysqli_fetch_assoc($staff_query);
        $stats['staff'] = (int)$staff_row['count'];
        
        // Student vehicles
        $student_query = mysqli_query($con, "SELECT COUNT(*) as count FROM studentcar WHERE sticker_status != 'removed'");
        $student_row = mysqli_fetch_assoc($student_query);
        $stats['student'] = (int)$student_row['count'];
        
        // Visitor vehicles
        $visitor_query = mysqli_query($con, "SELECT COUNT(*) as count FROM visitorcar WHERE sticker_status != 'removed'");
        $visitor_row = mysqli_fetch_assoc($visitor_query);
        $stats['visitor'] = (int)$visitor_row['count'];
        
        // Contractor vehicles
        $contractor_query = mysqli_query($con, "SELECT COUNT(*) as count FROM contractorcar WHERE sticker_status != 'removed'");
        $contractor_row = mysqli_fetch_assoc($contractor_query);
        $stats['contractor'] = (int)$contractor_row['count'];
        
        // Total vehicles
        $total = $stats['staff'] + $stats['student'] + $stats['visitor'] + $stats['contractor'];
        $stats['total'] = $total;
        
        // Removed stickers count
        $removed_query = mysqli_query($con, "
            (SELECT COUNT(*) as count FROM staffcar WHERE sticker_status = 'removed')
            UNION ALL
            (SELECT COUNT(*) FROM studentcar WHERE sticker_status = 'removed')
            UNION ALL
            (SELECT COUNT(*) FROM visitorcar WHERE sticker_status = 'removed')
            UNION ALL
            (SELECT COUNT(*) FROM contractorcar WHERE sticker_status = 'removed')
        ");
        
        $removed_count = 0;
        while ($row = mysqli_fetch_assoc($removed_query)) {
            $removed_count += (int)$row['count'];
        }
        $stats['removed'] = $removed_count;
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
    } elseif ($action === 'get_vehicles_by_type') {
        $type = mysqli_real_escape_string($con, $_GET['type'] ?? '');
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);
        
        if (empty($type)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Type parameter required']);
            exit();
        }
        
        $vehicles = [];
        $total = 0;
        
        if ($type === 'staff') {
            $count_query = mysqli_query($con, "SELECT COUNT(*) as count FROM staffcar");
            $count_row = mysqli_fetch_assoc($count_query);
            $total = (int)$count_row['count'];
            
            $query = "SELECT staffid, name, phone, staffno, model, platenum, sticker, sticker_status, created_at 
                     FROM staffcar ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
            $result = mysqli_query($con, $query);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $vehicles[] = $row;
            }
        } elseif ($type === 'student') {
            $count_query = mysqli_query($con, "SELECT COUNT(*) as count FROM studentcar");
            $count_row = mysqli_fetch_assoc($count_query);
            $total = (int)$count_row['count'];
            
            $query = "SELECT studentid, name, phone, matric, model, platenum, sticker, sticker_status, created_at 
                     FROM studentcar ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
            $result = mysqli_query($con, $query);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $vehicles[] = $row;
            }
        } elseif ($type === 'visitor') {
            $count_query = mysqli_query($con, "SELECT COUNT(*) as count FROM visitorcar");
            $count_row = mysqli_fetch_assoc($count_query);
            $total = (int)$count_row['count'];
            
            $query = "SELECT visitorid, name, phone, ic_passport, model, platenum, sticker, sticker_status, created_at 
                     FROM visitorcar ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
            $result = mysqli_query($con, $query);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $vehicles[] = $row;
            }
        } elseif ($type === 'contractor') {
            $count_query = mysqli_query($con, "SELECT COUNT(*) as count FROM contractorcar");
            $count_row = mysqli_fetch_assoc($count_query);
            $total = (int)$count_row['count'];
            
            $query = "SELECT contractorid, name, phone, ic_passport, company_name, model, platenum, sticker, sticker_status, created_at 
                     FROM contractorcar ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
            $result = mysqli_query($con, $query);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $vehicles[] = $row;
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid type']);
            exit();
        }
        
        echo json_encode([
            'success' => true,
            'type' => $type,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'vehicles' => $vehicles
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>
