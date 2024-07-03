<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: admin_login.php");
    exit();
}
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $message = $_POST['message'] ?? '';

    if (!empty($message)) {
        $sql = "INSERT INTO messages (user_id, message_text) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $message);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_manage.php?table=messages");
        exit();
    } else {
        echo "Message cannot be empty. <a href='admin_manage.php?table=messages'>Try again</a>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Send Message</title>
    <link rel="stylesheet" href="../css/admin_manage.css">
</head>
<body>
    <div class="container">
        <h2>Send Message</h2>
        <form action="admin_send_message.php" method="POST">
            <label for="user_id">User ID:</label>
            <select id="user_id" name="user_id" required>
                <?php
                $users = fetchUsers($conn);
                while ($user = $users->fetch_assoc()): ?>
                    <option value="<?php echo $user['user_id']; ?>">
                        <?php echo $user['user_id'] . ' - ' . $user['username']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <label for="message">Message:</label>
            <textarea id="message" name="message" rows="4" required></textarea>
            <button type="submit">Send Message</button>
        </form>
        <a class="btn-back" href="admin_manage.php?table=messages">Back to Manage Messages</a>
    </div>
</body>
</html>
