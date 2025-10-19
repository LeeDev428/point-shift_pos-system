<?php
require_once 'config.php';
requireAdmin(); // Only admin can access inventory

$inventoryController = new InventoryController();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $inventoryController->addProduct($_POST);
                break;
            case 'update':
                $inventoryController->updateProduct($_POST['id'], $_POST);
                break;
            case 'delete':
                $inventoryController->deleteProduct($_POST['id']);
                break;
        }
        header('Location: inventory.php');
        exit();
    }
}

$search = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$stock_filter = $_GET['stock_filter'] ?? '';
$stats = $inventoryController->getStats();
$products = $inventoryController->getAllProducts($search, $category_id, $stock_filter);
$categories = $inventoryController->getAllCategories();

$page_title = 'Inventory Management';

ob_start();
?>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <a href="inventory.php" class="text-decoration-none">
            <div class="stats-card <?php echo empty($stock_filter) ? 'border-primary' : ''; ?>" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-primary me-3" style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: white;">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 text-dark"><?php echo $stats['total_products']; ?></h3>
                        <p class="text-muted mb-0">Total Products</p>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <a href="inventory.php?stock_filter=low_stock" class="text-decoration-none">
            <div class="stats-card <?php echo $stock_filter === 'low_stock' ? 'border-warning' : ''; ?>" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-warning me-3" style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: white;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 text-dark"><?php echo $stats['low_stock']; ?></h3>
                        <p class="text-muted mb-0">Low Stock</p>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <a href="inventory.php?stock_filter=out_of_stock" class="text-decoration-none">
            <div class="stats-card <?php echo $stock_filter === 'out_of_stock' ? 'border-danger' : ''; ?>" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-danger me-3" style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: white;">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 text-dark"><?php echo $stats['out_of_stock']; ?></h3>
                        <p class="text-muted mb-0">Out of Stock</p>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <a href="inventory.php?stock_filter=in_stock" class="text-decoration-none">
            <div class="stats-card <?php echo $stock_filter === 'in_stock' ? 'border-success' : ''; ?>" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-success me-3" style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: white;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 text-dark"><?php echo $stats['in_stock']; ?></h3>
                        <p class="text-muted mb-0">In Stock</p>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Inventory Management -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Inventory Management</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-danger btn-custom" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="fas fa-plus me-2"></i>Add
                    </button>
                    <button class="btn btn-outline-danger btn-custom">
                        <i class="fas fa-download me-2"></i>Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Search and Filter Bar -->
                <form method="GET" class="row mb-3">
                    <div class="col-md-5 mb-2">
                        <input type="text" name="search" class="form-control" placeholder="Search by Name, SKU or Barcode" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4 mb-2">
                        <select name="category_id" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button type="submit" class="btn btn-outline-danger me-2">
                            <i class="fas fa-search me-1"></i> Filter
                        </button>
                        <a href="inventory.php" class="btn btn-outline-secondary">
                            <i class="fas fa-redo me-1"></i> Reset
                        </a>
                    </div>
                </form>
                
                <!-- Products Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-danger">
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>SKU</th>
                                <th>Barcode</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No products found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                </td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></span></td>
                                <td>SKU-<?php echo str_pad($product['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($product['barcode']); ?></td>
                                <td><?php echo formatCurrency($product['price']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        if ($product['stock_quantity'] == 0) echo 'danger';
                                        elseif ($product['stock_quantity'] <= 10) echo 'warning';
                                        else echo 'success';
                                    ?>">
                                        <?php echo $product['stock_quantity']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($product['stock_quantity'] == 0): ?>
                                        <span class="badge bg-danger">Out of Stock</span>
                                    <?php elseif ($product['stock_quantity'] <= 10): ?>
                                        <span class="badge bg-warning">Low Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">In Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="number" name="price" class="form-control" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" name="stock_quantity" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Barcode</label>
                        <input type="text" name="barcode" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editId">
                    
                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" id="editName" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="number" name="price" id="editPrice" class="form-control" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" name="stock_quantity" id="editStock" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Barcode</label>
                        <input type="text" name="barcode" id="editBarcode" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Update Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editProduct(product) {
    document.getElementById('editId').value = product.id;
    document.getElementById('editName').value = product.name;
    document.getElementById('editPrice').value = product.price;
    document.getElementById('editStock').value = product.stock_quantity;
    document.getElementById('editBarcode').value = product.barcode;
    
    new bootstrap.Modal(document.getElementById('editProductModal')).show();
}

function deleteProduct(id) {
    if (confirm('Are you sure you want to delete this product?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>
