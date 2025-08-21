<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();
    
    // Create order
    $stmt = $db->prepare("
        INSERT INTO orders (order_number, user_id, total_amount, tax_amount, status, created_at) 
        VALUES (?, ?, ?, ?, 'completed', NOW())
    ");
    
    $orderNumber = 'POS' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $totalAmount = $input['total'];
    $taxAmount = $input['tax'];
    $userId = $_SESSION['user_id'];
    
    $stmt->execute([$orderNumber, $userId, $totalAmount, $taxAmount]);
    $orderId = $db->lastInsertId();
    
    // Add order items
    $stmt = $db->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($input['items'] as $item) {
        $totalPrice = $item['price'] * $item['quantity'];
        $stmt->execute([
            $orderId,
            $item['id'],
            $item['quantity'],
            $item['price'],
            $totalPrice
        ]);
        
        // Update product stock
        $updateStock = $db->prepare("
            UPDATE products 
            SET stock_quantity = stock_quantity - ? 
            WHERE id = ?
        ");
        $updateStock->execute([$item['quantity'], $item['id']]);
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'message' => 'Order processed successfully'
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
