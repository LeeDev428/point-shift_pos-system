<?php
require_once '../config.php';
requireLogin();

$page_title = 'Point of Sale';

// Get product data for POS
$db = Database::getInstance()->getConnection();

// Get all active products with category names
$stmt = $db->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active' AND p.stock_quantity > 0 
    ORDER BY p.name ASC
");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get product categories
$stmt = $db->prepare("SELECT DISTINCT c.name FROM categories c JOIN products p ON c.id = p.category_id WHERE p.status = 'active' ORDER BY c.name ASC");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Helper function for currency formatting
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

ob_start();
?>

<style>
/* Receipt Print Styles */
@media print {
    body * { visibility: hidden; }
    .receipt-print, .receipt-print * { visibility: visible; }
    .receipt-print {
        position: absolute;
        left: 0;
        top: 0;
        width: 80mm;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        line-height: 1.2;
    }
    .no-print { display: none !important; }
}

.product-grid {
    max-height: 500px;
    overflow-y: auto;
}

.product-card {
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid transparent;
}

.product-card:hover {
    border-color: #dc3545;
    transform: translateY(-2px);
}

.cart-section {
    background: #f8f9fa;
    border-radius: 10px;
    max-height: 600px;
}

.cart-items {
    max-height: 300px;
    overflow-y: auto;
}

.receipt-print {
    display: none;
    font-family: 'Courier New', monospace;
    line-height: 1.2;
    font-size: 12px;
}

.total-section {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
}
</style>

<div class="row">
    <!-- Product Grid Section -->
    <div class="col-lg-8">
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Products</h5>
                <div class="d-flex gap-2">
                    <input type="text" id="productSearch" class="form-control form-control-sm" placeholder="Search products..." style="width: 200px;">
                    <select id="categoryFilter" class="form-select form-select-sm" style="width: 150px;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <div class="product-grid row" id="productGrid">
                    <?php foreach ($products as $product): ?>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3 product-item" 
                             data-category="<?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>"
                             data-name="<?php echo strtolower($product['name']); ?>">
                            <div class="card product-card h-100" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', <?php echo $product['price']; ?>, <?php echo $product['stock_quantity']; ?>)">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-box fa-2x text-primary mb-2"></i>
                                    <h6 class="card-title mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                    <p class="text-success fw-bold mb-1"><?php echo formatCurrency($product['price']); ?></p>
                                    <small class="text-muted">Stock: <?php echo $product['stock_quantity']; ?></small>
                                    <?php if ($product['stock_quantity'] <= 10): ?>
                                        <div class="badge bg-warning text-dark">Low Stock</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart Section -->
    <div class="col-lg-4">
        <div class="cart-section p-3">
            <h5 class="mb-3">
                <i class="fas fa-shopping-cart me-2"></i>Shopping Cart
            </h5>
            
            <!-- Cart Items -->
            <div class="cart-items" id="cartItems">
                <div class="text-center text-muted py-4">
                    <i class="fas fa-cart-plus fa-2x mb-2"></i>
                    <p>Click products to add to cart</p>
                </div>
            </div>

            <!-- Customer Info -->
            <div class="customer-info mt-3">
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small">Customer Name (Optional)</label>
                        <input type="text" id="customerName" class="form-control form-control-sm" placeholder="Enter customer name">
                    </div>
                </div>
            </div>

            <!-- Totals Section -->
            <div class="total-section">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span id="subtotal">₱0.00</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Tax (12%):</span>
                    <span id="tax">₱0.00</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <strong>Total:</strong>
                    <strong id="total" class="text-success">₱0.00</strong>
                </div>

                <!-- Payment Section -->
                <div class="payment-section">
                    <label class="form-label small">Amount Received</label>
                    <input type="number" id="amountReceived" class="form-control mb-2" placeholder="0.00" step="0.01">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Change:</span>
                        <span id="change" class="text-info">₱0.00</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-grid gap-2">
                    <button class="btn btn-success btn-custom" onclick="processPayment()" id="paymentBtn" disabled>
                        <i class="fas fa-credit-card me-2"></i>Process Payment
                    </button>
                    <button class="btn btn-outline-danger btn-custom" onclick="clearCart()">
                        <i class="fas fa-trash me-2"></i>Clear Cart
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Template (Hidden) -->
<div class="receipt-print" id="receiptTemplate">
    <div style="text-align: center; margin-bottom: 10px;">
        <strong style="font-size: 16px;">POINTSHIFT POS</strong><br>
        <small>Point of Sale System</small><br>
        <small>Tel: (123) 456-7890</small><br>
        <small>Email: info@jovespharmacy.com</small>
    </div>
    
    <div style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 5px 0; margin: 10px 0;">
        <div>Date: <span id="receiptDate"></span></div>
        <div>Time: <span id="receiptTime"></span></div>
        <div>Cashier: <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></div>
        <div>Customer: <span id="receiptCustomer"></span></div>
        <div>Receipt #: <span id="receiptNumber"></span></div>
    </div>
    
    <div id="receiptItems" style="margin: 10px 0;">
        <!-- Items will be inserted here -->
    </div>
    
    <div style="border-top: 1px dashed #000; padding-top: 5px; margin-top: 10px;">
        <div style="display: flex; justify-content: space-between;">
            <span>Subtotal:</span>
            <span id="receiptSubtotal"></span>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span>Tax (12%):</span>
            <span id="receiptTax"></span>
        </div>
        <div style="display: flex; justify-content: space-between; font-weight: bold; margin-top: 5px; font-size: 14px;">
            <span>TOTAL:</span>
            <span id="receiptTotal"></span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 5px;">
            <span>Cash:</span>
            <span id="receiptCash"></span>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span>Change:</span>
            <span id="receiptChange"></span>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 15px; font-size: 10px;">
        <div>Thank you for your business!</div>
        <div>Please come again!</div>
        <div style="margin-top: 10px;">*** CUSTOMER COPY ***</div>
    </div>
