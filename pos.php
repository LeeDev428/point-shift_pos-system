<?php
require_once 'config.php';
requireLogin();

$page_title = 'Point of Sale';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header">
                <h5 class="mb-0">Point of Sale System</h5>
            </div>
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                    <h4>POS Terminal</h4>
                    <p class="text-muted">This section will contain the point of sale interface for processing transactions.</p>
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-barcode fa-2x text-primary mb-2"></i>
                                    <h6>Barcode Scanner</h6>
                                    <p class="small text-muted">Scan product barcodes</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-credit-card fa-2x text-warning mb-2"></i>
                                    <h6>Payment Processing</h6>
                                    <p class="small text-muted">Process customer payments</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-receipt fa-2x text-success mb-2"></i>
                                    <h6>Receipt Generation</h6>
                                    <p class="small text-muted">Generate customer receipts</p>
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
