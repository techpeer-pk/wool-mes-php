<?php
require_once 'config/database.php';

echo "<h2>Password Hash Generator & Tester</h2>";
echo "<hr>";

// Generate correct hashes
$admin_password = 'admin123';
$supervisor_password = 'super123';

$admin_hash = password_hash($admin_password, PASSWORD_DEFAULT);
$supervisor_hash = password_hash($supervisor_password, PASSWORD_DEFAULT);

echo "<h3>Generated Hashes:</h3>";
echo "<strong>Admin (admin123):</strong><br>";
echo "<code>$admin_hash</code><br><br>";

echo "<strong>Supervisor (super123):</strong><br>";
echo "<code>$supervisor_hash</code><br><br>";

echo "<hr>";
echo "<h3>Update Database with Correct Hashes:</h3>";

// Update admin password
$query1 = "UPDATE users SET password_hash = ? WHERE username = 'admin'";
$stmt1 = mysqli_prepare($conn, $query1);
mysqli_stmt_bind_param($stmt1, "s", $admin_hash);
if (mysqli_stmt_execute($stmt1)) {
    echo "✅ Admin password updated successfully!<br>";
} else {
    echo "❌ Error updating admin password<br>";
}

// Update supervisor password
$query2 = "UPDATE users SET password_hash = ? WHERE username = 'supervisor'";
$stmt2 = mysqli_prepare($conn, $query2);
mysqli_stmt_bind_param($stmt2, "s", $supervisor_hash);
if (mysqli_stmt_execute($stmt2)) {
    echo "✅ Supervisor password updated successfully!<br>";
} else {
    echo "❌ Error updating supervisor password<br>";
}

echo "<hr>";
echo "<h3>Test Login:</h3>";

// Test admin login
$test_user = 'admin';
$test_pass = 'admin123';

$query = "SELECT * FROM users WHERE username = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $test_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
    echo "<strong>Testing admin login:</strong><br>";
    echo "Username: $test_user<br>";
    echo "Password: $test_pass<br>";
    
    if (password_verify($test_pass, $user['password_hash'])) {
        echo "✅ <span style='color: green;'>Password verification SUCCESSFUL!</span><br>";
    } else {
        echo "❌ <span style='color: red;'>Password verification FAILED!</span><br>";
    }
} else {
    echo "❌ User not found<br>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "1. Run this file once: <code>http://localhost/wool-mes/test-password.php</code><br>";
echo "2. Then try logging in again at: <code>http://localhost/wool-mes/auth/login.php</code><br>";
echo "3. Delete this test file after successful login for security<br>";
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
code { background: #f4f4f4; padding: 5px 10px; border-radius: 3px; display: inline-block; }
</style>