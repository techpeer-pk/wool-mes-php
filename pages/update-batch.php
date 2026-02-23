<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// ADD THIS LINE after the requires:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
}

requireLogin();

if (!hasRole('Admin') && !hasRole('Supervisor')) {
    header('Location: dashboard.php');
    exit();
}

$batch_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = '';
$error = '';

// Get batch details
$query = "SELECT b.*, ps.stage_name, ps.stage_number
          FROM batches b
          JOIN production_stages ps ON b.current_stage_id = ps.stage_id
          WHERE b.batch_id = ? AND b.status = 'In Progress'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $batch_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$batch = mysqli_fetch_assoc($result);

if (!$batch) {
    header('Location: batches.php');
    exit();
}

// Get current stage history
$query = "SELECT * FROM batch_stage_history 
          WHERE batch_id = ? AND stage_id = ? AND status = 'In Progress'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $batch_id, $batch['current_stage_id']);
mysqli_stmt_execute($stmt);
$current_stage_history = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Get next stage
$next_stage_number = $batch['stage_number'] + 1;
$query = "SELECT * FROM production_stages WHERE stage_number = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $next_stage_number);
mysqli_stmt_execute($stmt);
$next_stage = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Get vendors for next stage
$vendors = [];
if ($next_stage) {
    $query = "SELECT * FROM vendors WHERE is_active = 1 ORDER BY vendor_name";
    $vendors = mysqli_query($conn, $query);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $weight_out = (float)$_POST['weight_out'];
    $processing_cost = (float)($_POST['processing_cost'] ?? 0);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $action = $_POST['action'];
    
    if ($weight_out <= 0 || $weight_out > $batch['current_weight']) {
        $error = 'Invalid weight value';
    } else {
        mysqli_begin_transaction($conn);
        
        try {
            // Calculate duration
            $start_time = strtotime($current_stage_history['start_date']);
            $end_time = time();
            $duration_hours = round(($end_time - $start_time) / 3600);
            
            // Calculate weight loss
            $weight_loss = $current_stage_history['weight_in'] - $weight_out;
            
            // Update current stage history
            $query = "UPDATE batch_stage_history 
                      SET weight_out = ?, weight_loss = ?, end_date = NOW(), 
                          duration_hours = ?, processing_cost = ?, status = 'Completed', notes = ?, updated_by = ?
                      WHERE history_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ddidsii", 
                $weight_out, $weight_loss, $duration_hours, $processing_cost, $notes, 
                $_SESSION['user_id'], $current_stage_history['history_id']);
            mysqli_stmt_execute($stmt);
            
            // ========================================
            // UPDATED: Recalculate batch total costs
            // ========================================
            $query = "UPDATE batches 
                      SET total_processing_cost = (
                          SELECT COALESCE(SUM(processing_cost), 0) 
                          FROM batch_stage_history 
                          WHERE batch_id = ? AND status = 'Completed'
                      ),
                      total_cost = raw_material_cost + (
                          SELECT COALESCE(SUM(processing_cost), 0) 
                          FROM batch_stage_history 
                          WHERE batch_id = ? AND status = 'Completed'
                      ),
                      profit = selling_price - (raw_material_cost + (
                          SELECT COALESCE(SUM(processing_cost), 0) 
                          FROM batch_stage_history 
                          WHERE batch_id = ? AND status = 'Completed'
                      ))
                      WHERE batch_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "iiii", $batch_id, $batch_id, $batch_id, $batch_id);
            mysqli_stmt_execute($stmt);
            
            if ($action === 'complete' && !$next_stage) {
                // Final stage - mark batch as completed
                $query = "UPDATE batches 
                          SET status = 'Completed', current_weight = ?, 
                              actual_completion_date = CURDATE()
                          WHERE batch_id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "di", $weight_out, $batch_id);
                mysqli_stmt_execute($stmt);
                
                $success = "Batch completed successfully!";
            } elseif ($action === 'next') {
                // Move to next stage
                $vendor_id = (int)$_POST['vendor_id'];
                
                // Update batch
                $query = "UPDATE batches 
                          SET current_stage_id = ?, current_weight = ?
                          WHERE batch_id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "idi", $next_stage['stage_id'], $weight_out, $batch_id);
                mysqli_stmt_execute($stmt);
                
                // Create next stage history
                $query = "INSERT INTO batch_stage_history 
                          (batch_id, stage_id, vendor_id, weight_in, start_date, status, updated_by)
                          VALUES (?, ?, ?, ?, NOW(), 'In Progress', ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "iiidi", 
                    $batch_id, $next_stage['stage_id'], $vendor_id, $weight_out, $_SESSION['user_id']);
                mysqli_stmt_execute($stmt);
                
                $success = "Batch moved to next stage successfully!";
            }
            
            mysqli_commit($conn);
            
            // Redirect after success
            if ($success) {
                $_SESSION['update_success'] = $success;
                header("Location: batch-details.php?id={$batch_id}");
                exit();
            }
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = 'Error updating batch: ' . $e->getMessage();
        }
    }
}

