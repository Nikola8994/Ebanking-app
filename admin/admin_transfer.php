<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: admin_login.php");
    exit();
}
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_email = $_POST['sender_email'];
    $recipient_email = $_POST['recipient_email'];
    $transaction_type = $_POST['transaction_type'];
    $amount = $_POST['amount'];

    
    if ($amount <= 0) {
        echo "Amount must be greater than 0.";
        exit();
    }

    
    $sender_query = "SELECT account_id FROM accounts WHERE user_id = (SELECT user_id FROM users WHERE email = ?) LIMIT 1";
    $stmt_sender = $conn->prepare($sender_query);
    $stmt_sender->bind_param("s", $sender_email);
    $stmt_sender->execute();
    $result_sender = $stmt_sender->get_result();

    if ($result_sender->num_rows == 0) {
        echo "Sender account not found.";
        exit();
    }

    $sender_account = $result_sender->fetch_assoc();
    $sender_account_id = $sender_account['account_id'];

    
    $recipient_query = "SELECT account_id FROM accounts WHERE user_id = (SELECT user_id FROM users WHERE email = ?) LIMIT 1";
    $stmt_recipient = $conn->prepare($recipient_query);
    $stmt_recipient->bind_param("s", $recipient_email);
    $stmt_recipient->execute();
    $result_recipient = $stmt_recipient->get_result();

    if ($result_recipient->num_rows == 0) {
        echo "Recipient account not found.";
        exit();
    }

    $recipient_account = $result_recipient->fetch_assoc();
    $recipient_account_id = $recipient_account['account_id'];

    
    $conn->begin_transaction();

    try {
        
        if ($transaction_type == 'debit') {
            $deduct_query = "UPDATE accounts SET balance = balance - ? WHERE account_id = ? AND balance >= ?";
            $stmt_deduct = $conn->prepare($deduct_query);
            $stmt_deduct->bind_param("dii", $amount, $sender_account_id, $amount);
            $stmt_deduct->execute();

            if ($stmt_deduct->affected_rows == 0) {
                throw new Exception("Insufficient funds or invalid sender account.");
            }
        }

        
        if ($transaction_type == 'credit') {
            $add_query = "UPDATE accounts SET balance = balance + ? WHERE account_id = ?";
            $stmt_add = $conn->prepare($add_query);
            $stmt_add->bind_param("di", $amount, $recipient_account_id);
            $stmt_add->execute();
        }

        
        $log_query_sender = "INSERT INTO transactions (account_id, amount, transaction_type, description) VALUES (?, ?, 'debit', 'Transfer to account $recipient_account_id')";
        $stmt_log_sender = $conn->prepare($log_query_sender);
        $stmt_log_sender->bind_param("id", $sender_account_id, $amount);
        $stmt_log_sender->execute();

        $log_query_recipient = "INSERT INTO transactions (account_id, amount, transaction_type, description) VALUES (?, ?, 'credit', 'Transfer from account $sender_account_id')";
        $stmt_log_recipient = $conn->prepare($log_query_recipient);
        $stmt_log_recipient->bind_param("id", $recipient_account_id, $amount);
        $stmt_log_recipient->execute();

      
        $conn->commit();

        echo "Transfer successful. <a href='admin_manage.php?table=transactions'>Back to Transactions</a>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "Transfer failed: " . $e->getMessage() . ". <a href='admin_manage.php?table=transactions'>Try again</a>";
    }

    $stmt_sender->close();
    $stmt_recipient->close();
    if (isset($stmt_deduct)) {
        $stmt_deduct->close();
    }
    if (isset($stmt_add)) {
        $stmt_add->close();
    }
    $stmt_log_sender->close();
    $stmt_log_recipient->close();
    $conn->close();
}
?>
