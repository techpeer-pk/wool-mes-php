<?php
if (!isset($page_title)) $page_title = 'Wool MES';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Wool Production MES</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Bootstrap 5 CSS -->
    <!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet"> -->
    <!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css" rel="stylesheet"> -->
    
    <!-- DataTables Bootstrap 5 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables.net-bs5/1.13.6/dataTables.bootstrap5.min.css" rel="stylesheet">

</head>
<style>
    .zoom-card {
    transition: transform 0.3s ease; /* Smooth transition over 0.3 seconds */
    }

    .zoom-card:hover {
    transform: scale(1.05); /* Zoom in by 5% */
    }

</style>
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