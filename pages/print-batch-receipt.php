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
    die('Batch not found');
}

// Get stage history
$query = "SELECT bsh.*, ps.stage_name, v.vendor_name
          FROM batch_stage_history bsh
          LEFT JOIN production_stages ps ON bsh.stage_id = ps.stage_id
          LEFT JOIN vendors v ON bsh.vendor_id = v.vendor_id
          WHERE bsh.batch_id = ?
          ORDER BY ps.stage_number";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $batch_id);
mysqli_stmt_execute($stmt);
$history = mysqli_stmt_get_result($stmt);

// Get company name from settings
$company_query = mysqli_query($conn, "SELECT setting_value FROM system_settings WHERE setting_key = 'company_name'");
$company_name = mysqli_fetch_assoc($company_query)['setting_value'] ?? 'Wool Production MES';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Receipt - <?php echo $batch['batch_number']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            body { margin: 0; padding: 15px; }
        }
        body { 
            background: #f5f5f5; 
            font-family: Arial, sans-serif;
        }
        .receipt-container {
            max-width: 900px;
            margin: 20px auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .receipt-header {
            border-bottom: 3px solid #333;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .receipt-title {
            font-size: 18px;
            color: #666;
        }
        .batch-number {
            font-size: 32px;
            font-weight: bold;
            color: #000;
            margin: 20px 0;
        }
        .info-section {
            margin: 20px 0;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 14px;
        }
        .info-value {
            font-size: 16px;
            color: #000;
        }
        .badge-custom {
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: normal;
        }
        .timeline-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #0d6efd;
        }
        .stage-history {
            margin-top: 30px;
        }
        .stage-item {
            border-left: 3px solid #dee2e6;
            padding-left: 20px;
            margin-bottom: 15px;
            position: relative;
        }
        .stage-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 5px;
            width: 10px;
            height: 10px;
            background: #0d6efd;
            border-radius: 50%;
        }
        .stage-item.completed::before {
            background: #198754;
        }
        .footer-note {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 12px;
            color: #666;
        }
        @page {
            margin: 1cm;
        }
    </style>
</head>
<body>

