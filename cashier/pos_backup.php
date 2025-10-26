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

<!-- Modern POS Styles -->
<style>
.modern-pos-container {
    background: #f5f7fa;
    min-height: calc(100vh - 100px);
    padding: 0;
}

.pos-cart-section {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    padding: 1.5rem;
    height: calc(100vh - 120px);
    display: flex;
    flex-direction: column;
}

.pos-products-section {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    padding: 1.5rem;
    height: calc(100vh - 120px);
}

.section-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #e9ecef;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2d3748;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-icon {
    width: 35px;
    height: 35px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
}

.cart-items-container {
    flex: 1;
    overflow-y: auto;
    margin-bottom: 1rem;
    padding-right: 0.5rem;
}

.cart-items-container::-webkit-scrollbar {
    width: 6px;
}

.cart-items-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.cart-items-container::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 10px;
}

.cart-items-container::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}

.cart-item {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    transition: all 0.2s;
    border: 2px solid transparent;
}

.cart-item:hover {
    border-color: #667eea;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.15);
}

.cart-item-name {
    font-weight: 600;
    color: #2d3748;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.cart-item-price {
    color: #718096;
    font-size: 0.8rem;
}

.cart-item-qty {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.qty-btn {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.9rem;
    color: #4a5568;
}

.qty-btn:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.qty-input {
    width: 45px;
    text-align: center;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.25rem;
    font-weight: 600;
    color: #2d3748;
}

.remove-item-btn {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    border: 1px solid #feb2b2;
    background: #fff5f5;
    color: #c53030;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.remove-item-btn:hover {
    background: #c53030;
    color: white;
}

.empty-cart {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
    color: #a0aec0;
}

.empty-cart-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.cart-summary {
    background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
    border-radius: 15px;
    padding: 1rem;
    margin-top: auto;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    font-size: 0.9rem;
    color: #4a5568;
}

.summary-row.discount-row {
    border-top: 1px dashed #e2e8f0;
    padding-top: 0.75rem;
}

.summary-row.tax-row {
    border-bottom: 1px dashed #e2e8f0;
    padding-bottom: 0.75rem;
}

.summary-row.total-row {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    margin: 0.75rem -1rem -1rem -1rem;
    padding: 1rem 1rem;
    border-radius: 0 0 15px 15px;
    color: white;
    font-size: 1.3rem;
    font-weight: 700;
}

.discount-selector {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.discount-btn {
    flex: 1;
    padding: 0.5rem;
    border-radius: 8px;
    border: 2px solid #e2e8f0;
    background: white;
    font-size: 0.75rem;
    font-weight: 600;
    color: #4a5568;
    cursor: pointer;
    transition: all 0.2s;
}

.discount-btn:hover, .discount-btn.active {
    border-color: #667eea;
    background: #667eea;
    color: white;
}

.payment-section {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    margin-top: 1rem;
    border: 2px solid #e2e8f0;
}

.payment-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.input-group-modern {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.input-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #718096;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.input-modern {
    padding: 0.75rem;
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    font-size: 0.95rem;
    font-weight: 600;
    color: #2d3748;
    transition: all 0.2s;
}

.input-modern:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.complete-sale-btn {
    width: 100%;
    padding: 1rem;
    border-radius: 12px;
    border: none;
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    text-transform: uppercase;
    letter-spacing: 1px;
    box-shadow: 0 4px 15px rgba(72, 187, 120, 0.4);
}

.complete-sale-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(72, 187, 120, 0.5);
}

.complete-sale-btn:disabled {
    background: #cbd5e0;
    cursor: not-allowed;
    box-shadow: none;
}

.search-modern {
    position: relative;
    margin-bottom: 1rem;
}

.search-input {
    width: 100%;
    padding: 0.85rem 1rem 0.85rem 3rem;
    border-radius: 12px;
    border: 2px solid #e2e8f0;
    font-size: 0.95rem;
    transition: all 0.2s;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #a0aec0;
    font-size: 1.1rem;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 1rem;
    max-height: calc(100vh - 280px);
    overflow-y: auto;
    padding-right: 0.5rem;
}

.products-grid::-webkit-scrollbar {
    width: 6px;
}

.products-grid::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.products-grid::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 10px;
}

.product-card-modern {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 15px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    border: 2px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    position: relative;
    overflow: hidden;
}

.product-card-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transform: scaleX(0);
    transition: transform 0.3s;
}

.product-card-modern:hover::before {
    transform: scaleX(1);
}

.product-card-modern:hover {
    transform: translateY(-5px);
    border-color: #667eea;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.25);
}

