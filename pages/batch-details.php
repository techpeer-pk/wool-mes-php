<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$batch_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get batch details
$query = "SELECT b.*, ps.stage_name, u.full_name as creator_name
          FROM batches b
          LEFT JOIN production_stages ps ON b.current_stage_id = ps.stage_id
          LEFT JOIN users u ON b.created_by = u.user_id
          WHERE b.batch_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $batch_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$batch = mysqli_fetch_assoc($result);

if (!$batch) {
    header('Location: batches.php');
    exit();
}

// Get stage history
$query = "SELECT bsh.*, ps.stage_name, v.vendor_name, u.full_name as updated_by_name
          FROM batch_stage_history bsh
          LEFT JOIN production_stages ps ON bsh.stage_id = ps.stage_id
          LEFT JOIN vendors v ON bsh.vendor_id = v.vendor_id
          LEFT JOIN users u ON bsh.updated_by = u.user_id
          WHERE bsh.batch_id = ?
          ORDER BY ps.stage_number";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $batch_id);
mysqli_stmt_execute($stmt);
$history = mysqli_stmt_get_result($stmt);

// Get alerts
$query = "SELECT a.*, u.full_name as resolved_by_name
          FROM alerts a
          LEFT JOIN users u ON a.resolved_by = u.user_id
          WHERE a.batch_id = ?
          ORDER BY a.created_at DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $batch_id);
mysqli_stmt_execute($stmt);
$alerts = mysqli_stmt_get_result($stmt);

$page_title = 'Batch Details - ' . $batch['batch_number'];
include '../includes/header.php';
?>

<div class="row mb-3">
    <div class="col-md-12">
        <a href="batches.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to All Batches
        </a>
        
        <?php 
        // Check if user can update this batch
        $can_update = false;
        if (hasRole('Admin') || hasRole('Supervisor')) {
            $can_update = true;
        } elseif (hasRole('Vendor') && isset($user['vendor_id'])) {
            // Vendor can update if assigned to current stage
            $check_query = "SELECT 1 FROM batch_stage_history 
                            WHERE batch_id = ? 
                            AND vendor_id = ? 
                            AND status = 'In Progress'";
            $stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($stmt, "ii", $batch_id, $user['vendor_id']);
            mysqli_stmt_execute($stmt);
            $can_update = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
        }

        if ($can_update && $batch['status'] === 'In Progress'): 
        ?>
        <a href="update-batch.php?id=<?php echo $batch_id; ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-arrow-right-circle"></i> Update Stage
        </a>
        <?php endif; ?>

        <?php if ($batch['status'] === 'Completed'): ?>
        <a href="set-price.php?id=<?php echo $batch_id; ?>" class="btn btn-sm btn-success">
            <i class="bi bi-currency-exchange"></i> Set Selling Price
        </a>
        <?php endif; ?>        

        <a href="print-batch-receipt.php?id=<?php echo $batch_id; ?>" class="btn btn-sm btn-success" target="_blank">
            <i class="bi bi-printer"></i> Print Receipt
        </a>
    </div>
</div>

