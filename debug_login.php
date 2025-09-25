<?php
require_once 'db_connect.php';

echo "<h2>🔍 System Debug Information</h2>";

// 1. Cek koneksi database
echo "<h3>1. Database Connection:</h3>";
if ($conn->connect_error) {
    echo "❌ Database connection failed: " . $conn->connect_error . "<br>";
} else {
    echo "✅ Database connected successfully<br>";
}

// 2. Cek session
echo "<h3>2. Session Status:</h3>";
echo "Session Status: " . session_status() . " (1=disabled, 2=active)<br>";
echo "Session ID: " . session_id() . "<br>";
if (isset($_SESSION)) {
    echo "Session Data: <pre>" . print_r($_SESSION, true) . "</pre>";
} else {
    echo "❌ No session data<br>";
}

// 3. Cek tabel users
echo "<h3>3. Users Table:</h3>";
$users_result = $conn->query("SELECT id, username, email, role, status, created_at FROM users");
if ($users_result) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th></tr>";
    while ($user = $users_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "<td>" . $user['status'] . "</td>";
        echo "<td>" . $user['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Error querying users table: " . $conn->error . "<br>";
}

// 4. Test password hash
echo "<h3>4. Password Hash Test:</h3>";
$test_password = "admin123";
$admin_result = $conn->query("SELECT password FROM users WHERE email = 'admin@example.com'");
if ($admin_result && $admin_result->num_rows > 0) {
    $admin = $admin_result->fetch_assoc();
    echo "Password to test: " . $test_password . "<br>";
    echo "Hash in database: " . $admin['password'] . "<br>";
    echo "Password verify result: " . (password_verify($test_password, $admin['password']) ? "✅ TRUE" : "❌ FALSE") . "<br>";
} else {
    echo "❌ Admin user not found<br>";
}

// 5. Cek tabel projects
echo "<h3>5. Projects Table:</h3>";
$projects_result = $conn->query("SELECT COUNT(*) as count FROM projects");
if ($projects_result) {
    $count = $projects_result->fetch_assoc()['count'];
    echo "Total projects: " . $count . "<br>";
} else {
    echo "❌ Error querying projects table: " . $conn->error . "<br>";
}

echo "<br><hr>";
echo "<a href='simple_login_test.php'>🧪 Simple Login Test</a> | ";
echo "<a href='reset_admin.php'>🔧 Reset Admin Password</a> | ";
echo "<a href='login.php'>🔑 Go to Login</a>";
?>
