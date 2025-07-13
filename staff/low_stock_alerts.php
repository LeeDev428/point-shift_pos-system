<?php
require_once '../config.php';
User::requireLogin();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../login.php');
    exit();
}

$title = 'Low Stock Alerts';
$db = Database::getInstance()->getConnection();
$products = $db->query("SELECT * FROM products WHERE stock_quantity < low_stock_threshold")->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<div class="container py-4">
    <h2>Low Stock Alerts</h2>
    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>Name</th><th>SKU</th><th>Barcode</th><th>Current Stock</th><th>Low Stock Threshold</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td><?=htmlspecialchars($p['name'] ?? '')?></td>
                <td><?=htmlspecialchars($p['sku'] ?? '')?></td>
                <td><?=htmlspecialchars($p['barcode'] ?? '')?></td>
                <td><?=$p['stock_quantity']?></td>
                <td><?=$p['low_stock_threshold']?></td>
                <td><span class="text-danger">Low</span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
?>