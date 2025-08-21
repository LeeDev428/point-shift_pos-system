-- PointShift POS System - Clean Database Structure
CREATE DATABASE IF NOT EXISTS pointshift_pos;
USE pointshift_pos;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    barcode VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, role, first_name, last_name) VALUES
('admin', 'admin@pointshift.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin', 'User');

-- Insert sample staff user (password: staff123)
INSERT INTO users (username, email, password, role, first_name, last_name) VALUES
('staff', 'staff@pointshift.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyNGanHatGH/XcHUOhzEqJgjdW7g6', 'staff', 'Staff', 'User');

-- Insert sample products
INSERT INTO products (name, price, stock_quantity, barcode) VALUES
('Laptop Computer', 25999.99, 10, 'LAP001'),
('Wireless Mouse', 599.99, 25, 'MOU001'),
('USB Cable', 199.99, 50, 'USB001'),
('Keyboard', 1299.99, 15, 'KEY001'),
('Monitor', 8999.99, 8, 'MON001');
