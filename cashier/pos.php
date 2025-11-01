<?php
require_once '../config.php';
User::requireLogin();

// Redirect admin to admin panel
if (User::isAdmin()) {
    header('Location: ../pos.php');
    exit();
}

$posController = new POSController();

// Handle GET request for GCash QR Code
if (isset($_GET['action']) && $_GET['action'] === 'get_gcash_qr') {
    header('Content-Type: application/json');
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT qr_code_path FROM payment_qrcodes WHERE payment_method = ? AND is_active = 1 LIMIT 1");
    $stmt->execute(['gcash']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row && !empty($row['qr_code_path'])) {
        echo json_encode([
            'success' => true,
            'qr_code_path' => $row['qr_code_path']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No QR code found'
        ]);
    }
    exit();
}

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
    <!-- Products Section (Right Side on Desktop) -->
    <div class="col-lg-7 order-lg-2">
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
    
    <!-- Shopping Cart Section (Left Side on Desktop) -->
    <div class="col-lg-5 order-lg-1">
        <div class="card h-100">
            <div class="card-header py-2">
                <h6 class="mb-0">Shopping Cart</h6>
            </div>
            <div class="card-body p-2 d-flex flex-column" style="max-height: calc(100vh - 200px); overflow-y: auto;">
                <!-- Cart Items -->
                <div id="cart-items" style="max-height: 220px; overflow-y: auto; margin-bottom: 12px; background: #f8f9fa; border-radius: 6px; padding: 8px;">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0" style="font-size: 0.75rem;">
                            <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 1;">
                                <tr style="border-bottom: 2px solid #dee2e6;">
                                    <th class="py-2" style="font-weight: 600; color: #495057;">ITEM</th>
                                    <th class="py-2 text-center" style="font-weight: 600; color: #495057; width: 80px;">QTY</th>
                                    <th class="py-2 text-end" style="font-weight: 600; color: #495057; width: 90px;">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody id="cart-tbody">
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4" style="font-size: 0.85rem;">
                                        <i class="fas fa-shopping-cart mb-2" style="font-size: 2rem; opacity: 0.3;"></i>
                                        <div>Cart is empty</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Cart Summary (Compact) -->
                <div class="border-top pt-2" style="background: #f8f9fa; border-radius: 6px; padding: 10px; margin-bottom: 10px;">
                    <div class="row g-1 mb-1">
                        <div class="col-6"><small style="font-weight: 500;">Subtotal:</small></div>
                        <div class="col-6 text-end"><small id="subtotal" style="font-weight: 600;">₱0.00</small></div>
                    </div>
                    <div class="row g-1 mb-1 align-items-center">
                        <div class="col-6">
                            <small style="font-weight: 500;">Discount:</small>
                            <input type="number" id="discount" class="form-control form-control-sm d-inline" style="width: 45px; height: 22px; font-size: 0.7rem; padding: 2px 4px;" value="0" min="0" max="100">
                            <small>%</small>
                        </div>
                        <div class="col-6 text-end"><small id="discount-amount" style="font-weight: 600; color: #198754;">-₱0.00</small></div>
                    </div>
                    <div class="row g-1 mb-2" style="border-bottom: 1px solid #dee2e6; padding-bottom: 8px;">
                        <div class="col-6"><small style="font-weight: 500;">Tax (12%):</small></div>
                        <div class="col-6 text-end"><small id="tax" style="font-weight: 600;">₱0.00</small></div>
                    </div>
                    <div class="row g-1">
                        <div class="col-6"><strong style="font-size: 1.1rem; color: #212529;">TOTAL:</strong></div>
                        <div class="col-6 text-end"><strong id="total" style="font-size: 1.1rem; color: #dc3545;">₱0.00</strong></div>
                    </div>
                </div>
                
                <!-- Payment Details -->
                <div style="background: #fff; border: 1px solid #dee2e6; border-radius: 6px; padding: 10px; margin-bottom: 10px;">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label style="font-size: 0.75rem; font-weight: 600; color: #495057; margin-bottom: 4px; display: block;">Payment Method:</label>
                            <select id="payment-method" class="form-select form-select-sm" style="font-size: 0.8rem;">
                                <option value="cash">Cash</option>
                                <option value="gcash">GCash</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label style="font-size: 0.75rem; font-weight: 600; color: #495057; margin-bottom: 4px; display: block;">Amount Received:</label>
                            <input type="number" id="amount-received" class="form-control form-control-sm" step="0.01" min="0" placeholder="0.00" style="font-size: 0.8rem;">
                        </div>
                    </div>
                    
                    <!-- Quick Payment Amount Buttons -->
                    <div class="payment-buttons mb-2">
                        <small class="d-block mb-1" style="font-size: 0.7rem; color: #6c757d;">Quick Amount:</small>
                        <div class="row g-1 mb-1">
                            <div class="col-4"><button class="btn btn-outline-primary btn-sm w-100 quick-amount" data-amount="20" style="font-size: 0.7rem; padding: 4px;">₱20</button></div>
                            <div class="col-4"><button class="btn btn-outline-primary btn-sm w-100 quick-amount" data-amount="50" style="font-size: 0.7rem; padding: 4px;">₱50</button></div>
                            <div class="col-4"><button class="btn btn-outline-primary btn-sm w-100 quick-amount" data-amount="100" style="font-size: 0.7rem; padding: 4px;">₱100</button></div>
                        </div>
                        <div class="row g-1 mb-1">
                            <div class="col-4"><button class="btn btn-outline-primary btn-sm w-100 quick-amount" data-amount="200" style="font-size: 0.7rem; padding: 4px;">₱200</button></div>
                            <div class="col-4"><button class="btn btn-outline-primary btn-sm w-100 quick-amount" data-amount="500" style="font-size: 0.7rem; padding: 4px;">₱500</button></div>
                            <div class="col-4"><button class="btn btn-outline-primary btn-sm w-100 quick-amount" data-amount="1000" style="font-size: 0.7rem; padding: 4px;">₱1000</button></div>
                        </div>
                        <div class="row g-1">
                            <div class="col-6"><button class="btn btn-outline-success btn-sm w-100" id="exact-amount-btn" style="font-size: 0.7rem; padding: 4px;">Exact Amount</button></div>
                            <div class="col-6"><button class="btn btn-outline-danger btn-sm w-100" id="clear-amount-btn" style="font-size: 0.7rem; padding: 4px;">Clear</button></div>
                        </div>
                    </div>
                    
                    <!-- Change Display -->
                    <div id="change-display" class="alert alert-success py-1 px-2 mb-2" style="display: none; font-size: 0.8rem;">
                        <div class="d-flex justify-content-between">
                            <strong>Change:</strong>
                            <strong id="change-amount">₱0.00</strong>
                        </div>
                    </div>
                </div>
                    
                <button id="complete-sale" class="btn btn-success w-100" style="font-weight: 600; padding: 10px; font-size: 0.95rem;" disabled>
                    <i class="fas fa-check-circle me-1"></i> Complete Sale
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="receiptModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Receipt printed successfully!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="receiptContent" style="font-family: 'Courier New', monospace; background: #fff;">
                <!-- Receipt content will be dynamically generated here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print me-1"></i>Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<!-- GCash QR Code Modal -->
