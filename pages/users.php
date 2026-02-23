<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// ADD THIS LINE after the requires:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
}

requireAdmin();

$page_title = 'Manage Users';
$success = '';
$error = '';

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $vendor_id = !empty($_POST['vendor_id']) ? (int)$_POST['vendor_id'] : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password = $_POST['password'];
    
    if (empty($username) || empty($full_name) || empty($role)) {
        $error = 'Username, full name, and role are required';
    } else {
        if ($_POST['action'] === 'add') {
            if (empty($password)) {
                $error = 'Password is required for new users';
            } else {
                // Check if username exists
                $check = "SELECT user_id FROM users WHERE username = ?";
                $stmt = mysqli_prepare($conn, $check);
                mysqli_stmt_bind_param($stmt, "s", $username);
                mysqli_stmt_execute($stmt);
                if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0) {
                    $error = 'Username already exists';
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $query = "INSERT INTO users (username, password_hash, full_name, email, role, vendor_id, is_active) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "sssssii", $username, $password_hash, $full_name, $email, $role, $vendor_id, $is_active);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $success = "User added successfully!";
                    } else {
                        $error = "Error adding user";
                    }
                }
            }
        } else {
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET username=?, password_hash=?, full_name=?, email=?, role=?, vendor_id=?, is_active=? WHERE user_id=?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssssssii", $username, $password_hash, $full_name, $email, $role, $vendor_id, $is_active, $user_id);
            } else {
                $query = "UPDATE users SET username=?, full_name=?, email=?, role=?, vendor_id=?, is_active=? WHERE user_id=?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "sssssii", $username, $full_name, $email, $role, $vendor_id, $is_active, $user_id);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "User updated successfully!";
            } else {
                $error = "Error updating user";
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    if ($user_id === $_SESSION['user_id']) {
        $error = "You cannot delete your own account!";
    } else {
        $query = "UPDATE users SET is_active = 0 WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $success = "User deactivated successfully!";
        }
    }
}

// Get all users
$query = "SELECT u.*, v.vendor_name 
          FROM users u 
          LEFT JOIN vendors v ON u.vendor_id = v.vendor_id 
          ORDER BY u.created_at DESC";
$users = mysqli_query($conn, $query);

// Get vendors for dropdown
$vendors = mysqli_query($conn, "SELECT vendor_id, vendor_name FROM vendors WHERE is_active = 1 ORDER BY vendor_name");

// Get user for editing
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $query = "SELECT * FROM users WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $edit_user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

include '../includes/header.php';
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2><i class="bi bi-people"></i> Manage Users</h2>
    </div>
    <div class="col-md-6 text-end">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="clearForm()">
            <i class="bi bi-person-plus"></i> Add User
        </button>
    </div>
</div>

<?php if ($success): echo showSuccess($success); endif; ?>
<?php if ($error): echo showError($error); endif; ?>

<!-- Users Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Vendor</th>
                        <th>Last Login</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td><strong><?php echo clean($user['username']); ?></strong></td>
                        <td><?php echo clean($user['full_name']); ?></td>
                        <td><?php echo clean($user['email']); ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $user['role'] === 'Admin' ? 'danger' : 
                                    ($user['role'] === 'Supervisor' ? 'primary' : 'secondary'); 
                            ?>">
                                <?php echo $user['role']; ?>
                            </span>
                        </td>
                        <td><?php echo clean($user['vendor_name']) ?: '-'; ?></td>
                        <td><?php echo $user['last_login'] ? formatDateTime($user['last_login']) : 'Never'; ?></td>
                        <td>
                            <?php if ($user['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick='editUser(<?php echo json_encode($user); ?>)'>
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php if ($user['user_id'] !== $_SESSION['user_id'] && $user['is_active']): ?>
                            <a href="?delete=<?php echo $user['user_id']; ?>" 
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Deactivate this user?')">
                                <i class="bi bi-x-circle"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="user_id">
                    <input type="hidden" name="action" id="action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="username" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="full_name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password <span id="pwd-required" class="text-danger">*</span></label>
                        <input type="password" name="password" id="password" class="form-control">
                        <small class="text-muted" id="pwd-hint">Leave blank to keep current password</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" id="role" class="form-select" required>
                            <option value="Viewer">Viewer</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Admin">Admin</option>
                            <option value="Vendor">Vendor</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="vendor-select">
                        <label class="form-label">Assign Vendor (Optional)</label>
                        <select name="vendor_id" id="vendor_id" class="form-select">
                            <option value="">None</option>
                            <?php mysqli_data_seek($vendors, 0); ?>
                            <?php while ($vendor = mysqli_fetch_assoc($vendors)): ?>
                            <option value="<?php echo $vendor['vendor_id']; ?>">
                                <?php echo clean($vendor['vendor_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Save User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function clearForm() {
    document.getElementById('modalTitle').textContent = 'Add User';
    document.getElementById('user_id').value = '';
    document.getElementById('action').value = 'add';
    document.getElementById('username').value = '';
    document.getElementById('full_name').value = '';
    document.getElementById('email').value = '';
    document.getElementById('password').value = '';
    document.getElementById('password').required = true;
    document.getElementById('pwd-required').style.display = 'inline';
    document.getElementById('pwd-hint').style.display = 'none';
    document.getElementById('role').value = 'Viewer';
    document.getElementById('vendor_id').value = '';
    document.getElementById('is_active').checked = true;
}

function editUser(user) {
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('user_id').value = user.user_id;
    document.getElementById('action').value = 'edit';
    document.getElementById('username').value = user.username;
    document.getElementById('full_name').value = user.full_name;
    document.getElementById('email').value = user.email;
    document.getElementById('password').value = '';
    document.getElementById('password').required = false;
    document.getElementById('pwd-required').style.display = 'none';
    document.getElementById('pwd-hint').style.display = 'block';
    document.getElementById('role').value = user.role;
    document.getElementById('vendor_id').value = user.vendor_id || '';
    document.getElementById('is_active').checked = user.is_active == 1;
    
    new bootstrap.Modal(document.getElementById('userModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>