<?php
require_once 'config.php';
requireLogin();

// Redirect staff to staff panel
if (!User::isAdmin()) {
    header('Location: staff/dashboard.php');
    exit();
}

$dashboardController = new DashboardController();
$stats = $dashboardController->getStats();
$recentOrders = $dashboardController->getRecentOrders(5);
$lowStockProducts = $dashboardController->getLowStockProducts();

$role = $_SESSION['role'];
$page_title = $role === 'admin' ? 'Admin Dashboard' : 'Staff Dashboard';

// Additional statistics for admin dashboard
$db = Database::getInstance()->getConnection();

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

// Get this month's sales
$monthQuery = $db->query("
    SELECT 
        COUNT(*) as order_count,
        COALESCE(SUM(total_amount), 0) as total_sales
    FROM orders 
    WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status = 'completed'
");
$monthStats = $monthQuery->fetch(PDO::FETCH_ASSOC);

// Get sales by payment method for chart
$paymentMethodQuery = $db->query("
    SELECT 
        payment_method,
        COUNT(*) as count,
        SUM(total_amount) as total
    FROM orders 
    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status = 'completed'
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
    WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND o.status = 'completed'
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
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status = 'completed'
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$dailySales = $dailySalesQuery->fetchAll(PDO::FETCH_ASSOC);

// Get category-wise product distribution
$categoryQuery = $db->query("
    SELECT 
        c.name as category,
        COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id, c.name
    ORDER BY product_count DESC
    LIMIT 6
");
$categoryData = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for charts
$salesDates = array_map(function($item) { return date('M d', strtotime($item['date'])); }, $dailySales);
$salesAmounts = array_map(function($item) { return $item['sales']; }, $dailySales);
$salesOrders = array_map(function($item) { return $item['orders']; }, $dailySales);

// Start content
ob_start();
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
.admin-dashboard-card {
    border-radius: 12px;
    padding: 1.5rem;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s;
    height: 100%;
}
.admin-dashboard-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.admin-stat-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}
.admin-chart-container {
    position: relative;
    height: 300px;
}
.admin-section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #dc3545;
}
</style>

<!-- Welcome Section -->
<div class="mb-4">
    <h4 class="mb-1">🎯 Admin Dashboard - <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Administrator'); ?></h4>
    <p class="text-muted mb-0">Complete overview of your business operations</p>
</div>

<!-- Top Metrics Row -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="admin-dashboard-card" style="border-left: 4px solid #667eea;">
            <div class="d-flex align-items-center">
                <div class="admin-stat-icon text-white me-3" style="background-color: #667eea !important;">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div>
                    <h3 class="mb-0 text-dark"><?php echo formatCurrency($todayStats['total_sales']); ?></h3>
                    <p class="text-muted mb-0">Today's Sales</p>
                    <small class="text-primary"><?= $todayStats['order_count'] ?> orders</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="admin-dashboard-card" style="border-left: 4px solid #f093fb;">
            <div class="d-flex align-items-center">
                <div class="admin-stat-icon text-white me-3" style="background-color: #f093fb !important;">
                    <i class="fas fa-calendar-week"></i>
                </div>
                <div>
                    <h3 class="mb-0 text-dark"><?php echo formatCurrency($weekStats['total_sales']); ?></h3>
                    <p class="text-muted mb-0">This Week</p>
                    <small class="text-info"><?= $weekStats['order_count'] ?> orders</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="admin-dashboard-card" style="border-left: 4px solid #17a2b8;">
            <div class="d-flex align-items-center">
                <div class="admin-stat-icon bg-info text-white me-3">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div>
                    <h3 class="mb-0 text-dark"><?php echo formatCurrency($monthStats['total_sales']); ?></h3>
                    <p class="text-muted mb-0">This Month</p>
                    <small class="text-info"><?= $monthStats['order_count'] ?> orders</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="admin-dashboard-card" style="border-left: 4px solid #28a745;">
            <div class="d-flex align-items-center">
                <div class="admin-stat-icon bg-success text-white me-3">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div>
                    <h3 class="mb-0 text-dark"><?php echo formatCurrency($stats['total_sales']); ?></h3>
                    <p class="text-muted mb-0">Total Revenue</p>
                    <small class="text-success">All time</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inventory & Orders Stats -->
<div class="row mb-4">
    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
        <div class="admin-dashboard-card" style="border-left: 4px solid #0d6efd;">
            <div class="text-center">
                <div class="admin-stat-icon bg-primary text-white mx-auto mb-2">
                    <i class="fas fa-boxes"></i>
                </div>
                <h3 class="mb-0"><?= $inventoryStats['total_products'] ?></h3>
                <small class="text-muted">Total Products</small>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
        <div class="admin-dashboard-card" style="border-left: 4px solid #28a745;">
            <div class="text-center">
                <div class="admin-stat-icon bg-success text-white mx-auto mb-2">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="mb-0"><?= $inventoryStats['in_stock'] ?></h3>
                <small class="text-muted">In Stock</small>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
        <div class="admin-dashboard-card" style="border-left: 4px solid #ffc107;">
            <div class="text-center">
                <div class="admin-stat-icon bg-warning text-white mx-auto mb-2">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="mb-0"><?= $inventoryStats['low_stock'] ?></h3>
                <small class="text-muted">Low Stock</small>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
        <div class="admin-dashboard-card" style="border-left: 4px solid #dc3545;">
            <div class="text-center">
                <div class="admin-stat-icon bg-danger text-white mx-auto mb-2">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h3 class="mb-0"><?= $inventoryStats['out_of_stock'] ?></h3>
                <small class="text-muted">Out of Stock</small>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
        <div class="admin-dashboard-card" style="border-left: 4px solid #6f42c1;">
            <div class="text-center">
                <div class="admin-stat-icon bg-purple text-white mx-auto mb-2" style="background-color: #6f42c1 !important;">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3 class="mb-0"><?= $stats['total_orders'] ?></h3>
                <small class="text-muted">Total Orders</small>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
        <div class="admin-dashboard-card" style="border-left: 4px solid #17a2b8;">
            <div class="text-center">
                <div class="admin-stat-icon bg-info text-white mx-auto mb-2">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="mb-0"><?= $stats['active_users'] ?? 0 ?></h3>
                <small class="text-muted">Active Users</small>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <!-- Sales Trend Chart -->
    <div class="col-xl-8 mb-3">
        <div class="admin-dashboard-card">
            <h5 class="admin-section-title"><i class="fas fa-chart-line me-2"></i>Sales Trend (Last 7 Days)</h5>
            <div class="admin-chart-container">
                <canvas id="adminSalesTrendChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Payment Methods Chart -->
    <div class="col-xl-4 mb-3">
        <div class="admin-dashboard-card">
            <h5 class="admin-section-title"><i class="fas fa-credit-card me-2"></i>Payment Distribution</h5>
            <div class="admin-chart-container" style="height: 250px;">
                <canvas id="adminPaymentMethodChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Content Row -->
<div class="row mb-4">
    <!-- Recent Orders -->
    <div class="col-xl-5 mb-3">
        <div class="admin-dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="admin-section-title mb-0"><i class="fas fa-history me-2"></i>Recent Orders</h5>
                <a href="transactions.php" class="btn btn-outline-danger btn-sm">View All</a>
            </div>
            <?php if (empty($recentOrders)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No recent orders found</p>
                </div>
            <?php else: ?>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover table-sm">
                        <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th>Order #</th>
                                <th>Staff</th>
                                <th>Amount</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><strong class="text-danger" style="font-size: 0.85rem;"><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                <td><small><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></small></td>
                                <td><strong style="font-size: 0.85rem;"><?php echo formatCurrency($order['total_amount']); ?></strong></td>
                                <td><small class="text-muted"><?php echo Layout::getTimeAgo($order['created_at']); ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Low Stock Alert -->
    <div class="col-xl-4 mb-3">
        <div class="admin-dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="admin-section-title mb-0"><i class="fas fa-exclamation-circle me-2"></i>Low Stock Alert</h5>
                <span class="badge bg-danger"><?php echo count($lowStockProducts); ?> Items</span>
            </div>
            <?php if (empty($lowStockProducts)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="text-muted">All items are well stocked</p>
                </div>
            <?php else: ?>
                <div style="max-height: 350px; overflow-y: auto;">
                    <?php foreach (array_slice($lowStockProducts, 0, 8) as $product): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($product['name']); ?></div>
                            <small class="text-muted">Stock: <?php echo $product['stock_quantity']; ?></small>
                        </div>
                        <span class="badge bg-danger"><?php echo $product['stock_quantity']; ?> left</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="inventory.php" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-boxes me-1"></i>Manage Inventory
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Top Products -->
    <div class="col-xl-3 mb-3">
        <div class="admin-dashboard-card">
            <h5 class="admin-section-title"><i class="fas fa-fire me-2"></i>Top 3 Products</h5>
            <?php if (empty($topProducts)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-box-open fa-2x text-muted mb-2"></i>
                    <p class="text-muted mb-0">No sales data</p>
                </div>
            <?php else: ?>
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php foreach (array_slice($topProducts, 0, 3) as $index => $product): ?>
                    <div class="mb-3 p-3 bg-light rounded">
                        <div class="d-flex align-items-center mb-2">
                            <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px; font-size: 1rem; font-weight: bold;">
                                <?= $index + 1 ?>
                            </div>
                            <div class="fw-bold"><?php echo htmlspecialchars($product['name']); ?></div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted"><i class="fas fa-box me-1"></i><?= $product['total_sold'] ?> sold</small>
                            <div class="fw-bold text-success"><?php echo formatCurrency($product['revenue']); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<script>
// Sales Trend Chart
const adminSalesCtx = document.getElementById('adminSalesTrendChart');
new Chart(adminSalesCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($salesDates); ?>,
        datasets: [{
            label: 'Sales (₱)',
            data: <?php echo json_encode($salesAmounts); ?>,
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.4,
            fill: true,
            yAxisID: 'y'
        }, {
            label: 'Orders',
            data: <?php echo json_encode($salesOrders); ?>,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            tension: 0.4,
            fill: true,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Sales (₱)'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Orders'
                },
                grid: {
                    drawOnChartArea: false
                }
            }
        }
    }
});

// Payment Method Chart
const adminPaymentCtx = document.getElementById('adminPaymentMethodChart');
new Chart(adminPaymentCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_map(function($pm) { return ucfirst($pm['payment_method']); }, $paymentMethods)); ?>,
        datasets: [{
            data: <?php echo json_encode(array_map(function($pm) { return $pm['count']; }, $paymentMethods)); ?>,
            backgroundColor: [
                '#28a745',
                '#17a2b8',
                '#ffc107',
                '#dc3545'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom'
            }
        }
    }
});
</script>





<!-- Quick Actions Row -->


<?php
$content = ob_get_clean();
include 'layout.php';
?>
