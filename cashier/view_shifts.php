<?php
require_once '../config.php';
User::requireLogin();

// Redirect admin to admin panel
if (User::isAdmin()) {
    header('Location: ../view_shifts.php');
    exit();
}

$title = "My Shifts";
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
        $db = Database::getInstance()->getConnection();
        $check_stmt = $db->prepare("SELECT user_id FROM shift_assignments WHERE id = ?");
        $check_stmt->execute([$assignment_id]);
        $assignment = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($assignment && $assignment['user_id'] == $current_user_id) {
            $stmt = $db->prepare("UPDATE shift_assignments SET status = ? WHERE id = ?");
            
            if ($stmt->execute([$status, $assignment_id])) {
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
$db = Database::getInstance()->getConnection();
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

$stmt = $db->prepare($query);
$stmt->execute([$current_user_id]);
$assigned_shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute([$current_user_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

ob_start();
?>

<style>
.stats-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}
.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
.shift-card {
    background: white;
    border-radius: 10px;
    padding: 1.25rem;
    margin-bottom: 1rem;
    border-left: 4px solid #dc3545;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.shift-card.confirmed {
    border-left-color: #28a745;
}
.shift-card.declined {
    border-left-color: #6c757d;
}
.shift-card.completed {
    border-left-color: #17a2b8;
}
.badge-status {
    font-size: 0.75rem;
    padding: 0.35rem 0.65rem;
    border-radius: 15px;
}
</style>

<div class="row mb-4">
    <!-- Statistics Cards -->
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-primary me-3" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo $stats['total_shifts']; ?></h3>
                    <p class="text-muted mb-0 small">Total Shifts</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-warning me-3" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo $stats['upcoming_count']; ?></h3>
                    <p class="text-muted mb-0 small">Upcoming</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-success me-3" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo $stats['confirmed_count']; ?></h3>
                    <p class="text-muted mb-0 small">Confirmed</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-info me-3" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                    <i class="fas fa-check-double"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo $stats['completed_count']; ?></h3>
                    <p class="text-muted mb-0 small">Completed</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Upcoming Shifts -->
<div class="card mb-4">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="fas fa-calendar-week me-2"></i>Upcoming Shifts</h5>
    </div>
    <div class="card-body">
        <?php if (empty($upcoming_shifts)): ?>
            <div class="text-center py-4">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <p class="text-muted">No upcoming shifts assigned</p>
            </div>
        <?php else: ?>
            <?php foreach ($upcoming_shifts as $shift): 
                $status_class = '';
                $status_badge = '';
                switch($shift['assignment_status']) {
                    case 'confirmed':
                        $status_class = 'confirmed';
                        $status_badge = 'bg-success';
                        break;
                    case 'declined':
                        $status_class = 'declined';
                        $status_badge = 'bg-secondary';
                        break;
                    case 'assigned':
                        $status_badge = 'bg-warning';
                        break;
                    default:
                        $status_badge = 'bg-primary';
                }
            ?>
            <div class="shift-card <?php echo $status_class; ?>">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="mb-2">
                            <?php echo htmlspecialchars($shift['shift_name']); ?>
                            <span class="badge <?php echo $status_badge; ?> badge-status ms-2">
                                <?php echo ucfirst($shift['assignment_status']); ?>
                            </span>
                        </h5>
                        <div class="mb-2">
                            <i class="fas fa-calendar text-muted me-2"></i>
                            <strong><?php echo date('F d, Y', strtotime($shift['shift_date'])); ?></strong>
                        </div>
                        <div class="mb-2">
                            <i class="fas fa-clock text-muted me-2"></i>
                            <?php echo date('h:i A', strtotime($shift['start_time'])); ?> - 
                            <?php echo date('h:i A', strtotime($shift['end_time'])); ?>
                        </div>
                        <?php if ($shift['location']): ?>
                        <div class="mb-2">
                            <i class="fas fa-map-marker-alt text-muted me-2"></i>
                            <?php echo htmlspecialchars($shift['location']); ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($shift['description']): ?>
                        <div class="mb-2">
                            <i class="fas fa-info-circle text-muted me-2"></i>
                            <?php echo htmlspecialchars($shift['description']); ?>
                        </div>
                        <?php endif; ?>
                        <div>
                            <i class="fas fa-users text-muted me-2"></i>
                            <small class="text-muted"><?php echo $shift['total_assigned']; ?> assigned</small>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <?php if ($shift['assignment_status'] == 'assigned'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="update_assignment_status">
                                <input type="hidden" name="assignment_id" value="<?php echo $shift['assignment_id']; ?>">
                                <input type="hidden" name="status" value="confirmed">
                                <button type="submit" class="btn btn-success btn-sm mb-2">
                                    <i class="fas fa-check me-1"></i>Confirm
                                </button>
                            </form>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="update_assignment_status">
                                <input type="hidden" name="assignment_id" value="<?php echo $shift['assignment_id']; ?>">
                                <input type="hidden" name="status" value="declined">
                                <button type="submit" class="btn btn-secondary btn-sm mb-2">
                                    <i class="fas fa-times me-1"></i>Decline
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Past Shifts -->
<div class="card">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Past Shifts</h5>
    </div>
    <div class="card-body">
        <?php if (empty($past_shifts)): ?>
            <div class="text-center py-4">
                <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                <p class="text-muted">No past shifts found</p>
            </div>
        <?php else: ?>
            <?php foreach ($past_shifts as $shift): 
                $status_class = $shift['shift_status'] == 'completed' ? 'completed' : '';
                $status_badge = $shift['shift_status'] == 'completed' ? 'bg-info' : 'bg-secondary';
            ?>
            <div class="shift-card <?php echo $status_class; ?>">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <h6 class="mb-2">
                            <?php echo htmlspecialchars($shift['shift_name']); ?>
                            <span class="badge <?php echo $status_badge; ?> badge-status ms-2">
                                <?php echo ucfirst($shift['shift_status']); ?>
                            </span>
                        </h6>
                        <div class="small">
                            <i class="fas fa-calendar text-muted me-2"></i>
                            <?php echo date('F d, Y', strtotime($shift['shift_date'])); ?>
                            <span class="mx-2">|</span>
                            <i class="fas fa-clock text-muted me-2"></i>
                            <?php echo date('h:i A', strtotime($shift['start_time'])); ?> - 
                            <?php echo date('h:i A', strtotime($shift['end_time'])); ?>
                            <?php if ($shift['location']): ?>
                            <span class="mx-2">|</span>
                            <i class="fas fa-map-marker-alt text-muted me-2"></i>
                            <?php echo htmlspecialchars($shift['location']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
?>
