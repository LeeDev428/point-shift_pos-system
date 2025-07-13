<?php
require_once '../config.php';
User::requireLogin();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cashier') {
    header('Location: ../login.php');
    exit();
}
$title = 'Transaction History';
$db = Database::getInstance()->getConnection();
$transactions = $db->query("SELECT o.*, u.username, o.created_at as date FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
$details = [];
if (isset($_GET['details'])) {
    $tid = intval($_GET['details']);
    $details = $db->query("SELECT oi.*, p.name, oi.unit_price as price FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $tid")->fetchAll(PDO::FETCH_ASSOC);
}
ob_start();
?>
<div class="container py-4">
    <h2>Transaction History</h2>
    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>Date</th><th>Cashier</th><th>Total Amount</th><th>Details</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $t): ?>
            <tr>
                <td><?=htmlspecialchars($t['date'])?></td>
                <td><?=htmlspecialchars($t['username'] ?? '')?></td>
                <td>₱<?=number_format($t['total_amount'],2)?></td>
                <td><a href="?details=<?=$t['id']?>" class="btn btn-info btn-sm">View</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($details): ?>
    <div class="mt-4">
        <h5>Transaction Details</h5>
        <table class="table table-bordered table-sm">
            <thead><tr><th>Product</th><th>Quantity</th><th>Price</th></tr></thead>
            <tbody>
                <?php foreach ($details as $d): ?>
                <tr>
                    <td><?=htmlspecialchars($d['name'])?></td>
                    <td><?=$d['quantity']?></td>
                    <td>₱<?=number_format($d['price'],2)?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
include 'views/layout.php';