.product-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.product-name {
    font-weight: 700;
    color: #2d3748;
    font-size: 0.9rem;
    line-height: 1.3;
    margin-bottom: 0.25rem;
    min-height: 2.6em;
}

.product-price {
    font-size: 1.1rem;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 0.25rem;
}

.product-stock {
    font-size: 0.75rem;
    color: #718096;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.stock-badge {
    display: inline-block;
    padding: 0.15rem 0.5rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
}

.stock-good {
    background: #c6f6d5;
    color: #22543d;
}

.stock-low {
    background: #fed7d7;
    color: #742a2a;
}

.add-to-cart-btn {
    width: 100%;
    padding: 0.5rem;
    border-radius: 8px;
    border: none;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: auto;
}

.add-to-cart-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

@media (max-width: 992px) {
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    }
}
</style>

<div class="modern-pos-container">
    <div class="row g-3">
        <!-- Cart Section -->
        <div class="col-lg-4">
            <div class="pos-cart-section">
                <div class="section-header">
                    <div class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        Shopping Cart
                    </div>
                    <div class="text-muted small">
                        <span id="cart-count">0</span> items
                    </div>
                </div>
                
                <!-- Cart Items -->
                <div class="cart-items-container" id="cart-items">
                    <div class="empty-cart">
                        <div class="empty-cart-icon">
                            <i class="fas fa-shopping-basket"></i>
                        </div>
                        <p class="mb-0">Your cart is empty</p>
                        <small>Add products to get started</small>
                    </div>
                </div>
                
                <!-- Cart Summary (Compact) -->
                <div class="border-top pt-2">
                    <div class="row g-1 mb-1">
                        <div class="col-6"><small>Subtotal:</small></div>
                        <div class="col-6 text-end"><small id="subtotal">₱0.00</small></div>
                    </div>
                    <div class="row g-1 mb-1">
                        <div class="col-6">
                            <small>Discount:</small>
                            <select id="discount-type" class="form-select form-select-sm" onchange="applyDiscountType(this.value)">
                                <option value="0">None</option>
                                <option value="20">PWD (20%)</option>
                                <option value="20">Senior Citizen (20%)</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        <div class="col-6 text-end">
                            <small id="discount-amount">₱0.00</small>
                            <input type="number" id="discount" class="form-control form-control-sm d-none" style="width: 50px; height: 20px; font-size: 0.7rem;" value="0" min="0" max="100" onchange="updateCart()">
                        </div>
                    </div>
                    <div class="row g-1 mb-1">
                        <div class="col-6"><small>Tax (12%):</small></div>
                        <div class="col-6 text-end"><small id="tax">₱0.00</small></div>
                    </div>
                    <div class="row g-1 mb-2 p-2 bg-warning bg-opacity-25 rounded">
                        <div class="col-6"><strong style="font-size: 1.1rem;">TOTAL:</strong></div>
                        <div class="col-6 text-end"><strong id="total" class="text-danger" style="font-size: 1.2rem;">₱0.00</strong></div>
                    </div>
                    
                    <div class="row g-1 mb-2">
                        <div class="col-6">
                            <small>Payment:</small>
                            <select id="payment-method" class="form-select form-select-sm">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="gcash">GCash</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <small>Amount:</small>
                            <input type="number" id="amount-received" class="form-control form-control-sm" step="0.01" min="0">
                        </div>
                    </div>
                    
                    <!-- Quick Payment Buttons (Complete Grid) -->
                    <div class="payment-buttons mb-2">
                        <div class="row g-1 mb-1">
                            <div class="col-4"><button class="btn btn-outline-secondary btn-sm w-100" style="font-size: 0.6rem; padding: 2px;">PROMO</button></div>
                            <div class="col-4"><button class="btn btn-outline-secondary btn-sm w-100" style="font-size: 0.6rem; padding: 2px;">DELETE ITEM</button></div>
                            <div class="col-4"><button class="btn btn-outline-secondary btn-sm w-100" style="font-size: 0.6rem; padding: 2px;">DISCOUNT</button></div>
                        </div>
                        <div class="row g-1 mb-1">
                            <div class="col-4"><button class="btn btn-outline-secondary btn-sm w-100" style="font-size: 0.6rem; padding: 2px;">INHOUSE</button></div>
                            <div class="col-4"><button class="btn btn-outline-secondary btn-sm w-100" style="font-size: 0.6rem; padding: 2px;">CASH</button></div>
                            <div class="col-4"><button class="btn btn-outline-secondary btn-sm w-100" style="font-size: 0.6rem; padding: 2px;">GCASH</button></div>
                        </div>
                        <div class="row g-1">
                            <div class="col-4"><button class="btn btn-outline-secondary btn-sm w-100" style="font-size: 0.6rem; padding: 2px;">GIFT CARD</button></div>
                            <div class="col-4"><button class="btn btn-outline-secondary btn-sm w-100" style="font-size: 0.6rem; padding: 2px;">LOYALTY</button></div>
                            <div class="col-4"></div>
                        </div>
                    </div>
                    
                    <button id="complete-sale" class="btn btn-complete-sale w-100 btn-sm" disabled>
                        Complete Sale
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Products Section -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header py-2">
                <h6 class="mb-0">Products</h6>
            </div>
            <div class="card-body p-2">
                <!-- Search Bar -->
                <div class="mb-2">
                    <input type="text" id="product-search" class="form-control form-control-sm" placeholder="Search by name, code, SKU, or barcode..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <!-- Products Grid -->
                <div style="max-height: calc(100vh - 280px); overflow-y: auto;">
                    <div class="row g-2">
                    <?php if (empty($products)): ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No products found</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <?php foreach ($products as $product): ?>
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="product-card p-2 border rounded" onclick="addToCart(<?php echo htmlspecialchars(json_encode($product)); ?>)" style="cursor: pointer; transition: all 0.2s;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1" style="font-size: 0.8rem; line-height: 1.2;"><?php echo htmlspecialchars($product['name']); ?></h6>
                                    <div class="text-success fw-bold" style="font-size: 0.85rem;"><?php echo formatCurrency($product['price']); ?></div>
                                    <small class="text-muted" style="font-size: 0.7rem;">Stock: <?php echo $product['stock_quantity']; ?></small>
                                </div>
                                <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); addToCart(<?php echo htmlspecialchars(json_encode($product)); ?>)" style="padding: 2px 6px;">
                                    <i class="fas fa-plus" style="font-size: 0.7rem;"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2 bg-light">
                <h6 class="modal-title">Receipt</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-2" id="receipt-content" style="font-family: 'Courier New', monospace; font-size: 11px;">
                <!-- Receipt content will be inserted here -->
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];
let discountPercent = 0;

