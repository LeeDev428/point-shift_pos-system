<?php
require_once '../config.php';
User::requireLogin();

// Check if user is cashier
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cashier') {
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
$stock_filter = isset($_GET['stock_filter']) ? $_GET['stock_filter'] : '';

// Pagination setup
$perPage = 15;
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

// Stock filter
if ($stock_filter === 'low_stock') {
    $whereConditions[] = "p.stock_quantity > 0 AND p.stock_quantity <= p.low_stock_threshold";
} elseif ($stock_filter === 'out_of_stock') {
    $whereConditions[] = "p.stock_quantity = 0";
} elseif ($stock_filter === 'in_stock') {
    $whereConditions[] = "p.stock_quantity > p.low_stock_threshold";
}

$whereConditions[] = "p.status = 'active'";
$whereClause = "WHERE " . implode(" AND ", $whereConditions);

// Count total products with filters
$countStmt = $db->prepare("SELECT COUNT(*) FROM products p $whereClause");
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Fetch products
$stmt = $db->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    $whereClause
    ORDER BY p.name ASC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats
$statsStmt = $db->query("
    SELECT 
        COUNT(*) as total_products,
        SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
        SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= low_stock_threshold THEN 1 ELSE 0 END) as low_stock,
        SUM(CASE WHEN stock_quantity > low_stock_threshold THEN 1 ELSE 0 END) as in_stock
    FROM products WHERE status = 'active'
");
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get expiring products (within 7 days) and expired products
$expiringProducts = [];
$expiredProducts = [];
foreach ($products as $product) {
    if (!empty($product['expiry'])) {
        $expiryDate = new DateTime($product['expiry']);
        $today = new DateTime();
        $interval = $today->diff($expiryDate);
        $daysUntilExpiry = (int)$interval->format('%R%a');
        
        if ($daysUntilExpiry < 0) {
            $expiredProducts[] = $product;
        } elseif ($daysUntilExpiry <= 7) {
            $expiringProducts[] = $product;
        }
    }
}

$page_title = 'View Inventory';
ob_start();
?>

<style>
    .stats-card {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-left: 4px solid #dee2e6;
    }
    .content-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    .card-header {
        background: #f8f9fa;
        padding: 1.25rem;
        border-bottom: 2px solid #dee2e6;
    }
    .card-body {
        padding: 1.5rem;
    }
    .btn-custom {
        padding: 0.5rem 1.5rem;
        border-radius: 5px;
        font-weight: 500;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(220, 53, 69, 0.05);
    }
    .expiry-expired {
        background-color: rgba(220, 53, 69, 0.1) !important;
    }
    .expiry-warning {
        background-color: rgba(255, 193, 7, 0.1) !important;
    }
    .expiry-critical {
        background-color: rgba(253, 126, 20, 0.1) !important;
    }
    .read-only-badge {
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 1000;
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    .page-link {
        color: #dc3545;
    }
    .page-link:hover {
        color: #b02a37;
        background-color: #f8d7da;
    }
    .page-item.active .page-link {
        background-color: #dc3545;
        border-color: #dc3545;
    }
</style>

<!-- Read-Only Access Badge -->
<div class="read-only-badge">
    <span class="badge bg-info px-3 py-2">
        <i class="fas fa-eye me-2"></i>Read-Only Access
    </span>
</div>

<!-- Expiry Alerts -->
<?php if (!empty($expiredProducts)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <h5 class="alert-heading"><i class="fas fa-times-circle me-2"></i>Expired Products!</h5>
    <p class="mb-2">The following products have expired:</p>
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

<!-- Inventory View -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-eye me-2"></i>View Inventory</h5>
                <span class="badge bg-info">Read-Only Access</span>
            </div>
            <div class="card-body">
                <!-- Search and Filter Bar -->
                <form method="GET" class="mb-3">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Search by Name, SKU or Barcode" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select name="category_id" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-danger flex-grow-1">
                                    <i class="fas fa-search me-1"></i> Filter
                                </button>
                                <a href="inventory.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-redo"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
                    </div>
                    <div class="col-md-4 mb-2">
                        <select name="category_id" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        </div>
                    </div>
                </form>
                
                <!-- Products Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
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
                                    <?php if (!empty($product['description'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($product['description'], 0, 50)); ?><?php echo strlen($product['description']) > 50 ? '...' : ''; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></span></td>
                                <td><?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></td>
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
                                        elseif ($product['stock_quantity'] <= $product['low_stock_threshold']) echo 'warning';
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
                                    <?php elseif ($product['stock_quantity'] <= $product['low_stock_threshold']): ?>
                                        <span class="badge bg-warning">Low Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">In Stock</span>
                                    <?php endif; ?>
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
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $category_filter; ?>&stock_filter=<?php echo $stock_filter; ?>">Previous</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == 1 || $i == $totalPages || abs($i - $page) <= 2): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $category_filter; ?>&stock_filter=<?php echo $stock_filter; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php elseif (abs($i - $page) == 3): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $category_filter; ?>&stock_filter=<?php echo $stock_filter; ?>">Next</a>
                        </li>
                    </ul>
                    <p class="text-center text-muted">Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $perPage, $totalProducts); ?> of <?php echo $totalProducts; ?> products</p>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
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
include 'views/layout.php';
?>
