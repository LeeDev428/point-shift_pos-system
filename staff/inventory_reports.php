<?php
require_once '../config.php';
User::requireLogin();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../login.php');
    exit();
}


$db = Database::getInstance()->getConnection();
$title = 'Inventory Reports';

// Handle create report form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_report'])) {
    $date = $_POST['date'] ?? date('Y-m-d');
    $product_id = $_POST['product_id'] ?? null;
    $change_type = $_POST['change_type'] ?? '';
    $quantity = $_POST['quantity'] ?? 0;
    $remarks = $_POST['remarks'] ?? '';
    if ($product_id && $change_type && $quantity > 0) {
        $stmt = $db->prepare("INSERT INTO inventory_reports (date, product_id, change_type, quantity, remarks) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$date, $product_id, $change_type, $quantity, $remarks]);
        // Optionally, you can add a success message here
        header('Location: inventory_reports.php');
        exit();
    }
}

$reports = $db->query("SELECT r.*, p.name, p.sku FROM inventory_reports r JOIN products p ON r.product_id = p.id ORDER BY r.date DESC")->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<div class="container py-4">
    <h2>Inventory Reports</h2>
    <!-- Create Report Button -->
    <button type="button" class="btn btn-danger mb-3" data-bs-toggle="modal" data-bs-target="#createReportModal">
        <i class="fas fa-plus"></i> Create Report
    </button>

    <!-- Create Report Modal -->
    <div class="modal fade" id="createReportModal" tabindex="-1" aria-labelledby="createReportModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="POST" id="reportForm">
            <div class="modal-header">
              <h5 class="modal-title" id="createReportModalLabel">Create Inventory Report</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" name="date" id="date" required class="form-control" value="<?=date('Y-m-d')?>">
              </div>
              <div class="mb-3">
                <label for="product_id" class="form-label">Product</label>
                <select name="product_id" id="product_id" required class="form-select">
                  <option value="">Select Product</option>
                  <?php $products = $db->query("SELECT id, name FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC); ?>
                  <?php foreach ($products as $p): ?>
                    <option value="<?=$p['id']?>"><?=htmlspecialchars($p['name'])?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label for="change_type" class="form-label">Change</label>
                <select name="change_type" id="change_type" required class="form-select">
                  <option value="Added">Added</option>
                  <option value="Removed">Removed</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="quantity" class="form-label">Quantity</label>
                <input type="number" name="quantity" id="quantity" required class="form-control" min="1">
              </div>
              <div class="mb-3">
                <label for="remarks" class="form-label">Remarks</label>
                <input type="text" name="remarks" id="remarks" class="form-control" placeholder="Remarks">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" name="create_report" class="btn btn-danger">Create Report</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Inventory Reports Table -->
    <div class="card p-3" style="border-radius: 12px;">
      <table class="table table-bordered table-sm mb-0">
        <thead>
          <tr>
            <th class="text-danger">Date</th>
            <th class="text-danger">Product</th>
            <th class="text-danger">Change</th>
            <th class="text-danger">Quantity</th>
            <th class="text-danger">Remarks</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reports as $r): ?>
          <tr>
            <td><?=$r['date']?></td>
            <td><?=htmlspecialchars($r['name'])?></td>
            <td><?=$r['change_type']?></td>
            <td><?=$r['quantity']?></td>
            <td><?=htmlspecialchars($r['remarks'])?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
?>