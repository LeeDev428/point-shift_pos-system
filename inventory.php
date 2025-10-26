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
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$stats = $inventoryController->getStats();
$allProducts = $inventoryController->getAllProducts($search, $category_id, $stock_filter);
$totalProducts = count($allProducts);
$totalPages = ceil($totalProducts / $perPage);
$products = array_slice($allProducts, $offset, $perPage);
$categories = $inventoryController->getAllCategories();

// Get expiring products (within 7 days) and expired products
$expiringProducts = [];
$expiredProducts = [];
foreach ($products as $product) {
    if (!empty($product['expiry'])) {
        $expiryDate = new DateTime($product['expiry']);
        $today = new DateTime();
        $interval = $today->diff($expiryDate);
        $daysUntilExpiry = (int)$interval->format('%R%a'); // +/- days
        
        if ($daysUntilExpiry < 0) {
            // Already expired
            $expiredProducts[] = $product;
        } elseif ($daysUntilExpiry <= 7) {
            // Expiring within 7 days
            $expiringProducts[] = $product;
        }
    }
}

$page_title = 'Inventory Management';

ob_start();
?>

<!-- Expiry Alerts -->
<?php if (!empty($expiredProducts)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <h5 class="alert-heading"><i class="fas fa-times-circle me-2"></i>Expired Products!</h5>
    <p class="mb-2">The following products have expired. Please remove from display immediately:</p>
    <ul class="mb-0">
        <?php foreach ($expiredProducts as $product): ?>
            <li>
                <strong><?php echo htmlspecialchars($product['name']); ?></strong> 
                expired on <strong><?php echo date('M d, Y', strtotime($product['expiry'])); ?></strong>
            </li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($expiringProducts)): ?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Products Nearing Expiration</h5>
    <p class="mb-2">The following products will expire soon:</p>
    <ul class="mb-0">
        <?php foreach ($expiringProducts as $product): ?>
            <?php
                $expiryDate = new DateTime($product['expiry']);
                $today = new DateTime();
                $daysLeft = (int)$today->diff($expiryDate)->format('%a');
            ?>
            <li>
                <strong><?php echo htmlspecialchars($product['name']); ?></strong> 
                will expire on <strong><?php echo date('M d, Y', strtotime($product['expiry'])); ?></strong>
                (<?php echo $daysLeft; ?> day<?php echo $daysLeft != 1 ? 's' : ''; ?> remaining)
            </li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

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
                                <th>Expiry Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No products found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($products as $product): 
                                // Calculate expiry status
                                $expiryClass = '';
                                $expiryBadge = '';
                                $expiryText = 'N/A';
                                if (!empty($product['expiry'])) {
                                    $expiryDate = new DateTime($product['expiry']);
                                    $today = new DateTime();
                                    $interval = $today->diff($expiryDate);
                                    $daysUntilExpiry = (int)$interval->format('%R%a');
                                    
                                    $expiryText = date('M d, Y', strtotime($product['expiry']));
                                    
                                    if ($daysUntilExpiry < 0) {
                                        // Expired
                                        $expiryClass = 'table-danger';
                                        $expiryBadge = '<span class="badge bg-danger ms-2">EXPIRED</span>';
                                    } elseif ($daysUntilExpiry <= 7) {
                                        // Expiring soon
                                        $expiryClass = 'table-warning';
                                        $expiryBadge = '<span class="badge bg-warning text-dark ms-2">' . $daysUntilExpiry . ' days left</span>';
                                    }
                                }
                            ?>
                            <tr class="<?php echo $expiryClass; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                </td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></span></td>
                                <td>SKU-<?php echo str_pad($product['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span><?php echo htmlspecialchars($product['barcode'] ?? 'N/A'); ?></span>
                                        <?php if (!empty($product['barcode'])): ?>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="showBarcode('<?php echo htmlspecialchars($product['barcode']); ?>', '<?php echo htmlspecialchars($product['name']); ?>')" title="Show Barcode">
                                                <i class="fas fa-barcode"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
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
                                    <?php echo $expiryText; ?>
                                    <?php echo $expiryBadge; ?>
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
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-3">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $category_id; ?>&stock_filter=<?php echo $stock_filter; ?>">Previous</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == 1 || $i == $totalPages || abs($i - $page) <= 2): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $category_id; ?>&stock_filter=<?php echo $stock_filter; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php elseif (abs($i - $page) == 3): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $category_id; ?>&stock_filter=<?php echo $stock_filter; ?>">Next</a>
                        </li>
                    </ul>
                    <p class="text-center text-muted">Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $perPage, $totalProducts); ?> of <?php echo $totalProducts; ?> products</p>
                </nav>
                <?php endif; ?>
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
                    
                    <div class="mb-3">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry" class="form-control">
                        <small class="text-muted">Leave empty if product doesn't expire</small>
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
                    
                    <div class="mb-3">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry" id="editExpiry" class="form-control">
                        <small class="text-muted">Leave empty if product doesn't expire</small>
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
    document.getElementById('editBarcode').value = product.barcode || '';
    document.getElementById('editExpiry').value = product.expiry || '';
    
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

// Show barcode in modal
function showBarcode(barcode, productName) {
    document.getElementById('barcodeValue').textContent = barcode;
    document.getElementById('productNameDisplay').textContent = productName;
    document.getElementById('barcodeImage').src = 'https://barcode.tec-it.com/barcode.ashx?data=' + encodeURIComponent(barcode) + '&code=Code128&translate-esc=on&unit=Fit&dpi=96&imagetype=Gif&rotation=0&color=%23000000&bgcolor=%23ffffff&qunit=Mm&quiet=0';
    
    const modal = new bootstrap.Modal(document.getElementById('barcodeModal'));
    modal.show();
}

// Print barcode
function printBarcode() {
    const printWindow = window.open('', '', 'height=400,width=600');
    const barcodeImg = document.getElementById('barcodeImage').src;
    const productName = document.getElementById('productNameDisplay').textContent;
    const barcodeValue = document.getElementById('barcodeValue').textContent;
    
    printWindow.document.write('<html><head><title>Print Barcode</title>');
    printWindow.document.write('<style>body{text-align:center;padding:20px;font-family:Arial;}img{max-width:100%;}</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<h3>' + productName + '</h3>');
    printWindow.document.write('<img src="' + barcodeImg + '" />');
    printWindow.document.write('<p style="margin-top:10px;font-size:14px;">' + barcodeValue + '</p>');
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}
</script>

<!-- Barcode Display Modal -->
<div class="modal fade" id="barcodeModal" tabindex="-1" aria-labelledby="barcodeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="barcodeModalLabel">
                    <i class="fas fa-barcode me-2"></i>Product Barcode
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <h6 class="mb-3" id="productNameDisplay"></h6>
                <div class="p-3 bg-light rounded mb-3">
                    <img id="barcodeImage" src="" alt="Barcode" class="img-fluid" style="max-height: 150px;">
                </div>
                <p class="text-muted mb-0">Barcode: <strong id="barcodeValue"></strong></p>
                <p class="text-info small mt-2">
                    <i class="fas fa-mobile-alt me-1"></i>Scan this barcode with your mobile app
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" onclick="printBarcode()">
                    <i class="fas fa-print me-1"></i>Print
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout.php';
?>
