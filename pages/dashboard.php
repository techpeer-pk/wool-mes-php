<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$page_title = 'Dashboard';

// Get current user info for vendor filtering
$user_query = "SELECT vendor_id FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// ========================================
// FIXED: Get statistics with vendor filtering
// ========================================
$stats = [];

if (hasRole('Vendor') && isset($user['vendor_id'])) {
    // VENDOR: Only count batches they're assigned to
    $vendor_id = (int)$user['vendor_id'];
    
    // Total batches (ever assigned to this vendor)
    $query = "SELECT COUNT(DISTINCT b.batch_id) as total 
              FROM batches b
              JOIN batch_stage_history bsh ON b.batch_id = bsh.batch_id
              WHERE bsh.vendor_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $vendor_id);
    mysqli_stmt_execute($stmt);
    $stats['total_batches'] = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
    
    // In progress (currently assigned to vendor)
    $query = "SELECT COUNT(DISTINCT b.batch_id) as total 
              FROM batches b
              JOIN batch_stage_history bsh ON b.batch_id = bsh.batch_id
              WHERE bsh.vendor_id = ? 
              AND b.status = 'In Progress'
              AND bsh.status = 'In Progress'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $vendor_id);
    mysqli_stmt_execute($stmt);
    $stats['in_progress'] = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
    
    // Completed this month (where vendor was involved)
    $query = "SELECT COUNT(DISTINCT b.batch_id) as total 
              FROM batches b
              JOIN batch_stage_history bsh ON b.batch_id = bsh.batch_id
              WHERE bsh.vendor_id = ?
              AND b.status = 'Completed' 
              AND MONTH(b.actual_completion_date) = MONTH(CURRENT_DATE())
              AND YEAR(b.actual_completion_date) = YEAR(CURRENT_DATE())";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $vendor_id);
    mysqli_stmt_execute($stmt);
    $stats['completed_month'] = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
    
    // Total current weight (batches currently with vendor)
    $query = "SELECT SUM(DISTINCT b.current_weight) as total 
              FROM batches b
              JOIN batch_stage_history bsh ON b.batch_id = bsh.batch_id
              WHERE bsh.vendor_id = ?
              AND b.status = 'In Progress'
              AND bsh.status = 'In Progress'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $vendor_id);
    mysqli_stmt_execute($stmt);
    $stats['total_weight'] = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0;
    
    // Delayed batches (currently with vendor and delayed)
    $query = "SELECT COUNT(DISTINCT b.batch_id) as total 
              FROM batches b
              JOIN batch_stage_history bsh ON b.batch_id = bsh.batch_id
              WHERE bsh.vendor_id = ?
              AND b.status = 'In Progress'
              AND bsh.status = 'In Progress'
              AND b.expected_completion_date < CURDATE()";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $vendor_id);
    mysqli_stmt_execute($stmt);
    $stats['delayed'] = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
    
    // Don't show total cost to vendors
    $stats['total_cost'] = 0;
    
} else {
    // ADMIN/SUPERVISOR/VIEWER: Show all batches
    
    // Total batches
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM batches");
    $stats['total_batches'] = mysqli_fetch_assoc($result)['total'];
    
    // In Progress batches
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM batches WHERE status = 'In Progress'");
    $stats['in_progress'] = mysqli_fetch_assoc($result)['total'];
    
    // Completed this month
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM batches 
                                   WHERE status = 'Completed' 
                                   AND MONTH(actual_completion_date) = MONTH(CURRENT_DATE())
                                   AND YEAR(actual_completion_date) = YEAR(CURRENT_DATE())");
    $stats['completed_month'] = mysqli_fetch_assoc($result)['total'];
    
    // Total current weight
    $result = mysqli_query($conn, "SELECT SUM(current_weight) as total FROM batches WHERE status = 'In Progress'");
    $stats['total_weight'] = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Total Cost
    $result = mysqli_query($conn, "SELECT SUM(total_cost) as totalCost FROM batches");
    $stats['total_cost'] = mysqli_fetch_assoc($result)['totalCost'] ?? 0;
    
    // Delayed batches
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM batches 
                                   WHERE status = 'In Progress' 
                                   AND expected_completion_date < CURDATE()");
    $stats['delayed'] = mysqli_fetch_assoc($result)['total'];
}

