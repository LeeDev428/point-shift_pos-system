<?php
require_once '../config.php';
User::requireLogin();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../login.php');
    exit();
}
// Set page title for layout
$title = 'Barcode Scanner';
$db = Database::getInstance()->getConnection();
$product = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'];
    $stmt = $db->prepare("SELECT * FROM products WHERE barcode = ?");
    $stmt->execute([$code]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}

ob_start();
?>
<div class="container py-4">
    <h2>Barcode Scanner</h2>
    <form method="POST" class="mb-3">
        <input type="text" name="code" placeholder="Enter Barcode or SKU" required class="form-control d-inline w-auto" style="width:250px;">
        <button type="submit" class="btn btn-primary">Scan</button>
    </form>
    <?php if ($product): ?>
        <div class="alert alert-success">
            <strong><?=htmlspecialchars($product['name'] ?? '')?></strong><br>
            SKU: <?=htmlspecialchars($product['sku'] ?? '')?><br>
            Category: <?=htmlspecialchars($product['category_id'] ?? '')?><br>
            Barcode: <?=htmlspecialchars($product['barcode'] ?? '')?><br>
            Price: ₱<?=number_format($product['price'] ?? 0,2)?><br>
            Stock: <?=$product['stock_quantity'] ?? 0?><br>
            Low Stock Threshold: <?=$product['low_stock_threshold'] ?? ''?><br>
            Description: <?=htmlspecialchars($product['description'] ?? '')?><br>
            Status: <?=htmlspecialchars($product['status'] ?? '')?><br>
        </div>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="alert alert-danger">Product not found.</div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
?>