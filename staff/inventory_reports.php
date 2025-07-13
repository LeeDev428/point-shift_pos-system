<?php
require_once '../config.php';
User::requireLogin();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../login.php');
    exit();
}

$db = Database::getInstance()->getConnection();
$title = 'Inventory Reports';
$reports = $db->query("SELECT r.*, p.name, p.sku FROM inventory_reports r JOIN products p ON r.product_id = p.id ORDER BY r.date DESC")->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<div class="container py-4">
    <h2>Inventory Reports</h2>
    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>Date</th><th>Product</th><th>SKU</th><th>Change</th><th>Quantity</th><th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reports as $r): ?>
            <tr>
                <td><?=$r['date']?></td>
                <td><?=htmlspecialchars($r['name'])?></td>
                <td><?=htmlspecialchars($r['sku'] ?? '')?></td>
                <td><?=$r['change_type']?></td>
                <td><?=$r['quantity']?></td>
                <td><?=htmlspecialchars($r['remarks'])?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
?>