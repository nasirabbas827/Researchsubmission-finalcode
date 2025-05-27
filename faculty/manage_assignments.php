<?php
include('config.php');
session_start();

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION["id"];
$msg = "";

// Delete assignment
if (isset($_GET['delete'])) {
    $assignment_id = intval($_GET['delete']);
    $stmt = mysqli_prepare($conn, "DELETE FROM ResearchAssignments WHERE assignment_id = ? AND created_by = ?");
    mysqli_stmt_bind_param($stmt, "ii", $assignment_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $msg = '<div class="alert alert-success">Assignment deleted successfully.</div>';
    } else {
        $msg = '<div class="alert alert-danger">Failed to delete assignment.</div>';
    }
    mysqli_stmt_close($stmt);
}

// Handle edit submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["edit_id"])) {
    $edit_id = intval($_POST["edit_id"]);
    $title = trim($_POST["edit_title"]);
    $description = trim($_POST["edit_description"]);
    $due_date = $_POST["edit_due_date"];

    $stmt = mysqli_prepare($conn, "UPDATE ResearchAssignments SET title = ?, description = ?, due_date = ? WHERE assignment_id = ? AND created_by = ?");
    mysqli_stmt_bind_param($stmt, "sssii", $title, $description, $due_date, $edit_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $msg = '<div class="alert alert-success">Assignment updated successfully.</div>';
    } else {
        $msg = '<div class="alert alert-danger">Error updating assignment.</div>';
    }
    mysqli_stmt_close($stmt);
}

// Fetch assignments
$stmt = mysqli_prepare($conn, "SELECT assignment_id, title, description, due_date FROM ResearchAssignments WHERE created_by = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Assignments</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5 mb-5">
            <h2 class="mb-4">Your Research Assignments</h2>
            <?php echo $msg; ?>

            <?php if (mysqli_num_rows($result) > 0): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($row['due_date'])) ?></td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="openEditModal(<?= $row['assignment_id'] ?>, '<?= htmlspecialchars(addslashes($row['title'])) ?>', '<?= htmlspecialchars(addslashes($row['description'])) ?>', '<?= date('Y-m-d\TH:i', strtotime($row['due_date'])) ?>')">Edit</button>
                                <a href="?delete=<?= $row['assignment_id'] ?>" onclick="return confirm('Are you sure you want to delete this assignment?');" class="btn btn-sm btn-danger">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No assignments found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Assignment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="form-group">
                    <label>Title:</label>
                    <input type="text" name="edit_title" id="edit_title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="edit_description" id="edit_description" class="form-control" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label>Due Date:</label>
                    <input type="datetime-local" name="edit_due_date" id="edit_due_date" class="form-control" min="<?= date('Y-m-d\TH:i'); ?>" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Update Assignment</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </form>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openEditModal(id, title, desc, dueDate) {
    document.getElementById("edit_id").value = id;
    document.getElementById("edit_title").value = title;
    document.getElementById("edit_description").value = desc;
    document.getElementById("edit_due_date").value = dueDate;
    $('#editModal').modal('show');
}
</script>

</body>
</html>
