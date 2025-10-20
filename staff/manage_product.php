<?php
require_once '../config.php';
User::requireLogin();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../login.php');
    exit();
}
// Get DB connection
$db = Database::getInstance()->getConnection();
// Fetch categories for dropdown
$categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// Pagination setup
$perPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// Build WHERE clause
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter > 0) {
    $whereConditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Count total products with filters
$countStmt = $db->prepare("SELECT COUNT(*) FROM products p $whereClause");
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Add product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $expiry = !empty($_POST['expiry']) ? $_POST['expiry'] : null;
    $stmt = $db->prepare("INSERT INTO products (name, sku, category_id, price, stock_quantity, low_stock_threshold, barcode, expiry, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['name'], $_POST['sku'], $_POST['category_id'], $_POST['price'], $_POST['stock_quantity'], $_POST['low_stock_threshold'],
        $_POST['barcode'], $expiry, $_POST['status']
    ]);
    echo '<script>document.addEventListener("DOMContentLoaded",function(){
        document.getElementById("productForm").reset();
        document.getElementById("product_id").value = "";
        document.getElementById("modalAddBtn").classList.remove("d-none");
        document.getElementById("modalUpdateBtn").classList.add("d-none");
    });</script>';
    header('Location: manage_product.php');
    exit();
}

// Update product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $product_id = intval($_POST['id']);
    $new_stock = intval($_POST['stock_quantity']);
    $user_id = $_SESSION['user_id'];
    
    // Get old stock quantity to track changes
    $oldStockStmt = $db->prepare("SELECT stock_quantity, name FROM products WHERE id = ?");
    $oldStockStmt->execute([$product_id]);
    $oldProduct = $oldStockStmt->fetch(PDO::FETCH_ASSOC);
    $old_stock = intval($oldProduct['stock_quantity']);
    $product_name = $oldProduct['name'];
    
    // Calculate stock change
    $stock_change = $new_stock - $old_stock;
    
    // Update product
    $expiry = !empty($_POST['expiry']) ? $_POST['expiry'] : null;
    $stmt = $db->prepare("UPDATE products SET name=?, sku=?, category_id=?, price=?, stock_quantity=?, low_stock_threshold=?, barcode=?, expiry=?, status=?, last_updated_by=? WHERE id=?");
    $stmt->execute([
        $_POST['name'], $_POST['sku'], $_POST['category_id'], $_POST['price'], $new_stock, $_POST['low_stock_threshold'],
        $_POST['barcode'], $expiry, $_POST['status'], $user_id, $product_id
    ]);
    
    // Record inventory change in inventory_reports if stock quantity changed
    if ($stock_change != 0) {
        $change_type = ($stock_change > 0) ? 'Added' : 'Removed';
        $quantity_changed = abs($stock_change);
        $remarks = ($stock_change > 0) 
            ? "Stock added by staff. Previous: $old_stock, New: $new_stock" 
            : "Stock removed by staff. Previous: $old_stock, New: $new_stock";
        
        $reportStmt = $db->prepare("INSERT INTO inventory_reports (product_id, change_type, quantity, quantity_changed, previous_quantity, new_quantity, date, remarks, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, NOW())");
        $reportStmt->execute([
            $product_id,
            $change_type,
            $quantity_changed,  // Use same value for 'quantity' (legacy column)
            $quantity_changed,
            $old_stock,
            $new_stock,
            $remarks,
            $user_id
        ]);
    }
    
    header('Location: manage_product.php');
    exit();
}

// Delete product
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
}


// Statistics queries
$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'low_stock' => $db->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= low_stock_threshold AND stock_quantity > 0")->fetchColumn(),
    'out_of_stock' => $db->query("SELECT COUNT(*) FROM products WHERE stock_quantity = 0")->fetchColumn(),
    'total_value' => $db->query("SELECT SUM(price) FROM products")->fetchColumn(),
];

// Fetch products with filters
$productsStmt = $db->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $whereClause ORDER BY p.id DESC LIMIT $perPage OFFSET $offset");
$productsStmt->execute($params);
$products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

