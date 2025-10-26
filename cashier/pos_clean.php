<?php
require_once '../config.php';
User::requireLogin();

// Redirect admin to admin panel
if (User::isAdmin()) {
    header('Location: ../pos.php');
    exit();
}

$posController = new POSController();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'complete_sale':
            $items = json_decode($_POST['items'], true);
            $amount_received = floatval($_POST['amount_received']);
            $payment_method = $_POST['payment_method'] ?? 'cash';
            $discount_percent = floatval($_POST['discount_percent'] ?? 0);
            $result = $posController->createOrder($items, $payment_method, $amount_received, $discount_percent);
            echo json_encode($result);
            exit();
            
        case 'get_product':
            $product = $posController->getProductById($_POST['product_id']);
            echo json_encode($product);
            exit();
    }
}

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$products = $posController->getProducts($search, $category);
$categories = $posController->getCategories();

$title = 'Point of Sale';

ob_start();
?>

<style>
/* Clean, Organized POS System */
* {
    box-sizing: border-box;
}

.pos-wrapper {
    padding: 1.5rem;
    background: #f8f9fa;
}

.pos-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 1rem;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #e9ecef;
}

.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #212529;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.title-icon {
    width: 40px;
    height: 40px;
    background: #0d6efd;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

/* Search Section */
.search-wrapper {
    margin-bottom: 1.5rem;
}

.search-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.search-input:focus {
    outline: none;
    border-color: #0d6efd;
}

/* Products Grid */
.products-container {
    max-height: 500px;
    overflow-y: auto;
    padding-right: 0.5rem;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1.25rem;
}

.product-item {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 1.25rem;
    cursor: pointer;
    transition: all 0.3s;
}

.product-item:hover {
    border-color: #0d6efd;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.product-name {
    font-size: 0.95rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.75rem;
    min-height: 2.8em;
    line-height: 1.4;
}

.product-price {
    font-size: 1.15rem;
    font-weight: 700;
    color: #0d6efd;
    margin-bottom: 0.5rem;
}

.product-stock {
    font-size: 0.85rem;
    color: #6c757d;
}

