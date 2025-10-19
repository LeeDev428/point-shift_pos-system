<?php
require_once 'config.php';
requireLogin();
requireAdmin();

$page_title = "Settings";

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Store Configuration Update
    if ($action === 'update_store_config') {
        $settings = [
            'store_name' => $_POST['store_name'],
            'store_branch' => $_POST['store_branch'],
            'store_address' => $_POST['store_address'],
            'store_phone' => $_POST['store_phone'],
            'store_email' => $_POST['store_email'],
            'business_hours_open' => $_POST['business_hours_open'],
            'business_hours_close' => $_POST['business_hours_close'],
            'business_days' => $_POST['business_days'],
            'receipt_header' => $_POST['receipt_header'],
            'receipt_footer' => $_POST['receipt_footer'],
            'tax_rate' => $_POST['tax_rate'],
            'currency_symbol' => $_POST['currency_symbol'],
            'receipt_show_logo' => isset($_POST['receipt_show_logo']) ? '1' : '0',
            'receipt_show_cashier' => isset($_POST['receipt_show_cashier']) ? '1' : '0'
        ];
        
        foreach ($settings as $key => $value) {
            $stmt = $conn->prepare("INSERT INTO store_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $key, $value, $value);
            $stmt->execute();
        }
        
        // Handle logo upload
        if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['store_logo']['name'], PATHINFO_EXTENSION);
            $new_filename = 'logo_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['store_logo']['tmp_name'], $upload_path)) {
                $stmt = $conn->prepare("INSERT INTO store_settings (setting_key, setting_value, setting_type) VALUES ('store_logo', ?, 'image') ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("ss", $upload_path, $upload_path);
                $stmt->execute();
            }
        }
        
        $message = "Store settings updated successfully!";
        $message_type = "success";
    }
    
    // GCash QR Code Upload
    if ($action === 'upload_gcash_qr') {
        if (isset($_FILES['gcash_qr']) && $_FILES['gcash_qr']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/qrcodes/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['gcash_qr']['name'], PATHINFO_EXTENSION);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array(strtolower($file_extension), $allowed_extensions)) {
                $new_filename = 'gcash_qr_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['gcash_qr']['tmp_name'], $upload_path)) {
                    // Insert or update in payment_qrcodes table
                    $stmt = $conn->prepare("INSERT INTO payment_qrcodes (payment_method, qr_code_path, description, is_active) VALUES ('gcash', ?, 'GCash Payment QR Code', 1) ON DUPLICATE KEY UPDATE qr_code_path = ?, updated_at = CURRENT_TIMESTAMP");
                    $stmt->bind_param("ss", $upload_path, $upload_path);
                    $stmt->execute();
                    
                    $message = "GCash QR code uploaded successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error uploading QR code!";
                    $message_type = "danger";
                }
            } else {
                $message = "Invalid file type! Only JPG, PNG, and GIF allowed.";
                $message_type = "danger";
            }
        } else {
            $message = "Please select a QR code image to upload.";
            $message_type = "warning";
        }
    }
    
    // Delete GCash QR Code
    if ($action === 'delete_gcash_qr') {
        $stmt = $conn->prepare("SELECT qr_code_path FROM payment_qrcodes WHERE payment_method = 'gcash' AND is_active = 1 LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (file_exists($row['qr_code_path'])) {
                unlink($row['qr_code_path']);
            }
            
            $stmt = $conn->prepare("UPDATE payment_qrcodes SET qr_code_path = '', is_active = 0 WHERE payment_method = 'gcash'");
            $stmt->execute();
            
            $message = "GCash QR code deleted successfully!";
            $message_type = "success";
        }
    }
    
    // User Management Actions (from user_management.php)
    if ($action === 'create_user') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $status = $_POST['status'];
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, first_name, last_name, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $username, $email, $hashed_password, $role, $first_name, $last_name, $status);
        
        if ($stmt->execute()) {
            $message = "User created successfully!";
            $message_type = "success";
        } else {
            $message = "Error creating user: " . $conn->error;
            $message_type = "danger";
        }
    }
    
    if ($action === 'update_user') {
        $user_id = $_POST['user_id'];
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE users SET email=?, role=?, first_name=?, last_name=?, status=? WHERE id=?");
        $stmt->bind_param("sssssi", $email, $role, $first_name, $last_name, $status, $user_id);
        
        if ($stmt->execute()) {
            $message = "User updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating user.";
            $message_type = "danger";
        }
    }
    
    if ($action === 'reset_password') {
        $user_id = $_POST['user_id'];
        $new_password = $_POST['new_password'];
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            $message = "Password reset successfully!";
            $message_type = "success";
        } else {
            $message = "Error resetting password.";
            $message_type = "danger";
        }
    }
    
    if ($action === 'delete_user') {
        $user_id = $_POST['user_id'];
        
        $check_stmt = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result()->fetch_assoc();
        
        if ($result['order_count'] > 0) {
            $message = "Cannot delete user with existing orders. Set status to 'inactive' instead.";
            $message_type = "warning";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                $message = "User deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error deleting user.";
                $message_type = "danger";
            }
        }
    }
    
    // Shift Management Actions
    if ($action === 'create_shift') {
        $shift_name = trim($_POST['shift_name']);
        $shift_date = $_POST['shift_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $description = trim($_POST['description']);
        $location = trim($_POST['location']);
        $max_employees = intval($_POST['max_employees']);
        $created_by = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("INSERT INTO shifts (shift_name, shift_date, start_time, end_time, description, location, max_employees, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssii", $shift_name, $shift_date, $start_time, $end_time, $description, $location, $max_employees, $created_by);
        
        if ($stmt->execute()) {
            $message = "Shift created successfully!";
            $message_type = "success";
        } else {
            $message = "Error creating shift: " . $conn->error;
            $message_type = "danger";
        }
    }
    
    if ($action === 'update_shift') {
        $shift_id = $_POST['shift_id'];
        $shift_name = trim($_POST['shift_name']);
        $shift_date = $_POST['shift_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $description = trim($_POST['description']);
        $location = trim($_POST['location']);
        $max_employees = intval($_POST['max_employees']);
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE shifts SET shift_name=?, shift_date=?, start_time=?, end_time=?, description=?, location=?, max_employees=?, status=? WHERE id=?");
        $stmt->bind_param("ssssssssi", $shift_name, $shift_date, $start_time, $end_time, $description, $location, $max_employees, $status, $shift_id);
        
        if ($stmt->execute()) {
            $message = "Shift updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating shift.";
            $message_type = "danger";
        }
    }
    
    if ($action === 'delete_shift') {
        $shift_id = $_POST['shift_id'];
        
        $stmt = $conn->prepare("DELETE FROM shifts WHERE id=?");
        $stmt->bind_param("i", $shift_id);
        
        if ($stmt->execute()) {
            $message = "Shift deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting shift.";
            $message_type = "danger";
        }
    }
    
    if ($action === 'assign_employees') {
        $shift_id = $_POST['shift_id'];
        $user_ids = $_POST['user_ids'] ?? [];
        $assigned_by = $_SESSION['user_id'];
        
        // First, remove all existing assignments for this shift
        $delete_stmt = $conn->prepare("DELETE FROM shift_assignments WHERE shift_id = ?");
        $delete_stmt->bind_param("i", $shift_id);
        $delete_stmt->execute();
        
        // Then, add new assignments
        if (!empty($user_ids)) {
            $stmt = $conn->prepare("INSERT INTO shift_assignments (shift_id, user_id, assigned_by) VALUES (?, ?, ?)");
            foreach ($user_ids as $user_id) {
                $stmt->bind_param("iii", $shift_id, $user_id, $assigned_by);
                $stmt->execute();
            }
            $message = "Employees assigned to shift successfully!";
            $message_type = "success";
        } else {
            $message = "All employees removed from shift.";
            $message_type = "info";
        }
    }
}

// Fetch store settings
$settings_query = $conn->query("SELECT setting_key, setting_value FROM store_settings");
$store_settings = [];
while ($row = $settings_query->fetch_assoc()) {
    $store_settings[$row['setting_key']] = $row['setting_value'];
}

// Fetch GCash QR Code
$gcash_qr_query = $conn->query("SELECT qr_code_path FROM payment_qrcodes WHERE payment_method = 'gcash' AND is_active = 1 LIMIT 1");
$gcash_qr = $gcash_qr_query->fetch_assoc();
$gcash_qr_path = $gcash_qr['qr_code_path'] ?? '';

// Fetch users
$users = $conn->query("SELECT u.*, 
    (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as total_orders,
    (SELECT SUM(total_amount) FROM orders WHERE user_id = u.id AND status = 'completed') as total_sales
    FROM users u ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Get user statistics
$user_stats = $conn->query("SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
    SUM(CASE WHEN role = 'staff' THEN 1 ELSE 0 END) as staff_count,
    SUM(CASE WHEN role = 'cashier' THEN 1 ELSE 0 END) as cashier_count,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count
FROM users")->fetch_assoc();

// Fetch shifts with assignment count
$shifts_query = "SELECT s.*, 
    u.first_name as created_by_name, u.last_name as created_by_lastname,
    (SELECT COUNT(*) FROM shift_assignments WHERE shift_id = s.id) as assigned_count
    FROM shifts s
    LEFT JOIN users u ON s.created_by = u.id
    ORDER BY s.shift_date DESC, s.start_time ASC";
$shifts = $conn->query($shifts_query)->fetch_all(MYSQLI_ASSOC);

// Get shift statistics
$shift_stats = $conn->query("SELECT 
    COUNT(*) as total_shifts,
    SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_count,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
    SUM(CASE WHEN shift_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_count
FROM shifts")->fetch_assoc();

// Fetch all active users for assignment dropdown
$active_users = $conn->query("SELECT id, username, first_name, last_name, role FROM users WHERE status = 'active' ORDER BY first_name, last_name")->fetch_all(MYSQLI_ASSOC);

ob_start();
?>

<style>
    .nav-tabs .nav-link {
        color: #6c757d;
        border: none;
        border-bottom: 3px solid transparent;
        font-weight: 500;
    }
    .nav-tabs .nav-link.active {
        color: #dc3545;
        border-bottom: 3px solid #dc3545;
        background: transparent;
    }
    .settings-section {
        background: white;
        border-radius: 10px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .settings-section h5 {
        color: #dc3545;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #f8f9fa;
    }
    .logo-preview {
        max-width: 200px;
        max-height: 100px;
        margin-top: 10px;
    }
</style>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <h4 class="mb-0"><i class="fas fa-cog me-2"></i>System Settings</h4>
    <p class="text-muted mb-0">Manage your store configuration and users</p>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="store-tab" data-bs-toggle="tab" data-bs-target="#store-config" type="button" role="tab">
            <i class="fas fa-store me-2"></i>Store Configuration
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#user-management" type="button" role="tab">
            <i class="fas fa-users-cog me-2"></i>User Management
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="shifts-tab" data-bs-toggle="tab" data-bs-target="#shift-management" type="button" role="tab">
            <i class="fas fa-calendar-alt me-2"></i>Shift Management
        </button>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content">
    <!-- Store Configuration Tab -->
    <div class="tab-pane fade show active" id="store-config" role="tabpanel">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_store_config">
            
            <!-- Basic Information -->
            <div class="settings-section">
                <h5><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Store Name *</label>
                        <input type="text" class="form-control" name="store_name" value="<?php echo htmlspecialchars($store_settings['store_name'] ?? 'PointShift POS'); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Branch Name</label>
                        <input type="text" class="form-control" name="store_branch" value="<?php echo htmlspecialchars($store_settings['store_branch'] ?? ''); ?>">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Store Address *</label>
                        <textarea class="form-control" name="store_address" rows="2" required><?php echo htmlspecialchars($store_settings['store_address'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone Number *</label>
                        <input type="text" class="form-control" name="store_phone" value="<?php echo htmlspecialchars($store_settings['store_phone'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" class="form-control" name="store_email" value="<?php echo htmlspecialchars($store_settings['store_email'] ?? ''); ?>" required>
                    </div>
                </div>
            </div>
            
            <!-- Business Hours -->
            <div class="settings-section">
                <h5><i class="fas fa-clock me-2"></i>Business Hours</h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Opening Time *</label>
                        <input type="time" class="form-control" name="business_hours_open" value="<?php echo htmlspecialchars($store_settings['business_hours_open'] ?? '08:00'); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Closing Time *</label>
                        <input type="time" class="form-control" name="business_hours_close" value="<?php echo htmlspecialchars($store_settings['business_hours_close'] ?? '20:00'); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Business Days</label>
                        <input type="text" class="form-control" name="business_days" value="<?php echo htmlspecialchars($store_settings['business_days'] ?? 'Monday to Sunday'); ?>" placeholder="e.g., Monday to Sunday">
                    </div>
                </div>
            </div>
            
            <!-- Store Logo -->
            <div class="settings-section">
                <h5><i class="fas fa-image me-2"></i>Store Logo</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Upload Logo</label>
                        <input type="file" class="form-control" name="store_logo" accept="image/*">
                        <small class="text-muted">Recommended size: 300x100px (PNG or JPG)</small>
                        <?php if (!empty($store_settings['store_logo'])): ?>
                            <div class="mt-2">
                                <p class="mb-1"><strong>Current Logo:</strong></p>
                                <img src="<?php echo htmlspecialchars($store_settings['store_logo']); ?>" alt="Store Logo" class="logo-preview img-thumbnail">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Receipt Configuration -->
            <div class="settings-section">
                <h5><i class="fas fa-receipt me-2"></i>Receipt Configuration</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Receipt Header Text</label>
                        <input type="text" class="form-control" name="receipt_header" value="<?php echo htmlspecialchars($store_settings['receipt_header'] ?? ''); ?>" placeholder="e.g., Thank you for your purchase!">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Receipt Footer Text</label>
                        <input type="text" class="form-control" name="receipt_footer" value="<?php echo htmlspecialchars($store_settings['receipt_footer'] ?? ''); ?>" placeholder="e.g., Please come again!">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tax Rate (%)</label>
                        <input type="number" step="0.01" class="form-control" name="tax_rate" value="<?php echo htmlspecialchars($store_settings['tax_rate'] ?? '12'); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Currency Symbol</label>
                        <input type="text" class="form-control" name="currency_symbol" value="<?php echo htmlspecialchars($store_settings['currency_symbol'] ?? '₱'); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Receipt Options</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="receipt_show_logo" id="receipt_show_logo" <?php echo ($store_settings['receipt_show_logo'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="receipt_show_logo">Show Logo on Receipt</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="receipt_show_cashier" id="receipt_show_cashier" <?php echo ($store_settings['receipt_show_cashier'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="receipt_show_cashier">Show Cashier Name on Receipt</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-end">
                <button type="submit" class="btn btn-danger btn-lg">
                    <i class="fas fa-save me-2"></i>Save Store Settings
                </button>
            </div>
        </form>
        
        <!-- GCash QR Code Section -->
        <div class="card mt-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-qrcode me-2"></i>GCash Payment QR Code</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Upload a GCash QR code that will be displayed when customers select GCash as payment method in POS.</p>
                
                <?php if (!empty($gcash_qr_path) && file_exists($gcash_qr_path)): ?>
                    <!-- Current QR Code Display -->
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle me-2"></i>QR Code Uploaded</h6>
                        <div class="text-center my-3">
                            <img src="<?php echo htmlspecialchars($gcash_qr_path); ?>" alt="GCash QR Code" class="img-thumbnail" style="max-width: 300px; max-height: 300px;">
                        </div>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="delete_gcash_qr">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete the GCash QR code?')">
                                <i class="fas fa-trash me-1"></i>Delete QR Code
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>No GCash QR code uploaded yet.
                    </div>
                <?php endif; ?>
                
                <!-- Upload Form -->
                <form method="POST" enctype="multipart/form-data" class="mt-3">
                    <input type="hidden" name="action" value="upload_gcash_qr">
                    <div class="row">
                        <div class="col-md-8">
                            <input type="file" class="form-control" name="gcash_qr" accept="image/*" required>
                            <small class="form-text text-muted">Accepted formats: JPG, PNG, GIF. Max size: 5MB</small>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-upload me-2"></i><?php echo !empty($gcash_qr_path) ? 'Replace' : 'Upload'; ?> QR Code
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- User Management Tab -->
    <div class="tab-pane fade" id="user-management" role="tabpanel">
        <!-- User Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Users</h6>
                            <h3 class="mb-0"><?php echo number_format($user_stats['total_users']); ?></h3>
                        </div>
                        <div class="stats-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Admins</h6>
                            <h3 class="mb-0"><?php echo number_format($user_stats['admin_count']); ?></h3>
                        </div>
                        <div class="stats-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Staff</h6>
                            <h3 class="mb-0"><?php echo number_format($user_stats['staff_count']); ?></h3>
                        </div>
                        <div class="stats-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <i class="fas fa-user-tie"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Cashiers</h6>
                            <h3 class="mb-0"><?php echo number_format($user_stats['cashier_count']); ?></h3>
                        </div>
                        <div class="stats-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                            <i class="fas fa-cash-register"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Add User Button -->
        <div class="mb-3">
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-user-plus me-2"></i>Add New User
            </button>
        </div>
        
        <!-- Users Table -->
        <div class="content-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Users</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Orders</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $user['role'] == 'admin' ? 'bg-danger' : ($user['role'] == 'staff' ? 'bg-primary' : 'bg-info'); ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $user['status'] == 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($user['total_orders']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Shift Management Tab -->
    <div class="tab-pane fade" id="shift-management" role="tabpanel">
        <!-- Shift Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Shifts</h6>
                            <h3 class="mb-0"><?php echo number_format($shift_stats['total_shifts']); ?></h3>
                        </div>
                        <div class="stats-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Scheduled</h6>
                            <h3 class="mb-0"><?php echo number_format($shift_stats['scheduled_count']); ?></h3>
                        </div>
                        <div class="stats-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Completed</h6>
                            <h3 class="mb-0"><?php echo number_format($shift_stats['completed_count']); ?></h3>
                        </div>
                        <div class="stats-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Upcoming</h6>
                            <h3 class="mb-0"><?php echo number_format($shift_stats['upcoming_count']); ?></h3>
                        </div>
                        <div class="stats-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Add Shift Button -->
        <div class="mb-3">
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addShiftModal">
                <i class="fas fa-plus me-2"></i>Create New Shift
            </button>
        </div>
        
        <!-- Shifts Table -->
        <div class="content-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Shifts</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Shift Name</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Location</th>
                                <th>Assigned</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($shifts)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No shifts created yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($shifts as $shift): ?>
                                    <tr>
                                        <td><?php echo $shift['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($shift['shift_name']); ?></strong></td>
                                        <td><?php echo date('M d, Y', strtotime($shift['shift_date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($shift['start_time'])) . ' - ' . date('h:i A', strtotime($shift['end_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($shift['location'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo $shift['assigned_count']; ?> / <?php echo $shift['max_employees']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $status_colors = [
                                                'scheduled' => 'primary',
                                                'in-progress' => 'warning',
                                                'completed' => 'success',
                                                'cancelled' => 'danger'
                                            ];
                                            $color = $status_colors[$shift['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $color; ?>">
                                                <?php echo ucfirst($shift['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($shift['created_by_name'] . ' ' . $shift['created_by_lastname']); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-success" onclick="assignEmployees(<?php echo $shift['id']; ?>, '<?php echo htmlspecialchars($shift['shift_name']); ?>')" title="Assign Employees">
                                                    <i class="fas fa-user-plus"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editShift(<?php echo htmlspecialchars(json_encode($shift)); ?>)" title="Edit Shift">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteShift(<?php echo $shift['id']; ?>, '<?php echo htmlspecialchars($shift['shift_name']); ?>')" title="Delete Shift">
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
            </div>
        </div>
    </div>
</div>

<!-- Add Shift Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_user">
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" required minlength="6">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role *</label>
                            <select class="form-select" name="role" required>
                                <option value="cashier">Cashier</option>
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status *</label>
                            <select class="form-select" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit_username" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" id="edit_email" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role *</label>
                            <select class="form-select" name="role" id="edit_role" required>
                                <option value="cashier">Cashier</option>
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status *</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-key me-2"></i>Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" id="reset_user_id">
                    <p>Reset password for user: <strong id="reset_username"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">New Password *</label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Shift Modal -->
<div class="modal fade" id="addShiftModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Create New Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_shift">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Shift Name *</label>
                            <input type="text" class="form-control" name="shift_name" required placeholder="e.g., Morning Shift">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date *</label>
                            <input type="date" class="form-control" name="shift_date" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Time *</label>
                            <input type="time" class="form-control" name="start_time" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Time *</label>
                            <input type="time" class="form-control" name="end_time" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location" placeholder="e.g., Main Store">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Max Employees *</label>
                            <input type="number" class="form-control" name="max_employees" value="10" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Shift details..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Create Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Shift Modal -->
<div class="modal fade" id="editShiftModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_shift">
                    <input type="hidden" name="shift_id" id="edit_shift_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Shift Name *</label>
                            <input type="text" class="form-control" name="shift_name" id="edit_shift_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date *</label>
                            <input type="date" class="form-control" name="shift_date" id="edit_shift_date" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Time *</label>
                            <input type="time" class="form-control" name="start_time" id="edit_start_time" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Time *</label>
                            <input type="time" class="form-control" name="end_time" id="edit_end_time" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location" id="edit_location">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Max Employees *</label>
                            <input type="number" class="form-control" name="max_employees" id="edit_max_employees" min="1" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status *</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="scheduled">Scheduled</option>
                                <option value="in-progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Employees Modal -->
<div class="modal fade" id="assignEmployeesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Assign Employees to Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_employees">
                    <input type="hidden" name="shift_id" id="assign_shift_id">
                    <p class="mb-3">Shift: <strong id="assign_shift_name"></strong></p>
                    <label class="form-label">Select Employees</label>
                    <div id="employee-checkboxes" class="mb-3" style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px;">
                        <?php foreach ($active_users as $user): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input employee-checkbox" type="checkbox" name="user_ids[]" value="<?php echo $user['id']; ?>" id="user_<?php echo $user['id']; ?>">
                                <label class="form-check-label" for="user_<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> 
                                    <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'staff' ? 'primary' : 'info'); ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Assign Employees</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_first_name').value = user.first_name;
    document.getElementById('edit_last_name').value = user.last_name;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_status').value = user.status;
    
    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
}

function resetPassword(userId, username) {
    document.getElementById('reset_user_id').value = userId;
    document.getElementById('reset_username').textContent = username;
    
    const modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
    modal.show();
}

function deleteUser(userId, username) {
    if (confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Shift Management Functions
function editShift(shift) {
    document.getElementById('edit_shift_id').value = shift.id;
    document.getElementById('edit_shift_name').value = shift.shift_name;
    document.getElementById('edit_shift_date').value = shift.shift_date;
    document.getElementById('edit_start_time').value = shift.start_time;
    document.getElementById('edit_end_time').value = shift.end_time;
    document.getElementById('edit_location').value = shift.location || '';
    document.getElementById('edit_max_employees').value = shift.max_employees;
    document.getElementById('edit_status').value = shift.status;
    document.getElementById('edit_description').value = shift.description || '';
    
    const modal = new bootstrap.Modal(document.getElementById('editShiftModal'));
    modal.show();
}

function deleteShift(shiftId, shiftName) {
    if (confirm(`Are you sure you want to delete shift "${shiftName}"? This will also remove all employee assignments. This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_shift">
            <input type="hidden" name="shift_id" value="${shiftId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function assignEmployees(shiftId, shiftName) {
    document.getElementById('assign_shift_id').value = shiftId;
    document.getElementById('assign_shift_name').textContent = shiftName;
    
    // Clear all checkboxes first
    document.querySelectorAll('.employee-checkbox').forEach(cb => cb.checked = false);
    
    // Fetch currently assigned employees via AJAX
    fetch(`get_shift_assignments.php?shift_id=${shiftId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                data.user_ids.forEach(userId => {
                    const checkbox = document.getElementById(`user_${userId}`);
                    if (checkbox) checkbox.checked = true;
                });
            }
        })
        .catch(error => console.error('Error fetching assignments:', error));
    
    const modal = new bootstrap.Modal(document.getElementById('assignEmployeesModal'));
    modal.show();
}

// Keep the active tab after form submission
if (window.location.hash) {
    const hash = window.location.hash;
    const tab = document.querySelector(`button[data-bs-target="${hash}"]`);
    if (tab) {
        const bsTab = new bootstrap.Tab(tab);
        bsTab.show();
    }
}
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>
