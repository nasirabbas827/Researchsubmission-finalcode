<?php
include('config.php');
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION["id"];
$upload_error = $update_msg = "";

// Fetch user info
$sql = "SELECT username, email, age, bio, profile_picture FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $username, $email, $age, $bio, $profile_picture);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Update user info
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newUsername = trim($_POST["username"]);
    $newEmail = trim($_POST["email"]);
    $newAge = intval($_POST["age"]);
    $newBio = trim($_POST["bio"]);

    // Handle profile picture upload
    if (!empty($_FILES["profile_picture"]["name"])) {
        $target_dir = "images/";
        $file_name = basename($_FILES["profile_picture"]["name"]);
        $target_file = $target_dir . time() . "_" . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageFileType, $allowed)) {
            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                $profile_picture = $target_file;
            } else {
                $upload_error = "Error uploading the profile picture.";
            }
        } else {
            $upload_error = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
    }

    // Update query
    $update_query = "UPDATE users SET username = ?, email = ?, age = ?, bio = ?, profile_picture = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "ssissi", $newUsername, $newEmail, $newAge, $newBio, $profile_picture, $user_id);

    if (mysqli_stmt_execute($update_stmt)) {
        $update_msg = '<div class="alert alert-success">Profile updated successfully.</div>';
        $username = $newUsername;
        $email = $newEmail;
        $age = $newAge;
        $bio = $newBio;
    } else {
        $update_msg = '<div class="alert alert-danger">Error updating profile: ' . mysqli_error($conn) . '</div>';
    }

    mysqli_stmt_close($update_stmt);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Profile</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 600px;">
        <div class="card-body">
            <h2 class="mb-4">Update Profile</h2>
            <?php echo $update_msg; ?>
            <?php if (!empty($upload_error)) echo '<div class="alert alert-warning">'.$upload_error.'</div>'; ?>

            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="form-group">
                    <label>Age:</label>
                    <input type="number" class="form-control" name="age" value="<?php echo htmlspecialchars($age); ?>" required>
                </div>
                <div class="form-group">
                    <label>Bio:</label>
                    <textarea class="form-control" name="bio" rows="3"><?php echo htmlspecialchars($bio); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Profile Picture:</label><br>
                    <?php if (!empty($profile_picture)) echo "<img src='$profile_picture' width='100' class='mb-2'><br>"; ?>
                    <input type="file" name="profile_picture" class="form-control-file">
                </div>
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
