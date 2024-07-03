<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: admin_login.php");
    exit();
}
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $bill_type = $_POST['bill_type'];
    $amount = $_POST['amount'];
    $due_date = $_POST['due_date'];

    $stmt = $conn->prepare("INSERT INTO bills (user_id, bill_type, amount, due_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isds", $user_id, $bill_type, $amount, $due_date);

    if ($stmt->execute()) {
        header("Location: admin_manage.php?table=bills");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
