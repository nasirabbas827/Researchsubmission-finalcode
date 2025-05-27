<?php
session_start();
include('config.php');

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : "";

$sql = "SELECT s.*, u.username AS student_name, u.email AS student_email, ra.title AS assignment_title 
        FROM Submissions s
        JOIN Users u ON s.student_id = u.id
        JOIN ResearchAssignments ra ON s.assignment_id = ra.assignment_id";

if (!empty($status_filter)) {
    $sql .= " WHERE s.status = ?";
}

$sql .= " ORDER BY s.submission_date DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($status_filter)) {
    mysqli_stmt_bind_param($stmt, "s", $status_filter);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - All Submissions</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('admin_navbar.php'); ?>

<div class="container mt-5">
    <h3 class="mb-4">All Submissions (Admin View)</h3>

    <form method="get" class="mb-4">
        <label class="mr-2">Filter by Status:</label>
        <select name="status_filter" class="form-control mr-2">
            <option value="">All</option>
            <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="under_review" <?= $status_filter == 'under_review' ? 'selected' : '' ?>>Under Review</option>
            <option value="accepted" <?= $status_filter == 'accepted' ? 'selected' : '' ?>>Accepted</option>
            <option value="accepted_and_published" <?= $status_filter == 'accepted_and_published' ? 'selected' : '' ?>>Accepted & Published</option>
            <option value="rejected" <?= $status_filter == 'rejected' ? 'selected' : '' ?>>Rejected</option>
            <option value="needs_improvement" <?= $status_filter == 'needs_improvement' ? 'selected' : '' ?>>Needs Improvement</option>
        </select>
        <button type="submit" class="mt-2 btn btn-primary">Filter</button>
    </form>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5><?= htmlspecialchars($row['title']) ?> <small class="text-muted">(<?= htmlspecialchars($row['assignment_title']) ?>)</small></h5>
                    <p><strong>Student:</strong> <?= htmlspecialchars($row['student_name']) ?> (<?= $row['student_email'] ?>)</p>
                    <p><strong>Status:</strong> <?= ucfirst(str_replace("_", " ", $row['status'])) ?></p>
                    <p><strong>Submitted:</strong> <?= date('Y-m-d H:i', strtotime($row['submission_date'])) ?></p>
                    <p><strong>Category:</strong> <?= htmlspecialchars($row['category']) ?> | <strong>Domain:</strong> <?= htmlspecialchars($row['domain']) ?></p>

                    <a href="../student/<?= htmlspecialchars($row['file_path']) ?>" class="btn btn-sm btn-outline-primary" target="_blank">Download Submission</a>

                    <?php if (!empty($row['feedback_file'])): ?>
                        <a href="<?= htmlspecialchars($row['feedback_file']) ?>" class="btn btn-sm btn-outline-secondary ml-2" target="_blank">Download Feedback File</a>
                    <?php endif; ?>

                    <?php if (!empty($row['feedback'])): ?>
                        <div class="mt-3">
                            <strong>Feedback:</strong>
                            <p><?= nl2br(htmlspecialchars($row['feedback'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Version History -->
                    <?php
                    $ver_sql = "SELECT * FROM SubmissionVersions WHERE submission_id = ? ORDER BY uploaded_at DESC";
                    $ver_stmt = mysqli_prepare($conn, $ver_sql);
                    mysqli_stmt_bind_param($ver_stmt, "i", $row['submission_id']);
                    mysqli_stmt_execute($ver_stmt);
                    $ver_result = mysqli_stmt_get_result($ver_stmt);
                    if (mysqli_num_rows($ver_result) > 0): ?>
                        <hr>
                        <strong>Previous Versions:</strong>
                        <ul class="list-unstyled">
                            <?php while ($ver = mysqli_fetch_assoc($ver_result)): ?>
                                <li><a href="../student/<?= htmlspecialchars($ver['file_path']) ?>" target="_blank">Version from <?= date('Y-m-d H:i', strtotime($ver['uploaded_at'])) ?></a></li>
                            <?php endwhile; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="alert alert-info">No submissions found.</div>
    <?php endif; ?>
</div>
</body>
</html>
