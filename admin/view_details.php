<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid user ID.";
    exit;
}

$id = (int)$_GET['id'];
$result = mysqli_query($conn, "SELECT * FROM users WHERE id = $id");

if (mysqli_num_rows($result) === 0) {
    echo "User not found.";
    exit;
}

$user = mysqli_fetch_assoc($result);

$folder = strtolower($user['user_type']) === 'student' ? 'students' : 'faculty';
$imagePath = "../{$folder}/" . $user['profile_picture'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Details - <?= htmlspecialchars($user['username']) ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container mt-5">
    <h2>User Details</h2>
    <div class="card mt-4">
        <div class="card-header bg-dark text-white">
            Profile: <?= htmlspecialchars($user['username']) ?>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 text-center">
                    <?php if (!empty($user['profile_picture']) && file_exists($imagePath)): ?>
                        <img src="<?= $imagePath ?>" class="img-fluid rounded mb-3" style="max-height: 250px;">
                    <?php else: ?>
                        <p>No profile image available.</p>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <p><strong>Full Name:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone']) ?></p>
                    <p><strong>Age:</strong> <?= htmlspecialchars($user['age']) ?></p>
                    <p><strong>User Type:</strong> <?= htmlspecialchars($user['user_type']) ?></p>
                    <p><strong>Status:</strong> <?= htmlspecialchars($user['status']) ?></p>
                    <p><strong>Affiliation:</strong> <?= htmlspecialchars($user['affiliation']) ?></p>
                    <p><strong>Expertise:</strong> <?= htmlspecialchars($user['expertise']) ?></p>
                    <p><strong>Publications:</strong> <?= nl2br(htmlspecialchars($user['publications'])) ?></p>
                    <p><strong>Bio:</strong> <?= nl2br(htmlspecialchars($user['bio'])) ?></p>
                    <a href="view_users.php" class="btn btn-secondary">Back to User List</a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