</div>

<script>
let cart = [];
let cartTotal = 0;

// Add product to cart
function addToCart(id, name, price, stock) {
    // Check if product already in cart
    const existingItem = cart.find(item => item.id === id);
    
    if (existingItem) {
        if (existingItem.quantity < stock) {
            existingItem.quantity += 1;
        } else {
            alert('Insufficient stock!');
            return;
        }
    } else {
        cart.push({
            id: id,
            name: name,
            price: price,
            quantity: 1,
            stock: stock
        });
    }
    
    updateCartDisplay();
}

// Remove item from cart
function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    updateCartDisplay();
}

// Update quantity
function updateQuantity(id, newQuantity) {
    const item = cart.find(item => item.id === id);
    if (item) {
        if (newQuantity <= 0) {
            removeFromCart(id);
        } else if (newQuantity <= item.stock) {
            item.quantity = newQuantity;
            updateCartDisplay();
        } else {
            alert('Insufficient stock!');
        }
    }
}

// Update cart display
function updateCartDisplay() {
    const cartContainer = document.getElementById('cartItems');
    
    if (cart.length === 0) {
        cartContainer.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-cart-plus fa-2x mb-2"></i>
                <p>Click products to add to cart</p>
            </div>
        `;
        updateTotals();
        return;
    }
    
    let cartHTML = '';
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        cartHTML += `
            <div class="card mb-2">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${item.name}</h6>
                            <small class="text-muted">${formatCurrency(item.price)} each</small>
                        </div>
                        <button class="btn btn-outline-danger btn-sm" onclick="removeFromCart(${item.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div class="input-group input-group-sm" style="width: 100px;">
                            <button class="btn btn-outline-secondary" onclick="updateQuantity(${item.id}, ${item.quantity - 1})">-</button>
                            <input type="number" class="form-control text-center" value="${item.quantity}" min="1" max="${item.stock}" onchange="updateQuantity(${item.id}, parseInt(this.value))">
                            <button class="btn btn-outline-secondary" onclick="updateQuantity(${item.id}, ${item.quantity + 1})">+</button>
                        </div>
                        <strong class="text-success">${formatCurrency(itemTotal)}</strong>
                    </div>
                </div>
            </div>
        `;
    });
    
    cartContainer.innerHTML = cartHTML;
    updateTotals();
}

// Update totals
function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const tax = subtotal * 0.12;
    const total = subtotal + tax;
    
    document.getElementById('subtotal').textContent = formatCurrency(subtotal);
    document.getElementById('tax').textContent = formatCurrency(tax);
    document.getElementById('total').textContent = formatCurrency(total);
    
    cartTotal = total;
    
    // Update change calculation
    const amountReceived = parseFloat(document.getElementById('amountReceived').value) || 0;
    const change = amountReceived - total;
    document.getElementById('change').textContent = formatCurrency(Math.max(0, change));
    
    // Enable/disable payment button
    document.getElementById('paymentBtn').disabled = cart.length === 0 || amountReceived < total;
}

// Process payment
async function processPayment() {
    if (cart.length === 0) {
        alert('Cart is empty!');
        return;
    }
    
    const amountReceived = parseFloat(document.getElementById('amountReceived').value) || 0;
    if (amountReceived < cartTotal) {
        alert('Insufficient payment amount!');
        return;
    }
    
    const customerName = document.getElementById('customerName').value || 'Walk-in Customer';
    
    try {
        // Create order data
        const orderData = {
            customer_name: customerName,
            items: cart,
            subtotal: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0),
            tax: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0) * 0.12,
            total: cartTotal,
            amount_received: amountReceived,
            change: amountReceived - cartTotal
        };
        
        // Simulate order processing (replace with actual API call)
        const response = await processOrder(orderData);
        
        if (response.success) {
            // Generate receipt
            generateReceipt(orderData, response.order_id);
            
            // Print receipt
            printReceipt();
            
            // Clear cart
            clearCart();
            
            alert('Payment processed successfully!');
        } else {
            alert('Payment processing failed: ' + response.error);
        }
    } catch (error) {
        alert('Payment processing failed: ' + error.message);
    }
}

// Generate receipt
function generateReceipt(orderData, orderId) {
    const now = new Date();
    const receiptNumber = 'POS' + now.getFullYear() + (now.getMonth() + 1).toString().padStart(2, '0') + now.getDate().toString().padStart(2, '0') + '-' + orderId;
    
    document.getElementById('receiptDate').textContent = now.toLocaleDateString();
    document.getElementById('receiptTime').textContent = now.toLocaleTimeString();
    document.getElementById('receiptCustomer').textContent = orderData.customer_name;
    document.getElementById('receiptNumber').textContent = receiptNumber;
    
    // Generate items list
    let itemsHTML = '';
    orderData.items.forEach(item => {
        const itemTotal = item.price * item.quantity;
        itemsHTML += `
            <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                <div style="flex: 1;">
                    <div style="font-weight: bold;">${item.name}</div>
                    <div style="font-size: 10px;">${item.quantity} x ${formatCurrency(item.price)}</div>
                </div>
                <div style="text-align: right;">
                    ${formatCurrency(itemTotal)}
                </div>
            </div>
        `;
    });
    
    document.getElementById('receiptItems').innerHTML = itemsHTML;
    document.getElementById('receiptSubtotal').textContent = formatCurrency(orderData.subtotal);
    document.getElementById('receiptTax').textContent = formatCurrency(orderData.tax);
    document.getElementById('receiptTotal').textContent = formatCurrency(orderData.total);
    document.getElementById('receiptCash').textContent = formatCurrency(orderData.amount_received);
    document.getElementById('receiptChange').textContent = formatCurrency(orderData.change);
}

// Print receipt
function printReceipt() {
    // Show receipt for printing
    document.getElementById('receiptTemplate').style.display = 'block';
    
    // Print
    window.print();
    
    // Hide receipt after printing
    setTimeout(() => {
        document.getElementById('receiptTemplate').style.display = 'none';
    }, 1000);
}

// Simulate order processing (replace with actual backend call)
async function processOrder(orderData) {
    try {
        const response = await fetch('process_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(orderData)
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        return {
            success: false,
            error: error.message
        };
    }
}

// Clear cart
function clearCart() {
    cart = [];
    updateCartDisplay();
    document.getElementById('customerName').value = '';
    document.getElementById('amountReceived').value = '';
}

// Product search and filter
document.getElementById('productSearch').addEventListener('input', filterProducts);
document.getElementById('categoryFilter').addEventListener('change', filterProducts);
document.getElementById('amountReceived').addEventListener('input', updateTotals);

function filterProducts() {
    const searchTerm = document.getElementById('productSearch').value.toLowerCase();
    const selectedCategory = document.getElementById('categoryFilter').value;
    const products = document.querySelectorAll('.product-item');
    
    products.forEach(product => {
        const productName = product.dataset.name;
        const productCategory = product.dataset.category;
        
        const matchesSearch = productName.includes(searchTerm);
        const matchesCategory = !selectedCategory || productCategory === selectedCategory;
        
        product.style.display = matchesSearch && matchesCategory ? 'block' : 'none';
    });
}

// Format currency helper
function formatCurrency(amount) {
    return '₱' + parseFloat(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // F1 - Process Payment
    if (e.key === 'F1') {
        e.preventDefault();
        if (!document.getElementById('paymentBtn').disabled) {
            processPayment();
        }
    }
    
    // F2 - Clear Cart
    if (e.key === 'F2') {
        e.preventDefault();
        clearCart();
    }
    
    // F3 - Focus on search
    if (e.key === 'F3') {
        e.preventDefault();
        document.getElementById('productSearch').focus();
    }
});
</script>

<?php
$content = ob_get_clean();
include '../layout.php';
?>
