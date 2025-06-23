<?php
require_once 'config/admin_session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (extendAdminSession()) {
        $remaining = getSessionTimeRemaining();
        echo json_encode([
            'success' => true,
            'remaining_time' => $remaining,
            'message' => 'Session extended successfully'
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Session invalid or expired'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
?> 