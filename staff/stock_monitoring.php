<?php
require_once '../config.php';
User::requireLogin();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../login.php');
    exit();
}
$db = Database::getInstance()->getConnection();
$products = $db->query("SELECT * FROM products")->fetchAll(PDO::FETCH_ASSOC);
$title = 'Stock Monitoring';
ob_start();
?>
<div class="container py-4">
    <h2>Stock Monitoring</h2>
    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>Name</th><th>SKU</th><th>Barcode</th><th>Category ID</th><th>Stock</th><th>Low Stock Threshold</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td><?=htmlspecialchars($p['name'] ?? '')?></td>
                <td><?=htmlspecialchars($p['sku'] ?? '')?></td>
                <td><?=htmlspecialchars($p['barcode'] ?? '')?></td>
                <td><?=$p['category_id']?></td>
                <td><?=$p['stock_quantity']?></td>
                <td><?=$p['low_stock_threshold']?></td>
                <td>
                    <?php if (($p['stock_quantity'] ?? 0) < ($p['low_stock_threshold'] ?? 0)): ?>
                        <span class="text-danger">Low</span>
                    <?php else: ?>
                        <span class="text-success">In Stock</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
?>