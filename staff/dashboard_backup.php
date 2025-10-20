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
$recentOrders = $dashboardController->getRecentOrders(5);

// Additional statistics for inventory dashboard cards
$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Get comprehensive statistics
$inventoryStats = [
    'total_products' => $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'low_stock' => $db->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= low_stock_threshold AND stock_quantity > 0")->fetchColumn(),
    'out_of_stock' => $db->query("SELECT COUNT(*) FROM products WHERE stock_quantity = 0")->fetchColumn(),
    'in_stock' => $db->query("SELECT COUNT(*) FROM products WHERE stock_quantity > low_stock_threshold")->fetchColumn(),
];

// Get today's sales
$todayQuery = $db->query("
    SELECT 
        COUNT(*) as order_count,
        COALESCE(SUM(total_amount), 0) as total_sales
    FROM orders 
    WHERE DATE(created_at) = CURDATE() AND status = 'completed'
");
$todayStats = $todayQuery->fetch(PDO::FETCH_ASSOC);

// Get this week's sales
$weekQuery = $db->query("
    SELECT 
        COUNT(*) as order_count,
        COALESCE(SUM(total_amount), 0) as total_sales
    FROM orders 
    WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) AND status = 'completed'
");
$weekStats = $weekQuery->fetch(PDO::FETCH_ASSOC);

// Get sales by payment method for chart
$paymentMethodQuery = $db->query("
    SELECT 
        payment_method,
        COUNT(*) as count,
        SUM(total_amount) as total
    FROM orders 
    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAYS) AND status = 'completed'
    GROUP BY payment_method
");
$paymentMethods = $paymentMethodQuery->fetchAll(PDO::FETCH_ASSOC);

// Get top selling products (last 30 days)
$topProductsQuery = $db->query("
    SELECT 
        p.name,
        SUM(oi.quantity) as total_sold,
        SUM(oi.total_price) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAYS) AND o.status = 'completed'
    GROUP BY p.id, p.name
    ORDER BY total_sold DESC
    LIMIT 5
");
$topProducts = $topProductsQuery->fetchAll(PDO::FETCH_ASSOC);

// Get daily sales for last 7 days
$dailySalesQuery = $db->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as orders,
        SUM(total_amount) as sales
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAYS) AND status = 'completed'
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$dailySales = $dailySalesQuery->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for charts
$salesDates = array_map(function($item) { return date('M d', strtotime($item['date'])); }, $dailySales);
$salesAmounts = array_map(function($item) { return $item['sales']; }, $dailySales);
$salesOrders = array_map(function($item) { return $item['orders']; }, $dailySales);

$title = 'Staff Dashboard';

ob_start();
?><script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
.dashboard-card {
    border-radius: 12px;
    padding: 1.5rem;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s;
    height: 100%;
}
.dashboard-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.stat-icon-lg {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}
.chart-container {
    position: relative;
    height: 300px;
}
.section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #dc3545;
}
</style>
<div style="max-height: 95vh; overflow-y: auto;">

<!-- Inventory Dashboard Statistics Cards -->
<div class="mb-4">
    <br>
   <h4 class="mb-3">   &nbsp; &nbsp;Inventory Dashboard</h4>
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
<!-- <div class="row">
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
</div> -->


</div>
<?php
$content = ob_get_clean();

// Include staff layout
$title = 'Staff Dashboard';
include 'views/layout.php';
?>