$page_title = 'Update Batch Stage';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        
        <a href="batch-details.php?id=<?php echo $batch_id; ?>" class="btn btn-sm btn-outline-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Back to Batch Details
        </a>
        
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="bi bi-arrow-right-circle"></i> Update Stage: <?php echo clean($batch['batch_number']); ?>
                </h4>
            </div>
            <div class="card-body">
                
                <?php if ($error): ?>
                    <?php echo showError($error); ?>
                <?php endif; ?>
                
                <!-- Current Stage Info -->
                <div class="alert alert-info">
                    <strong>Current Stage:</strong> <?php echo clean($batch['stage_name']); ?><br>
                    <strong>Current Weight:</strong> <?php echo formatWeight($batch['current_weight']); ?><br>
                    <strong>Started:</strong> <?php echo formatDateTime($current_stage_history['start_date']); ?>
                </div>
                
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <div class="mb-3">
                        <label class="form-label">Weight Out (kg) <span class="text-danger">*</span></label>
                        <input type="number" name="weight_out" class="form-control" 
                               step="0.01" min="0.01" max="<?php echo $batch['current_weight']; ?>"
                               value="<?php echo isset($_POST['weight_out']) ? $_POST['weight_out'] : ''; ?>" 
                               required>
                        <small class="text-muted">
                            Maximum: <?php echo formatWeight($batch['current_weight']); ?>
                        </small>
                    </div>
                    
                    <!-- ========================================
                         UPDATED: Better cost input with helper text
                         ======================================== -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-cash-coin"></i> Processing Cost for This Stage (PKR)
                        </label>
                        <input type="number" name="processing_cost" class="form-control" 
                               step="0.01" min="-99999"
                               value="<?php echo isset($_POST['processing_cost']) ? $_POST['processing_cost'] : '0'; ?>" 
                               placeholder="0.00">
                        <small class="text-muted">
                            Enter the cost for processing at this stage (labor, materials, vendor charges, etc.)
                        </small>
                    </div>
                    
                    <?php if ($next_stage): ?>
                    <div class="mb-3">
                        <label class="form-label">Next Stage Vendor</label>
                        <select name="vendor_id" class="form-select" required>
                            <option value="">Select Vendor...</option>
                            <?php while ($vendor = mysqli_fetch_assoc($vendors)): ?>
                            <option value="<?php echo $vendor['vendor_id']; ?>">
                                <?php echo clean($vendor['vendor_name']); ?> 
                                (<?php echo clean($vendor['vendor_type']." - ".$vendor['specialization']); ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes / Issues</label>
                        <textarea name="notes" class="form-control" rows="3" 
                                  placeholder="Any quality issues, delays, or observations..."><?php echo isset($_POST['notes']) ? clean($_POST['notes']) : ''; ?></textarea>
                    </div>
                    
                    <hr>
                    
                    <?php if ($next_stage): ?>
                    <div class="alert alert-success">
                        <strong><i class="bi bi-arrow-right"></i> Next Stage:</strong> 
                        <?php echo clean($next_stage['stage_name']); ?>
                        <br>
                        <small>Expected duration: <?php echo $next_stage['avg_duration_days']; ?> days</small>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="batch-details.php?id=<?php echo $batch_id; ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" name="action" value="next" class="btn btn-primary">
                            <i class="bi bi-arrow-right-circle"></i> Move to Next Stage
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <strong><i class="bi bi-check-circle"></i> Final Stage</strong>
                        <br>
                        This is the last stage. Completing this will mark the batch as finished.
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="batch-details.php?id=<?php echo $batch_id; ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" name="action" value="complete" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Complete Batch
                        </button>
                    </div>
                    <?php endif; ?>
                    
                </form>
                
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>