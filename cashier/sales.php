<?php
require_once '../config.php';
User::requireLogin();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cashier') {
    header('Location: ../login.php');
    exit();
}
$title = 'Daily Sales';
$db = Database::getInstance()->getConnection();
$sales = $db->query("SELECT DATE(created_at) as sale_date, SUM(total_amount) as total FROM orders WHERE status = 'completed' GROUP BY sale_date ORDER BY sale_date DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
ob_start();
?>
<div class="container py-4">
    <h2>Daily Sales</h2>
    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>Date</th><th>Total Sales</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sales as $s): ?>
            <tr>
                <td><?=htmlspecialchars($s['sale_date'])?></td>
                <td>₱<?=number_format($s['total'],2)?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
