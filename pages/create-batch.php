<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// ADD THIS LINE after the requires:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
}


requireLogin();

// Only Admin and Supervisor can create batches
if (!hasRole('Admin') && !hasRole('Supervisor')) {
    header('Location: dashboard.php');
    exit();
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_number = mysqli_real_escape_string($conn, $_POST['batch_number']);
    $initial_weight = (float)$_POST['initial_weight'];
    $source_supplier = mysqli_real_escape_string($conn, $_POST['source_supplier']);
    $raw_material_cost = (float)($_POST['raw_material_cost'] ?? 0);
    $expected_days = (int)$_POST['expected_days'];
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    
    // Validation
    if (empty($batch_number) || $initial_weight <= 0) {
        $error = 'Please fill all required fields correctly';
    } else {
        // Check if batch number exists
        $check = "SELECT batch_id FROM batches WHERE batch_number = ?";
        $stmt = mysqli_prepare($conn, $check);
        mysqli_stmt_bind_param($stmt, "s", $batch_number);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error = 'Batch number already exists';
        } else {
            // Calculate expected completion date
            $start_date = date('Y-m-d');
            $expected_completion = date('Y-m-d', strtotime("+{$expected_days} days"));
            
            // Insert batch
            $query = "INSERT INTO batches (batch_number, initial_weight, current_weight, current_stage_id, 
                      start_date, expected_completion_date, source_supplier, raw_material_cost, total_cost, notes, created_by) 
                      VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sddsssddsi", 
                $batch_number, $initial_weight, $initial_weight, $start_date, 
                $expected_completion, $source_supplier, $raw_material_cost, $raw_material_cost, $notes, $_SESSION['user_id']);
            
            if (mysqli_stmt_execute($stmt)) {
                $new_batch_id = mysqli_insert_id($conn);
                
                // Get first stage vendor
                $vendor_query = "SELECT vendor_id FROM vendors WHERE specialization = 'Storage' LIMIT 1";
                $vendor_result = mysqli_query($conn, $vendor_query);
                $vendor = mysqli_fetch_assoc($vendor_result);
                $vendor_id = $vendor['vendor_id'] ?? 1;
                
                // Insert first stage history
                $history_query = "INSERT INTO batch_stage_history (batch_id, stage_id, vendor_id, weight_in, 
                                  start_date, status, updated_by) 
                                  VALUES (?, 1, ?, ?, NOW(), 'In Progress', ?)";
                $stmt2 = mysqli_prepare($conn, $history_query);
                mysqli_stmt_bind_param($stmt2, "iidi", 
                    $new_batch_id, $vendor_id, $initial_weight, $_SESSION['user_id']);
                mysqli_stmt_execute($stmt2);
                
                $success = "Batch created successfully! Batch Number: {$batch_number}";
                
                // Clear form
                $_POST = array();
            } else {
                $error = 'Error creating batch: ' . mysqli_error($conn);
            }
        }
    }
}

// Auto-generate batch number
$suggested_batch = generateBatchNumber($conn);

$page_title = 'Create New Batch';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="bi bi-plus-circle"></i> Create New Batch</h4>
            </div>
            <div class="card-body">
                
                <?php if ($success): ?>
                    <?php echo showSuccess($success); ?>
                    <a href="batches.php" class="btn btn-primary">View All Batches</a>
                    <a href="create-batch.php" class="btn btn-outline-secondary">Create Another</a>
                <?php else: ?>
                
                <?php if ($error): ?>
                    <?php echo showError($error); ?>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <div class="mb-3">
                        <label class="form-label">Batch Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="batch_number" class="form-control" 
                                   value="<?php echo isset($_POST['batch_number']) ? clean($_POST['batch_number']) : $suggested_batch; ?>" 
                                   required>
                            <button type="button" class="btn btn-outline-secondary" 
                                    onclick="this.previousElementSibling.value='<?php echo $suggested_batch; ?>'">
                                Auto Generate
                            </button>
                        </div>
                        <small class="text-muted">Suggested: <?php echo $suggested_batch; ?></small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Initial Weight (kg) <span class="text-danger">*</span></label>
                                <input type="number" name="initial_weight" class="form-control" 
                                       step="0.01" min="0.01"
                                       value="<?php echo isset($_POST['initial_weight']) ? $_POST['initial_weight'] : ''; ?>" 
                                       required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Expected Duration (days)</label>
                                <input type="number" name="expected_days" class="form-control" 
                                       value="<?php echo isset($_POST['expected_days']) ? $_POST['expected_days'] : '23'; ?>">
                                <small class="text-muted">Default: 23 days (average production time)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Source Supplier</label>
                        <input type="text" name="source_supplier" class="form-control" 
                               value="<?php echo isset($_POST['source_supplier']) ? clean($_POST['source_supplier']) : ''; ?>" 
                               placeholder="e.g., Green Valley Farm">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Raw Material Cost (PKR)</label>
                        <input type="number" name="raw_material_cost" class="form-control" 
                               step="0.01" min="0"
                               value="<?php echo isset($_POST['raw_material_cost']) ? $_POST['raw_material_cost'] : ''; ?>" 
                               placeholder="e.g., 10000">
                        <small class="text-muted">Cost of raw wool purchase</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" 
                                  placeholder="Any special instructions or notes..."><?php echo isset($_POST['notes']) ? clean($_POST['notes']) : ''; ?></textarea>
                    </div>
                    
                    <hr>
                    
                    <div class="alert alert-info">
                        <strong><i class="bi bi-info-circle"></i> Note:</strong> 
                        This batch will be automatically assigned to Stage 1 (Raw Wool Receipt) after creation.
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Create Batch
                        </button>
                    </div>
                    
                </form>
                
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>