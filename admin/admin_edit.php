<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: admin_login.php");
    exit();
}
include '../db.php';

$table = $_GET['table'];
$id = $_GET['id'];
$valid_tables = ['users', 'accounts', 'transactions', 'bills', 'messages', 'notifications'];

if (!in_array($table, $valid_tables)) {
    echo "Invalid table.";
    exit();
}

function fetchRow($conn, $table, $id) {
    $id_column = '';
    if ($table == 'users') {
        $id_column = 'user_id';
    } elseif ($table == 'accounts') {
        $id_column = 'account_id';
    } elseif ($table == 'transactions') {
        $id_column = 'transaction_id';
    } elseif ($table == 'bills') {
        $id_column = 'bill_id';
    } elseif ($table == 'messages') {
        $id_column = 'message_id';
    } elseif ($table == 'notifications') {
        $id_column = 'notification_id';
    }

    $sql = "SELECT * FROM $table WHERE $id_column = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($table == 'users') {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $role = $_POST['role'];

        $sql = "UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $username, $email, $password, $role, $id);
    } elseif ($table == 'accounts') {
        $user_id = $_POST['user_id'];
        $account_type = $_POST['account_type'];
        $balance = $_POST['balance'];

        $sql = "UPDATE accounts SET user_id = ?, account_type = ?, balance = ? WHERE account_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isdi", $user_id, $account_type, $balance, $id);
    } elseif ($table == 'transactions') {
        $account_id = $_POST['account_id'];
        $amount = $_POST['amount'];
        $transaction_type = $_POST['transaction_type'];
        $description = $_POST['description'];

        $sql = "UPDATE transactions SET account_id = ?, amount = ?, transaction_type = ?, description = ? WHERE transaction_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("idssi", $account_id, $amount, $transaction_type, $description, $id);
    } elseif ($table == 'bills') {
        $user_id = $_POST['user_id'];
        $bill_type = $_POST['bill_type'];
        $amount = $_POST['amount'];
        $due_date = $_POST['due_date'];
        $status = $_POST['status'];

        $sql = "UPDATE bills SET user_id = ?, bill_type = ?, amount = ?, due_date = ?, status = ? WHERE bill_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isissi", $user_id, $bill_type, $amount, $due_date, $status, $id);
    } elseif ($table == 'messages') {
        $user_id = $_POST['user_id'];
        $message_text = $_POST['message_text'];

        $sql = "UPDATE messages SET user_id = ?, message_text = ? WHERE message_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $user_id, $message_text, $id);
    } elseif ($table == 'notifications') {
        $user_id = $_POST['user_id'];
        $notification_text = $_POST['notification_text'];

        $sql = "UPDATE notifications SET user_id = ?, notification_text = ? WHERE notification_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $user_id, $notification_text, $id);
    }
    $stmt->execute();
    header("Location: admin_manage.php?table=$table");
    exit();
}

$row = fetchRow($conn, $table, $id);

function fetchUsers($conn) {
    $sql = "SELECT user_id, email FROM users";
    return $conn->query($sql);
}

function fetchAccounts($conn) {
    $sql = "SELECT account_id, account_type FROM accounts";
    return $conn->query($sql);
}

$users = fetchUsers($conn);
$accounts = fetchAccounts($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit <?php echo ucfirst($table); ?></title>
    <link rel="stylesheet" href="../css/admin_edit.css">
</head>
<body>
    <div class="container">
        <h2>Edit <?php echo ucfirst($table); ?></h2>
        <form action="admin_edit.php?table=<?php echo $table; ?>&id=<?php echo $id; ?>" method="POST">
            <?php if ($table == 'users'): ?>
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($row['username']); ?>" required>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" value="<?php echo htmlspecialchars($row['password']); ?>" required>
                <label for="role">Role:</label>
                <select id="role" name="role" required>
                    <option value="user" <?php echo ($row['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                    <option value="admin" <?php echo ($row['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
            <?php elseif ($table == 'accounts'): ?>
                <label for="user_id">User:</label>
                <select id="user_id" name="user_id" required>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <option value="<?php echo $user['user_id']; ?>" <?php echo ($user['user_id'] == $row['user_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['email']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <label for="account_type">Account Type:</label>
                <input type="text" id="account_type" name="account_type" value="<?php echo htmlspecialchars($row['account_type']); ?>" required>
                <label for="balance">Balance:</label>
                <input type="number" id="balance" name="balance" value="<?php echo htmlspecialchars($row['balance']); ?>" min="0" required>
            <?php elseif ($table == 'transactions'): ?>
                <label for="account_id">Account:</label>
                <select id="account_id" name="account_id" required>
                    <?php while ($account = $accounts->fetch_assoc()): ?>
                        <option value="<?php echo $account['account_id']; ?>" <?php echo ($account['account_id'] == $row['account_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($account['account_type']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <label for="amount">Amount:</label>
                <input type="number" id="amount" name="amount" value="<?php echo htmlspecialchars($row['amount']); ?>" required>
                <label for="transaction_type">Transaction Type:</label>
                <input type="text" id="transaction_type" name="transaction_type" value="<?php echo htmlspecialchars($row['transaction_type']); ?>" required>
                <label for="description">Description:</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($row['description']); ?></textarea>
            <?php elseif ($table == 'bills'): ?>
                <label for="user_id">User:</label>
                <select id="user_id" name="user_id" required>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <option value="<?php echo $user['user_id']; ?>" <?php echo ($user['user_id'] == $row['user_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['email']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <label for="bill_type">Bill Type:</label>
                <input type="text" id="bill_type" name="bill_type" value="<?php echo htmlspecialchars($row['bill_type']); ?>" required>
                <label for="amount">Amount:</label>
                <input type="number" id="amount" name="amount" value="<?php echo htmlspecialchars($row['amount']); ?>" min="1" required>
                <label for="due_date">Due Date:</label>
                <input type="date" id="due_date" name="due_date" value="<?php echo htmlspecialchars($row['due_date']); ?>" required>
                <label for="status">Status:</label>
                <select id="status" name="status" required>
                    <option value="unpaid" <?php echo ($row['status'] == 'unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                    <option value="paid" <?php echo ($row['status'] == 'paid') ? 'selected' : ''; ?>>Paid</option>
                </select>
            <?php elseif ($table == 'messages'): ?>
                <label for="user_id">User:</label>
                <select id="user_id" name="user_id" required>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <option value="<?php echo $user['user_id']; ?>" <?php echo ($user['user_id'] == $row['user_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['email']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <label for="message_text">Message:</label>
                <textarea id="message_text" name="message_text" rows="4" required><?php echo htmlspecialchars($row['message_text']); ?></textarea>
            <?php elseif ($table == 'notifications'): ?>
                <label for="user_id">User:</label>
                <select id="user_id" name="user_id" required>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <option value="<?php echo $user['user_id']; ?>" <?php echo ($user['user_id'] == $row['user_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['email']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <label for="notification_text">Notification:</label>
                <textarea id="notification_text" name="notification_text" rows="4" required><?php echo htmlspecialchars($row['notification_text']); ?></textarea>
            <?php endif; ?>
            <button type="submit"  style="  padding: 10px 20px;
    background-color: #009879;
    color: #fff;
    border: 1px solid #009879;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease, border-color 0.3s ease;
    margin: 10px; width:600px; font-size:20px "  >Update</button>
        </form>
        <a class="btn-back" href="admin_manage.php?table=<?php echo $table; ?>">Back to Manage <?php echo ucfirst($table); ?></a>
    </div>
</body>
</html>
