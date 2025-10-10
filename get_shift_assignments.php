<?php
require_once 'config.php';
requireLogin();
requireAdmin();

header('Content-Type: application/json');

if (isset($_GET['shift_id'])) {
    $shift_id = intval($_GET['shift_id']);
    
    $stmt = $conn->prepare("SELECT user_id FROM shift_assignments WHERE shift_id = ?");
    $stmt->bind_param("i", $shift_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $user_ids = [];
    while ($row = $result->fetch_assoc()) {
        $user_ids[] = $row['user_id'];
    }
    
    echo json_encode(['success' => true, 'user_ids' => $user_ids]);
} else {
    echo json_encode(['success' => false, 'message' => 'Shift ID not provided']);
}
?>
