<?php
include('config.php');
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION["id"];
$create_msg = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $due_date = $_POST["due_date"];

    if (!empty($title) && !empty($due_date)) {
        $sql = "INSERT INTO ResearchAssignments (title, description, due_date, created_by) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssi", $title, $description, $due_date, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            $create_msg = '<div class="alert alert-success">Research assignment created successfully.</div>';
        } else {
            $create_msg = '<div class="alert alert-danger">Error creating assignment: ' . mysqli_error($conn) . '</div>';
        }

        mysqli_stmt_close($stmt);
    } else {
        $create_msg = '<div class="alert alert-warning">Title and Due Date are required fields.</div>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Research Assignment</title>
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
            <h2 class="mb-4">Create Research Assignment</h2>
            <?php echo $create_msg; ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label>Assignment Title:</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" class="form-control" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label>Due Date:</label>
                    <input type="datetime-local" name="due_date" class="form-control" min="<?= date('Y-m-d\TH:i'); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Create Assignment</button>
                <a class="btn btn-dark" href="manage_assignments.php">Manage Assignments</a>
            </form>
        </div>
    </div>
</div>

</body>
</html>
