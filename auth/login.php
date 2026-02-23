<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$error = '';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../pages/dashboard.php');
    exit();
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        $query = "SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user['password_hash'])) {
                // Set session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                // Update last login
                $update = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
                $stmt2 = mysqli_prepare($conn, $update);
                mysqli_stmt_bind_param($stmt2, "i", $user['user_id']);
                mysqli_stmt_execute($stmt2);
                
                header('Location: ../pages/dashboard.php');
                exit();
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'User not found';
        }
    }
}

$page_title = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#DBEAFE" />
    <meta property="og:image" content="https://mes.techpeer.online/favicon_io/android-chrome-192x192.png" />
    <title>Login - Wool Production MES</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="apple-touch-icon" sizes="180x180" href="/favicon_io/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon_io/favicon-16x16.png">
    <link rel="manifest" href="/favicon_io/site.webmanifest">



</head>
<body class="bg-light">

<div class="container">
    <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-box-seam fs-1 text-primary"></i>
                        <h3 class="mt-3">Wool Production MES (Demo)</h3>
                        <p class="text-muted">Login to continue</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo clean($error); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required autofocus>
                        </div>
                        
                        <!-- <div class="mb-4">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div> -->
                        
                        <div class="input-group mb-3">
                            <input type="password" name="password" class="form-control" id="passwordInput" placeholder="Password">
                            <span class="input-group-text" id="togglePassword">
                                <i class="bi bi-eye-slash" id="eyeIcon"></i>
                            </span>
                        </div>


                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <small class="text-muted">
                            <strong>Demo Accounts:</strong><br>
                            Admin: admin / admin123<br>
                            Supervisor: supervisor / super123
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('passwordInput');
    const eyeIcon = document.getElementById('eyeIcon');

    togglePassword.addEventListener('click', function () {
        // Toggle the type attribute between 'password' and 'text'
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        // Toggle the eye icon (slash or open eye)
        eyeIcon.classList.toggle('bi-eye-slash');
        eyeIcon.classList.toggle('bi-eye');
    });
</script>

<?php include '../includes/footer.php'; ?>

</body>
</html>