function addToCart(product) {
    const quantityInput = event.target.closest('.product-card')?.querySelector('input[type="number"]');
    const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
    
    // Check if product already in cart
    const existingItem = cart.find(item => item.id === product.id);
    
    if (existingItem) {
        existingItem.quantity += quantity;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: parseFloat(product.price),
            quantity: quantity,
            stock: product.stock_quantity
        });
    }
    
    updateCartDisplay();
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCartDisplay();
}

function updateCartQuantity(productId, newQuantity) {
    const item = cart.find(item => item.id === productId);
    if (item) {
        if (newQuantity <= 0) {
            removeFromCart(productId);
        } else {
            item.quantity = newQuantity;
            updateCartDisplay();
        }
    }
}

function updateCartDisplay() {
    const cartTbody = document.getElementById('cart-tbody');
    
    if (cart.length === 0) {
        cartTbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-2" style="font-size: 0.8rem;">Cart is empty</td></tr>';
        document.getElementById('complete-sale').disabled = true;
    } else {
        let html = '';
        cart.forEach(item => {
            const total = item.price * item.quantity;
            html += `
                <tr style="font-size: 0.75rem;">
                    <td class="py-1">
                        <div style="font-weight: 500;">${item.name}</div>
                        <small class="text-muted">₱${item.price.toFixed(2)}</small>
                    </td>
                    <td class="py-1">₱${item.price.toFixed(2)}</td>
                    <td class="py-1">
                        <div class="d-flex align-items-center">
                            <input type="number" class="form-control form-control-sm" style="width: 40px; height: 24px; font-size: 0.7rem;" 
                                   value="${item.quantity}" min="1" max="${item.stock}"
                                   onchange="updateCartQuantity(${item.id}, parseInt(this.value))">
                            <button class="btn btn-sm btn-outline-danger ms-1" onclick="removeFromCart(${item.id})" style="padding: 1px 4px;">
                                <i class="fas fa-times" style="font-size: 0.6rem;"></i>
                            </button>
                        </div>
                    </td>
                    <td class="py-1 text-end">₱${total.toFixed(2)}</td>
                </tr>
            `;
        });
        cartTbody.innerHTML = html;
        document.getElementById('complete-sale').disabled = false;
    }
    
    updateTotals();
}

