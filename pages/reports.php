<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$page_title = 'Reports';

// Date range filter
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Production Summary
$summary_query = "SELECT 
    COUNT(*) as total_batches,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(initial_weight) as total_initial_weight,
    SUM(current_weight) as total_current_weight,
    SUM(total_cost) as total_cost,
    SUM(selling_price) as total_revenue,
    SUM(profit) as total_profit,
    AVG(DATEDIFF(COALESCE(actual_completion_date, CURDATE()), start_date)) as avg_duration
FROM batches
WHERE start_date BETWEEN ? AND ?";
$stmt = mysqli_prepare($conn, $summary_query);
mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
mysqli_stmt_execute($stmt);
$summary = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Stage-wise breakdown (for chart)
$stage_query = "SELECT 
    ps.stage_name,
    ps.stage_number,
    COUNT(DISTINCT bsh.batch_id) as batch_count,
    AVG(bsh.duration_hours) as avg_duration_hours,
    AVG(bsh.weight_loss) as avg_weight_loss,
    SUM(bsh.processing_cost) as total_cost
FROM production_stages ps
LEFT JOIN batch_stage_history bsh ON ps.stage_id = bsh.stage_id 
    AND bsh.status = 'Completed'
    AND bsh.start_date BETWEEN ? AND ?
GROUP BY ps.stage_id, ps.stage_name, ps.stage_number
ORDER BY ps.stage_number";
$stmt = mysqli_prepare($conn, $stage_query);
mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
mysqli_stmt_execute($stmt);
$stage_stats = mysqli_stmt_get_result($stmt);

// Prepare chart data
$chart_stages = [];
$chart_batch_counts = [];
$chart_durations = [];
$chart_weight_loss = [];
$chart_costs = [];

mysqli_data_seek($stage_stats, 0);
while ($stage = mysqli_fetch_assoc($stage_stats)) {
    $chart_stages[] = $stage['stage_name'];
    $chart_batch_counts[] = $stage['batch_count'] ?? 0;
    $chart_durations[] = round($stage['avg_duration_hours'] ?? 0);
    $chart_weight_loss[] = round($stage['avg_weight_loss'] ?? 0, 2);
    $chart_costs[] = round($stage['total_cost'] ?? 0);
}

// Batch status distribution (for pie chart)
$status_query = "SELECT 
    status,
    COUNT(*) as count
FROM batches
WHERE start_date BETWEEN ? AND ?
GROUP BY status";
$stmt = mysqli_prepare($conn, $status_query);
mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
mysqli_stmt_execute($stmt);
$status_result = mysqli_stmt_get_result($stmt);

$status_labels = [];
$status_counts = [];
while ($row = mysqli_fetch_assoc($status_result)) {
    $status_labels[] = $row['status'];
    $status_counts[] = $row['count'];
}

// Monthly trend (last 6 months)
$trend_query = "SELECT 
    DATE_FORMAT(start_date, '%b %Y') as month,
    COUNT(*) as batches_started,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as batches_completed
FROM batches
WHERE start_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY DATE_FORMAT(start_date, '%Y-%m')
ORDER BY start_date";
$trend_result = mysqli_query($conn, $trend_query);

$trend_months = [];
$trend_started = [];
$trend_completed = [];
while ($row = mysqli_fetch_assoc($trend_result)) {
    $trend_months[] = $row['month'];
    $trend_started[] = $row['batches_started'];
    $trend_completed[] = $row['batches_completed'];
}

// Vendor Performance
$vendor_query = "SELECT 
    v.vendor_name,
    COUNT(bsh.history_id) as batches_handled,
    AVG(bsh.duration_hours) as avg_duration,
    AVG(bsh.weight_loss) as avg_weight_loss
FROM vendors v
LEFT JOIN batch_stage_history bsh ON v.vendor_id = bsh.vendor_id 
    AND bsh.status = 'Completed'
    AND bsh.start_date BETWEEN ? AND ?
WHERE v.is_active = 1
GROUP BY v.vendor_id, v.vendor_name
HAVING batches_handled > 0
ORDER BY batches_handled DESC
LIMIT 10";
$stmt = mysqli_prepare($conn, $vendor_query);
mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
mysqli_stmt_execute($stmt);
$vendor_stats = mysqli_stmt_get_result($stmt);

include '../includes/header.php';
?>

<style>
.chart-container {
    position: relative;
    height: 300px;
    margin-bottom: 20px;
}
@media print {
    .no-print { display: none; }
    .chart-container { height: 250px; page-break-inside: avoid; }
}
</style>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-bar-chart-line"></i> Production Reports & Analytics</h2>
    </div>
</div>

<!-- Date Filter -->
<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">From Date</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">To Date</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-filter"></i> Generate Report
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="text-muted">Total Batches</h6>
                <h2 class="mb-0"><?php echo $summary['total_batches']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-success">
            <div class="card-body">
                <h6 class="text-muted">Completed</h6>
                <h2 class="mb-0 text-success"><?php echo $summary['completed']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-primary">
            <div class="card-body">
                <h6 class="text-muted">In Progress</h6>
                <h2 class="mb-0 text-primary"><?php echo $summary['in_progress']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="text-muted">Avg Duration</h6>
                <h2 class="mb-0"><?php echo round($summary['avg_duration']); ?> days</h2>
            </div>
        </div>
    </div>
