<?php
// Database Migration Script
// This ensures your database has the correct structure for the POS system

require_once 'config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>PointShift POS Database Migration</h2>";
    echo "<p>Checking and updating database structure...</p>";
    
    // Check if orders table has the required columns
    $result = $db->query("DESCRIBE orders");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['tax_amount', 'subtotal', 'order_number'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $column) {
        if (!in_array($column, $columns)) {
            $missingColumns[] = $column;
        }
    }
    
    if (!empty($missingColumns)) {
        echo "<p>Adding missing columns to orders table...</p>";
        
        if (in_array('order_number', $missingColumns)) {
            $db->exec("ALTER TABLE orders ADD COLUMN order_number VARCHAR(20) AFTER id");
            echo "✓ Added order_number column<br>";
        }
        
        if (in_array('tax_amount', $missingColumns)) {
            $db->exec("ALTER TABLE orders ADD COLUMN tax_amount DECIMAL(10,2) DEFAULT 0.00 AFTER total_amount");
            echo "✓ Added tax_amount column<br>";
        }
        
        if (in_array('subtotal', $missingColumns)) {
            $db->exec("ALTER TABLE orders ADD COLUMN subtotal DECIMAL(10,2) DEFAULT 0.00 AFTER total_amount");
            echo "✓ Added subtotal column<br>";
        }
    }
    
    // Update existing orders to have order numbers if they don't
    $db->exec("UPDATE orders SET order_number = CONCAT('ORD-', LPAD(id, 4, '0')) WHERE order_number IS NULL OR order_number = ''");
    echo "✓ Updated order numbers<br>";
    
    // Check if products table has correct structure
    $result = $db->query("DESCRIBE products");
    $productColumns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('category_id', $productColumns)) {
        echo "<p>Adding category_id to products table...</p>";
        $db->exec("ALTER TABLE products ADD COLUMN category_id INT AFTER name");
        echo "✓ Added category_id column<br>";
    }
    
    // Ensure categories table exists
    $tables = $db->query("SHOW TABLES LIKE 'categories'")->fetchAll();
    if (empty($tables)) {
        echo "<p>Creating categories table...</p>";
        $db->exec("
            CREATE TABLE categories (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Insert default categories
        $db->exec("
            INSERT INTO categories (name, description) VALUES
            ('Electronics', 'Electronic devices and accessories'),
            ('Clothing', 'Apparel and fashion items'),
            ('Food & Beverages', 'Food and drink products'),
            ('General', 'General merchandise')
        ");
        echo "✓ Created categories table and added default categories<br>";
    }
    
    // Update products without category_id
    $db->exec("UPDATE products SET category_id = 4 WHERE category_id IS NULL OR category_id = 0");
    echo "✓ Updated products with default category<br>";
    
    echo "<br><h3 style='color: green;'>✅ Migration completed successfully!</h3>";
    echo "<p>Your database is now ready for the POS system.</p>";
    echo "<p><a href='dashboard.php'>Go to Dashboard</a> | <a href='staff/pos.php'>Go to POS</a> | <a href='reports.php'>View Reports</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Migration failed!</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}
?>