function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const discount = (subtotal * discountPercent) / 100;
    const discountedSubtotal = subtotal - discount;
    const tax = discountedSubtotal * 0.12;
    const total = discountedSubtotal + tax;
    
    document.getElementById('subtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('discount-amount').textContent = '-₱' + discount.toFixed(2);
    document.getElementById('tax').textContent = '₱' + tax.toFixed(2);
    document.getElementById('total').textContent = '₱' + total.toFixed(2);
}

// Discount type handler
function applyDiscountType(value) {
    const discountInput = document.getElementById('discount');
    if (value === 'custom') {
        discountInput.classList.remove('d-none');
        discountInput.focus();
    } else {
        discountInput.classList.add('d-none');
        discountPercent = parseFloat(value) || 0;
        discountInput.value = discountPercent;
        updateTotals();
    }
}

// Discount input handler
function updateCart() {
    discountPercent = parseFloat(document.getElementById('discount').value) || 0;
    updateTotals();
}

// Quick payment buttons
document.addEventListener('click', function(e) {
    if (e.target.closest('.payment-buttons button')) {
        const button = e.target.closest('button');
        const text = button.textContent.trim();
        const paymentMethodSelect = document.getElementById('payment-method');
        const amountReceivedInput = document.getElementById('amount-received');
        const discountInput = document.getElementById('discount');
        
        switch(text) {
            case 'CASH':
                paymentMethodSelect.value = 'cash';
                const total = parseFloat(document.getElementById('total').textContent.replace('₱', '')) || 0;
                amountReceivedInput.value = total.toFixed(2);
                break;
                
            case 'GCASH':
                paymentMethodSelect.value = 'gcash';
                const totalGcash = parseFloat(document.getElementById('total').textContent.replace('₱', '')) || 0;
                amountReceivedInput.value = totalGcash.toFixed(2);
                break;
                
            case 'DISCOUNT':
                const discountPercent = prompt('Enter discount percentage (0-100):');
                if (discountPercent !== null && !isNaN(discountPercent) && discountPercent >= 0 && discountPercent <= 100) {
                    discountInput.value = discountPercent;
                    discountInput.dispatchEvent(new Event('input'));
                }
                break;
                
            case 'DELETE ITEM':
                if (cart.length > 0) {
                    const itemNames = cart.map((item, index) => `${index + 1}. ${item.name}`).join('\n');
                    const itemIndex = prompt(`Select item to delete:\n${itemNames}\n\nEnter item number:`);
                    if (itemIndex && !isNaN(itemIndex)) {
                        const index = parseInt(itemIndex) - 1;
                        if (index >= 0 && index < cart.length) {
                            cart.splice(index, 1);
                            updateCartDisplay();
                        }
                    }
                }
                break;
                
            case 'PROMO':
                alert('Promo functionality not implemented yet');
                break;
                
            case 'INHOUSE':
                alert('In-house payment functionality not implemented yet');
                break;
                
            case 'GIFT CARD':
                alert('Gift card functionality not implemented yet');
                break;
                
            case 'LOYALTY':
                alert('Loyalty program functionality not implemented yet');
                break;
        }
    }
});

// Complete sale
document.getElementById('complete-sale').addEventListener('click', function() {
    const amountReceived = parseFloat(document.getElementById('amount-received').value);
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const discount = (subtotal * discountPercent) / 100;
    const discountedSubtotal = subtotal - discount;
    const tax = discountedSubtotal * 0.12;
    const total = discountedSubtotal + tax;
    
    if (!amountReceived || amountReceived < total) {
        alert(`Amount received (₱${amountReceived ? amountReceived.toFixed(2) : '0.00'}) is less than total amount (₱${total.toFixed(2)})!`);
        return;
    }
    
    const paymentMethod = document.getElementById('payment-method').value;
    
    // Send order to server
    fetch('pos.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=complete_sale&items=${encodeURIComponent(JSON.stringify(cart))}&amount_received=${amountReceived}&payment_method=${paymentMethod}&discount_percent=${discountPercent}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Generate receipt
            const receiptHTML = generateReceipt(data, cart);
            document.getElementById('receipt-content').innerHTML = receiptHTML;
            
            // Show receipt modal
            const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
            receiptModal.show();
            
            // Clear cart after showing receipt
            cart = [];
            updateCartDisplay();
            document.getElementById('amount-received').value = '';
            document.getElementById('discount').value = '0';
            document.getElementById('discount-type').value = '0';
            discountPercent = 0;
        } else {
            alert('Error completing sale: ' + data.message);
        }
    });
});

// Search functionality
document.getElementById('product-search').addEventListener('keyup', function(e) {
    if (e.key === 'Enter') {
        window.location.href = `pos.php?search=${encodeURIComponent(this.value)}`;
    }
});

