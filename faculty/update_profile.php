<?php
include('config.php');
session_start();

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION["id"];
$upload_error = $update_msg = "";
$profile_picture = "";

// Fetch current user data
$sql = "SELECT username, email, age, bio, profile_picture, full_name, affiliation, expertise, publications 
        FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $username, $email, $age, $bio, $profile_picture, $full_name, $affiliation, $expertise, $publications);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];

    $newUsername = trim($_POST["username"]);
    $newEmail = trim($_POST["email"]);
    $newAge = intval($_POST["age"]);
    $newBio = trim($_POST["bio"]);
    $newFullName = trim($_POST["full_name"]);
    $newAffiliation = trim($_POST["affiliation"]);
    $newPublications = trim($_POST["publications"]);
    $newExpertise = isset($_POST["expertise"]) ? implode(", ", $_POST["expertise"]) : "";

    // Validations
    if (empty($newFullName) || !preg_match("/^[a-zA-Z ]{3,30}$/", $newFullName)) {
        $errors[] = "Full name must be 3–30 letters and spaces only.";
    }

    if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }

    if ($newAge < 18 || $newAge > 120) {
        $errors[] = "Age must be between 18 and 120.";
    }

    if (empty($newAffiliation)) {
        $errors[] = "Affiliation is required.";
    }

    if (empty($_POST["expertise"]) || !is_array($_POST["expertise"])) {
        $errors[] = "Please select at least one area of expertise.";
    }

    if (!is_numeric($newPublications) || $newPublications < 0) {
        $errors[] = "Publications must be a non-negative number.";
    }

    // Profile picture upload
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
                $errors[] = "Error uploading the profile picture.";
            }
        } else {
            $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
    }

    if (empty($errors)) {
        $update_query = "UPDATE users SET 
            username = ?, email = ?, age = ?, bio = ?, profile_picture = ?, 
            full_name = ?, affiliation = ?, expertise = ?, publications = ? 
            WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param(
            $stmt, "ssisssssii",
            $newUsername, $newEmail, $newAge, $newBio, $profile_picture,
            $newFullName, $newAffiliation, $newExpertise, $newPublications, $user_id
        );

        if (mysqli_stmt_execute($stmt)) {
            $update_msg = '<div class="alert alert-success">Profile updated successfully.</div>';
            $username = $newUsername;
            $email = $newEmail;
            $age = $newAge;
            $bio = $newBio;
            $full_name = $newFullName;
            $affiliation = $newAffiliation;
            $expertise = $newExpertise;
            $publications = $newPublications;
        } else {
            $update_msg = '<div class="alert alert-danger">Error updating profile: ' . mysqli_error($conn) . '</div>';
        }
        mysqli_stmt_close($stmt);
    } else {
        $update_msg = '<div class="alert alert-danger"><ul><li>' . implode('</li><li>', $errors) . '</li></ul></div>';
    }
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
    <div class="card mx-auto" style="max-width: 750px;">
        <div class="card-body">
            <h2 class="mb-4">Update Profile</h2>
            <?php echo $update_msg; ?>

            <form method="post" action="" enctype="multipart/form-data">
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
                    <label>Full Name:</label>
                    <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                </div>
                <div class="form-group">
                    <label>Affiliation:</label>
                    <input type="text" class="form-control" name="affiliation" value="<?php echo htmlspecialchars($affiliation); ?>" required>
                </div>
                <div class="form-group">
                    <label>Expertise:</label><br>
                    <?php
                    $areas = ["AI", "Data Science", "Cybersecurity", "IoT", "Software Engineering"];
                    $selected_expertise = explode(", ", $expertise);
                    foreach ($areas as $area) {
                        $checked = in_array($area, $selected_expertise) ? "checked" : "";
                        echo "<div class='form-check form-check-inline'>
                                <input class='form-check-input' type='checkbox' name='expertise[]' value='$area' $checked>
                                <label class='form-check-label'>$area</label>
                              </div>";
                    }
                    ?>
                </div>
                <div class="form-group">
                    <label>Number of Publications:</label>
                    <input type="number" class="form-control" name="publications" value="<?php echo htmlspecialchars($publications); ?>" required>
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