</div>

<!-- Financial Summary -->
<?php if ($summary['total_cost'] > 0 || $summary['total_revenue'] > 0): ?>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center border-danger">
            <div class="card-body">
                <h6 class="text-muted">Total Cost</h6>
                <h4 class="mb-0 text-danger"><?php echo formatCurrency($summary['total_cost']); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-success">
            <div class="card-body">
                <h6 class="text-muted">Total Revenue</h6>
                <h4 class="mb-0 text-success"><?php echo formatCurrency($summary['total_revenue']); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-<?php echo $summary['total_profit'] >= 0 ? 'success' : 'danger'; ?>">
            <div class="card-body">
                <h6 class="text-muted">Total Profit</h6>
                <h4 class="mb-0 text-<?php echo $summary['total_profit'] >= 0 ? 'success' : 'danger'; ?>">
                    <?php echo formatCurrency($summary['total_profit']); ?>
                </h4>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Charts Row 1 -->
<div class="row mb-4">
    <!-- Batch Status Distribution -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Batch Status Distribution</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Batches per Stage -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Batches Processed per Stage</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="stageCountChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 2 -->
<div class="row mb-4">
    <!-- Weight Loss by Stage -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-down"></i> Average Weight Loss by Stage</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="weightLossChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Duration by Stage -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Average Duration by Stage (Hours)</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="durationChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 3 -->
<div class="row mb-4">
    <!-- Monthly Trend -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Production Trend (Last 6 Months)</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vendor Performance Table -->
<?php if (mysqli_num_rows($vendor_stats) > 0): ?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-people"></i> Top 10 Vendor Performance</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Vendor</th>
                                <th>Batches Handled</th>
                                <th>Avg Duration (hrs)</th>
                                <th>Avg Weight Loss (kg)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php mysqli_data_seek($vendor_stats, 0); ?>
                            <?php while ($vendor = mysqli_fetch_assoc($vendor_stats)): ?>
                            <tr>
                                <td><strong><?php echo clean($vendor['vendor_name']); ?></strong></td>
                                <td><?php echo $vendor['batches_handled']; ?></td>
                                <td><?php echo round($vendor['avg_duration']); ?>h</td>
                                <td><?php echo round($vendor['avg_weight_loss'], 2); ?> kg</td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Chart.js Configuration
const chartColors = {
    primary: 'rgba(13, 110, 253, 0.8)',
    success: 'rgba(25, 135, 84, 0.8)',
    danger: 'rgba(220, 53, 69, 0.8)',
    warning: 'rgba(255, 193, 7, 0.8)',
    info: 'rgba(13, 202, 240, 0.8)',
    secondary: 'rgba(108, 117, 125, 0.8)'
};

// 1. Status Distribution Chart (Doughnut)
<?php if (!empty($status_labels)): ?>
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($status_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($status_counts); ?>,
            backgroundColor: [
                chartColors.primary,
                chartColors.success,
                chartColors.warning,
                chartColors.danger
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});
<?php endif; ?>

// 2. Batches per Stage Chart (Bar)
<?php if (!empty($chart_stages)): ?>
new Chart(document.getElementById('stageCountChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chart_stages); ?>,
        datasets: [{
            label: 'Batches Processed',
            data: <?php echo json_encode($chart_batch_counts); ?>,
            backgroundColor: chartColors.primary,
            borderColor: chartColors.primary,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
<?php endif; ?>

// 3. Weight Loss Chart (Bar - Red)
<?php if (!empty($chart_stages)): ?>
new Chart(document.getElementById('weightLossChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chart_stages); ?>,
        datasets: [{
            label: 'Avg Weight Loss (kg)',
            data: <?php echo json_encode($chart_weight_loss); ?>,
            backgroundColor: chartColors.danger,
            borderColor: chartColors.danger,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
<?php endif; ?>

// 4. Duration Chart (Horizontal Bar)
<?php if (!empty($chart_stages)): ?>
new Chart(document.getElementById('durationChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chart_stages); ?>,
        datasets: [{
            label: 'Avg Duration (hours)',
            data: <?php echo json_encode($chart_durations); ?>,
            backgroundColor: chartColors.info,
            borderColor: chartColors.info,
            borderWidth: 1
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
<?php endif; ?>

// 5. Trend Chart (Line)
<?php if (!empty($trend_months)): ?>
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($trend_months); ?>,
        datasets: [
            {
                label: 'Batches Started',
                data: <?php echo json_encode($trend_started); ?>,
                borderColor: chartColors.primary,
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Batches Completed',
                data: <?php echo json_encode($trend_completed); ?>,
                borderColor: chartColors.success,
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                tension: 0.4,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: {
                position: 'top'
            }
        }
    }
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>