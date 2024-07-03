<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: admin_login.php");
    exit();
}
include '../db.php';

$table = $_GET['table'];
$valid_tables = ['users', 'accounts', 'transactions', 'bills', 'messages', 'notifications'];

if (!in_array($table, $valid_tables)) {
    echo "Invalid table.";
    exit();
}

$search_email = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $search_email = $_GET['search'];
}
function fetchTableData($conn, $table, $search_email) {
    if ($table == 'accounts') {
        $sql = "SELECT accounts.account_id, accounts.user_id, users.email, accounts.account_type, accounts.balance FROM accounts INNER JOIN users ON accounts.user_id = users.user_id";
    } elseif ($table == 'transactions' && $search_email) {
        $stmt = $conn->prepare("
            SELECT t.*, u.email 
            FROM transactions t 
            JOIN accounts a ON t.account_id = a.account_id 
            JOIN users u ON a.user_id = u.user_id 
            WHERE u.email LIKE ? 
            ORDER BY t.created_at DESC 
            LIMIT 5
        ");
        $search_param = "%$search_email%";
        $stmt->bind_param("s", $search_param);
        $stmt->execute();
        return $stmt->get_result();
    } elseif ($table == 'transactions') {
        $sql = "
            SELECT t.*, u.email 
            FROM transactions t 
            JOIN accounts a ON t.account_id = a.account_id 
            JOIN users u ON a.user_id = u.user_id 
            ORDER BY t.created_at DESC
        ";
        return $conn->query($sql);
    } else {
        $sql = "SELECT * FROM $table";
    }
    return $conn->query($sql);
}
$data = fetchTableData($conn, $table, $search_email);

function fetchUsers($conn) {
    $sql = "SELECT user_id, username, email FROM users WHERE user_id > 0";
    return $conn->query($sql);
}

$users = fetchUsers($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $table === 'transactions') {
    $sender_email = $_POST['sender_email'];
    $recipient_email = $_POST['recipient_email'];
    $transaction_type = $_POST['transaction_type'];
    $amount = $_POST['amount'];

    // Assuming you have a method to get account_id from email
    $sender_account_id = getAccountIdFromEmail($conn, $sender_email);
    $recipient_account_id = getAccountIdFromEmail($conn, $recipient_email);

    // Insert transaction
    $stmt = $conn->prepare("INSERT INTO transactions (account_id, amount, transaction_type, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $sender_account_id, $amount, $transaction_type, $description);
    $description = 'Transfer to ' . $recipient_email;
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO transactions (account_id, amount, transaction_type, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $recipient_account_id, $amount, $transaction_type, $description);
    $description = 'Transfer from ' . $sender_email;
    $stmt->execute();
    $stmt->close();

    // Redirect to avoid form resubmission
    header("Location: admin_manage.php?table=transactions");
    exit();
}

function getAccountIdFromEmail($conn, $email) {
    $stmt = $conn->prepare("SELECT a.account_id FROM accounts a JOIN users u ON a.user_id = u.user_id WHERE u.email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['account_id'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage <?php echo ucfirst($table); ?></title>
    <link rel="stylesheet" href="../css/admin_manage.css">
</head>
<body>
    <div >
        <h2  style="margin-left:540px;"  >Manage  <?php echo ucfirst($table); ?></h2>
        <?php if ($table === 'transactions'): ?>
            <form method="GET" action="admin_manage.php">
                <input type="hidden" name="table" value="transactions">
                <input type="text" name="search" placeholder="Search last 5 transactions by email" value="<?php echo htmlspecialchars($search_email); ?>">
                <button type="submit">Search</button>
            </form>
            <form action="admin_manage.php?table=transactions" method="POST">
                <label for="sender_email" style="margin-top:10px;" >Sender Email:</label>
                <input type="email" id="sender_email" name="sender_email" required>
                <label for="recipient_email"  style="margin-top:10px;"  >Recipient Email:</label>
                <input type="email" id="recipient_email" name="recipient_email" required>
                <label for="transaction_type"  style="margin-top:10px;"   >Transaction Type:</label>
                <select id="transaction_type" name="transaction_type" required>
                    <option value="debit">Debit</option>
                    <option value="credit">Credit</option>
                </select>
                <label for="amount"  style="margin-top:20px;"  >Amount:</label>
                <input type="number" id="amount" name="amount" min="1" required>
                <button type="submit">Transfer Money</button>
            </form>
        <?php endif; ?>
        <?php if ($table === 'bills'): ?>
            <form action="admin_send_bill.php" method="POST">
                <label for="user_id"  style="margin-top:10px;"  >User ID:</label>
                <select id="user_id" name="user_id" required>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <option value="<?php echo $user['user_id']; ?>">
                            <?php echo $user['user_id'] . ' - ' . $user['username']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <label for="bill_type" style="margin-top:10px;"  >Bill Type:</label>
                <input type="text" id="bill_type" name="bill_type" required>
                <label for="amount"  style="margin-top:20px;"  >Amount:</label>
                <input type="number" id="amount" name="amount" min="1" required>
                <label for="due_date"  style="margin-top:10px;"  >Due Date:</label>
                <input type="date" id="due_date" name="due_date" required>
                <button type="submit">Send Bill</button>
            </form>
        <?php endif; ?>
        <?php if ($table === 'messages'): ?>
            <form action="admin_send_message.php" method="POST">
                <label for="user_id"  style="margin-top:20px;"   >User ID:</label>
                <select id="user_id" name="user_id" required>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <option value="<?php echo $user['user_id']; ?>">
                            <?php echo $user['user_id'] . ' - ' . $user['username']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <label for="message"  style="margin-top:20px;"  >Message:</label>
                <textarea id="message" name="message" rows="4" required></textarea>
                <button type="submit">Send Message</button> 
                
            </form>
        <?php endif; ?>
        <div class="btn-container">
            <a class="btn-back" href="admin_dashboard.php"     >Back to Dashboard</a>
        </div>
    </div>
    <div >
        <div class="table-container">
            <div class="table-responsive">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <?php
                            if ($data->num_rows > 0) {
                                while ($field_info = $data->fetch_field()) {
                                    echo "<th>{$field_info->name}</th>";
                                }
                                echo "<th>Actions</th>";
                            }
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($data->num_rows > 0) {
                            while ($row = $data->fetch_assoc()) {
                                echo "<tr>";
                                foreach ($row as $key => $value) {
                                    echo "<td>{$value}</td>";
                                }
                                if ($table == 'accounts') {
                                    $id = $row['account_id'];
                                } else {
                                    $id = $row[array_keys($row)[0]];
                                }
                                echo "<td>
                                    <div class='action-buttons'>
                                        <a class='btn-edit' href='admin_edit.php?table={$table}&id={$id}'>Edit</a>
                                        <a class='btn-delete' href='admin_delete.php?table={$table}&id={$id}'>Delete</a>
                                    </div>
                                </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='100%'>No records found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
