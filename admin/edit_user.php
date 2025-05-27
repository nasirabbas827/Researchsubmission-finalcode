<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$id = (int)$_GET['id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $id"));

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"];
    $email = $_POST["email"];
    $phone = $_POST["phone"];
    $age = $_POST["age"];
    $user_type = $_POST["user_type"];
    $status = $_POST["status"];

    $sql = "UPDATE users SET username=?, email=?, phone=?, age=?, user_type=?, status=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssi", $username, $email, $phone, $age, $user_type, $status, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: view_users.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-body">
            <h2 class="mb-4">Edit User</h2>
            <form method="post">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Phone:</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label>Age:</label>
                    <input type="number" name="age" value="<?= htmlspecialchars($user['age']) ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label>User Type:</label>
                    <select name="user_type" class="form-control" required>
                        <option value="Faculty Member" <?= $user['user_type'] === 'Faculty Member' ? 'selected' : '' ?>>Faculty Member</option>
                        <option value="Student" <?= $user['user_type'] === 'Student' ? 'selected' : '' ?>>Student</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status:</label>
                    <select name="status" class="form-control" required>
                        <option value="pending" <?= $user['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $user['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $user['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update User</button>
                <a href="view_users.php" class="btn btn-secondary ml-2">Cancel</a>
            </form>
        </div>
    </div>
</div>

</body>
</html>
