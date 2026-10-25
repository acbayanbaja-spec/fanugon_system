<?php
require_once __DIR__ . '/includes/local_only.php';
// Reset every account's password to "admin123" (localhost only - delete before deploying)
require_once __DIR__ . '/config/config.php';

$database = new Database();
$pdo = $database->getConnection();
$correct_hash = password_hash("admin123", PASSWORD_DEFAULT);

// Update all users with the correct hash
$pdo->prepare("UPDATE users SET password = ?")->execute([$correct_hash]);

echo "Passwords updated successfully. Try logging in with admin/admin123";
echo "<br><a href='index.php'>Go to login</a>";
?>