/* Cart Section */
.cart-container {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.cart-items-wrapper {
    flex: 1;
    max-height: 300px;
    overflow-y: auto;
    margin-bottom: 1.5rem;
    padding-right: 0.5rem;
}

.cart-item {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.cart-item-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.cart-item-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: #212529;
    flex: 1;
    padding-right: 1rem;
}

.cart-remove-btn {
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 0.25rem 0.6rem;
    font-size: 0.85rem;
    cursor: pointer;
    transition: background 0.2s;
}

.cart-remove-btn:hover {
    background: #bb2d3b;
}

.cart-item-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.qty-wrapper {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.qty-button {
    background: #0d6efd;
    color: white;
    border: none;
    border-radius: 6px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.2s;
}

.qty-button:hover {
    background: #0b5ed7;
}

.qty-number {
    min-width: 40px;
    text-align: center;
    font-weight: 600;
    font-size: 1.05rem;
}

.cart-item-total {
    font-weight: 700;
    font-size: 1.05rem;
    color: #0d6efd;
}

.empty-cart-message {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

.empty-cart-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Discount Section */
.discount-wrapper {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.discount-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 1rem;
    display: block;
}

.discount-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.discount-button {
    padding: 0.65rem;
    border: 2px solid #dee2e6;
    background: white;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.discount-button:hover {
    border-color: #0d6efd;
}

.discount-button.active {
    background: #0d6efd;
    color: white;
    border-color: #0d6efd;
}

.custom-discount-input {
    width: 100%;
    padding: 0.65rem;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    margin-top: 0.75rem;
}

/* Cart Summary */
.summary-wrapper {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.summary-line {
    display: flex;
    justify-content: space-between;
    padding: 0.65rem 0;
    font-size: 1rem;
}

.summary-line.divider {
    border-top: 1px dashed #dee2e6;
    margin-top: 0.5rem;
    padding-top: 1rem;
}

.summary-line.total-line {
    border-top: 2px solid #212529;
    margin-top: 1rem;
    padding-top: 1rem;
    font-size: 1.35rem;
    font-weight: 700;
}

.summary-label {
    font-weight: 500;
    color: #495057;
}

.summary-value {
    font-weight: 600;
    color: #212529;
}

.discount-value {
    color: #198754;
}

/* Payment Section */
.payment-wrapper {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.payment-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 1rem;
    display: block;
}

.payment-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-field {
    display: flex;
    flex-direction: column;
}

.field-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.field-input {
    padding: 0.65rem;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: border-color 0.2s;
}

.field-input:focus {
    outline: none;
    border-color: #0d6efd;
}

.quick-amounts-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.quick-amount-btn {
    padding: 0.6rem;
    background: white;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.quick-amount-btn:hover {
    background: #0d6efd;
    color: white;
    border-color: #0d6efd;
}

.exact-amount-btn {
    width: 100%;
    padding: 0.65rem;
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: 1rem;
    transition: background 0.2s;
}

.exact-amount-btn:hover {
    background: #5c636a;
}

.change-box {
    background: #d1e7dd;
    color: #0f5132;
    padding: 1rem;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    margin-bottom: 1rem;
}

.change-amount {
    font-size: 1.25rem;
}

/* Complete Sale Button */
.complete-btn {
    width: 100%;
    padding: 1rem;
    background: #198754;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.complete-btn:hover:not(:disabled) {
    background: #157347;
    transform: translateY(-2px);
}

.complete-btn:disabled {
    background: #6c757d;
    cursor: not-allowed;
}

/* Scrollbar Styling */
.products-container::-webkit-scrollbar,
.cart-items-wrapper::-webkit-scrollbar {
    width: 8px;
}

.products-container::-webkit-scrollbar-track,
.cart-items-wrapper::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.products-container::-webkit-scrollbar-thumb,
.cart-items-wrapper::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.products-container::-webkit-scrollbar-thumb:hover,
.cart-items-wrapper::-webkit-scrollbar-thumb:hover {
    background: #a0a0a0;
}
</style>

<div class="pos-wrapper">
    <div class="row g-4">
        <!-- Products Section -->
        <div class="col-lg-7">
            <div class="pos-card">
                <div class="card-header">
                    <div class="card-title">
                        <div class="title-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        Products
                    </div>
                </div>
                
                <!-- Search -->
                <div class="search-wrapper">
                    <input type="text" 
                           id="product-search" 
                           class="search-input" 
                           placeholder="Search by name, code, or SKU...">
                </div>
                
                <!-- Products Grid -->
                <div class="products-container">
                    <div class="products-grid">
                        <?php if (empty($products)): ?>
                            <div class="col-12 text-center py-5">
                                <p class="text-muted">No products found</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <div class="product-item" onclick="addToCart(<?= htmlspecialchars(json_encode($product)) ?>)">
                                    <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                                    <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>
                                    <div class="product-stock">Stock: <?= $product['stock_quantity'] ?? 0 ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Cart Section -->
        <div class="col-lg-5">
            <div class="pos-card">
                <div class="card-header">
                    <div class="card-title">
                        <div class="title-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        Shopping Cart
                    </div>
                    <span class="badge bg-primary" id="cart-count" style="font-size: 1rem; padding: 0.5rem 0.75rem;">0</span>
                </div>
                
                <!-- Cart Items -->
                <div class="cart-items-wrapper" id="cart-items">
                    <div class="empty-cart-message">
                        <div class="empty-cart-icon">
                            <i class="fas fa-shopping-basket"></i>
                        </div>
                        <p>Cart is empty</p>
                    </div>
                </div>
                
                <!-- Discount -->
                <div class="discount-wrapper">
                    <label class="discount-label">Apply Discount</label>
                    <div class="discount-grid">
                        <button class="discount-button active" data-discount="0">None</button>
                        <button class="discount-button" data-discount="20" data-type="PWD">PWD (20%)</button>
                        <button class="discount-button" data-discount="20" data-type="Senior">Senior (20%)</button>
                        <button class="discount-button" data-discount="custom">Custom</button>
                    </div>
                    <input type="number" 
                           id="custom-discount" 
                           class="custom-discount-input" 
                           placeholder="Enter discount %" 
                           min="0" 
                           max="100" 
                           style="display: none;">
                </div>
                
                <!-- Summary -->
                <div class="summary-wrapper">
                    <div class="summary-line">
                        <span class="summary-label">Subtotal</span>
                        <span class="summary-value">₱<span id="subtotal">0.00</span></span>
                    </div>
                    <div class="summary-line" id="discount-row" style="display: none;">
                        <span class="summary-label">Discount (<span id="discount-percent">0</span>%)</span>
                        <span class="summary-value discount-value">-₱<span id="discount-amount">0.00</span></span>
                    </div>
                    <div class="summary-line divider">
                        <span class="summary-label">Tax (12%)</span>
                        <span class="summary-value">₱<span id="tax">0.00</span></span>
                    </div>
                    <div class="summary-line total-line">
                        <span>TOTAL</span>
                        <span>₱<span id="total">0.00</span></span>
                    </div>
                </div>
                
                <!-- Payment -->
                <div class="payment-wrapper">
                    <label class="payment-label">Payment Details</label>
                    <div class="payment-row">
                        <div class="form-field">
                            <label class="field-label">Method</label>
                            <select id="payment-method" class="field-input">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="gcash">GCash</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label class="field-label">Amount Paid</label>
                            <input type="number" 
                                   id="amount-paid" 
                                   class="field-input" 
                                   placeholder="0.00" 
                                   step="0.01">
                        </div>
                    </div>
                    
                    <div class="quick-amounts-grid">
                        <button class="quick-amount-btn" data-amount="20">₱20</button>
                        <button class="quick-amount-btn" data-amount="50">₱50</button>
                        <button class="quick-amount-btn" data-amount="100">₱100</button>
                        <button class="quick-amount-btn" data-amount="200">₱200</button>
                        <button class="quick-amount-btn" data-amount="500">₱500</button>
                        <button class="quick-amount-btn" data-amount="1000">₱1000</button>
                    </div>
                    
                    <button class="exact-amount-btn" id="exact-amount">Exact Amount</button>
                    
                    <div id="change-display" class="change-box" style="display: none;">
                        <span>Change:</span>
                        <span class="change-amount">₱<span id="change-amount">0.00</span></span>
                    </div>
                </div>
                
                <!-- Complete Sale -->
                <button class="complete-btn" id="complete-sale">
                    <i class="fas fa-check-circle"></i>
                    Complete Sale
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="receipt-content"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];
let discountPercent = 0;

// Add to cart
function addToCart(product) {
    const existingItem = cart.find(item => item.id === product.id);
    const stockQty = product.stock_quantity || product.quantity || 0;
    
    if (existingItem) {
        if (existingItem.quantity < stockQty) {
            existingItem.quantity++;
        } else {
            alert('Not enough stock available');
            return;
        }
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: parseFloat(product.price),
            quantity: 1,
            max_quantity: stockQty
        });
    }
    
    updateCart();
}

// Remove from cart
function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCart();
}

// Update quantity
function updateQuantity(productId, change) {
    const item = cart.find(item => item.id === productId);
    if (item) {
        const newQty = item.quantity + change;
        if (newQty > 0 && newQty <= item.max_quantity) {
            item.quantity = newQty;
            updateCart();
        } else if (newQty <= 0) {
            removeFromCart(productId);
        } else {
            alert('Not enough stock available');
        }
    }
}

// Update cart display
function updateCart() {
    const cartItems = document.getElementById('cart-items');
    const cartCount = document.getElementById('cart-count');
    
    if (cart.length === 0) {
        cartItems.innerHTML = `
            <div class="empty-cart-message">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-basket"></i>
                </div>
                <p>Cart is empty</p>
            </div>
        `;
        cartCount.textContent = '0';
    } else {
        let html = '';
        cart.forEach(item => {
            const itemTotal = item.price * item.quantity;
            html += `
                <div class="cart-item">
                    <div class="cart-item-row">
                        <div class="cart-item-name">${item.name}</div>
                        <button class="cart-remove-btn" onclick="removeFromCart(${item.id})">Remove</button>
                    </div>
                    <div class="cart-item-controls">
                        <div class="qty-wrapper">
                            <button class="qty-button" onclick="updateQuantity(${item.id}, -1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="qty-number">${item.quantity}</span>
                            <button class="qty-button" onclick="updateQuantity(${item.id}, 1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div class="cart-item-total">₱${itemTotal.toFixed(2)}</div>
                    </div>
                </div>
            `;
        });
        cartItems.innerHTML = html;
        
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        cartCount.textContent = totalItems;
    }
    
    updateTotals();
}

// Update totals
function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const discountAmount = subtotal * (discountPercent / 100);
    const afterDiscount = subtotal - discountAmount;
    const tax = afterDiscount * 0.12;
    const total = afterDiscount + tax;
    
    document.getElementById('subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('tax').textContent = tax.toFixed(2);
    document.getElementById('total').textContent = total.toFixed(2);
    
    if (discountPercent > 0) {
        document.getElementById('discount-row').style.display = 'flex';
        document.getElementById('discount-percent').textContent = discountPercent.toFixed(1);
        document.getElementById('discount-amount').textContent = discountAmount.toFixed(2);
    } else {
        document.getElementById('discount-row').style.display = 'none';
    }
}

// Discount buttons
document.querySelectorAll('.discount-button').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.discount-button').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const discount = this.dataset.discount;
        const customInput = document.getElementById('custom-discount');
        
        if (discount === 'custom') {
            customInput.style.display = 'block';
            customInput.focus();
        } else {
            customInput.style.display = 'none';
            discountPercent = parseFloat(discount) || 0;
            updateTotals();
        }
    });
});

