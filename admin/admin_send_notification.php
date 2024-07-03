<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: admin_login.php");
    exit();
}
include '../db.php';

function fetchUsers($conn) {
    $sql = "SELECT user_id, email FROM users";
    return $conn->query($sql);
}

$users = fetchUsers($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $notification_text = $_POST['notification_text'];

    $sql = "INSERT INTO notifications (user_id, notification_text) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $notification_text);
    $stmt->execute();
    header("Location: admin_manage.php?table=notifications");
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Send Notification</title>
    <link rel="stylesheet" href="../css/admin_send_notification.css">
</head>
<body>
    <div class="container" style="background-color:#DCDCDC;" >
        <h2>Send Notification</h2>
        <form action="admin_send_notification.php" method="POST">
            <label for="user_id">User:</label>
            <select id="user_id" name="user_id" required>
                <?php while ($user = $users->fetch_assoc()): ?>
                    <option value="<?php echo $user['user_id']; ?>">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <label for="notification_text">Notification Text:</label>
            <textarea id="notification_text" name="notification_text" required></textarea>
            <button type="submit"  >Send Notification</button>
        </form>
        <a class="btn-back" href="admin_manage.php?table=notifications"  style="font-size:18px;"  >Back to Manage Notifications</a>
    </div>
</body>
</html>
