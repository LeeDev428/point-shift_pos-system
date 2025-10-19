<?php
class InventoryController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getStats() {
        try {
            $stats = [];
            
            // Total Products
            $stmt = $this->db->query("SELECT COUNT(*) as total_products FROM products WHERE status = 'active'");
            $stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'] ?? 0;
            
            // Low Stock (quantity <= low_stock_threshold AND > 0)
            $stmt = $this->db->query("SELECT COUNT(*) as low_stock FROM products WHERE stock_quantity <= low_stock_threshold AND stock_quantity > 0 AND status = 'active'");
            $stats['low_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['low_stock'] ?? 0;
            
            // Out of Stock
            $stmt = $this->db->query("SELECT COUNT(*) as out_of_stock FROM products WHERE stock_quantity = 0 AND status = 'active'");
            $stats['out_of_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['out_of_stock'] ?? 0;
            
            // In Stock (quantity > low_stock_threshold)
            $stmt = $this->db->query("SELECT COUNT(*) as in_stock FROM products WHERE stock_quantity > low_stock_threshold AND status = 'active'");
            $stats['in_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['in_stock'] ?? 0;
            
            return $stats;
        } catch(PDOException $e) {
            return [];
        }
    }
    
    public function getAllProducts($search = '', $category_id = '', $stock_filter = '') {
        try {
            $sql = "SELECT p.*, c.name as category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.status = 'active'";
            $params = [];
            
            if (!empty($search)) {
                $sql .= " AND (p.name LIKE ? OR p.barcode LIKE ? OR p.sku LIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }
            
            if (!empty($category_id)) {
                $sql .= " AND p.category_id = ?";
                $params[] = $category_id;
            }
            
            // Stock filter
            if (!empty($stock_filter)) {
                switch ($stock_filter) {
                    case 'low_stock':
                        $sql .= " AND p.stock_quantity <= p.low_stock_threshold AND p.stock_quantity > 0";
                        break;
                    case 'out_of_stock':
                        $sql .= " AND p.stock_quantity = 0";
                        break;
                    case 'in_stock':
                        $sql .= " AND p.stock_quantity > p.low_stock_threshold";
                        break;
                }
            }
            
            $sql .= " ORDER BY p.name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    public function getAllCategories() {
        try {
            $stmt = $this->db->query("SELECT * FROM categories ORDER BY name ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    public function addProduct($data) {
        try {
            $stmt = $this->db->prepare("INSERT INTO products (name, price, stock_quantity, barcode) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $data['name'],
                $data['price'],
                $data['stock_quantity'],
                $data['barcode']
            ]);
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function updateProduct($id, $data) {
        try {
            // Get old stock quantity to track changes
            $oldStockStmt = $this->db->prepare("SELECT stock_quantity, name FROM products WHERE id = ?");
            $oldStockStmt->execute([$id]);
            $oldProduct = $oldStockStmt->fetch(PDO::FETCH_ASSOC);
            $old_stock = intval($oldProduct['stock_quantity']);
            $new_stock = intval($data['stock_quantity']);
            $product_name = $oldProduct['name'];
            
            // Calculate stock change
            $stock_change = $new_stock - $old_stock;
            
            // Update product with user tracking
            $user_id = $_SESSION['user_id'] ?? null;
            $stmt = $this->db->prepare("UPDATE products SET name = ?, price = ?, stock_quantity = ?, barcode = ?, last_updated_by = ? WHERE id = ?");
            $stmt->execute([
                $data['name'],
                $data['price'],
                $new_stock,
                $data['barcode'],
                $user_id,
                $id
            ]);
            
            // Record inventory change in inventory_reports if stock quantity changed
            if ($stock_change != 0 && $user_id) {
                $change_type = ($stock_change > 0) ? 'Added' : 'Removed';
                $quantity_changed = abs($stock_change);
                $user_role = $_SESSION['role'] ?? 'admin';
                $remarks = ($stock_change > 0) 
                    ? "Stock added by $user_role. Previous: $old_stock, New: $new_stock" 
                    : "Stock removed by $user_role. Previous: $old_stock, New: $new_stock";
                
                $reportStmt = $this->db->prepare("INSERT INTO inventory_reports (product_id, change_type, quantity, quantity_changed, previous_quantity, new_quantity, date, remarks, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, NOW())");
                $reportStmt->execute([
                    $id,
                    $change_type,
                    $quantity_changed,  // Use same value for 'quantity' (legacy column)
                    $quantity_changed,
                    $old_stock,
                    $new_stock,
                    $remarks,
                    $user_id
                ]);
            }
            
            return true;
        } catch(PDOException $e) {
            error_log("Update product error: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteProduct($id) {
        try {
            $stmt = $this->db->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$id]);
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }
}
?>
