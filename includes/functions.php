<?php
// Add this line at the very top (after <?php)
require_once __DIR__ . '/csrf.php';

// Security: Prevent XSS attacks
function clean($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../auth/login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!hasRole('Admin')) {
        $_SESSION['error'] = 'Access denied. Admin privileges required.';
        header('Location: ../pages/dashboard.php');
        exit();
    }
}

// Redirect if not admin or supervisor
function requireAdminOrSupervisor() {
    requireLogin();
    if (!hasRole('Admin') && !hasRole('Supervisor')) {
        $_SESSION['error'] = 'Access denied. Supervisor or Admin privileges required.';
        header('Location: ../pages/dashboard.php');
        exit();
    }
}

// Check if user can edit batches
function canEditBatches() {
    return hasRole('Admin') || hasRole('Supervisor');
}

// Check if user can view only
function isViewer() {
    return hasRole('Viewer');
}

// Format date
function formatDate($date) {
    if (empty($date)) return '-';
    return date('d M Y', strtotime($date));
}

// Format datetime
function formatDateTime($datetime) {
    if (empty($datetime)) return '-';
    return date('d M Y H:i', strtotime($datetime));
}

// Format weight
function formatWeight($weight) {
    return number_format($weight, 2) . ' kg';
}

// Format Amount
function formatCurrency($currency) {
    return number_format($currency, 2) . ' PKR';
}

// Calculate weight loss percentage
function calculateWeightLoss($weight_in, $weight_out) {
    if ($weight_in == 0) return 0;
    return round((($weight_in - $weight_out) / $weight_in) * 100, 2);
}

// Get status badge color
function getStatusBadge($status) {
    $badges = [
        'In Progress' => 'primary',
        'Completed' => 'success',
        'On Hold' => 'warning',
        'Cancelled' => 'danger',
        'Pending' => 'secondary',
        'Failed' => 'danger'
    ];
    $color = $badges[$status] ?? 'secondary';
    return "<span class='badge bg-{$color}'>{$status}</span>";
}

// Generate batch number
function generateBatchNumber($conn) {
    $year = date('Y');
    $query = "SELECT batch_number FROM batches WHERE batch_number LIKE 'WB-{$year}-%' ORDER BY batch_id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $last_number = (int)substr($row['batch_number'], -3);
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    
    return 'WB-' . $year . '-' . str_pad($new_number, 3, '0', STR_PAD_LEFT);
}

// Success message
function showSuccess($message) {
    return "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

// Error message
function showError($message) {
    return "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

// Get days difference
function getDaysDiff($date1, $date2 = null) {
    $date2 = $date2 ?? date('Y-m-d');
    $diff = strtotime($date2) - strtotime($date1);
    return floor($diff / (60 * 60 * 24));
}

// Add this function to your functions.php
function validateNumber($value, $min = 0, $max = 999999999) {
    if (!is_numeric($value)) {
        return false;
    }
    $num = (float)$value;
    if ($num < $min || $num > $max) {
        return false;
    }
    return $num;
}

?>