<?php
// Script untuk test password hash
$password = "admin123";
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: " . $password . "<br>";
echo "Hash: " . $hash . "<br>";
echo "Verify: " . (password_verify($password, $hash) ? "TRUE" : "FALSE") . "<br>";

// Test hash yang ada di database
$db_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
echo "<br>Testing database hash:<br>";
echo "Hash dari DB: " . $db_hash . "<br>";
echo "Verify dengan 'admin123': " . (password_verify("admin123", $db_hash) ? "TRUE" : "FALSE") . "<br>";
echo "Verify dengan 'secret': " . (password_verify("secret", $db_hash) ? "TRUE" : "FALSE") . "<br>";

// Generate hash baru untuk admin123
$new_hash = '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm';
echo "<br>Testing new hash:<br>";
echo "New Hash: " . $new_hash . "<br>";
echo "Verify dengan 'admin123': " . (password_verify("admin123", $new_hash) ? "TRUE" : "FALSE") . "<br>";
?>
