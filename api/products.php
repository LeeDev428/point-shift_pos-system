<?php
// API Products Endpoint for Mobile App
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'getByBarcode':
        getProductByBarcode();
        break;
    case 'search':
        searchProducts();
        break;
    case 'getAll':
        getAllProducts();
        break;
    default:
        sendResponse(false, 'Invalid action');
        break;
}

function getProductByBarcode() {
    global $conn;
    
    $barcode = $_GET['barcode'] ?? '';
    
    if (empty($barcode)) {
        sendResponse(false, 'Barcode is required');
        return;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.barcode = ? AND p.status = 'active'
        ");
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendResponse(false, 'Product not found');
            return;
        }
        
        $product = $result->fetch_assoc();
        
        // Format the product data
        $product['price'] = number_format($product['price'], 2, '.', '');
        $product['in_stock'] = (int)$product['stock_quantity'] > 0;
        $product['is_low_stock'] = (int)$product['stock_quantity'] <= (int)$product['low_stock_threshold'];
        
        sendResponse(true, 'Product found', ['product' => $product]);
        
    } catch (Exception $e) {
        sendResponse(false, 'Server error: ' . $e->getMessage());
    }
}

function searchProducts() {
    global $conn;
    
    $query = $_GET['query'] ?? '';
    
    if (empty($query)) {
        sendResponse(false, 'Search query is required');
        return;
    }
    
    try {
        $searchTerm = "%{$query}%";
        $stmt = $conn->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE (p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?) 
            AND p.status = 'active'
            ORDER BY p.name ASC
            LIMIT 50
        ");
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $row['price'] = number_format($row['price'], 2, '.', '');
            $row['in_stock'] = (int)$row['stock_quantity'] > 0;
            $row['is_low_stock'] = (int)$row['stock_quantity'] <= (int)$row['low_stock_threshold'];
            $products[] = $row;
        }
        
        sendResponse(true, 'Products found', ['products' => $products]);
        
    } catch (Exception $e) {
        sendResponse(false, 'Server error: ' . $e->getMessage());
    }
}

function getAllProducts() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.status = 'active'
            ORDER BY p.name ASC
            LIMIT 100
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $row['price'] = number_format($row['price'], 2, '.', '');
            $row['in_stock'] = (int)$row['stock_quantity'] > 0;
            $row['is_low_stock'] = (int)$row['stock_quantity'] <= (int)$row['low_stock_threshold'];
            $products[] = $row;
        }
        
        sendResponse(true, 'Products retrieved', ['products' => $products]);
        
    } catch (Exception $e) {
        sendResponse(false, 'Server error: ' . $e->getMessage());
    }
}

function sendResponse($success, $message, $data = []) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response);
    exit();
}
?>
