<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$page_title = 'All Batches';

// Get current user info for vendor filtering
$user_query = "SELECT vendor_id FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Get filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$stage_filter = isset($_GET['stage']) ? (int)$_GET['stage'] : 0;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Build query
$where = ["1=1"];
$params = [];
$types = "";

if ($status_filter) {
    $where[] = "b.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($stage_filter) {
    $where[] = "b.current_stage_id = ?";
    $params[] = $stage_filter;
    $types .= "i";
}

if ($search) {
    $where[] = "(b.batch_number LIKE ? OR b.source_supplier LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$where_clause = implode(" AND ", $where);

// Add vendor filtering for vendor role
if (hasRole('Vendor') && isset($user['vendor_id'])) {
    $where_clause .= " AND EXISTS (
        SELECT 1 FROM batch_stage_history bsh 
        WHERE bsh.batch_id = b.batch_id 
        AND bsh.vendor_id = {$user['vendor_id']}
    )";
}

$query = "SELECT b.*, ps.stage_name, ps.stage_number
          FROM batches b
          JOIN production_stages ps ON b.current_stage_id = ps.stage_id
          WHERE {$where_clause}
          ORDER BY b.created_at DESC";

if ($types) {
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $query);
}

// Get all stages for filter
$stages = mysqli_query($conn, "SELECT * FROM production_stages ORDER BY stage_number");

include '../includes/header.php';
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2><i class="bi bi-list-ul"></i> All Batches</h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if (hasRole('Admin') || hasRole('Supervisor')): ?>
        <a href="create-batch.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Batch
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" 
                       placeholder="Batch number or supplier..." 
                       value="<?php echo clean($search); ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="In Progress" <?php echo $status_filter === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="Completed" <?php echo $status_filter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="On Hold" <?php echo $status_filter === 'On Hold' ? 'selected' : ''; ?>>On Hold</option>
                    <option value="Cancelled" <?php echo $status_filter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Stage</label>
                <select name="stage" class="form-select">
                    <option value="">All Stages</option>
                    <?php mysqli_data_seek($stages, 0); ?>
                    <?php while ($stage = mysqli_fetch_assoc($stages)): ?>
                    <option value="<?php echo $stage['stage_id']; ?>" 
                            <?php echo $stage_filter === $stage['stage_id'] ? 'selected' : ''; ?>>
                        <?php echo clean($stage['stage_name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label><br>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-filter"></i> Filter
                </button>
                <a href="batches.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x"></i> Clear
                </a>
            </div>
            
        </form>
    </div>
</div>

<!-- Batches Table -->
<div class="container-fluid mb-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="batchTable" class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Batch No.</th>
                        <th>Current Stage</th>
                        <th>Initial Weight</th>
                        <th>Current Weight</th>
                        <th>Weight Loss</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>Days</th>
                        <th>Source</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) === 0): ?>
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mb-0">No batches found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php while ($batch = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>
                            <a href="batch-details.php?id=<?php echo $batch['batch_id']; ?>">
                                <strong><?php echo clean($batch['batch_number']); ?></strong>
                            </a>
                        </td>
                        <td>
                            <small class="text-muted">Stage <?php echo $batch['stage_number']; ?></small><br>
                            <?php echo clean($batch['stage_name']); ?>
                        </td>
                        <td><?php echo formatWeight($batch['initial_weight']); ?></td>
                        <td><?php echo formatWeight($batch['current_weight']); ?></td>
                        <td>
                            <?php 
                            $loss_percent = calculateWeightLoss($batch['initial_weight'], $batch['current_weight']);
                            $color = $loss_percent > 60 ? 'text-danger' : ($loss_percent > 40 ? 'text-warning' : 'text-success');
                            ?>
                            <span class="<?php echo $color; ?>">
                                <?php echo $loss_percent; ?>%
                            </span>
                        </td>
                        <td><?php echo getStatusBadge($batch['status']); ?></td>
                        <td><?php echo formatDate($batch['start_date']); ?></td>
                        <td>
                            <?php 
                            $days = getDaysDiff($batch['start_date'], 
                                $batch['actual_completion_date'] ?? date('Y-m-d'));
                            echo $days . ' days';
                            ?>
                        </td>
                        <td>
                            <small><?php echo clean($batch['source_supplier']); ?></small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="batch-details.php?id=<?php echo $batch['batch_id']; ?>" 
                                   class="btn btn-outline-primary" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ($batch['status'] === 'In Progress' && (hasRole('Admin') || hasRole('Supervisor'))): ?>
                                <a href="update-batch.php?id=<?php echo $batch['batch_id']; ?>" 
                                   class="btn btn-outline-success" title="Update Stage">
                                    <i class="bi bi-arrow-right-circle"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <small class="text-muted">
            Total: <?php echo mysqli_num_rows($result); ?> batch(es) found
        </small>
    </div>
</div>

<?php include '../includes/footer.php'; ?>