<?php
include('config.php');
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION["id"];

// Fetch submissions of the user
$sql = "SELECT s.*, ra.title AS assignment_title, u.username AS supervisor_name, u.profile_picture 
        FROM Submissions s
        JOIN ResearchAssignments ra ON s.assignment_id = ra.assignment_id
        JOIN Users u ON s.supervisor_id = u.id
        WHERE s.student_id = ?
        ORDER BY s.submission_date DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Submissions</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Card hover effect */
        .card:hover {
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            transition: box-shadow 0.3s ease-in-out;
        }
        /* Badge colors */
        .badge-info { background-color: #17a2b8; }
        .badge-warning { background-color: #ffc107; color: #212529; }
        /* Ensure modal textarea height */
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        /* Supervisor image */
        .supervisor-img {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 10px;
        }
        /* Limit the card body content for uniform height */
        .card-body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .card-body > .info-group {
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <h3 class="mb-4 font-weight-bold">My Research Submissions</h3>
    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="row">
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="col-md-4 mb-4 d-flex align-items-stretch">
                    <div class="card w-100 shadow-sm rounded">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title font-weight-bold"><?= htmlspecialchars($row['title']) ?></h5>
                            <div class="info-group">
                                <p class="mb-1"><strong>Assignment:</strong> <?= htmlspecialchars($row['assignment_title']) ?></p>
                                <p class="mb-1">
                                    <strong>Status:</strong> 
                                    <span class="badge badge-<?= 
                                        $row['status'] === 'accepted' || $row['status'] === 'accepted_and_published' ? 'success' : 
                                        ($row['status'] === 'rejected' ? 'danger' : 
                                        ($row['status'] === 'under_review' ? 'info' : 
                                        ($row['status'] === 'needs_improvement' ? 'warning' : 'secondary'))
                                    ) ?>">
                                        <?= ucfirst(str_replace('_', ' ', $row['status'])) ?>
                                    </span>
                                </p>
                                <p class="mb-1"><strong>Submitted:</strong> <?= date('Y-m-d H:i', strtotime($row['submission_date'])) ?></p>
                                <p class="mb-1"><strong>File Type:</strong> <?= strtoupper($row['file_type']) ?></p>
                            </div>

                            <div class="d-flex align-items-center mt-auto">
                                <?php if (!empty($row['profile_picture'])): ?>
                                    <img src="../faculty/<?= htmlspecialchars($row['profile_picture']) ?>" alt="Supervisor Profile" class="supervisor-img">
                                <?php endif; ?>
                                <div>
                                    <small class="text-muted">Supervisor</small><br>
                                    <strong><?= htmlspecialchars($row['supervisor_name']) ?></strong>
                                </div>
                            </div>

                            <!-- Previous Versions -->
                            <?php
                            $ver_sql = "SELECT * FROM SubmissionVersions WHERE submission_id = ? ORDER BY uploaded_at DESC";
                            $ver_stmt = mysqli_prepare($conn, $ver_sql);
                            mysqli_stmt_bind_param($ver_stmt, "i", $row['submission_id']);
                            mysqli_stmt_execute($ver_stmt);
                            $ver_result = mysqli_stmt_get_result($ver_stmt);

                            if (mysqli_num_rows($ver_result) > 0):
                                echo "<hr class='my-3'><strong>Previous Versions:</strong><ul class='list-unstyled small'>";
                                while ($v = mysqli_fetch_assoc($ver_result)) {
                                    echo "<li><a href='" . htmlspecialchars($v['file_path']) . "' target='_blank'>Version on " . date('Y-m-d H:i', strtotime($v['uploaded_at'])) . "</a></li>";
                                }
                                echo "</ul>";
                            endif;
                            ?>

                            <!-- Commented Feedback File -->
                            <?php if (($row['status'] === 'rejected' || $row['status'] === 'needs_improvement') && !empty($row['feedback_file'])): ?>
                                <hr class="my-3">
                                <p><strong>Commented File:</strong> 
                                    <a href="../faculty/<?= htmlspecialchars($row['feedback_file']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">View Feedback File</a>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white border-top-0 text-right">
                            <a href="<?= htmlspecialchars($row['file_path']) ?>" class="btn btn-sm btn-outline-primary" target="_blank">View File</a>
                            <?php if ($row['status'] === 'needs_improvement'): ?>
                                <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#updateModal<?= $row['submission_id'] ?>">Update</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Update Modal -->
                <div class="modal fade" id="updateModal<?= $row['submission_id'] ?>" tabindex="-1" role="dialog" aria-labelledby="updateLabel" aria-hidden="true">
                  <div class="modal-dialog" role="document">
                    <form method="post" action="update_submission.php" enctype="multipart/form-data">
                        <input type="hidden" name="submission_id" value="<?= $row['submission_id'] ?>">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Update Research Paper</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                            </div>
                            <div class="modal-body">
                                <label>Upload New Version (PDF/DOC/DOCX)</label>
                                <input type="file" name="new_file" required class="form-control mb-3" accept=".pdf,.doc,.docx">
                                <label>Remarks (Optional)</label>
                                <textarea name="remarks" class="form-control" placeholder="Add remarks or comments..."></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="update" class="btn btn-primary">Upload</button>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            </div>
                        </div>
                    </form>
                  </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No submissions found.</div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
