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
// First check JSON body, then GET, then POST
$input = file_get_contents('php://input');
$jsonData = json_decode($input, true);
$action = $jsonData['action'] ?? $_GET['action'] ?? $_POST['action'] ?? '';

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
    case 'add':
        addProduct();
        break;
    case 'update':
        updateProduct();
        break;
    case 'delete':
        deleteProduct();
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

function addProduct() {
    global $conn;
    
    // Get JSON data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        $data = $_POST;
    }
    
    $name = $data['name'] ?? '';
    $price = $data['price'] ?? 0;
    $stock_quantity = $data['stock_quantity'] ?? 0;
    $barcode = $data['barcode'] ?? null;
    $expiry = $data['expiry'] ?? null;
    $description = $data['description'] ?? '';
    $category_id = $data['category_id'] ?? 1; // Default category
    $low_stock_threshold = 10;
    
    if (empty($name) || $price <= 0) {
        sendResponse(false, 'Product name and price are required');
        return;
    }
    
    try {
        // Generate SKU
        $stmt = $conn->prepare("SELECT MAX(id) as max_id FROM products");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $next_id = ($row['max_id'] ?? 0) + 1;
        $sku = 'SKU-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);
        
        $stmt = $conn->prepare("
            INSERT INTO products (name, sku, category_id, price, stock_quantity, low_stock_threshold, barcode, expiry, description, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        $stmt->bind_param("ssiiiisss", $name, $sku, $category_id, $price, $stock_quantity, $low_stock_threshold, $barcode, $expiry, $description);
        
        if ($stmt->execute()) {
            $product_id = $stmt->insert_id;
            sendResponse(true, 'Product added successfully', ['product_id' => $product_id]);
        } else {
            sendResponse(false, 'Failed to add product');
        }
        
    } catch (Exception $e) {
        sendResponse(false, 'Server error: ' . $e->getMessage());
    }
}

function updateProduct() {
    global $conn;
    
    // Get JSON data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        $data = $_POST;
    }
    
    $id = $data['id'] ?? 0;
    $name = $data['name'] ?? '';
    $price = $data['price'] ?? 0;
    $stock_quantity = $data['stock_quantity'] ?? 0;
    $barcode = $data['barcode'] ?? null;
    $expiry = $data['expiry'] ?? null;
    $description = $data['description'] ?? '';
    
    if (empty($id) || empty($name)) {
        sendResponse(false, 'Product ID and name are required');
        return;
    }
    
    try {
        $stmt = $conn->prepare("
            UPDATE products 
            SET name = ?, price = ?, stock_quantity = ?, barcode = ?, expiry = ?, description = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("sdiissi", $name, $price, $stock_quantity, $barcode, $expiry, $description, $id);
        
        if ($stmt->execute()) {
            sendResponse(true, 'Product updated successfully');
        } else {
            sendResponse(false, 'Failed to update product');
        }
        
    } catch (Exception $e) {
        sendResponse(false, 'Server error: ' . $e->getMessage());
    }
}

function deleteProduct() {
    global $conn;
    
    // Get JSON data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        $data = $_POST;
    }
    
    $id = $data['id'] ?? 0;
    
    if (empty($id)) {
        sendResponse(false, 'Product ID is required');
        return;
    }
    
    try {
        // Soft delete by setting status to 'inactive'
        $stmt = $conn->prepare("UPDATE products SET status = 'inactive', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            sendResponse(true, 'Product deleted successfully');
        } else {
            sendResponse(false, 'Failed to delete product');
        }
        
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
