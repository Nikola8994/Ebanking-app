<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include '../db.php';

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Notifications</title>
    <link rel="stylesheet" href="../css/notifications.css">
</head>
<body>
    <div class="container">
        <h2>Notifications</h2>
        <table>
            <tr>
                <th>Notification</th>
                <th>Date</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['notification_text']; ?></td>
                <td><?php echo $row['created_at']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <a href="dashboard.php"  style="display: block; margin: 15px 0; padding: 15px 0; background-color: #dc3545; color: #fff; text-decoration: none; border-radius: 5px; text-align: center; transition: background-color 0.3s, border-color 0.3s; font-size: 1.2em;" >Back to dashboard</a>
    </div>
</body>
</html>
