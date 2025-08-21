<?php
require_once 'config.php';
requireLogin();

$page_title = 'Sales Reports & Analytics';

// Get date range from request or default to last 30 days
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Database connection
$db = Database::getInstance()->getConnection();

// Get sales summary
function getSalesSummary($db, $start_date, $end_date) {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_sales,
            AVG(total_amount) as avg_order_value,
            SUM(COALESCE(subtotal, total_amount * 0.8929)) as net_sales,
            SUM(COALESCE(tax_amount, total_amount * 0.1071)) as total_tax
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ? 
        AND status = 'completed'
    ");
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get daily sales data for chart
function getDailySales($db, $start_date, $end_date) {
    $stmt = $db->prepare("
        SELECT 
            DATE(created_at) as sale_date,
            COUNT(*) as orders_count,
            SUM(total_amount) as daily_total
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ? 
        AND status = 'completed'
        GROUP BY DATE(created_at)
        ORDER BY sale_date ASC
    ");
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get top selling products
function getTopProducts($db, $start_date, $end_date) {
    $stmt = $db->prepare("
        SELECT 
            p.name,
            p.price as selling_price,
            SUM(oi.quantity) as total_sold,
            SUM(oi.total_price) as total_revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.created_at) BETWEEN ? AND ? 
        AND o.status = 'completed'
        GROUP BY p.id, p.name, p.price
        ORDER BY total_sold DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get hourly sales pattern
function getHourlySales($db, $start_date, $end_date) {
    $stmt = $db->prepare("
        SELECT 
            HOUR(created_at) as hour,
            COUNT(*) as orders_count,
            SUM(total_amount) as hourly_total
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ? 
        AND status = 'completed'
        GROUP BY HOUR(created_at)
        ORDER BY hour ASC
    ");
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get data
$summary = getSalesSummary($db, $start_date, $end_date);
$dailySales = getDailySales($db, $start_date, $end_date);
$topProducts = getTopProducts($db, $start_date, $end_date);
$hourlySales = getHourlySales($db, $start_date, $end_date);

ob_start();
?>

<!-- Date Range Filter -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header">
                <h5 class="mb-0">Report Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-custom me-2">
                            <i class="fas fa-filter me-2"></i>Apply Filter
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-custom" onclick="printReport()">
                            <i class="fas fa-print me-2"></i>Print Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-success me-3">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo formatCurrency($summary['total_sales'] ?? 0); ?></h3>
                    <p class="text-muted mb-0">Total Sales</p>
                    <small class="text-success">Period: <?php echo date('M j', strtotime($start_date)) . ' - ' . date('M j, Y', strtotime($end_date)); ?></small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-info me-3">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo number_format($summary['total_orders'] ?? 0); ?></h3>
                    <p class="text-muted mb-0">Total Orders</p>
                    <small class="text-info">Completed transactions</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-warning me-3">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo formatCurrency($summary['avg_order_value'] ?? 0); ?></h3>
                    <p class="text-muted mb-0">Avg Order Value</p>
                    <small class="text-warning">Per transaction</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stats-card">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-danger me-3">
                    <i class="fas fa-receipt"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo formatCurrency($summary['total_tax'] ?? 0); ?></h3>
                    <p class="text-muted mb-0">Total Tax</p>
                    <small class="text-danger">Tax collected</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <!-- Daily Sales Chart -->
    <div class="col-lg-8 mb-4">
        <div class="content-card">
            <div class="card-header">
                <h5 class="mb-0">Daily Sales Trend</h5>
            </div>
            <div class="card-body">
                <canvas id="dailySalesChart" style="height: 300px;"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Hourly Sales Pattern -->
    <div class="col-lg-4 mb-4">
        <div class="content-card">
            <div class="card-header">
                <h5 class="mb-0">Hourly Sales Pattern</h5>
            </div>
            <div class="card-body">
                <canvas id="hourlySalesChart" style="height: 300px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Top Products Table -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header">
                <h5 class="mb-0">Top Selling Products</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Product Name</th>
                                <th>Unit Price</th>
                                <th>Qty Sold</th>
                                <th>Total Revenue</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topProducts)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle me-2"></i>No sales data found for the selected period
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($topProducts as $index => $product): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary">#<?php echo $index + 1; ?></span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        </td>
                                        <td><?php echo formatCurrency($product['selling_price']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo number_format($product['total_sold']); ?></span>
                                        </td>
                                        <td>
                                            <strong class="text-success"><?php echo formatCurrency($product['total_revenue']); ?></strong>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 8px;">
                                                <?php 
                                                $maxRevenue = $topProducts[0]['total_revenue'] ?? 1;
                                                $percentage = ($product['total_revenue'] / $maxRevenue) * 100;
                                                ?>
                                                <div class="progress-bar bg-success" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?php echo number_format($percentage, 1); ?>%</small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Daily Sales Chart
const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
const dailySalesData = <?php echo json_encode($dailySales); ?>;

new Chart(dailySalesCtx, {
    type: 'line',
    data: {
        labels: dailySalesData.map(item => {
            const date = new Date(item.sale_date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }),
        datasets: [{
            label: 'Daily Sales (₱)',
            data: dailySalesData.map(item => parseFloat(item.daily_total || 0)),
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Orders Count',
            data: dailySalesData.map(item => parseInt(item.orders_count || 0)),
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Sales Amount (₱)'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Number of Orders'
                },
                grid: {
                    drawOnChartArea: false,
                },
            }
        },
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        }
    }
});

// Hourly Sales Chart
const hourlySalesCtx = document.getElementById('hourlySalesChart').getContext('2d');
const hourlySalesData = <?php echo json_encode($hourlySales); ?>;

// Create 24-hour array
const hourlyData = Array(24).fill(0);
hourlySalesData.forEach(item => {
    hourlyData[parseInt(item.hour)] = parseFloat(item.hourly_total || 0);
});

new Chart(hourlySalesCtx, {
    type: 'bar',
    data: {
        labels: Array.from({length: 24}, (_, i) => {
            const hour = i.toString().padStart(2, '0');
            return `${hour}:00`;
        }),
        datasets: [{
            label: 'Sales by Hour (₱)',
            data: hourlyData,
            backgroundColor: 'rgba(220, 53, 69, 0.8)',
            borderColor: '#dc3545',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Sales Amount (₱)'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Hour of Day'
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Print Report Function
function printReport() {
    window.print();
}

// Print styles
const printStyles = `
    <style media="print">
        .btn, .card-header .btn { display: none !important; }
        .content-card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
        .stats-card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
        .sidebar { display: none !important; }
        .main-content { margin-left: 0 !important; }
        .top-navbar { display: none !important; }
        body { font-size: 12px !important; }
        .container-fluid { padding: 0 !important; }
        @page { margin: 1cm; }
    </style>
`;
document.head.insertAdjacentHTML('beforeend', printStyles);
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>
