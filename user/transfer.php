<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include '../db.php';

$user_email = $_SESSION['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // dobijanje e-pošte primaoca iz obrasca
    $recipient_email = $_POST['recipient_email'];
    
    // uzimanje iznosa iz obrasca
    $amount = $_POST['amount'];

    
    if ($amount < 1) {
        echo "Amount must be at least 1.";
        exit();
    }

    // preuzimanje podatka o nalogu pošiljaoca
    $sender_query = "SELECT account_id, balance FROM accounts WHERE user_id = (SELECT user_id FROM users WHERE email = ?)";
    $stmt_sender = $conn->prepare($sender_query);
    $stmt_sender->bind_param("s", $user_email);
    $stmt_sender->execute();
    $result_sender = $stmt_sender->get_result();

    if ($result_sender->num_rows == 0) {
        echo "Sender account not found.";
        exit();
    }

    $sender_account = $result_sender->fetch_assoc();
    $sender_account_id = $sender_account['account_id'];
    $sender_balance = $sender_account['balance'];

    // preuzimanje informacija o nalogu primaoca
    $recipient_query = "SELECT account_id FROM accounts WHERE user_id = (SELECT user_id FROM users WHERE email = ?)";
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

    // početak transakcije
    $conn->begin_transaction();

    try {
        // dduzimanje iznosa sa računa pošiljaoca
        if ($sender_balance < $amount) {
            throw new Exception("Insufficient funds.");
        }

        $deduct_query = "UPDATE accounts SET balance = balance - ? WHERE account_id = ?";
        $stmt_deduct = $conn->prepare($deduct_query);
        if (!$stmt_deduct) {
            throw new Exception("Failed to prepare deduct statement: " . $conn->error);
        }
        $stmt_deduct->bind_param("di", $amount, $sender_account_id);
        $stmt_deduct->execute();

        // dodavanje iznosa na račun primaoca
        $add_query = "UPDATE accounts SET balance = balance + ? WHERE account_id = ?";
        $stmt_add = $conn->prepare($add_query);
        if (!$stmt_add) {
            throw new Exception("Failed to prepare add statement: " . $conn->error);
        }
        $stmt_add->bind_param("di", $amount, $recipient_account_id);
        $stmt_add->execute();

        // evidencija transakcija za oba naloga
        $log_query_sender = "INSERT INTO transactions (account_id, amount, transaction_type, description) VALUES (?, ?, 'debit', 'Transfer to $recipient_email')";
        $stmt_log_sender = $conn->prepare($log_query_sender);
        if (!$stmt_log_sender) {
            throw new Exception("Failed to prepare log query for sender: " . $conn->error);
        }
        $stmt_log_sender->bind_param("id", $sender_account_id, $amount);
        $stmt_log_sender->execute();

        $log_query_recipient = "INSERT INTO transactions (account_id, amount, transaction_type, description) VALUES (?, ?, 'credit', 'Transfer from $user_email')";
        $stmt_log_recipient = $conn->prepare($log_query_recipient);
        if (!$stmt_log_recipient) {
            throw new Exception("Failed to prepare log query for recipient: " . $conn->error);
        }
        $stmt_log_recipient->bind_param("id", $recipient_account_id, $amount);
        $stmt_log_recipient->execute();

        // komitovanje transakcije
        $conn->commit();

        echo "Transfer successful. <a href='dashboard.php'>Back to dashboard</a>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "Transfer failed: " . $e->getMessage() . ". <a href='transfer.php'>Try again</a>";
    }

    // Zatvaranje svih pripremljenih izjava ako su inicijalizovane
    if (isset($stmt_sender)) $stmt_sender->close();
    if (isset($stmt_recipient)) $stmt_recipient->close();
    if (isset($stmt_deduct)) $stmt_deduct->close();
    if (isset($stmt_add)) $stmt_add->close();
    if (isset($stmt_log_sender)) $stmt_log_sender->close();
    if (isset($stmt_log_recipient)) $stmt_log_recipient->close();

    // Zatvaranje konekcije sa bazom podataka
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Transfer Money</title>
    <link rel="stylesheet" href="../css/transfer.css">
    <script>
        function validateForm() {
            const amount = document.getElementById('amount').value;
            if (amount < 1) {
                alert('Amount must be at least 1.');
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>Transfer Money</h2>
        <form action="transfer.php" method="POST" onsubmit="return validateForm();">
            <label for="sender_email">Your Email:</label>
            <input type="email" id="sender_email" name="sender_email" value="<?php echo htmlspecialchars($user_email); ?>" readonly>
            <label for="recipient_email">Recipient's Email:</label>
            <input type="email" id="recipient_email" name="recipient_email" required>
            <label for="amount">Amount:</label>
            <input type="number" id="amount" name="amount" min="1" value="0" step="1">
            <button type="submit"  >Transfer</button>
        </form>
        <a href="dashboard.php"  style="display: block; margin: 15px 0; padding: 15px 0; background-color: #dc3545; color: #fff; text-decoration: none; border-radius: 5px; text-align: center; transition: background-color 0.3s, border-color 0.3s; font-size: 1.2em;">Back to dashboard</a>
    </div>
</body>
</html>
