<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is Admin
requireAdmin();

$page_title = 'Master Report';

// Fetch comprehensive batch data with stage information
$query = "
    SELECT 
        b.batch_id,
        b.batch_number,
        b.initial_weight,
        b.current_weight,
        ROUND((b.initial_weight - b.current_weight) / b.initial_weight * 100, 2) as weight_loss_percent,
        b.status,
        b.start_date,
        b.expected_completion_date,
        b.actual_completion_date,
        DATEDIFF(COALESCE(b.actual_completion_date, CURDATE()), b.start_date) as total_days,
        b.source_supplier,
        b.raw_material_cost,
        b.total_processing_cost,
        b.total_cost,
        b.selling_price,
        b.profit,
        CASE 
            WHEN b.selling_price > 0 THEN ROUND((b.profit / b.selling_price * 100), 2)
            ELSE 0 
        END as profit_margin,
        b.created_at,
        (SELECT COUNT(*) FROM batch_stage_history WHERE batch_id = b.batch_id AND status = 'Completed') as completed_stages,
        (SELECT SUM(processing_cost) FROM batch_stage_history WHERE batch_id = b.batch_id) as total_stage_costs
    FROM batches b
    ORDER BY b.batch_id DESC
";

$result = mysqli_query($conn, $query);

// Check for SQL errors
if (!$result) {
    die("Query failed: " . mysqli_error($conn) . "<br>Query: " . $query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Wool Production MES</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/searchbuilder/1.6.0/css/searchBuilder.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/datetime/1.5.1/css/dataTables.dateTime.min.css" rel="stylesheet">
    
    <style>
        /* body { padding-top: 70px; }
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .table-wrapper {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .dt-buttons { margin-bottom: 15px; }
        .dtsb-searchBuilder { margin-bottom: 15px; } */
    </style>
</head>
<body>

<?php if (isLoggedIn()): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="../pages/dashboard.php">
            <i class="bi bi-box-seam"></i> Wool MES
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../pages/dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../pages/batches.php">
                        <i class="bi bi-list-ul"></i> 
                        <?php echo hasRole('Vendor') ? 'My Batches' : 'All Batches'; ?>
                    </a>
                </li>
                
                <!-- Hide from Vendors -->
                <?php if (!hasRole('Vendor')): ?>
                <?php if (hasRole('Admin') || hasRole('Supervisor')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="../pages/create-batch.php">
                        <i class="bi bi-plus-circle"></i> New Batch
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link" href="../pages/reports.php">
                        <i class="bi bi-file-earmark-text"></i> Reports
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasRole('Admin')): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-gear"></i> Admin
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../pages/master-report.php">MIS</a></li>
                        <li><a class="dropdown-item" href="../pages/vendors.php">Vendors</a></li>
                        <li><a class="dropdown-item" href="../pages/users.php">Users</a></li>
                        <li><a class="dropdown-item" href="../pages/stages.php">Stages</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo clean($_SESSION['full_name']); ?>
                        <?php if (hasRole('Vendor')): ?>
                        <span class="badge bg-info">Vendor</span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#">Role: <?php echo clean($_SESSION['role']); ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../pages/settings.php">
                            <i class="bi bi-gear"></i> Settings
                        </a></li>
                        <li><a class="dropdown-item" href="../auth/logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<br><br>
<?php endif; ?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-file-earmark-bar-graph"></i> Master Report (MIS)</h2>
            <p class="text-muted">Comprehensive batch analysis and export</p>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card border-primary">
                <div class="card-body">
                    <h6 class="text-muted">Total Batches</h6>
                    <h3 id="totalBatches">-</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card border-success">
                <div class="card-body">
                    <h6 class="text-muted">Total Revenue</h6>
                    <h3 id="totalRevenue">-</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card border-info">
                <div class="card-body">
                    <h6 class="text-muted">Total Profit</h6>
                    <h3 id="totalProfit">-</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card border-warning">
                <div class="card-body">
                    <h6 class="text-muted">Avg Weight Loss</h6>
                    <h3 id="avgWeightLoss">-</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Master Report Table -->
    <div class="table-wrapper table-responsive">
        <table id="masterReport" class="table table-striped table-hover" style="width:100%">
            <thead class="table-dark">
                <tr>
                    <th>Batch No.</th>
                    <th>Status</th>
                    <th>Initial Weight (kg)</th>
                    <th>Current Weight (kg)</th>
                    <th>Weight Loss %</th>
                    <th>Start Date</th>
                    <th>Total Days</th>
                    <th>Source</th>
                    <th>Raw Cost</th>
                    <th>Processing Cost</th>
                    <th>Total Cost</th>
                    <th>Selling Price</th>
                    <th>Profit</th>
                    <th>Profit Margin %</th>
                    <th>Completed Stages</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><strong><?php echo clean($row['batch_number']); ?></strong></td>
                    <td>
                        <?php 
                        $statusClass = [
                            'In Progress' => 'primary',
                            'Completed' => 'success',
                            'On Hold' => 'warning',
                            'Cancelled' => 'danger'
                        ];
                        ?>
                        <span class="badge bg-<?php echo $statusClass[$row['status']] ?? 'secondary'; ?>">
                            <?php echo $row['status']; ?>
                        </span>
                    </td>
                    <td><?php echo number_format($row['initial_weight'], 2); ?></td>
                    <td><?php echo number_format($row['current_weight'], 2); ?></td>
                    <td><?php echo number_format($row['weight_loss_percent'], 2); ?>%</td>
                    <td><?php echo date('Y-m-d', strtotime($row['start_date'])); ?></td>
                    <td><?php echo $row['total_days']; ?></td>
                    <td><?php echo clean($row['source_supplier'] ?? 'N/A'); ?></td>
                    <td><?php echo number_format($row['raw_material_cost'], 2); ?></td>
                    <td><?php echo number_format($row['total_processing_cost'], 2); ?></td>
                    <td><?php echo number_format($row['total_cost'], 2); ?></td>
                    <td><?php echo number_format($row['selling_price'], 2); ?></td>
                    <td><?php echo number_format($row['profit'], 2); ?></td>
                    <td><?php echo number_format($row['profit_margin'], 2); ?>%</td>
                    <td><?php echo $row['completed_stages']; ?></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<footer class="mt-5 py-3 bg-light text-center">
    <p class="mb-0 text-muted">Wool Production MES &copy; <?php echo date('Y'); ?></p>
</footer>

<!-- jQuery (MUST load first) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables Core -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<!-- DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>

<!-- SearchBuilder -->
<script src="https://cdn.datatables.net/searchbuilder/1.6.0/js/dataTables.searchBuilder.min.js"></script>
<script src="https://cdn.datatables.net/searchbuilder/1.6.0/js/searchBuilder.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/datetime/1.5.1/js/dataTables.dateTime.min.js"></script>

<script>
$(document).ready(function() {
    console.log('Initializing DataTable...');
    
    var table = $('#masterReport').DataTable({
        dom: "<'row'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'Q>>" +
             "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        buttons: [
            {
                extend: 'copy',
                className: 'btn btn-secondary btn-sm',
                text: '<i class="bi bi-clipboard"></i> Copy'
            },
            {
                extend: 'excel',
                className: 'btn btn-success btn-sm',
                text: '<i class="bi bi-file-excel"></i> Excel',
                title: 'Wool_MES_Master_Report_' + new Date().toISOString().split('T')[0],
                exportOptions: { columns: ':visible' }
            },
            {
                extend: 'pdf',
                className: 'btn btn-danger btn-sm',
                text: '<i class="bi bi-file-pdf"></i> PDF',
                title: 'Wool MES Master Report',
                orientation: 'landscape',
                pageSize: 'A3',
                exportOptions: { columns: ':visible' },
                customize: function(doc) {
                    doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                    doc.styles.tableHeader.fillColor = '#343a40';
                    doc.styles.tableHeader.color = 'white';
                    doc.defaultStyle.fontSize = 8;
                }
            },
            {
                extend: 'print',
                className: 'btn btn-info btn-sm',
                text: '<i class="bi bi-printer"></i> Print',
                exportOptions: { columns: ':visible' }
            },
            {
                extend: 'colvis',
                className: 'btn btn-warning btn-sm',
                text: '<i class="bi bi-eye"></i> Columns'
            }
        ],
        searchBuilder: {
            depthLimit: 2
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[0, 'desc']],
        responsive: true,
        footerCallback: function() {
            var api = this.api();
            
            var totalRevenue = api.column(11, {page: 'current'}).data().reduce((a, b) => {
                return parseFloat(a) + parseFloat(b.replace(/,/g, '') || 0);
            }, 0);
            
            var totalProfit = api.column(12, {page: 'current'}).data().reduce((a, b) => {
                return parseFloat(a) + parseFloat(b.replace(/,/g, '') || 0);
            }, 0);
            
            var avgWeightLoss = api.column(4, {page: 'current'}).data().reduce((a, b, idx, arr) => {
                var val = parseFloat(b.replace('%', '') || 0);
                return idx === arr.length - 1 ? (parseFloat(a) + val) / arr.length : parseFloat(a) + val;
            }, 0);
            
            $('#totalBatches').text(api.rows({page: 'current'}).count());
            $('#totalRevenue').text('PKR ' + totalRevenue.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","));
            $('#totalProfit').text('PKR ' + totalProfit.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","));
            $('#avgWeightLoss').text(avgWeightLoss.toFixed(2) + '%');
        }
    });
    
    console.log('DataTable initialized successfully!');
});
</script>

</body>
</html>