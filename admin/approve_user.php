<?php
session_start();
include('config.php');

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id']) && isset($_POST['action'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];
    
    if ($action == 'approve') {
        $status = 'approved';
    } elseif ($action == 'reject') {
        $status = 'rejected';
    } else {
        die("Invalid action");
    }

    $update_sql = "UPDATE Users SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "si", $status, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: admin_home.php");
    } else {
        echo "Error updating user status: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
} else {
    die("Invalid request");
}

mysqli_close($conn);
?>