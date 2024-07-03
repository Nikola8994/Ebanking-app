<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include '../db.php';

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM messages WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Messages</title>
    <link rel="stylesheet" href="../css/messages.css">
</head>
<body>
    <div class="container">
        <h2>Messages</h2>
        <table>
            <tr>
                <th>Message</th>
                <th>Date</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['message_text']; ?></td>
                <td><?php echo $row['created_at']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <a href="dashboard.php"  style="display: block; margin: 15px 0; padding: 15px 0; background-color: #dc3545; color: #fff; text-decoration: none; border-radius: 5px; text-align: center; transition: background-color 0.3s, border-color 0.3s; font-size: 1.2em;">Back to dashboard</a>
    </div>
</body>
</html>
