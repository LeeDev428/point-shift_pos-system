<?php
require_once '../config.php';
User::requireLogin();

if (User::isAdmin()) {
    header('Location: ../sales_analysis.php');
    exit();
}

$dashboardController = new DashboardController();
$stats = $dashboardController->getStats();

$title = 'Daily Sales';

ob_start();
?>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <i class="fas fa-shopping-cart fa-2x text-danger mb-2"></i>
                <h4><?php echo $stats['total_orders']; ?></h4>
                <small class="text-muted">Total Orders Today</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
                <h4><?php echo formatCurrency($stats['total_sales']); ?></h4>
                <small class="text-muted">Total Sales Today</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                <h4><?php echo $stats['total_orders'] > 0 ? formatCurrency($stats['total_sales'] / $stats['total_orders']) : '₱0.00'; ?></h4>
                <small class="text-muted">Average Order Value</small>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0">
        <h5 class="mb-0">Sales Report</h5>
    </div>
    <div class="card-body">
        <div class="text-center py-5">
            <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
            <h4>Sales Analytics</h4>
            <p class="text-muted">Detailed sales reports and charts will be available here.</p>
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-day fa-2x text-danger mb-2"></i>
                            <h6>Daily Reports</h6>
                            <p class="small text-muted">View today's sales performance</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-pie fa-2x text-warning mb-2"></i>
                            <h6>Product Analysis</h6>
                            <p class="small text-muted">Top selling products today</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center">
                            <i class="fas fa-clock fa-2x text-success mb-2"></i>
                            <h6>Hourly Trends</h6>
                            <p class="small text-muted">Peak sales hours analysis</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'views/layout.php';
?>
