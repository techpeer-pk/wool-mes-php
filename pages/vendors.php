<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// ADD THIS LINE after the requires:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
}

requireAdmin();

$page_title = 'Manage Vendors';
$success = '';
$error = '';

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $vendor_id = isset($_POST['vendor_id']) ? (int)$_POST['vendor_id'] : 0;
    $vendor_name = mysqli_real_escape_string($conn, $_POST['vendor_name']);
    $vendor_type = mysqli_real_escape_string($conn, $_POST['vendor_type']);
    $contact_person = mysqli_real_escape_string($conn, $_POST['contact_person']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $specialization = mysqli_real_escape_string($conn, $_POST['specialization']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($vendor_name)) {
        $error = 'Vendor name is required';
    } else {
        if ($_POST['action'] === 'add') {
            $query = "INSERT INTO vendors (vendor_name, vendor_type, contact_person, phone, email, address, specialization, is_active) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssssssi", $vendor_name, $vendor_type, $contact_person, $phone, $email, $address, $specialization, $is_active);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Vendor added successfully!";
            } else {
                $error = "Error adding vendor";
            }
        } else {
            $query = "UPDATE vendors SET vendor_name=?, vendor_type=?, contact_person=?, phone=?, email=?, address=?, specialization=?, is_active=? WHERE vendor_id=?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssssssii", $vendor_name, $vendor_type, $contact_person, $phone, $email, $address, $specialization, $is_active, $vendor_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Vendor updated successfully!";
            } else {
                $error = "Error updating vendor";
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $vendor_id = (int)$_GET['delete'];
    $query = "UPDATE vendors SET is_active = 0 WHERE vendor_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $vendor_id);
    if (mysqli_stmt_execute($stmt)) {
        $success = "Vendor deactivated successfully!";
    }
}

// Get all vendors
$vendors = mysqli_query($conn, "SELECT * FROM vendors ORDER BY vendor_name");

// Get vendor for editing
$edit_vendor = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $query = "SELECT * FROM vendors WHERE vendor_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $edit_vendor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

include '../includes/header.php';
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2><i class="bi bi-building"></i> Manage Vendors</h2>
    </div>
    <div class="col-md-6 text-end">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#vendorModal" onclick="clearForm()">
            <i class="bi bi-plus-circle"></i> Add Vendor
        </button>
    </div>
</div>

<?php if ($success): echo showSuccess($success); endif; ?>
<?php if ($error): echo showError($error); endif; ?>

<!-- Vendors Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Vendor Name</th>
                        <th>Type</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Specialization</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($vendor = mysqli_fetch_assoc($vendors)): ?>
                    <tr>
                        <td><strong><?php echo clean($vendor['vendor_name']); ?></strong></td>
                        <td>
                            <span class="badge bg-<?php echo $vendor['vendor_type'] === 'Internal' ? 'primary' : 'secondary'; ?>">
                                <?php echo $vendor['vendor_type']; ?>
                            </span>
                        </td>
                        <td><?php echo clean($vendor['contact_person']); ?></td>
                        <td><?php echo clean($vendor['phone']); ?></td>
                        <td><?php echo clean($vendor['email']); ?></td>
                        <td><?php echo clean($vendor['specialization']); ?></td>
                        <td>
                            <?php if ($vendor['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick='editVendor(<?php echo json_encode($vendor); ?>)'>
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php if ($vendor['is_active']): ?>
                            <a href="?delete=<?php echo $vendor['vendor_id']; ?>" 
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Deactivate this vendor?')">
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
<div class="modal fade" id="vendorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="vendor_id" id="vendor_id">
                    <input type="hidden" name="action" id="action" value="add">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Vendor Name <span class="text-danger">*</span></label>
                                <input type="text" name="vendor_name" id="vendor_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select name="vendor_type" id="vendor_type" class="form-select">
                                    <option value="Internal">Internal</option>
                                    <option value="External">External</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="contact_person" id="contact_person" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" id="phone" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Specialization</label>
                        <input type="text" name="specialization" id="specialization" class="form-control" 
                               placeholder="e.g., Washing, Dyeing, Spinning">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="address" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Save Vendor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function clearForm() {
    document.getElementById('modalTitle').textContent = 'Add Vendor';
    document.getElementById('vendor_id').value = '';
    document.getElementById('action').value = 'add';
    document.getElementById('vendor_name').value = '';
    document.getElementById('vendor_type').value = 'External';
    document.getElementById('contact_person').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('email').value = '';
    document.getElementById('specialization').value = '';
    document.getElementById('address').value = '';
    document.getElementById('is_active').checked = true;
}

function editVendor(vendor) {
    document.getElementById('modalTitle').textContent = 'Edit Vendor';
    document.getElementById('vendor_id').value = vendor.vendor_id;
    document.getElementById('action').value = 'edit';
    document.getElementById('vendor_name').value = vendor.vendor_name;
    document.getElementById('vendor_type').value = vendor.vendor_type;
    document.getElementById('contact_person').value = vendor.contact_person;
    document.getElementById('phone').value = vendor.phone;
    document.getElementById('email').value = vendor.email;
    document.getElementById('specialization').value = vendor.specialization;
    document.getElementById('address').value = vendor.address;
    document.getElementById('is_active').checked = vendor.is_active == 1;
    
    new bootstrap.Modal(document.getElementById('vendorModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>