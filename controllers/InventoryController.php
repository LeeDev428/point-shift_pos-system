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
            
            // Low Stock (quantity <= 10)
            $stmt = $this->db->query("SELECT COUNT(*) as low_stock FROM products WHERE stock_quantity <= 10 AND status = 'active'");
            $stats['low_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['low_stock'] ?? 0;
            
            // Out of Stock
            $stmt = $this->db->query("SELECT COUNT(*) as out_of_stock FROM products WHERE stock_quantity = 0 AND status = 'active'");
            $stats['out_of_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['out_of_stock'] ?? 0;
            
            // Total Value
            $stmt = $this->db->query("SELECT SUM(price * stock_quantity) as total_value FROM products WHERE status = 'active'");
            $stats['total_value'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_value'] ?? 0;
            
            return $stats;
        } catch(PDOException $e) {
            return [];
        }
    }
    
    public function getAllProducts($search = '') {
        try {
            $sql = "SELECT * FROM products WHERE status = 'active'";
            $params = [];
            
            if (!empty($search)) {
                $sql .= " AND (name LIKE ? OR barcode LIKE ?)";
                $params = ["%{$search}%", "%{$search}%"];
            }
            
            $sql .= " ORDER BY name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
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
            $stmt = $this->db->prepare("UPDATE products SET name = ?, price = ?, stock_quantity = ?, barcode = ? WHERE id = ?");
            $stmt->execute([
                $data['name'],
                $data['price'],
                $data['stock_quantity'],
                $data['barcode'],
                $id
            ]);
            return true;
        } catch(PDOException $e) {
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
