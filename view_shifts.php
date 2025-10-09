<?php
require_once 'config.php';
requireLogin();

$page_title = "My Shifts";
$current_user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Handle shift confirmation/declination
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_assignment_status') {
        $assignment_id = intval($_POST['assignment_id']);
        $status = $_POST['status'];
        
        // Verify the assignment belongs to the current user
        $check_stmt = $conn->prepare("SELECT user_id FROM shift_assignments WHERE id = ?");
        $check_stmt->bind_param("i", $assignment_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $assignment = $result->fetch_assoc();
        
        if ($assignment && $assignment['user_id'] == $current_user_id) {
            $stmt = $conn->prepare("UPDATE shift_assignments SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $assignment_id);
            
            if ($stmt->execute()) {
                $message = "Shift status updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating shift status.";
                $message_type = "danger";
            }
        } else {
            $message = "Unauthorized action.";
            $message_type = "danger";
        }
    }
}

// Fetch user's assigned shifts
$query = "SELECT 
    sa.id as assignment_id,
    sa.status as assignment_status,
    sa.role as shift_role,
    sa.notes,
    sa.assigned_at,
    s.id as shift_id,
    s.shift_name,
    s.shift_date,
    s.start_time,
    s.end_time,
    s.description,
    s.location,
    s.status as shift_status,
    u.first_name as assigned_by_fname,
    u.last_name as assigned_by_lname,
    (SELECT COUNT(*) FROM shift_assignments WHERE shift_id = s.id) as total_assigned
FROM shift_assignments sa
JOIN shifts s ON sa.shift_id = s.id
LEFT JOIN users u ON sa.assigned_by = u.id
WHERE sa.user_id = ?
ORDER BY s.shift_date DESC, s.start_time ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$assigned_shifts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Group shifts by status
$upcoming_shifts = [];
$past_shifts = [];
$today = date('Y-m-d');

foreach ($assigned_shifts as $shift) {
    if ($shift['shift_date'] >= $today && $shift['shift_status'] != 'completed') {
        $upcoming_shifts[] = $shift;
    } else {
        $past_shifts[] = $shift;
    }
}

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_shifts,
    COALESCE(SUM(CASE WHEN s.shift_date >= CURDATE() AND s.status != 'completed' THEN 1 ELSE 0 END), 0) as upcoming_count,
    COALESCE(SUM(CASE WHEN sa.status = 'confirmed' THEN 1 ELSE 0 END), 0) as confirmed_count,
    COALESCE(SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END), 0) as completed_count
FROM shift_assignments sa
JOIN shifts s ON sa.shift_id = s.id
WHERE sa.user_id = ?";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $current_user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Ensure all stats values are integers, not null
$stats['total_shifts'] = intval($stats['total_shifts'] ?? 0);
$stats['upcoming_count'] = intval($stats['upcoming_count'] ?? 0);
$stats['confirmed_count'] = intval($stats['confirmed_count'] ?? 0);
$stats['completed_count'] = intval($stats['completed_count'] ?? 0);

ob_start();
?>

