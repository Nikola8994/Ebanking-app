<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include '../db.php';

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM accounts WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <div class="container">
        <h2>Welcome, <?php echo $_SESSION['username']; ?></h2>
        <h3 style="color:black;">Account Overview</h3>
        <table>
            <tr>
                <th>Account Type</th>
                <th>Balance</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['account_type']; ?></td>
                <td><?php echo $row['balance']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <nav>
            <ul>
                <li><a href="transfer.php">Transfer Money</a></li>
                <li><a href="pay_bill.php">Pay Bill</a></li>
                <li><a href="transaction_history.php">Transaction History</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="notifications.php">Notifications</a></li>
            </ul>
        </nav>
        <a class="btn-logout" href="logout.php">Logout</a>
    </div>
</body>
</html>
