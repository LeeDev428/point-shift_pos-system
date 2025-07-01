<?php
require_once 'config.php';
requireLogin();

$page_title = 'Dashboard Overview';

// Start output buffering
ob_start();

// Get dashboard statistics
try {
    // Total Sales
    $stmt = $pdo->query("SELECT SUM(total_amount) as total_sales FROM orders WHERE status = 'completed'");
    $total_sales = $stmt->fetch(PDO::FETCH_ASSOC)['total_sales'] ?? 0;
    
    // Total Inventory Items
    $stmt = $pdo->query("SELECT COUNT(*) as total_items FROM products WHERE status = 'active'");
    $total_items = $stmt->fetch(PDO::FETCH_ASSOC)['total_items'] ?? 0;
    
    // Total Orders
    $stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
    $total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'] ?? 0;
    
    // Active Users
    $stmt = $pdo->query("SELECT COUNT(*) as active_users FROM users WHERE status = 'active'");
    $active_users = $stmt->fetch(PDO::FETCH_ASSOC)['active_users'] ?? 0;
    
    // Recent Activity (Recent Orders)
    $stmt = $pdo->prepare("SELECT o.*, u.first_name, u.last_name FROM orders o 
                          JOIN users u ON o.user_id = u.id 
                          ORDER BY o.created_at DESC LIMIT 5");
    $stmt->execute();
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Low Stock Products
    $stmt = $pdo->query("SELECT * FROM products WHERE stock_quantity <= low_stock_threshold AND status = 'active' ORDER BY stock_quantity ASC LIMIT 5");
    $low_stock_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Error fetching dashboard data: " . $e->getMessage();
}
?>

<!-- Dashboard Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-success me-3">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo formatCurrency($total_sales); ?></h3>
                    <p class="text-muted mb-0">Total Sales</p>
                    <small class="text-success"><i class="fas fa-arrow-up me-1"></i>+4.75%</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-info me-3">
                    <i class="fas fa-boxes"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo $total_items; ?></h3>
                    <p class="text-muted mb-0">Inventory Items</p>
                    <small class="text-danger"><i class="fas fa-arrow-down me-1"></i>-2.3%</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-warning me-3">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo $total_orders; ?></h3>
                    <p class="text-muted mb-0">Total Orders</p>
                    <small class="text-success"><i class="fas fa-arrow-up me-1"></i>+8.2%</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-primary me-3">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo $active_users; ?></h3>
                    <p class="text-muted mb-0">Active Users</p>
                    <small class="text-success"><i class="fas fa-arrow-up me-1"></i>+2.5%</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Activity -->
    <div class="col-xl-8 mb-4">
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Activity</h5>
                <span class="badge bg-primary"><?php echo count($recent_orders); ?> Orders</span>
            </div>
            <div class="card-body">
                <?php if (empty($recent_orders)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No recent orders found</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Staff</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>
                                        <strong class="text-primary"><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                    <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $order['status'] == 'completed' ? 'success' : ($order['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php 
                                            $time_diff = time() - strtotime($order['created_at']);
                                            if ($time_diff < 3600) {
                                                echo floor($time_diff / 60) . 'min ago';
                                            } elseif ($time_diff < 86400) {
                                                echo floor($time_diff / 3600) . 'h ago';
                                            } else {
                                                echo date('M j, Y', strtotime($order['created_at']));
                                            }
                                            ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Low Stock Alert -->
    <div class="col-xl-4 mb-4">
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Low Stock Alert</h5>
                <span class="badge bg-danger"><?php echo count($low_stock_products); ?> Items</span>
            </div>
            <div class="card-body">
                <?php if (empty($low_stock_products)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted">All items are well stocked</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($low_stock_products as $product): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                        <div>
                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($product['name']); ?></div>
                            <small class="text-muted">Stock: <?php echo $product['stock_quantity']; ?></small>
                        </div>
                        <span class="badge bg-danger">Only <?php echo $product['stock_quantity']; ?> left</span>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (isAdmin()): ?>
                    <div class="text-center mt-3">
                        <a href="inventory.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-boxes me-1"></i>Manage Inventory
                        </a>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="pos.php" class="btn btn-primary w-100 btn-custom">
                            <i class="fas fa-shopping-cart me-2"></i>
                            New Sale
                        </a>
                    </div>
                    <?php if (isAdmin()): ?>
                    <div class="col-md-3 mb-3">
                        <a href="inventory.php" class="btn btn-success w-100 btn-custom">
                            <i class="fas fa-plus me-2"></i>
                            Add Product
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="user_management.php" class="btn btn-info w-100 btn-custom">
                            <i class="fas fa-user-plus me-2"></i>
                            Add User
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3 mb-3">
                        <a href="sales_analysis.php" class="btn btn-warning w-100 btn-custom">
                            <i class="fas fa-chart-bar me-2"></i>
                            View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout.php';
?>
