<?php
// Test login sederhana tanpa session
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    echo "<h3>Login Test Results:</h3>";
    echo "Email entered: " . htmlspecialchars($email) . "<br>";
    echo "Password entered: " . htmlspecialchars($password) . "<br>";
    echo "Password length: " . strlen($password) . "<br><br>";
    
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user) {
        echo "✅ User found in database<br>";
        echo "Username: " . htmlspecialchars($user['username']) . "<br>";
        echo "Role: " . $user['role'] . "<br>";
        echo "Status: " . $user['status'] . "<br>";
        echo "Password hash: " . $user['password'] . "<br><br>";
        
        if (password_verify($password, $user['password'])) {
            echo "✅ Password is correct<br>";
            
            if ($user['status'] == 'approved') {
                echo "✅ User is approved<br>";
                echo "<strong style='color: green; font-size: 18px;'>🎉 LOGIN SHOULD WORK!</strong><br><br>";
                
                if ($user['role'] == 'admin') {
                    echo "<a href='index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Dashboard</a>";
                } else {
                    echo "<a href='user_dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to User Dashboard</a>";
                }
            } else {
                echo "❌ User status is: " . $user['status'] . "<br>";
            }
        } else {
            echo "❌ Password is incorrect<br>";
            echo "Expected hash: " . $user['password'] . "<br>";
            echo "Verify result: " . (password_verify($password, $user['password']) ? "TRUE" : "FALSE") . "<br>";
        }
    } else {
        echo "❌ User not found with email: " . htmlspecialchars($email) . "<br>";
    }
    
    echo "<br><hr><a href='simple_login_test.php'>Try Again</a> | <a href='debug_login.php'>Debug System</a>";
} else {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Login Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; }
        input { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ddd; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; }
        .info { background: #f8f9fa; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0; }
    </style>
</head>
<body>
    <h2>🧪 Simple Login Test</h2>
    
    <div class="info">
        <strong>Test Credentials:</strong><br>
        Email: admin@example.com<br>
        Password: admin123
    </div>
    
    <form method="POST">
        <input type="email" name="email" placeholder="Email" value="admin@example.com" required>
        <input type="password" name="password" placeholder="Password" value="admin123" required>
        <button type="submit">Test Login</button>
    </form>
    
    <p><a href="debug_login.php">🔍 Debug System</a></p>
    <p><a href="reset_admin.php">🔧 Reset Password</a></p>
    <p><a href="login.php">🔑 Real Login Page</a></p>
</body>
</html>
<?php } ?>