<div class="receipt-container">
    <!-- Print Button -->
    <div class="no-print text-end mb-3">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer"></i> Print Receipt
        </button>
        <a href="batch-details.php?id=<?php echo $batch_id; ?>" class="btn btn-outline-secondary">
            Back to Details
        </a>
    </div>

    <!-- Header -->
    <div class="receipt-header">
        <div class="row">
            <div class="col-8">
                <div class="company-name"><?php echo clean($company_name); ?></div>
                <div class="receipt-title">Production Batch Receipt</div>
            </div>
            <div class="col-4 text-end">
                <div class="text-muted small">Receipt Date</div>
                <div><strong><?php echo date('d M Y H:i'); ?></strong></div>
            </div>
        </div>
    </div>

    <!-- Batch Number -->
    <div class="text-center batch-number">
        <?php echo clean($batch['batch_number']); ?>
    </div>

    <!-- Main Information -->
    <div class="row info-section">
        <div class="col-md-6">
            <div class="mb-3">
                <div class="info-label">Created by</div>
                <div class="info-value"><?php echo clean($batch['creator_name']); ?> on <?php echo formatDate($batch['created_at']); ?></div>
            </div>
        </div>
    </div>

    <div class="row info-section">
        <div class="col-md-4">
            <div class="mb-3">
                <div class="info-label">Current Stage:</div>
                <span class="badge badge-custom bg-primary"><?php echo clean($batch['stage_name']); ?></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <div class="info-label">Status:</div>
                <?php 
                $status_colors = [
                    'In Progress' => 'primary',
                    'Completed' => 'success',
                    'On Hold' => 'warning',
                    'Cancelled' => 'danger'
                ];
                $color = $status_colors[$batch['status']] ?? 'secondary';
                ?>
                <span class="badge badge-custom bg-<?php echo $color; ?>"><?php echo $batch['status']; ?></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <div class="info-label">Days in Production:</div>
                <div class="info-value"><?php echo getDaysDiff($batch['start_date']); ?> days</div>
            </div>
        </div>
    </div>

    <!-- Weight Information -->
    <div class="row info-section">
        <div class="col-md-6">
            <div class="mb-3">
                <div class="info-label">Initial Weight:</div>
                <div class="info-value"><?php echo formatWeight($batch['initial_weight']); ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <div class="info-label">Current Weight:</div>
                <div class="info-value"><?php echo formatWeight($batch['current_weight']); ?></div>
            </div>
        </div>
    </div>

    <div class="row info-section">
        <div class="col-md-6">
            <div class="mb-3">
                <div class="info-label">Total Weight Loss:</div>
                <div class="info-value">
                    <?php 
                    $loss = $batch['initial_weight'] - $batch['current_weight'];
                    $loss_percent = calculateWeightLoss($batch['initial_weight'], $batch['current_weight']);
                    echo formatWeight($loss) . " ({$loss_percent}%)";
                    ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <div class="info-label">Source Supplier:</div>
                <div class="info-value"><?php echo clean($batch['source_supplier']); ?></div>
            </div>
        </div>
    </div>

    <!-- Cost Information -->
    <?php if ($batch['raw_material_cost'] > 0 || $batch['total_cost'] > 0): ?>
    <div class="info-section">
        <h5 class="mb-3">Cost Information</h5>
        <div class="timeline-box">
            <div class="row">
                <div class="col-md-4">
                    <div class="info-label">Raw Material Cost:</div>
                    <div class="info-value">PKR <?php echo number_format($batch['raw_material_cost'], 2); ?></div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Processing Cost:</div>
                    <div class="info-value">PKR <?php echo number_format($batch['total_processing_cost'], 2); ?></div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Total Cost:</div>
                    <div class="info-value text-danger">
                        <strong>PKR <?php echo number_format($batch['total_cost'], 2); ?></strong>
                    </div>
                </div>
            </div>
            
            <?php if ($batch['selling_price'] > 0): ?>
            <hr>
            <div class="row">
                <div class="col-md-4">
                    <div class="info-label">Selling Price:</div>
                    <div class="info-value">PKR <?php echo number_format($batch['selling_price'], 2); ?></div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Profit/Loss:</div>
                    <div class="info-value <?php echo $batch['profit'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <strong>PKR <?php echo number_format($batch['profit'], 2); ?></strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Profit Margin:</div>
                    <div class="info-value">
                        <?php 
                        $margin = $batch['selling_price'] > 0 ? ($batch['profit'] / $batch['selling_price'] * 100) : 0;
                        echo number_format($margin, 2) . '%';
                        ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Timeline -->
    <div class="info-section">
        <h5 class="mb-3">Timeline</h5>
        <div class="timeline-box">
            <div class="row">
                <div class="col-md-4">
                    <div class="info-label">Start Date:</div>
                    <div class="info-value"><?php echo formatDate($batch['start_date']); ?></div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Expected:</div>
                    <div class="info-value"><?php echo formatDate($batch['expected_completion_date']); ?></div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Duration:</div>
                    <div class="info-value">
                        <?php
                        if ($batch['status'] === 'Completed') {
                            echo getDaysDiff($batch['start_date'], $batch['actual_completion_date']) . ' days';
                        } else {
                            echo getDaysDiff($batch['start_date']) . ' days (ongoing)';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notes -->
    <?php if ($batch['notes']): ?>
    <div class="info-section">
        <div class="info-label">Notes:</div>
        <div class="info-value"><?php echo nl2br(clean($batch['notes'])); ?></div>
    </div>
    <?php endif; ?>

    <!-- Stage History -->
    <div class="stage-history">
        <h5 class="mb-3">Production Stage History</h5>
        <?php if (mysqli_num_rows($history) > 0): ?>
            <?php while ($stage = mysqli_fetch_assoc($history)): ?>
            <div class="stage-item <?php echo $stage['status'] === 'Completed' ? 'completed' : ''; ?>">
                <div class="row">
                    <div class="col-md-3">
                        <strong><?php echo clean($stage['stage_name']); ?></strong><br>
                        <small class="text-muted"><?php echo clean($stage['vendor_name']); ?></small>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Weight:</small><br>
                        <?php echo formatWeight($stage['weight_in']); ?> → 
                        <?php echo $stage['weight_out'] ? formatWeight($stage['weight_out']) : '-'; ?>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Cost:</small><br>
                        <?php echo $stage['processing_cost'] > -999999 ? 'PKR ' . number_format($stage['processing_cost'], 2) : '-'; ?>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Duration:</small><br>
                        <?php echo $stage['duration_hours'] ? $stage['duration_hours'] . 'h' : '-'; ?>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Status:</small><br>
                        <span class="badge bg-<?php echo $stage['status'] === 'Completed' ? 'success' : 'primary'; ?>">
                            <?php echo $stage['status']; ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-muted">No stage history available</p>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="footer-note">
        <div class="row">
            <div class="col-md-6">
                <strong>Document ID:</strong> <?php echo $batch['batch_id']; ?><br>
                <strong>Generated by:</strong> <?php echo clean($_SESSION['full_name']); ?>
            </div>
            <div class="col-md-6 text-end">
                <strong>Print Date:</strong> <?php echo date('d M Y H:i:s'); ?><br>
                <strong>System:</strong> <?php echo clean($company_name); ?>
            </div>
        </div>
        <div class="text-center mt-3">
            <small class="text-muted">
                This is a computer-generated document and does not require a signature.<br>
                For verification, please contact the production department.
            </small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>