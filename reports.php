<?php
require_once 'config.php';
requireAdmin(); // Only admin can view inventory reports

$page_title = 'Inventory Stock Reports';

// Get date range from request or default to last 7 days
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$filter_type = $_GET['filter_type'] ?? 'all'; // all, recent, staff, admin

// Database connection
$db = Database::getInstance()->getConnection();

// Get inventory change reports with user information
function getInventoryReports($db, $start_date, $end_date, $filter_type) {
    $sql = "
        SELECT 
            ir.id,
            ir.date,
            ir.product_id,
            ir.change_type,
            ir.quantity as changed_quantity,
            ir.remarks,
            ir.user_id,
            ir.created_at,
            p.name as product_name,
            p.sku,
            p.stock_quantity as current_stock,
            p.category_id,
            c.name as category_name,
            u.username,
            u.first_name,
            u.last_name,
            u.role,
            CASE 
                WHEN ir.created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR) THEN 1
                ELSE 0
            END as is_recent
        FROM inventory_reports ir
        LEFT JOIN products p ON ir.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON ir.user_id = u.id
        WHERE ir.date BETWEEN ? AND ?
    ";
    
    $params = [$start_date, $end_date];
    
    // Filter by type
    if ($filter_type === 'recent') {
        $sql .= " AND ir.created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)";
    } elseif ($filter_type === 'staff') {
        $sql .= " AND u.role = 'staff'";
    } elseif ($filter_type === 'admin') {
        $sql .= " AND u.role = 'admin'";
    }
    
    $sql .= " ORDER BY ir.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get summary statistics
function getInventoryStats($db, $start_date, $end_date) {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_changes,
            SUM(CASE WHEN change_type = 'Added' THEN quantity ELSE 0 END) as total_added,
            SUM(CASE WHEN change_type = 'Removed' THEN quantity ELSE 0 END) as total_removed,
            COUNT(DISTINCT product_id) as products_affected,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR) THEN 1 END) as recent_changes
        FROM inventory_reports
        WHERE date BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get data
$reports = getInventoryReports($db, $start_date, $end_date, $filter_type);
$stats = getInventoryStats($db, $start_date, $end_date);

ob_start();
?>

<style>
.stat-card {
    border-radius: 12px;
    padding: 1.5rem;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-left: 4px solid;
    transition: transform 0.2s;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
}
.badge-new {
    animation: pulse 2s infinite;
    font-weight: bold;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}
.report-table th {
    background: #f8f9fa;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    color: #495057;
}
.report-table td {
    vertical-align: middle;
}
.change-added {
    color: #28a745;
    font-weight: 600;
}
.change-removed {
    color: #dc3545;
    font-weight: 600;
}
</style>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="stat-card" style="border-left-color: #0d6efd;">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-primary text-white me-3">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo number_format($stats['total_changes'] ?? 0); ?></h3>
                    <p class="text-muted mb-0">Total Changes</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="stat-card" style="border-left-color: #28a745;">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-success text-white me-3">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo number_format($stats['total_added'] ?? 0); ?></h3>
                    <p class="text-muted mb-0">Stock Added</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="stat-card" style="border-left-color: #dc3545;">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-danger text-white me-3">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo number_format($stats['total_removed'] ?? 0); ?></h3>
                    <p class="text-muted mb-0">Stock Removed</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="stat-card" style="border-left-color: #ffc107;">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-warning text-white me-3">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo number_format($stats['recent_changes'] ?? 0); ?></h3>
                    <p class="text-muted mb-0">Recent (48hrs)</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Report Filters</h5>
                <a href="?start_date=<?php echo date('Y-m-d', strtotime('-7 days')); ?>&end_date=<?php echo date('Y-m-d'); ?>&filter_type=all" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-redo me-1"></i>Reset
                </a>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Filter By</label>
                        <select name="filter_type" class="form-select">
                            <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Changes</option>
                            <option value="recent" <?php echo $filter_type === 'recent' ? 'selected' : ''; ?>>Recent Only (48hrs)</option>
                            <option value="staff" <?php echo $filter_type === 'staff' ? 'selected' : ''; ?>>Staff Changes</option>
                            <option value="admin" <?php echo $filter_type === 'admin' ? 'selected' : ''; ?>>Admin Changes</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i>Apply Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Inventory Reports Table -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Inventory Stock Changes</h5>
                <button class="btn btn-success btn-sm" onclick="window.print()">
                    <i class="fas fa-print me-1"></i>Print Report
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($reports)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <p class="text-muted">No inventory changes found for the selected period</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover report-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Change Type</th>
                                <th>Quantity Changed</th>
                                <th>Current Stock</th>
                                <th>Updated By</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?php echo date('M d, Y', strtotime($report['date'])); ?></div>
                                    <small class="text-muted"><?php echo date('h:i A', strtotime($report['created_at'] ?? $report['date'])); ?></small>
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($report['product_name']); ?></div>
                                    <small class="text-muted">SKU: <?php echo htmlspecialchars($report['sku'] ?? 'N/A'); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($report['category_name'] ?? 'N/A'); ?></span>
                                </td>
                                <td>
                                    <?php if ($report['change_type'] === 'Added'): ?>
                                        <span class="badge bg-success"><i class="fas fa-plus me-1"></i>Added</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="fas fa-minus me-1"></i>Removed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="<?php echo $report['change_type'] === 'Added' ? 'change-added' : 'change-removed'; ?>">
                                    <?php echo $report['change_type'] === 'Added' ? '+' : '-'; ?><?php echo number_format($report['changed_quantity']); ?>
                                </td>
                                <td>
                                    <strong><?php echo number_format($report['current_stock']); ?></strong>
                                </td>
                                <td>
                                    <div>
                                        <?php 
                                        $full_name = trim($report['first_name'] . ' ' . $report['last_name']);
                                        echo htmlspecialchars($full_name ?: $report['username'] ?: 'Unknown'); 
                                        ?>
                                    </div>
                                    <?php if ($report['role'] === 'admin'): ?>
                                        <span class="badge bg-danger" style="font-size: 0.7rem;">ADMIN</span>
                                    <?php elseif ($report['role'] === 'staff'): ?>
                                        <span class="badge bg-primary" style="font-size: 0.7rem;">STAFF</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary" style="font-size: 0.7rem;">CASHIER</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($report['is_recent']): ?>
                                        <span class="badge bg-warning badge-new">
                                            <i class="fas fa-star me-1"></i>NEW
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Past</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($report['remarks'] ?: '-'); ?></small>
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
</div>

<!-- Export Options -->
<div class="row mt-3">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Note:</strong> The "NEW" badge indicates stock changes made within the last 48 hours. 
            Reports show stock additions and removals made by staff and admins.
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'layout.php';
?>