// ========================================
// KEEP YOUR ORIGINAL: Recent batches query
// ========================================
if (hasRole('Vendor') && isset($user['vendor_id'])) {
    // Vendors see only their batches
    $query = "SELECT b.*, ps.stage_name 
              FROM batches b
              JOIN production_stages ps ON b.current_stage_id = ps.stage_id
              JOIN batch_stage_history bsh ON b.batch_id = bsh.batch_id
              WHERE bsh.vendor_id = ? AND bsh.status = 'In Progress'
              GROUP BY b.batch_id
              ORDER BY b.created_at DESC
              LIMIT 10";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user['vendor_id']);
    mysqli_stmt_execute($stmt);
    $recent_batches = mysqli_stmt_get_result($stmt);
} else {
    // Others see all batches
    $query = "SELECT b.*, ps.stage_name 
              FROM batches b
              JOIN production_stages ps ON b.current_stage_id = ps.stage_id
              ORDER BY b.created_at DESC
              LIMIT 10";
    $recent_batches = mysqli_query($conn, $query);
}

// Batches by stage
if (hasRole('Vendor') && isset($user['vendor_id'])) {
    // Vendors: show stages where they have active batches
    $query = "SELECT ps.stage_name, COUNT(DISTINCT b.batch_id) as count
              FROM production_stages ps
              LEFT JOIN batches b ON ps.stage_id = b.current_stage_id 
                  AND b.status = 'In Progress'
              LEFT JOIN batch_stage_history bsh ON b.batch_id = bsh.batch_id
                  AND bsh.vendor_id = ?
                  AND bsh.status = 'In Progress'
              WHERE bsh.history_id IS NOT NULL OR b.batch_id IS NULL
              GROUP BY ps.stage_id, ps.stage_name
              ORDER BY ps.stage_number";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user['vendor_id']);
    mysqli_stmt_execute($stmt);
    $stages_data = mysqli_stmt_get_result($stmt);
} else {
    $query = "SELECT ps.stage_name, COUNT(b.batch_id) as count
              FROM production_stages ps
              LEFT JOIN batches b ON ps.stage_id = b.current_stage_id AND b.status = 'In Progress'
              GROUP BY ps.stage_id, ps.stage_name
              ORDER BY ps.stage_number";
    $stages_data = mysqli_query($conn, $query);
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
        <p class="text-muted">
            Welcome back, <?php echo clean($_SESSION['full_name']); ?>!
            <?php if (hasRole('Vendor')): ?>
                <span class="badge bg-info">Vendor View - Showing only your assigned batches</span>
            <?php endif; ?>
        </p>
        <hr>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 zoom-card">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="text-muted">
                    <?php echo hasRole('Vendor') ? 'My Total Batches' : 'Total Batches'; ?>
                </h6>
                <h2 class="mb-0"><?php echo $stats['total_batches']; ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 zoom-card">
        <div class="card text-center border-primary">
            <div class="card-body">
                <h6 class="text-muted">
                    <?php echo hasRole('Vendor') ? 'Currently With Me' : 'In Progress'; ?>
                </h6>
                <h2 class="mb-0 text-primary"><?php echo $stats['in_progress']; ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 zoom-card">
        <div class="card text-center border-success">
            <div class="card-body">
                <h6 class="text-muted">Completed (Month)</h6>
                <h2 class="mb-0 text-success"><?php echo $stats['completed_month']; ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-3 zoom-card">
        <div class="card text-center <?php echo $stats['delayed'] > 0 ? 'border-danger' : ''; ?>">
            <div class="card-body">
                <h6 class="text-muted">Delayed Batches</h6>
                <h2 class="mb-0 <?php echo $stats['delayed'] > 0 ? 'text-danger' : ''; ?>">
                    <?php echo $stats['delayed']; ?>
                </h2>
            </div>
        </div>
    </div>    

    <div class="col-md-6 zoom-card">
        <div class="card text-center border-success">
            <div class="card-body">
                <h6 class="text-muted">
                    <?php echo hasRole('Vendor') ? 'Current Weight With Me' : 'Current Weight'; ?>
                </h6>
                <h2 class="mb-0 text-success"><?php echo formatWeight($stats['total_weight']); ?></h2>
            </div>
        </div>
    </div>

    <?php if (!hasRole('Vendor')): // Don't show costs to vendors ?>
    <div class="col-md-6 zoom-card">
        <div class="card text-center border-danger">
            <div class="card-body">
                <h6 class="text-muted">Total Cost</h6>
                <h2 class="mb-0 text-danger"><?php echo formatCurrency($stats['total_cost']); ?></h2>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row">
    <!-- Recent Batches -->
    <div class="col-md-8">
        <!-- Quick Chart -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-graph-up"></i> Batches by Stage</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="dashboardChart" style="height: 200px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // Get data for chart
        $chart_query = "SELECT ps.stage_name, COUNT(b.batch_id) as count
                        FROM production_stages ps
                        LEFT JOIN batches b ON ps.stage_id = b.current_stage_id AND b.status = 'In Progress'
                        GROUP BY ps.stage_id, ps.stage_name
                        ORDER BY ps.stage_number";
        $chart_result = mysqli_query($conn, $chart_query);

        $stage_names = [];
        $stage_counts = [];
        while ($row = mysqli_fetch_assoc($chart_result)) {
            $stage_names[] = $row['stage_name'];
            $stage_counts[] = $row['count'];
        }
        ?>

        <script>
        new Chart(document.getElementById('dashboardChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($stage_names); ?>,
                datasets: [{
                    label: 'Active Batches',
                    data: <?php echo json_encode($stage_counts); ?>,
                    backgroundColor: 'rgba(13, 110, 253, 0.8)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
        </script>        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo hasRole('Vendor') ? 'My Active Batches' : 'Recent Batches'; ?>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Batch No.</th>
                                <th>Current Stage</th>
                                <th>Weight</th>
                                <th>Status</th>
                                <th>Days</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($recent_batches) === 0): ?>
                            <tr>
                                <td colspan="6" class="text-center py-3 text-muted">
                                    <i class="bi bi-inbox"></i> 
                                    <?php echo hasRole('Vendor') ? 'No batches currently assigned to you' : 'No batches found'; ?>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php while ($batch = mysqli_fetch_assoc($recent_batches)): ?>
                            <tr>
                                <td>
                                    <a href="batch-details.php?id=<?php echo $batch['batch_id']; ?>">
                                        <?php echo clean($batch['batch_number']); ?>
                                    </a>
                                </td>
                                <td><?php echo clean($batch['stage_name']); ?></td>
                                <td><?php echo formatWeight($batch['current_weight']); ?></td>
                                <td><?php echo getStatusBadge($batch['status']); ?></td>
                                <td><?php echo getDaysDiff($batch['start_date']); ?> days</td>
                                <td>
                                    <a href="batch-details.php?id=<?php echo $batch['batch_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Batches by Stage -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo hasRole('Vendor') ? 'My Stages' : 'Batches by Stage'; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php while ($stage = mysqli_fetch_assoc($stages_data)): ?>
                <div class="mb-2">
                    <small class="text-muted"><?php echo clean($stage['stage_name']); ?></small>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar" 
                             style="width: <?php echo min($stage['count'] * 20, 100); ?>%">
                            <?php echo $stage['count']; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>