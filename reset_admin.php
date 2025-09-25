<?php
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'];
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password admin
    $sql = "UPDATE users SET password = ? WHERE email = 'admin@example.com'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $password_hash);
    
    if ($stmt->execute()) {
        echo "<div style='color: green; font-weight: bold;'>✅ Password admin berhasil diubah!</div>";
        echo "<p>Email: admin@example.com</p>";
        echo "<p>Password baru: " . htmlspecialchars($new_password) . "</p>";
        echo "<p>Hash: " . $password_hash . "</p>";
        echo "<br><a href='login.php'>🔑 Login Sekarang</a>";
    } else {
        echo "<div style='color: red;'>❌ Gagal mengubah password: " . $conn->error . "</div>";
    }
} else {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Admin Password</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
        input { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ddd; }
        button { width: 100%; padding: 10px; background: #dc3545; color: white; border: none; }
        .info { background: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 10px 0; }
    </style>
</head>
<body>
    <h2>🔧 Reset Admin Password</h2>
    
    <div class="info">
        <strong>Current Admin:</strong><br>
        Email: admin@example.com<br>
        Default Password: admin123
    </div>
    
    <form method="POST">
        <label>Password Baru:</label>
        <input type="text" name="new_password" value="admin123" required>
        <button type="submit">Reset Password</button>
    </form>
    
    <p><a href="debug_login.php">🔍 Debug System</a></p>
    <p><a href="simple_login_test.php">🧪 Test Login</a></p>
</body>
</html>
<?php } ?>
