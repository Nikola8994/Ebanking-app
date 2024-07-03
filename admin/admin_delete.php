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

function deleteRow($conn, $table, $id) {
    // Preuzmite naziv kolone primarnog ključa
    $result = $conn->query("SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'");
    if ($result === false) {
        throw new Exception("Failed to fetch primary key: " . $conn->error);
    }
    $primary_key_column = $result->fetch_assoc()['Column_name'];
    
    // Priprema za sql izjavu
    $sql = "DELETE FROM $table WHERE $primary_key_column = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

try {
    
    if ($table === 'users') {
        
        $account_ids = [];
        $stmt_accounts = $conn->prepare("SELECT account_id FROM accounts WHERE user_id = ?");
        $stmt_accounts->bind_param("i", $id);
        $stmt_accounts->execute();
        $result_accounts = $stmt_accounts->get_result();
        while ($row = $result_accounts->fetch_assoc()) {
            $account_ids[] = $row['account_id'];
        }
        $stmt_accounts->close();

        
        foreach ($account_ids as $account_id) {
            deleteRow($conn, 'accounts', $account_id);
        }

        // Obriši korisnika
        deleteRow($conn, 'users', $id);
    } else {
        deleteRow($conn, $table, $id);
    }

    header("Location: admin_manage.php?table=$table");
    exit();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
