<?php
require_once 'config.php';
requireAdmin(); // Only admin can access user management

$page_title = 'User Management';

ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">User Management</h5>
                <button class="btn btn-primary btn-custom">
                    <i class="fas fa-user-plus me-2"></i>Add User
                </button>
            </div>
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fas fa-users fa-4x text-muted mb-3"></i>
                    <h4>User Management</h4>
                    <p class="text-muted">This section will contain user management features for administrators.</p>
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-user-plus fa-2x text-primary mb-2"></i>
                                    <h6>Add Users</h6>
                                    <p class="small text-muted">Create new user accounts</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-user-edit fa-2x text-warning mb-2"></i>
                                    <h6>Edit Users</h6>
                                    <p class="small text-muted">Modify user information</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <i class="fas fa-user-shield fa-2x text-success mb-2"></i>
                                    <h6>Role Management</h6>
                                    <p class="small text-muted">Assign user roles and permissions</p>
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