<!-- Batch Header -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h3><?php echo clean($batch['batch_number']); ?></h3>
                <p class="text-muted mb-3">Created by <?php echo clean($batch['creator_name']); ?> on <?php echo formatDate($batch['created_at']); ?></p>
                
                <div class="row">
                    <div class="col-md-4">
                        <strong>Current Stage:</strong><br>
                        <span class="badge bg-primary"><?php echo clean($batch['stage_name']); ?></span>
                    </div>
                    <div class="col-md-4">
                        <strong>Status:</strong><br>
                        <?php echo getStatusBadge($batch['status']); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Days in Production:</strong><br>
                        <?php echo getDaysDiff($batch['start_date']); ?> days
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-6">
                        <strong>Initial Weight:</strong><br>
                        <?php echo formatWeight($batch['initial_weight']); ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Current Weight:</strong><br>
                        <?php echo formatWeight($batch['current_weight']); ?>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-6">
                        <strong>Total Weight Loss:</strong><br>
                        <?php 
                        $loss = $batch['initial_weight'] - $batch['current_weight'];
                        $loss_percent = calculateWeightLoss($batch['initial_weight'], $batch['current_weight']);
                        echo formatWeight($loss) . " ({$loss_percent}%)";
                        ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Source Supplier:</strong><br>
                        <?php echo clean($batch['source_supplier']); ?>
                    </div>
                </div>
                
                <?php if ($batch['raw_material_cost'] > 0 || $batch['total_cost'] > 0): ?>
                <hr>
                
                <h5>Cost Information</h5>
                <div class="row">
                    <div class="col-md-4">
                        <strong>Raw Material:</strong><br>
                        PKR <?php echo number_format($batch['raw_material_cost'], 2); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Processing:</strong><br>
                        PKR <?php echo number_format($batch['total_processing_cost'], 2); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Total Cost:</strong><br>
                        <span class="text-danger"><strong>PKR <?php echo number_format($batch['total_cost'], 2); ?></strong></span>
                    </div>
                </div>
                
                <?php if ($batch['selling_price'] > 0): ?>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <strong>Selling Price:</strong><br>
                        PKR <?php echo number_format($batch['selling_price'], 2); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Profit:</strong><br>
                        <span class="<?php echo $batch['profit'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <strong>PKR <?php echo number_format($batch['profit'], 2); ?></strong>
                        </span>
                    </div>
                    <div class="col-md-4">
                        <strong>Margin:</strong><br>
                        <?php 
                        $margin = $batch['selling_price'] > 0 ? ($batch['profit'] / $batch['selling_price'] * 100) : 0;
                        echo number_format($margin, 2) . '%';
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Timeline</h5>
                <table class="table table-sm">
                    <tr>
                        <td><strong>Start Date:</strong></td>
                        <td><?php echo formatDate($batch['start_date']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Expected:</strong></td>
                        <td><?php echo formatDate($batch['expected_completion_date']); ?></td>
                    </tr>
                    <?php if ($batch['actual_completion_date']): ?>
                    <tr>
                        <td><strong>Completed:</strong></td>
                        <td><?php echo formatDate($batch['actual_completion_date']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td><strong>Duration:</strong></td>
                        <td>
                            <?php
                            if ($batch['status'] === 'Completed') {
                                echo getDaysDiff($batch['start_date'], $batch['actual_completion_date']) . ' days';
                            } else {
                                echo getDaysDiff($batch['start_date']) . ' days (ongoing)';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
                
                <?php if ($batch['notes']): ?>
                <hr>
                <strong>Notes:</strong>
                <p class="small"><?php echo nl2br(clean($batch['notes'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Stage History -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Stage History</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Stage</th>
                                <th>Vendor</th>
                                <th>Weight In</th>
                                <th>Weight Out</th>
                                <th>Loss</th>
                                <th>Cost (PKR)</th>
                                <th>Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($stage = mysqli_fetch_assoc($history)): ?>
                            <tr>
                                <td><?php echo clean($stage['stage_name']); ?></td>
                                <td><?php echo clean($stage['vendor_name']); ?></td>
                                <td><?php echo formatWeight($stage['weight_in']); ?></td>
                                <td><?php echo $stage['weight_out'] ? formatWeight($stage['weight_out']) : '-'; ?></td>
                                <td>
                                    <?php 
                                    if ($stage['weight_out']) {
                                        $loss_pct = calculateWeightLoss($stage['weight_in'], $stage['weight_out']);
                                        echo $loss_pct . '%';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $stage['processing_cost'] > -999999 ? number_format($stage['processing_cost'], 2) : '-'; ?></td>
                                <td><?php echo $stage['duration_hours'] ? $stage['duration_hours'] . 'h' : '-'; ?></td>
                                <td><?php echo getStatusBadge($stage['status']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if (mysqli_num_rows($alerts) > 0): ?>
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Alerts & Issues</h5>
            </div>
            <div class="card-body">
                <?php while ($alert = mysqli_fetch_assoc($alerts)): ?>
                <div class="alert alert-<?php echo $alert['severity'] === 'Critical' ? 'danger' : ($alert['severity'] === 'High' ? 'warning' : 'info'); ?> <?php echo $alert['is_resolved'] ? 'alert-dismissible' : ''; ?>">
                    <strong><?php echo clean($alert['alert_type']); ?>:</strong> 
                    <?php echo clean($alert['message']); ?>
                    <br>
                    <small class="text-muted">
                        Created: <?php echo formatDateTime($alert['created_at']); ?>
                        <?php if ($alert['is_resolved']): ?>
                        | Resolved by: <?php echo clean($alert['resolved_by_name']); ?> on <?php echo formatDateTime($alert['resolved_at']); ?>
                        <?php endif; ?>
                    </small>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>