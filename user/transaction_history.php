<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include '../db.php';

$user_id = $_SESSION['user_id'];
$sql = "SELECT t.transaction_id, t.amount, t.transaction_type, t.description, t.created_at, a.account_type 
        FROM transactions t 
        JOIN accounts a ON t.account_id = a.account_id 
        WHERE a.user_id = ? 
        ORDER BY t.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Transaction History</title>
    <link rel="stylesheet" href="../css/transaction_history.css">
</head>
<body>
    <div class="container">
        <h2>Transaction History</h2>
        <table>
            <tr>
                <th>Transaction ID</th>
                <th>Account Type</th>
                <th>Amount</th>
                <th>Type</th>
                <th>Description</th>
                <th>Date</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['transaction_id']; ?></td>
                <td><?php echo $row['account_type']; ?></td>
                <td><?php echo $row['amount']; ?></td>
                <td><?php echo $row['transaction_type']; ?></td>
                <td><?php echo $row['description']; ?></td>
                <td><?php echo $row['created_at']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <a href="dashboard.php"  style="display: block; margin: 15px 0; padding: 15px 0; background-color: #dc3545; color: #fff; text-decoration: none; border-radius: 5px; text-align: center; transition: background-color 0.3s, border-color 0.3s; font-size: 1.2em;">Back to dashboard</a>
    </div>
</body>
</html>
