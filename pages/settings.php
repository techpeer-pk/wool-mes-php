<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// ADD THIS LINE after the requires:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
}

requireLogin();

$page_title = 'Settings';
$success = '';
$error = '';

// Get current user details
$query = "SELECT u.*, v.vendor_name 
          FROM users u 
          LEFT JOIN vendors v ON u.vendor_id = v.vendor_id 
          WHERE u.user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    $query = "UPDATE users SET full_name = ?, email = ? WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssi", $full_name, $email, $_SESSION['user_id']);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['full_name'] = $full_name;
        $success = "Profile updated successfully!";
        $user['full_name'] = $full_name;
        $user['email'] = $email;
    } else {
        $error = "Error updating profile";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters";
    } else {
        // Verify current password
        $query = "SELECT password_hash FROM users WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        
        if (password_verify($current_password, $result['password_hash'])) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password_hash = ? WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "si", $new_hash, $_SESSION['user_id']);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Password changed successfully!";
            } else {
                $error = "Error changing password";
            }
        } else {
            $error = "Current password is incorrect";
        }
    }
}

// Get system settings (admin only)
$system_settings = [];
if (hasRole('Admin')) {
    $settings_query = mysqli_query($conn, "SELECT * FROM system_settings ORDER BY setting_key");
    while ($setting = mysqli_fetch_assoc($settings_query)) {
        $system_settings[$setting['setting_key']] = $setting;
    }
}

// Handle system settings update (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_system']) && hasRole('Admin')) {
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $alert_delay_days = (int)$_POST['alert_delay_days'];
    
    $query = "UPDATE system_settings SET setting_value = ? WHERE setting_key = 'company_name'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $company_name);
    mysqli_stmt_execute($stmt);
    
    $query = "UPDATE system_settings SET setting_value = ? WHERE setting_key = 'alert_delay_days'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $alert_delay_days);
    mysqli_stmt_execute($stmt);
    
    $success = "System settings updated successfully!";
}

include '../includes/header.php';
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-gear"></i> Settings</h2>
    </div>
</div>

<?php if ($success): echo showSuccess($success); endif; ?>
<?php if ($error): echo showError($error); endif; ?>

<div class="row">
    <!-- User Profile -->
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person"></i> My Profile</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?php echo clean($user['username']); ?>" disabled>
                        <small class="text-muted">Username cannot be changed</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" 
                               value="<?php echo clean($user['full_name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo clean($user['email']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" 
                               value="<?php echo clean($user['role']); ?>" disabled>
                    </div>
                    
                    <?php if ($user['vendor_name']): ?>
                    <div class="mb-3">
                        <label class="form-label">Assigned Vendor</label>
                        <input type="text" class="form-control" 
                               value="<?php echo clean($user['vendor_name']); ?>" disabled>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Last Login</label>
                        <input type="text" class="form-control" 
                               value="<?php echo formatDateTime($user['last_login']); ?>" disabled>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Change Password -->
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lock"></i> Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" 
                               minlength="6" required>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" 
                               minlength="6" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-warning">
                        <i class="bi bi-key"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Account Info -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Account Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>User ID:</strong></td>
                        <td><?php echo $user['user_id']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Account Created:</strong></td>
                        <td><?php echo formatDateTime($user['created_at']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <?php if ($user['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- System Settings (Admin Only) -->
<?php if (hasRole('Admin')): ?>
<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-gear-fill"></i> System Settings (Admin Only)</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="company_name" class="form-control" 
                                       value="<?php echo clean($system_settings['company_name']['setting_value'] ?? 'Wool Production MES'); ?>">
                                <small class="text-muted">Displayed in system header</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Alert Delay (days)</label>
                                <input type="number" name="alert_delay_days" class="form-control" min="1"
                                       value="<?php echo $system_settings['alert_delay_days']['setting_value'] ?? 2; ?>">
                                <small class="text-muted">Days before triggering delay alerts</small>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="update_system" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Save System Settings
                    </button>
                </form>
                
                <hr>
                
                <h6>Database Information</h6>
                <table class="table table-sm">
                    <tr>
                        <td><strong>Total Batches:</strong></td>
                        <td><?php 
                            $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM batches"));
                            echo $count['c'];
                        ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Users:</strong></td>
                        <td><?php 
                            $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users"));
                            echo $count['c'];
                        ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Vendors:</strong></td>
                        <td><?php 
                            $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM vendors"));
                            echo $count['c'];
                        ?></td>
                    </tr>
                    <tr>
                        <td><strong>Active Stages:</strong></td>
                        <td><?php 
                            $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM production_stages WHERE is_active = 1"));
                            echo $count['c'];
                        ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>