// Generate receipt HTML
function generateReceipt(data, items) {
    const now = new Date();
    const dateStr = now.toLocaleDateString('en-PH');
    const timeStr = now.toLocaleTimeString('en-PH');
    const cashierName = '<?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>';
    
    let itemsHTML = '';
    items.forEach(item => {
        const itemTotal = item.price * item.quantity;
        itemsHTML += `
            <tr style="font-size: 10px;">
                <td>${item.name}</td>
                <td class="text-center">${item.quantity}</td>
                <td class="text-end">₱${item.price.toFixed(2)}</td>
                <td class="text-end">₱${itemTotal.toFixed(2)}</td>
            </tr>
        `;
    });
    
    return `
        <div class="text-center mb-2">
            <h5 class="mb-0" style="font-size: 14px;"><strong>PointShift POS</strong></h5>
            <small style="font-size: 9px;">Modern Point of Sale System</small>
        </div>
        <hr class="my-1">
        <div style="font-size: 9px;">
            <div><strong>Date:</strong> ${dateStr} ${timeStr}</div>
            <div><strong>Order #:</strong> ${data.order_number}</div>
            <div><strong>Cashier:</strong> ${cashierName}</div>
        </div>
        <hr class="my-1">
        <table class="table table-sm mb-1" style="font-size: 10px;">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Price</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                ${itemsHTML}
            </tbody>
        </table>
        <hr class="my-1">
        <table class="table table-sm mb-0" style="font-size: 10px;">
            <tr>
                <td>Subtotal:</td>
                <td class="text-end">₱${data.subtotal.toFixed(2)}</td>
            </tr>
            <tr>
                <td>Discount:</td>
                <td class="text-end">-₱${data.discount.toFixed(2)}</td>
            </tr>
            <tr>
                <td>Tax (12%):</td>
                <td class="text-end">₱${data.tax.toFixed(2)}</td>
            </tr>
            <tr class="fw-bold" style="font-size: 12px; border-top: 2px solid #000;">
                <td>TOTAL:</td>
                <td class="text-end">₱${data.total.toFixed(2)}</td>
            </tr>
            <tr>
                <td>Amount Paid:</td>
                <td class="text-end">₱${(data.total + data.change).toFixed(2)}</td>
            </tr>
            <tr>
                <td>Change:</td>
                <td class="text-end">₱${data.change.toFixed(2)}</td>
            </tr>
        </table>
        <hr class="my-1">
        <div class="text-center" style="font-size: 9px;">
            <p class="mb-0">Thank you for your purchase!</p>
            <p class="mb-0">Visit us again soon!</p>
        </div>
    `;
}
</script>

<style>
.product-card:hover {
    background-color: #f8f9fa !important;
    border-color: #007bff !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
}

.btn-complete-sale {
    background-color: #28a745;
    border-color: #28a745;
    color: white;
    font-weight: 600;
    padding: 8px 16px;
}

.btn-complete-sale:hover {
    background-color: #218838;
    border-color: #1e7e34;
}

.btn-complete-sale:disabled {
    background-color: #6c757d;
    border-color: #6c757d;
    opacity: 0.5;
}

.nav-pills .nav-link.active {
    background-color: #007bff;
    color: white;
}

.nav-pills .nav-link {
    background-color: #f8f9fa;
    color: #6c757d;
    margin: 0 2px;
    border-radius: 4px;
}

.nav-pills .nav-link:hover {
    background-color: #e9ecef;
    color: #495057;
}

.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.payment-buttons .btn {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    color: #495057;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.payment-buttons .btn:hover {
    background-color: #e9ecef;
    border-color: #adb5bd;
    color: #212529;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.payment-buttons .btn:active {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
}

/* Print styles for receipt */
@media print {
    body * { visibility: hidden; }
    #receipt-content, #receipt-content * { visibility: visible; }
    #receipt-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 80mm;
    }
    .modal-header, .modal-footer, .btn-close { display: none !important; }
}

@media (max-width: 991px) {
    .col-lg-5, .col-lg-7 {
        margin-bottom: 15px;
    }
    
    .card-body {
        max-height: none !important;
    }
    
    #cart-items {
        max-height: 150px !important;
    }
}

@media (max-width: 576px) {
    .col-md-6 {
        flex: 0 0 50%;
        max-width: 50%;
    }
    
    .product-card h6 {
        font-size: 0.7rem !important;
        line-height: 1.1 !important;
    }
    
    .product-card .text-success {
        font-size: 0.75rem !important;
    }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
?>
