<?php
$page_title = 'Inventory Management';
requireAdmin(); // Only admin can access inventory

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Inventory Management</h5>
                <button class="btn btn-primary btn-custom">
                    <i class="fas fa-plus me-2"></i>Add Product
                </button>
            </div>
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fas fa-boxes fa-4x text-muted mb-3"></i>
                    <h4>Inventory Management</h4>
                    <p class="text-muted">This section will contain product inventory management features.</p>
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-plus-circle fa-2x text-primary mb-2"></i>
                                    <h6>Add Products</h6>
                                    <p class="small text-muted">Add new products to inventory</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-edit fa-2x text-warning mb-2"></i>
                                    <h6>Edit Products</h6>
                                    <p class="small text-muted">Update product information</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-chart-bar fa-2x text-success mb-2"></i>
                                    <h6>Stock Management</h6>
                                    <p class="small text-muted">Monitor stock levels</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout.php';
?>
