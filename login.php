<?php
include('config.php');

// Define variables and initialize with empty values
$email = $password = "";
$email_err = $password_err = $login_msg = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // If no errors, check credentials and log in user
    if (empty($email_err) && empty($password_err)) {
        // Modified SQL to fetch user status
        $sql = "SELECT id, email, password, user_type, status FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $param_email);
        $param_email = $email;
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) == 1) {
            mysqli_stmt_bind_result($stmt, $id, $email, $hashed_password, $user_type, $status);
            if (mysqli_stmt_fetch($stmt)) {
                if ($status !== 'approved') {
                    $login_msg = '<div class="alert alert-warning">Your account is not approved yet.</div>';
                } elseif (password_verify($password, $hashed_password)) {
                    // Password is correct and status approved
                    session_start();
                    $_SESSION["id"] = $id;
                    $_SESSION["email"] = $email;
                    $_SESSION["user_type"] = $user_type;

                    // Redirect based on user type
                    if ($user_type == 'Faculty Member') {
                        header("Location: faculty/faculty_dashboard.php");
                    } elseif ($user_type == 'Student') {
                        header("Location: student/student_dashboard.php");
                    } else {
                        $login_msg = '<div class="alert alert-danger">Invalid user type assigned.</div>';
                    }
                    exit();
                } else {
                    $password_err = "The password you entered is incorrect.";
                }
            }
        } else {
            $email_err = "No account found with that email.";
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Login</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
    <div class="container mt-5">
        <div class="card mx-auto" style="max-width: 500px;">
            <div class="card-body">
                <h2 class="text-center">User Login</h2>
                <p class="text-center">Please fill in your credentials to log in.</p>

                <!-- Display login message -->
                <?php echo $login_msg; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="text" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                        <span class="invalid-feedback"><?php echo $email_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    </div>
                    <div class="form-group text-center">
                        <input type="submit" value="Log in" class="btn btn-primary">
                    </div>
                </form>

                <p class="text-center">Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</body>
</html>