// Set page title for layout
$title = 'Inventory';
ob_start();
?>
<div class="container py-4" style="max-height: 90vh; overflow-y: auto;">
    <h2>Inventory</h2>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="card-title text-danger"><?=$stats['total']?></h3>
                    <p class="card-text">Total Products</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="card-title text-danger"><?=$stats['low_stock']?></h3>
                    <p class="card-text">Low Stock</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="card-title text-danger"><?=$stats['out_of_stock']?></h3>
                    <p class="card-text">Out of Stock</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="card-title text-danger">₱<?=number_format($stats['total_value'] ?? 0, 2)?></h3>
                    <p class="card-text">Total Value</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Add Button -->
    <button type="button" class="btn btn-success" style="position:fixed; bottom:30px; right:30px; z-index:1050; width:56px; height:56px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:2rem;" data-bs-toggle="modal" data-bs-target="#productModal" title="Add Product">
        <i class="fas fa-plus"></i>
    </button>

    <!-- Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form method="POST" id="productForm">
            <div class="modal-header">
              <h5 class="modal-title" id="productModalLabel">Add / Update Product</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="id" id="product_id">
              <div class="row g-3">
                <div class="col-md-4">
                  <label for="name" class="form-label">Product Name</label>
                  <input type="text" name="name" id="name" required class="form-control" placeholder="Product Name">
                </div>
                <div class="col-md-4">
                  <label for="sku" class="form-label">SKU</label>
                  <input type="text" name="sku" id="sku" required class="form-control" placeholder="SKU">
                </div>
                <div class="col-md-4">
                  <label for="categorySelect" class="form-label">Category</label>
                  <select name="category_id" required class="form-select" id="categorySelect">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                      <option value="<?=$cat['id']?>"><?=htmlspecialchars($cat['name'])?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label for="price" class="form-label">Price</label>
                  <input type="number" name="price" id="price" required class="form-control" step="0.01" placeholder="Price">
                </div>
                <div class="col-md-4">
                  <label for="stock_quantity" class="form-label">Quantity</label>
                  <input type="number" name="stock_quantity" id="stock_quantity" required class="form-control" placeholder="Quantity">
                </div>
                <div class="col-md-4">
                  <label for="low_stock_threshold" class="form-label">Low Stock Threshold</label>
                  <input type="number" name="low_stock_threshold" id="low_stock_threshold" required class="form-control" placeholder="Low Stock Threshold">
                </div>
                <div class="col-md-4">
                  <label for="barcode" class="form-label">Barcode</label>
                  <input type="text" name="barcode" id="barcode" required class="form-control" placeholder="Barcode">
                </div>
                <div class="col-md-4">
                  <label for="expiry" class="form-label">Expiry</label>
                  <input type="date" name="expiry" id="expiry" class="form-control" placeholder="Expiry">
                </div>
                <div class="col-md-4">
                  <label for="status" class="form-label">Status</label>
                  <input type="text" name="status" id="status" class="form-control" value="active" placeholder="Status">
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" name="add" class="btn btn-success" id="modalAddBtn">Add Product</button>
              <button type="submit" name="update" class="btn btn-primary d-none" id="modalUpdateBtn">Update Product</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Search and Filter Form -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search by Name, SKU or Barcode" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <select name="category_id" class="form-select">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                    <a href="manage_product.php" class="btn btn-secondary">
                        <i class="fas fa-redo me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>Name</th><th>SKU</th><th>Category</th><th>Price</th><th>Stock Qty</th><th>Low Stock Threshold</th><th>Barcode</th><th>Expiry</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td><?=htmlspecialchars($p['name'] ?? '')?></td>
                <td><?=htmlspecialchars($p['sku'] ?? '')?></td>
                <td><?=htmlspecialchars($p['category_name'] ?? '')?></td>
                <td>₱<?=number_format($p['price'],2)?></td>
                <td><?=$p['stock_quantity']?></td>
                <td><?=$p['low_stock_threshold']?></td>
                <td><?=htmlspecialchars($p['barcode'] ?? '')?></td>
                <td><?=htmlspecialchars($p['expiry'] ?? '')?></td>
                <!-- <td><?=htmlspecialchars($p['status'] ?? '')?></td> -->
                <td>
                    <button class="btn btn-primary btn-sm" onclick="editProduct(<?=htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8')?>)">Edit</button>
                    <a href="?delete=<?=$p['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <!-- Pagination -->
    <nav aria-label="Product pagination">
      <ul class="pagination justify-content-center mt-3">
        <?php 
        $queryParams = [];
        if (!empty($search)) $queryParams['search'] = $search;
        if ($category_filter > 0) $queryParams['category_id'] = $category_filter;
        
        for ($i = 1; $i <= $totalPages; $i++): 
            $queryParams['page'] = $i;
            $queryString = http_build_query($queryParams);
        ?>
          <li class="page-item<?=($i == $page ? ' active' : '')?>">
            <a class="page-link" href="?<?=$queryString?>"><?=$i?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>

    <script>
    // Make category dropdown searchable
    document.addEventListener('DOMContentLoaded', function() {
        var select = document.getElementById('categorySelect');
        if (select) {
            select.setAttribute('data-live-search', 'true');
            // For advanced search, use a JS library like select2 or bootstrap-select
            // Example: $('#categorySelect').select2();
        }
    });

    // Edit product handler
    function editProduct(product) {
        var modal = new bootstrap.Modal(document.getElementById('productModal'));
        setTimeout(function() {
            document.getElementById('product_id').value = product.id ?? '';
            document.getElementById('name').value = product.name ?? '';
            document.getElementById('sku').value = product.sku ?? '';
            document.getElementById('categorySelect').value = product.category_id ?? '';
            document.getElementById('price').value = product.price ?? '';
            document.getElementById('stock_quantity').value = product.stock_quantity ?? '';
            document.getElementById('low_stock_threshold').value = product.low_stock_threshold ?? '';
            document.getElementById('barcode').value = product.barcode ?? '';
            document.getElementById('expiry').value = product.expiry ?? '';
            document.getElementById('status').value = product.status ?? '';
            document.getElementById('modalAddBtn').classList.add('d-none');
            document.getElementById('modalUpdateBtn').classList.remove('d-none');
        }, 200);
        modal.show();
    }

    // Reset modal for add
    if (document.getElementById('productModal')) {
        document.getElementById('productModal').addEventListener('show.bs.modal', function (event) {
            if (!event.relatedTarget || event.relatedTarget.classList.contains('btn-primary')) {
                document.getElementById('productForm').reset();
                document.getElementById('product_id').value = '';
                document.getElementById('modalAddBtn').classList.remove('d-none');
                document.getElementById('modalUpdateBtn').classList.add('d-none');
            }
        });
    }
    </script>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
?>