<div class="modal fade" id="gcashQRModal" tabindex="-1" aria-labelledby="gcashQRModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="gcashQRModalLabel">
                    <i class="fas fa-qrcode me-2"></i>Scan GCash QR Code
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center" id="gcashQRContent">
                <p class="text-muted mb-3">Scan the QR code below to pay with GCash</p>
                <div id="qrCodeImage">
                    <!-- QR Code will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
        cartTbody.innerHTML = `<tr>
            <td colspan="3" class="text-center text-muted py-4" style="font-size: 0.85rem;">
                <i class="fas fa-shopping-cart mb-2" style="font-size: 2rem; opacity: 0.3;"></i>
                <div>Cart is empty</div>
            </td>
        </tr>`;
        document.getElementById('complete-sale').disabled = true;
    } else {
        let html = '';
        cart.forEach(item => {
            const total = item.price * item.quantity;
            html += `
                <tr style="border-bottom: 1px solid #e9ecef;">
                    <td class="py-2">
                        <div style="font-weight: 600; font-size: 0.8rem; color: #212529; margin-bottom: 2px;">${item.name}</div>
                        <div style="font-size: 0.7rem; color: #6c757d;">₱${item.price.toFixed(2)} each</div>
                    </td>
                    <td class="py-2 text-center">
                        <div class="d-flex align-items-center justify-content-center gap-1">
                            <button class="btn btn-sm btn-outline-secondary" onclick="updateCartQuantity(${item.id}, ${item.quantity - 1})" style="padding: 2px 6px; font-size: 0.7rem; line-height: 1;">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span style="font-weight: 600; min-width: 25px; text-align: center; font-size: 0.85rem;">${item.quantity}</span>
                            <button class="btn btn-sm btn-outline-secondary" onclick="updateCartQuantity(${item.id}, ${item.quantity + 1})" style="padding: 2px 6px; font-size: 0.7rem; line-height: 1;">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${item.id})" style="padding: 2px 6px; font-size: 0.7rem; line-height: 1;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                    <td class="py-2 text-end">
                        <div style="font-weight: 700; font-size: 0.9rem; color: #0d6efd;">₱${total.toFixed(2)}</div>
                    </td>
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
    calculateChange();
});

// Amount received input handler
document.getElementById('amount-received').addEventListener('input', function() {
    calculateChange();
});

// Quick amount buttons
document.querySelectorAll('.quick-amount').forEach(button => {
    button.addEventListener('click', function() {
        const amount = parseFloat(this.dataset.amount);
        document.getElementById('amount-received').value = amount.toFixed(2);
        calculateChange();
    });
});

// Exact amount button
document.getElementById('exact-amount-btn').addEventListener('click', function() {
    const total = parseFloat(document.getElementById('total').textContent.replace('₱', '')) || 0;
    document.getElementById('amount-received').value = total.toFixed(2);
    calculateChange();
});

// Clear amount button
document.getElementById('clear-amount-btn').addEventListener('click', function() {
    document.getElementById('amount-received').value = '';
    document.getElementById('change-display').style.display = 'none';
});

// Calculate change
function calculateChange() {
    const total = parseFloat(document.getElementById('total').textContent.replace('₱', '')) || 0;
    const amountReceived = parseFloat(document.getElementById('amount-received').value) || 0;
    const change = amountReceived - total;
    
    const changeDisplay = document.getElementById('change-display');
    const changeAmount = document.getElementById('change-amount');
    
    if (amountReceived > 0) {
        if (change >= 0) {
            changeDisplay.className = 'alert alert-success py-1 px-2 mb-2';
            changeAmount.textContent = '₱' + change.toFixed(2);
            changeDisplay.style.display = 'block';
        } else {
            changeDisplay.className = 'alert alert-danger py-1 px-2 mb-2';
            changeAmount.textContent = 'Short: ₱' + Math.abs(change).toFixed(2);
            changeDisplay.style.display = 'block';
        }
    } else {
        changeDisplay.style.display = 'none';
    }
}

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
            // Generate and show receipt
            generateReceipt(data);
            
            // Clear cart
            cart = [];
            updateCartDisplay();
            document.getElementById('amount-received').value = '';
            document.getElementById('discount').value = '0';
            discountPercent = 0;
            document.getElementById('change-display').style.display = 'none';
            
            // Show receipt modal
            const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
            receiptModal.show();
        } else {
            alert('Error completing sale: ' + data.message);
        }
    });
});

// Generate Receipt HTML
function generateReceipt(orderData) {
    const now = new Date();
    const dateStr = now.toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' });
    const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    
    const subtotal = orderData.subtotal || 0;
    const total = orderData.total || 0;
    const amountPaid = parseFloat(document.getElementById('amount-received').value) || 0;
    const change = orderData.change || (amountPaid - total);
    const paymentMethod = document.getElementById('payment-method').value.toUpperCase();
    
    let itemsHTML = '';
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        itemsHTML += `
            <tr>
                <td style="padding: 4px 0;">${item.name}</td>
                <td style="padding: 4px 0; text-align: center;">${item.quantity} x ₱${item.price.toFixed(2)}</td>
                <td style="padding: 4px 0; text-align: right;">₱${itemTotal.toFixed(2)}</td>
            </tr>
        `;
    });
    
    const receiptHTML = `
        <div style="max-width: 400px; margin: 0 auto; padding: 20px; text-align: center;">
            <h4 style="margin: 0 0 5px 0;">PointShift</h4>
            <p style="margin: 0; font-size: 11px; line-height: 1.4;"><br>
            
            </p>
            
            <hr style="border-top: 1px solid #000; margin: 15px 0;">
            
            <div style="text-align: left; font-size: 12px; margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                    <span>Sale #: <strong>${orderData.order_number || 'N/A'}</strong></span>
                    <span>${dateStr} ${timeStr}</span>
                </div>
                <div style="margin-bottom: 3px;">
                    <span>Cashier: <strong><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Staff') . ' ' . htmlspecialchars($_SESSION['last_name'] ?? 'Member'); ?></strong></span>
                </div>
                <div>
                    <span>Customer: <strong>Walk-in</strong></span>
                </div>
            </div>
            
            <table style="width: 100%; font-size: 12px; margin-bottom: 15px;">
                <tbody>
                    ${itemsHTML}
                </tbody>
            </table>
            
            <hr style="border-top: 1px dashed #000; margin: 15px 0;">
            
            <div style="text-align: left; font-size: 13px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>Subtotal:</span>
                    <span>₱${subtotal.toFixed(2)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #000;">
                    <strong style="font-size: 15px;">TOTAL:</strong>
                    <strong style="font-size: 15px;">₱${total.toFixed(2)}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>Payment (${paymentMethod}):</span>
                    <span>₱${amountPaid.toFixed(2)}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Change:</span>
                    <span>₱${change.toFixed(2)}</span>
                </div>
            </div>
            
            <hr style="border-top: 1px solid #000; margin: 20px 0 15px 0;">
            
            <p style="margin: 5px 0; font-size: 11px;">Thank you for your business!</p>
            <p style="margin: 5px 0; font-size: 11px;">Please come again!</p>
        </div>
    `;
    
    document.getElementById('receiptContent').innerHTML = receiptHTML;
}

// Payment Method Change - Show GCash QR if selected
document.getElementById('payment-method').addEventListener('change', function() {
    if (this.value === 'gcash') {
        // Fetch and show GCash QR code
        fetch('pos.php?action=get_gcash_qr')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.qr_code_path) {
                    document.getElementById('qrCodeImage').innerHTML = `
                        <img src="../${data.qr_code_path}" alt="GCash QR Code" class="img-fluid" style="max-width: 300px; border: 2px solid #0d6efd; border-radius: 8px;">
                    `;
                    const gcashModal = new bootstrap.Modal(document.getElementById('gcashQRModal'));
                    gcashModal.show();
                } else {
                    document.getElementById('qrCodeImage').innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No GCash QR code available. Please contact administrator.
                        </div>
                    `;
                    const gcashModal = new bootstrap.Modal(document.getElementById('gcashQRModal'));
                    gcashModal.show();
                }
            })
            .catch(error => {
                console.error('Error fetching GCash QR:', error);
                alert('Error loading GCash QR code');
            });
    }
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
