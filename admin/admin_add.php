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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $columns = [];
    $values = [];
    foreach ($_POST as $key => $value) {
        $columns[] = $key;
        $values[] = "'" . $conn->real_escape_string($value) . "'";
    }
    $insert_sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";
    if ($conn->query($insert_sql)) {
        header("Location: admin_manage.php?table=$table");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}

$user_ids = [];
if ($table === 'accounts') {
    $user_result = $conn->query("SELECT user_id, username, email FROM users");
    while ($row = $user_result->fetch_assoc()) {
        $user_ids[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add to <?php echo ucfirst($table); ?></title>
    <link rel="stylesheet" href="../css/admin_add.css">
</head>
<body>
    <div class="container">
        <h2>Add to <?php echo ucfirst($table); ?></h2>
        <form action="admin_add.php?table=<?php echo $table; ?>" method="POST">
            <?php
            $result = $conn->query("SHOW COLUMNS FROM $table");
            while ($row = $result->fetch_assoc()) {
                if ($row['Field'] !== 'account_id' && $row['Extra'] !== 'auto_increment') {
                    echo "<label for='{$row['Field']}'>" . ucfirst(str_replace('_', ' ', $row['Field'])) . ":</label>";
                    if ($row['Field'] === 'user_id' && $table === 'accounts') {
                        echo "<select id='{$row['Field']}' name='{$row['Field']}' required>";
                        foreach ($user_ids as $user) {
                            echo "<option value='{$user['user_id']}'>{$user['user_id']} - {$user['username']} ({$user['email']})</option>";
                        }
                        echo "</select><br>";
                    } elseif ($row['Field'] === 'account_type' && $table === 'accounts') {
                        echo "<select id='{$row['Field']}' name='{$row['Field']}' required>";
                        echo "<option value='current'>Current</option>";
                        echo "<option value='savings'>Savings</option>";
                        echo "<option value='foreign_exchange'>Foreign Exchange</option>";
                        echo "</select><br>";
                    } else {
                        echo "<input type='text' id='{$row['Field']}' name='{$row['Field']}' required><br>";
                    }
                }
            }
            ?>
            <button type="submit">Add</button>
        </form>
        <a class="btn-back" href="admin_manage.php?table=<?php echo $table; ?>">Back to Manage <?php echo ucfirst($table); ?></a>
    </div>
</body>
</html>
