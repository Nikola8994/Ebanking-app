<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: admin_login.php");
    exit();
}
include '../db.php';

$tables = ['users', 'accounts', 'transactions', 'bills', 'messages', 'notifications'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/admin_dashboard.css">
</head>
<body>
    <div class="container">
        <h2>Admin Dashboard</h2>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        <nav>
            <ul>
                <?php foreach ($tables as $table): ?>
                    <li><a href="admin_manage.php?table=<?php echo $table; ?>"><?php echo ucfirst($table); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <a class="btn-logout" href="logout.php">Logout</a>
    </div>
</body>
</html> 




