<?php
/**
 * Set Selling Price for Completed Batches
 * Save as: pages/set-price.php
 */
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

if (!hasRole('Admin') && !hasRole('Supervisor')) {
    header('Location: dashboard.php');
    exit();
}

$batch_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = '';
$error = '';

// Get batch details
$query = "SELECT * FROM batches WHERE batch_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $batch_id);
mysqli_stmt_execute($stmt);
$batch = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$batch) {
    header('Location: batches.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selling_price = (float)$_POST['selling_price'];
    
    if ($selling_price < 0) {
        $error = 'Invalid selling price';
    } else {
        // Calculate profit
        $profit = $selling_price - $batch['total_cost'];
        
        // Update batch
        $query = "UPDATE batches SET selling_price = ?, profit = ? WHERE batch_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ddi", $selling_price, $profit, $batch_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Selling price updated successfully!";
            
            // Refresh batch data
            $query = "SELECT * FROM batches WHERE batch_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $batch_id);
            mysqli_stmt_execute($stmt);
            $batch = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        } else {
            $error = 'Error updating selling price';
        }
    }
}

$page_title = 'Set Selling Price';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-6 offset-md-3">
        
        <a href="batch-details.php?id=<?php echo $batch_id; ?>" class="btn btn-sm btn-outline-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Back to Batch Details
        </a>
        
        <div class="card">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">
                    <i class="bi bi-currency-exchange"></i> Set Selling Price
                </h4>
            </div>
            <div class="card-body">
                
                <?php if ($success): echo showSuccess($success); endif; ?>
                <?php if ($error): echo showError($error); endif; ?>
                
                <!-- Batch Info -->
                <div class="mb-4">
                    <h5><?php echo clean($batch['batch_number']); ?></h5>
                    <p class="text-muted mb-1">
                        Status: <?php echo getStatusBadge($batch['status']); ?>
                    </p>
                </div>
                
                <!-- Cost Summary -->
                <div class="card bg-light mb-4">
                    <div class="card-body">
                        <h6 class="card-title">Cost Breakdown</h6>
                        <table class="table table-sm mb-0">
                            <tr>
                                <td>Raw Material Cost:</td>
                                <td class="text-end">
                                    <strong><?php echo formatCurrency($batch['raw_material_cost']); ?></strong>
                                </td>
                            </tr>
                            <tr>
                                <td>Processing Cost:</td>
                                <td class="text-end">
                                    <strong><?php echo formatCurrency($batch['total_processing_cost']); ?></strong>
                                </td>
                            </tr>
                            <tr class="table-active">
                                <td><strong>Total Cost:</strong></td>
                                <td class="text-end">
                                    <strong class="text-danger">
                                        <?php echo formatCurrency($batch['total_cost']); ?>
                                    </strong>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Selling Price Form -->
                <form method="POST" action="">
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-tag"></i> Selling Price (PKR) 
                            <span class="text-danger">*</span>
                        </label>
                        <input type="number" name="selling_price" class="form-control form-control-lg" 
                               step="0.01" min="0"
                               value="<?php echo $batch['selling_price'] > 0 ? $batch['selling_price'] : ''; ?>" 
                               placeholder="Enter selling price..."
                               required>
                        <small class="text-muted">
                            Recommended minimum: <?php echo formatCurrency($batch['total_cost'] * 1.2); ?> 
                            (20% markup)
                        </small>
                    </div>
                    
                    <?php if ($batch['selling_price'] > 0): ?>
                    <!-- Current Profit Display -->
                    <div class="alert alert-<?php echo $batch['profit'] >= 0 ? 'success' : 'danger'; ?>">
                        <strong>Current Profit/Loss:</strong><br>
                        <h3 class="mb-0">
                            <?php echo formatCurrency($batch['profit']); ?>
                        </h3>
                        <?php
                        $margin = $batch['selling_price'] > 0 
                            ? ($batch['profit'] / $batch['selling_price'] * 100) 
                            : 0;
                        ?>
                        <small>Margin: <?php echo number_format($margin, 2); ?>%</small>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-circle"></i> 
                            <?php echo $batch['selling_price'] > 0 ? 'Update' : 'Set'; ?> Selling Price
                        </button>
                    </div>
                    
                </form>
                
                <hr class="my-4">
                
                <!-- Quick Calculations -->
                <div class="small text-muted">
                    <strong>Quick Reference:</strong>
                    <ul class="mb-0">
                        <li>10% profit: <?php echo formatCurrency($batch['total_cost'] * 1.1); ?></li>
                        <li>20% profit: <?php echo formatCurrency($batch['total_cost'] * 1.2); ?></li>
                        <li>30% profit: <?php echo formatCurrency($batch['total_cost'] * 1.3); ?></li>
                    </ul>
                </div>
                
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