document.getElementById('custom-discount').addEventListener('input', function() {
    discountPercent = parseFloat(this.value) || 0;
    if (discountPercent > 100) {
        discountPercent = 100;
        this.value = 100;
    }
    if (discountPercent < 0) {
        discountPercent = 0;
        this.value = 0;
    }
    updateTotals();
});

// Quick amounts
document.querySelectorAll('.quick-amount-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('amount-paid').value = this.dataset.amount;
        calculateChange();
    });
});

// Exact amount
document.getElementById('exact-amount').addEventListener('click', function() {
    const total = parseFloat(document.getElementById('total').textContent);
    document.getElementById('amount-paid').value = total.toFixed(2);
    calculateChange();
});

// Calculate change
document.getElementById('amount-paid').addEventListener('input', calculateChange);

function calculateChange() {
    const total = parseFloat(document.getElementById('total').textContent);
    const paid = parseFloat(document.getElementById('amount-paid').value) || 0;
    const change = paid - total;
    
    if (change >= 0) {
        document.getElementById('change-display').style.display = 'flex';
        document.getElementById('change-amount').textContent = change.toFixed(2);
    } else {
        document.getElementById('change-display').style.display = 'none';
    }
}

// Product search
document.getElementById('product-search').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const productItems = document.querySelectorAll('.product-item');
    
    productItems.forEach(item => {
        const productName = item.querySelector('.product-name').textContent.toLowerCase();
        if (productName.includes(searchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});

// Complete sale
document.getElementById('complete-sale').addEventListener('click', function() {
    if (cart.length === 0) {
        alert('Cart is empty');
        return;
    }
    
    const total = parseFloat(document.getElementById('total').textContent);
    const amountPaid = parseFloat(document.getElementById('amount-paid').value) || 0;
    
    if (amountPaid < total) {
        alert('Insufficient payment amount');
        return;
    }
    
    const paymentMethod = document.getElementById('payment-method').value;
    
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    fetch('pos.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=complete_sale&items=${encodeURIComponent(JSON.stringify(cart))}&amount_received=${amountPaid}&payment_method=${paymentMethod}&discount_percent=${discountPercent}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            generateReceipt(data.order_id);
            cart = [];
            discountPercent = 0;
            document.getElementById('amount-paid').value = '';
            document.querySelectorAll('.discount-button')[0].click();
            updateCart();
            
            const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
            receiptModal.show();
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error processing sale: ' + error.message);
    })
    .finally(() => {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-check-circle"></i> Complete Sale';
    });
});

// Generate receipt
function generateReceipt(orderId) {
    const subtotal = parseFloat(document.getElementById('subtotal').textContent);
    const discountAmount = parseFloat(document.getElementById('discount-amount').textContent || 0);
    const tax = parseFloat(document.getElementById('tax').textContent);
    const total = parseFloat(document.getElementById('total').textContent);
    const amountPaid = parseFloat(document.getElementById('amount-paid').value);
    const change = amountPaid - total;
    const paymentMethod = document.getElementById('payment-method').options[document.getElementById('payment-method').selectedIndex].text;
    
    const now = new Date();
    const dateStr = now.toLocaleDateString('en-PH');
    const timeStr = now.toLocaleTimeString('en-PH');
    
    let itemsHtml = '';
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        itemsHtml += `
            <tr>
                <td style="padding: 5px 0; font-size: 11px;">${item.name}</td>
                <td style="padding: 5px 0; text-align: center; font-size: 11px;">${item.quantity}</td>
                <td style="padding: 5px 0; text-align: right; font-size: 11px;">₱${itemTotal.toFixed(2)}</td>
            </tr>
        `;
    });
    
    const receiptHtml = `
        <div style="width: 250px; font-family: monospace; font-size: 12px;">
            <div style="text-align: center; margin-bottom: 10px;">
                <h4 style="margin: 0;">POINT SHIFT POS</h4>
                <small>Receipt</small>
            </div>
            
            <div style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 8px 0; margin: 10px 0;">
                <div style="display: flex; justify-content: space-between; font-size: 10px; margin-bottom: 3px;">
                    <span>Order #:</span>
                    <span>${orderId}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 10px; margin-bottom: 3px;">
                    <span>Date:</span>
                    <span>${dateStr}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 10px; margin-bottom: 3px;">
                    <span>Time:</span>
                    <span>${timeStr}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 10px;">
                    <span>Cashier:</span>
                    <span><?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?></span>
                </div>
            </div>
            
            <table style="width: 100%; margin: 10px 0;">
                <thead>
                    <tr style="border-bottom: 1px dashed #000;">
                        <th style="text-align: left; padding: 5px 0; font-size: 11px;">Item</th>
                        <th style="text-align: center; padding: 5px 0; font-size: 11px;">Qty</th>
                        <th style="text-align: right; padding: 5px 0; font-size: 11px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsHtml}
                </tbody>
            </table>
            
            <div style="border-top: 1px dashed #000; padding-top: 8px; margin-top: 8px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 11px;">
                    <span>Subtotal:</span>
                    <span>₱${subtotal.toFixed(2)}</span>
                </div>
                ${discountPercent > 0 ? `
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 11px; color: #198754;">
                    <span>Discount (${discountPercent}%):</span>
                    <span>-₱${discountAmount.toFixed(2)}</span>
                </div>
                ` : ''}
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 11px;">
                    <span>Tax (12%):</span>
                    <span>₱${tax.toFixed(2)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin: 10px 0; padding: 10px 0; border-top: 2px solid #000; border-bottom: 2px solid #000; font-size: 14px; font-weight: bold;">
                    <span>TOTAL:</span>
                    <span>₱${total.toFixed(2)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 11px;">
                    <span>Payment (${paymentMethod}):</span>
                    <span>₱${amountPaid.toFixed(2)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 11px;">
                    <span>Change:</span>
                    <span>₱${change.toFixed(2)}</span>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 15px; font-size: 10px; border-top: 1px dashed #000; padding-top: 10px;">
                <p style="margin: 5px 0;">Thank you for your purchase!</p>
                <p style="margin: 5px 0;">Please come again!</p>
            </div>
        </div>
    `;
    
    document.getElementById('receipt-content').innerHTML = receiptHtml;
}
</script>

<?php
$content = ob_get_clean();
require_once 'views/layout.php';
?>
