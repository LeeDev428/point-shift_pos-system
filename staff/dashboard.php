<?php
require_once '../config.php';
User::requireLogin();

// Redirect admin to admin panel
if (User::isAdmin()) {
    header('Location: ../dashboard.php');
    exit();
}


$dashboardController = new DashboardController();
$stats = $dashboardController->getStats();
$recentOrders = $dashboardController->getRecentOrders(3);

// Additional statistics for inventory dashboard cards
$db = Database::getInstance()->getConnection();
$inventoryStats = [
    'total_products' => $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'low_stock' => $db->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= low_stock_threshold AND stock_quantity > 0")->fetchColumn(),
    'scanned_today' => 0,
    'new_reports' => 0,
];

// Check if 'scans' table exists before querying
$tableCheck = $db->query("SHOW TABLES LIKE 'scans'")->fetchColumn();
if ($tableCheck) {
    $inventoryStats['scanned_today'] = $db->query("SELECT COUNT(*) FROM scans WHERE DATE(scan_time) = CURDATE()") ? $db->query("SELECT COUNT(*) FROM scans WHERE DATE(scan_time) = CURDATE()") ->fetchColumn() : 0;
}

// Check if 'inventory_reports' table and 'status' column exist before querying
$tableCheck2 = $db->query("SHOW TABLES LIKE 'inventory_reports'")->fetchColumn();
if ($tableCheck2) {
    $colCheck = $db->query("SHOW COLUMNS FROM inventory_reports LIKE 'status'")->fetchColumn();
    if ($colCheck) {
        $inventoryStats['new_reports'] = $db->query("SELECT COUNT(*) FROM inventory_reports WHERE status = 'new'") ? $db->query("SELECT COUNT(*) FROM inventory_reports WHERE status = 'new'" )->fetchColumn() : 0;
    }
}

$title = 'Staff Dashboard';

ob_start();
?>
<div style="max-height: 95vh; overflow-y: auto;">

<!-- Inventory Dashboard Statistics Cards -->
<div class="mb-4">
    <br>
    <h4 class="mb-3">Inventory Dashboard</h4>
    <div class="row g-3">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="card-text">Total Products</div>
                    <h3 class="text-danger mb-0"><?=$inventoryStats['total_products']?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="card-text">Low Stock Items</div>
                    <h3 class="text-danger mb-0"><?=$inventoryStats['low_stock']?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="card-text">Scanned Items Today</div>
                    <h3 class="text-danger mb-0"><?=$inventoryStats['scanned_today']?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="card-text">Inventory Reports</div>
                    <h3 class="text-danger mb-0"><?=$inventoryStats['new_reports']?> <span style="font-size:1rem; color:#d9534f;">New</span></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Existing Stats Cards -->
<div class="row mb-4">
    <div class="col-xl-4 col-md-6 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="bg-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-shopping-cart text-white fa-lg"></i>
                    </div>
                    <div>
                        <h4 class="mb-0"><?php echo $stats['total_orders']; ?></h4>
                        <small class="text-muted">Total Orders</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-dollar-sign text-white fa-lg"></i>
                    </div>
                    <div>
                        <h4 class="mb-0"><?php echo formatCurrency($stats['total_sales']); ?></h4>
                        <small class="text-muted">Total Sales</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-boxes text-white fa-lg"></i>
                    </div>
                    <div>
                        <h4 class="mb-0"><?php echo $stats['total_products']; ?></h4>
                        <small class="text-muted">Available Products</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Actions -->
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-3">
                 
                    <a href="transactions.php" class="btn btn-outline-danger">
                        <i class="fas fa-receipt me-2"></i>
                        View Transaction History
                    </a>
                    <a href="sales.php" class="btn btn-outline-danger">
                        <i class="fas fa-chart-line me-2"></i>
                        Daily Sales Report
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Transactions -->
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Transactions</h5>
                <span class="badge bg-danger"><?php echo count($recentOrders); ?> Orders</span>
            </div>
            <div class="card-body">
                <?php if (empty($recentOrders)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No recent transactions</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentOrders as $order): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                        <div>
                            <strong class="text-danger"><?php echo htmlspecialchars($order['order_number']); ?></strong>
                            <br>
                            <small class="text-muted"><?php echo Layout::getTimeAgo($order['created_at']); ?></small>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold"><?php echo formatCurrency($order['total_amount']); ?></div>
                            <span class="badge bg-<?php echo $order['status'] == 'completed' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tips for Staff -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <h5 class="mb-0">Staff Tips</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-3">
                        <i class="fas fa-search fa-2x text-danger mb-2"></i>
                        <h6>Quick Search</h6>
                        <small class="text-muted">Use the search bar to quickly find products by name or barcode</small>
                    </div>
                    <div class="col-md-4 text-center mb-3">
                        <i class="fas fa-calculator fa-2x text-danger mb-2"></i>
                        <h6>Auto Calculate</h6>
                        <small class="text-muted">Tax and total amounts are automatically calculated</small>
                    </div>
                    <div class="col-md-4 text-center mb-3">
                        <i class="fas fa-credit-card fa-2x text-danger mb-2"></i>
                        <h6>Multiple Payments</h6>
                        <small class="text-muted">Accept cash, card, or other payment methods</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


</div>
<?php
$content = ob_get_clean();

// Include staff layout
$title = 'Staff Dashboard';
include 'views/layout.php';
?>
