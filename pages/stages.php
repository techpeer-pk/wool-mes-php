<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// ADD THIS LINE after the requires:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
}

requireAdmin();

$page_title = 'Manage Production Stages';
$success = '';
$error = '';

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $stage_id = isset($_POST['stage_id']) ? (int)$_POST['stage_id'] : 0;
    $stage_number = (int)$_POST['stage_number'];
    $stage_name = mysqli_real_escape_string($conn, $_POST['stage_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $avg_duration_days = (int)$_POST['avg_duration_days'];
    $avg_weight_loss_percent = (float)$_POST['avg_weight_loss_percent'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($stage_name) || $stage_number <= 0) {
        $error = 'Stage name and number are required';
    } else {
        if ($_POST['action'] === 'add') {
            // Check if stage number exists
            $check = "SELECT stage_id FROM production_stages WHERE stage_number = ?";
            $stmt = mysqli_prepare($conn, $check);
            mysqli_stmt_bind_param($stmt, "i", $stage_number);
            mysqli_stmt_execute($stmt);
            if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0) {
                $error = 'Stage number already exists';
            } else {
                $query = "INSERT INTO production_stages (stage_number, stage_name, description, avg_duration_days, avg_weight_loss_percent, is_active) 
                          VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "issidi", $stage_number, $stage_name, $description, $avg_duration_days, $avg_weight_loss_percent, $is_active);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Stage added successfully!";
                } else {
                    $error = "Error adding stage";
                }
            }
        } else {
            $query = "UPDATE production_stages SET stage_number=?, stage_name=?, description=?, avg_duration_days=?, avg_weight_loss_percent=?, is_active=? WHERE stage_id=?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "issidii", $stage_number, $stage_name, $description, $avg_duration_days, $avg_weight_loss_percent, $is_active, $stage_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Stage updated successfully!";
            } else {
                $error = "Error updating stage";
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $stage_id = (int)$_GET['delete'];
    // Check if stage is in use
    $check = "SELECT COUNT(*) as count FROM batches WHERE current_stage_id = ?";
    $stmt = mysqli_prepare($conn, $check);
    mysqli_stmt_bind_param($stmt, "i", $stage_id);
    mysqli_stmt_execute($stmt);
    $in_use = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['count'];
    
    if ($in_use > 0) {
        $error = "Cannot delete stage - it is currently in use by {$in_use} batch(es)";
    } else {
        $query = "UPDATE production_stages SET is_active = 0 WHERE stage_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $stage_id);
        if (mysqli_stmt_execute($stmt)) {
            $success = "Stage deactivated successfully!";
        }
    }
}

// Get all stages
$stages = mysqli_query($conn, "SELECT * FROM production_stages ORDER BY stage_number");

// Get stage for editing
$edit_stage = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $query = "SELECT * FROM production_stages WHERE stage_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $edit_stage = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

include '../includes/header.php';
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2><i class="bi bi-diagram-3"></i> Manage Production Stages</h2>
    </div>
    <div class="col-md-6 text-end">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#stageModal" onclick="clearForm()">
            <i class="bi bi-plus-circle"></i> Add Stage
        </button>
    </div>
</div>

<?php if ($success): echo showSuccess($success); endif; ?>
<?php if ($error): echo showError($error); endif; ?>

<div class="alert alert-info">
    <strong><i class="bi bi-info-circle"></i> Note:</strong> 
    Stage numbers define the workflow sequence. Batches move from Stage 1 → 2 → 3, etc.
</div>

<!-- Stages Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Stage #</th>
                        <th>Stage Name</th>
                        <th>Description</th>
                        <th>Avg Duration</th>
                        <th>Avg Weight Loss</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($stage = mysqli_fetch_assoc($stages)): ?>
                    <tr>
                        <td>
                            <strong class="badge bg-primary" style="font-size: 1rem;">
                                <?php echo $stage['stage_number']; ?>
                            </strong>
                        </td>
                        <td><strong><?php echo clean($stage['stage_name']); ?></strong></td>
                        <td>
                            <small><?php echo clean($stage['description']); ?></small>
                        </td>
                        <td><?php echo $stage['avg_duration_days']; ?> days</td>
                        <td><?php echo $stage['avg_weight_loss_percent']; ?>%</td>
                        <td>
                            <?php if ($stage['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick='editStage(<?php echo json_encode($stage); ?>)'>
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php if ($stage['is_active']): ?>
                            <a href="?delete=<?php echo $stage['stage_id']; ?>" 
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Deactivate this stage?')">
                                <i class="bi bi-x-circle"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="stageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Stage</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="stage_id" id="stage_id">
                    <input type="hidden" name="action" id="action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">Stage Number <span class="text-danger">*</span></label>
                        <input type="number" name="stage_number" id="stage_number" class="form-control" min="1" required>
                        <small class="text-muted">Defines the order in production workflow</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Stage Name <span class="text-danger">*</span></label>
                        <input type="text" name="stage_name" id="stage_name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Avg Duration (days)</label>
                                <input type="number" name="avg_duration_days" id="avg_duration_days" class="form-control" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Avg Weight Loss (%)</label>
                                <input type="number" name="avg_weight_loss_percent" id="avg_weight_loss_percent" 
                                       class="form-control" step="0.01" min="0" max="100">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Save Stage
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function clearForm() {
    document.getElementById('modalTitle').textContent = 'Add Stage';
    document.getElementById('stage_id').value = '';
    document.getElementById('action').value = 'add';
    document.getElementById('stage_number').value = '';
    document.getElementById('stage_name').value = '';
    document.getElementById('description').value = '';
    document.getElementById('avg_duration_days').value = '1';
    document.getElementById('avg_weight_loss_percent').value = '0';
    document.getElementById('is_active').checked = true;
}

function editStage(stage) {
    document.getElementById('modalTitle').textContent = 'Edit Stage';
    document.getElementById('stage_id').value = stage.stage_id;
    document.getElementById('action').value = 'edit';
    document.getElementById('stage_number').value = stage.stage_number;
    document.getElementById('stage_name').value = stage.stage_name;
    document.getElementById('description').value = stage.description;
    document.getElementById('avg_duration_days').value = stage.avg_duration_days;
    document.getElementById('avg_weight_loss_percent').value = stage.avg_weight_loss_percent;
    document.getElementById('is_active').checked = stage.is_active == 1;
    
    new bootstrap.Modal(document.getElementById('stageModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>