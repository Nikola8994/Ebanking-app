<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bill_id = $_POST['bill_id'];
    $amount = $_POST['amount'];

    $sql = "SELECT account_id, balance FROM accounts WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $account_id = $row['account_id'];

        if ($row['balance'] >= $amount) {
            $sql = "UPDATE accounts SET balance = balance - ? WHERE account_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("di", $amount, $account_id);
            $stmt->execute();

            $sql = "UPDATE bills SET status = 'paid' WHERE bill_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $bill_id);
            $stmt->execute();

            $sql = "INSERT INTO transactions (account_id, amount, transaction_type, description) VALUES (?, ?, 'debit', 'Bill payment')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("id", $account_id, $amount);
            $stmt->execute();

            echo "Bill payment successful. <a href='dashboard.php'>Back to dashboard</a>";
        } else {
            echo "Insufficient balance. <a href='pay_bill.php'>Try again</a>";
        }
    } else {
        echo "Invalid account. <a href='pay_bill.php'>Try again</a>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pay Bill</title>
    <link rel="stylesheet" href="../css/pay_bill.css">
</head>
<body>
    <div class="container">
        <h2>Pay Bill</h2>
        <form action="pay_bill.php" method="POST">
            <label for="bill_id">Bill ID:</label>
            <input type="number" id="bill_id" name="bill_id" required>
            <label for="amount">Amount:</label>
            <input type="number" id="amount" name="amount" step="1" min="0" required>
            <button type="submit" style="max-width: 800px; font-size: 20px;">Pay</button>
        </form>
        <a href="dashboard.php" style="display: block; margin: 15px 0; padding: 15px 0; background-color: #dc3545; color: #fff; text-decoration: none; border-radius: 5px; text-align: center; transition: background-color 0.3s, border-color 0.3s; font-size: 1.2em;">Back to dashboard</a>
    </div>
</body>
</html>
