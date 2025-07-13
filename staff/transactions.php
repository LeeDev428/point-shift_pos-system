<?php
require_once '../config.php';
User::requireLogin();

if (User::isAdmin()) {
    header('Location: ../sales_analysis.php');
    exit();
}

$dashboardController = new DashboardController();
$orders = $dashboardController->getRecentOrders(20);

$title = 'Staff Transaction History';

ob_start();
?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0">
        <h5 class="mb-0">Transaction History</h5>
    </div>
    <div class="card-body">
        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="fas fa-receipt fa-4x text-muted mb-3"></i>
                <h4>No Transactions Found</h4>
                <p class="text-muted">Start making sales to see transaction history here.</p>
                <a href="pos.php" class="btn btn-danger">
                    <i class="fas fa-shopping-cart me-2"></i>Start New Sale
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Date & Time</th>
                            <th>Staff</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong class="text-danger"><?php echo htmlspecialchars($order['order_number']); ?></strong>
                            </td>
                            <td>
                                <div><?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                                <small class="text-muted"><?php echo date('g:i A', strtotime($order['created_at'])); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                            <td>
                                <strong><?php echo formatCurrency($order['total_amount']); ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $order['status'] == 'completed' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function viewOrderDetails(orderId) {
    alert('Order details feature will be implemented. Order ID: ' + orderId);
}
</script>

<?php
$content = ob_get_clean();
include 'views/layout.php';
?>
