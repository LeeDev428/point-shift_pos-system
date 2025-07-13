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

<div class="row">
    <!-- Cart Section -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header py-2">
                <h6 class="mb-0">Shopping Cart</h6>
            </div>
            <div class="card-body p-2 d-flex flex-column" style="max-height: calc(100vh - 200px); overflow-y: auto;">
                <!-- Cart Items -->
                <div id="cart-items" style="max-height: 180px; overflow-y: auto; margin-bottom: 10px;">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr class="text-muted" style="font-size: 0.75rem;">
                                    <th class="py-1">ITEM</th>
                                    <th class="py-1">PRICE</th>
                                    <th class="py-1">QTY</th>
                                    <th class="py-1">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody id="cart-tbody">
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-2" style="font-size: 0.8rem;">Cart is empty</td>
                                </tr>
                            </tbody>
                        </table>
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
                            <input type="number" id="discount" class="form-control form-control-sm d-inline" style="width: 40px; height: 20px; font-size: 0.7rem;" value="0" min="0">%
                        </div>
                        <div class="col-6 text-end"><small id="discount-amount">₱0.00</small></div>
                    </div>
                    <div class="row g-1 mb-1">
                        <div class="col-6"><small>Tax (12%):</small></div>
                        <div class="col-6 text-end"><small id="tax">₱0.00</small></div>
                    </div>
                    <div class="row g-1 mb-2">
                        <div class="col-6"><strong style="font-size: 0.9rem;">Total:</strong></div>
                        <div class="col-6 text-end"><strong id="total" class="text-danger" style="font-size: 0.9rem;">₱0.00</strong></div>
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
                    <input type="text" id="product-search" class="form-control form-control-sm" placeholder="Search by name or code..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <!-- Category Tabs -->
                <div class="mb-2">
                    <nav class="nav nav-pills nav-fill">
                        <a class="nav-link py-1 <?php echo empty($category) ? 'active' : ''; ?>" href="?category=" style="font-size: 0.75rem;">All</a>
                        <a class="nav-link py-1 <?php echo $category === 'Electronics' ? 'active' : ''; ?>" href="?category=Electronics" style="font-size: 0.75rem;">Electronics</a>
                        <a class="nav-link py-1 <?php echo $category === 'Clothing' ? 'active' : ''; ?>" href="?category=Clothing" style="font-size: 0.75rem;">Clothing</a>
                        <a class="nav-link py-1 <?php echo $category === 'Food' ? 'active' : ''; ?>" href="?category=Food" style="font-size: 0.75rem;">Food</a>
                        <a class="nav-link py-1 <?php echo $category === 'Automotive' ? 'active' : ''; ?>" href="?category=Automotive" style="font-size: 0.75rem;">Auto</a>
                    </nav>
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

// Discount input handler
document.getElementById('discount').addEventListener('input', function() {
    discountPercent = parseFloat(this.value) || 0;
    updateTotals();
});

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
            alert(`Sale completed!\nOrder: ${data.order_number}\nTotal: ₱${data.total.toFixed(2)}\nChange: ₱${data.change.toFixed(2)}`);
            
            // Clear cart
            cart = [];
            updateCartDisplay();
            document.getElementById('amount-received').value = '';
            document.getElementById('discount').value = '0';
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