<style>
    .shift-card {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border-left: 4px solid #dc3545;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .shift-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .shift-card.upcoming {
        border-left-color: #0d6efd;
    }
    .shift-card.completed {
        border-left-color: #198754;
        opacity: 0.85;
    }
    .shift-card.cancelled {
        border-left-color: #6c757d;
        opacity: 0.7;
    }
    .shift-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f0f0f0;
    }
    .shift-title {
        font-size: 1.25rem;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 0.25rem;
    }
    .shift-date {
        color: #6c757d;
        font-size: 0.95rem;
    }
    .shift-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .info-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .info-item i {
        color: #dc3545;
        width: 20px;
    }
    .stats-card {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border-left: 4px solid #dc3545;
        transition: transform 0.2s;
    }
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }
    .section-title {
        font-size: 1.5rem;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #dc3545;
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
    <h4 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>My Assigned Shifts</h4>
    <p class="text-muted mb-0">View and manage your shift schedule</p>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-2">Total Shifts</h6>
                    <h3 class="mb-0"><?php echo number_format($stats['total_shifts']); ?></h3>
                </div>
                <div class="stats-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-calendar"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-2">Upcoming</h6>
                    <h3 class="mb-0"><?php echo number_format($stats['upcoming_count']); ?></h3>
                </div>
                <div class="stats-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-2">Confirmed</h6>
                    <h3 class="mb-0"><?php echo number_format($stats['confirmed_count']); ?></h3>
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
                    <h6 class="text-muted mb-2">Completed</h6>
                    <h3 class="mb-0"><?php echo number_format($stats['completed_count']); ?></h3>
                </div>
                <div class="stats-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <i class="fas fa-star"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming Shifts -->
<div class="mb-5">
    <h5 class="section-title"><i class="fas fa-clock me-2"></i>Upcoming Shifts</h5>
    
    <?php if (empty($upcoming_shifts)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>You have no upcoming shifts assigned.
        </div>
    <?php else: ?>
        <?php foreach ($upcoming_shifts as $shift): ?>
            <div class="shift-card upcoming">
                <div class="shift-header">
                    <div>
                        <div class="shift-title"><?php echo htmlspecialchars($shift['shift_name']); ?></div>
                        <div class="shift-date">
                            <i class="fas fa-calendar me-2"></i>
                            <?php echo date('l, F j, Y', strtotime($shift['shift_date'])); ?>
                        </div>
                    </div>
                    <div class="text-end">
                        <?php
                        $status_colors = [
                            'assigned' => 'warning',
                            'confirmed' => 'success',
                            'declined' => 'danger',
                            'completed' => 'info',
                            'no-show' => 'secondary'
                        ];
                        $color = $status_colors[$shift['assignment_status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $color; ?> mb-2">
                            <?php echo ucwords(str_replace('-', ' ', $shift['assignment_status'])); ?>
                        </span>
                        <?php if ($shift['shift_role'] == 'supervisor'): ?>
                            <br><span class="badge bg-primary">Supervisor</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="shift-info">
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo date('h:i A', strtotime($shift['start_time'])) . ' - ' . date('h:i A', strtotime($shift['end_time'])); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($shift['location'] ?: 'Not specified'); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-users"></i>
                        <span><?php echo $shift['total_assigned']; ?> employees assigned</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-user-tie"></i>
                        <span>Assigned by: <?php echo htmlspecialchars($shift['assigned_by_fname'] . ' ' . $shift['assigned_by_lname']); ?></span>
                    </div>
                </div>
                
                <?php if ($shift['description']): ?>
                    <div class="mb-3">
                        <strong>Description:</strong>
                        <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($shift['description'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($shift['notes']): ?>
                    <div class="mb-3">
                        <strong>Notes:</strong>
                        <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($shift['notes'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($shift['assignment_status'] == 'assigned'): ?>
                    <div class="text-end">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="update_assignment_status">
                            <input type="hidden" name="assignment_id" value="<?php echo $shift['assignment_id']; ?>">
                            <input type="hidden" name="status" value="confirmed">
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fas fa-check me-1"></i>Confirm
                            </button>
                        </form>
                        <form method="POST" class="d-inline ms-2">
                            <input type="hidden" name="action" value="update_assignment_status">
                            <input type="hidden" name="assignment_id" value="<?php echo $shift['assignment_id']; ?>">
                            <input type="hidden" name="status" value="declined">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to decline this shift?')">
                                <i class="fas fa-times me-1"></i>Decline
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Past Shifts -->
<div>
    <h5 class="section-title"><i class="fas fa-history me-2"></i>Past Shifts</h5>
    
    <?php if (empty($past_shifts)): ?>
        <div class="alert alert-secondary">
            <i class="fas fa-info-circle me-2"></i>No past shifts to display.
        </div>
    <?php else: ?>
        <?php foreach ($past_shifts as $shift): ?>
            <div class="shift-card <?php echo $shift['shift_status'] == 'completed' ? 'completed' : ($shift['shift_status'] == 'cancelled' ? 'cancelled' : ''); ?>">
                <div class="shift-header">
                    <div>
                        <div class="shift-title"><?php echo htmlspecialchars($shift['shift_name']); ?></div>
                        <div class="shift-date">
                            <i class="fas fa-calendar me-2"></i>
                            <?php echo date('l, F j, Y', strtotime($shift['shift_date'])); ?>
                        </div>
                    </div>
                    <div class="text-end">
                        <?php
                        $status_colors = [
                            'assigned' => 'warning',
                            'confirmed' => 'success',
                            'declined' => 'danger',
                            'completed' => 'info',
                            'no-show' => 'secondary'
                        ];
                        $color = $status_colors[$shift['assignment_status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $color; ?> mb-2">
                            <?php echo ucwords(str_replace('-', ' ', $shift['assignment_status'])); ?>
                        </span>
                        <?php
                        $shift_status_colors = [
                            'scheduled' => 'primary',
                            'in-progress' => 'warning',
                            'completed' => 'success',
                            'cancelled' => 'danger'
                        ];
                        $shift_color = $shift_status_colors[$shift['shift_status']] ?? 'secondary';
                        ?>
                        <br><span class="badge bg-<?php echo $shift_color; ?>">
                            <?php echo ucfirst($shift['shift_status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="shift-info">
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo date('h:i A', strtotime($shift['start_time'])) . ' - ' . date('h:i A', strtotime($shift['end_time'])); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($shift['location'] ?: 'Not specified'); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-users"></i>
                        <span><?php echo $shift['total_assigned']; ?> employees assigned</span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include 'layout.php';
?>
