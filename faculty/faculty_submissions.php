<?php
session_start();
include('config.php');
 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';


// Redirect to login if not authenticated
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION["id"];
$feedback_msg = "";

// Function to send email using PHPMailer
function sendStatusUpdateEmail($toEmail, $toName, $submissionTitle, $status, $feedback) {
    $mail = new PHPMailer(true);
    try {
        // SMTP configuration - CHANGE THESE to your SMTP server details
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // Your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'nasiryt.827@gmail.com';  // SMTP username
        $mail->Password = 'mtvp ruzp aqfu tfxt';           // SMTP password
        $mail->SMTPSecure = 'tls';                    // or 'ssl'
        $mail->Port = 587;                            // TCP port

        $mail->setFrom('nasiryt.827@gmail.com', 'Research Portal');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = "Update on your submission: " . htmlspecialchars($submissionTitle);

        $body = "<p>Dear " . htmlspecialchars($toName) . ",</p>";
        $body .= "<p>Your submission titled <strong>" . htmlspecialchars($submissionTitle) . "</strong> has been updated.</p>";
        $body .= "<p><strong>Status:</strong> " . ucfirst(str_replace("_", " ", $status)) . "</p>";

        if (!empty($feedback)) {
            $body .= "<p><strong>Feedback / Comments:</strong><br>" . nl2br(htmlspecialchars($feedback)) . "</p>";
        }

        $body .= "<p>Please log in to your account to view more details.</p>";
        $body .= "<p>Best regards,<br>Research Portal Team</p>";

        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error if needed: $mail->ErrorInfo
        return false;
    }
}

// Handle status update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submission_id'])) {
    $submission_id = intval($_POST['submission_id']);
    $status = $_POST['status'];
    $feedback = trim($_POST['feedback']);
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : null;
    $feedback_file = '';

    if (in_array($status, ['rejected', 'needs_improvement']) && !empty($_FILES["feedback_file"]["name"])) {
        $allowed_types = ['pdf', 'doc', 'docx'];
        $ext = strtolower(pathinfo($_FILES["feedback_file"]["name"], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed_types)) {
            $upload_dir = "feedbacks/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $feedback_file = $upload_dir . time() . "_" . basename($_FILES["feedback_file"]["name"]);
            move_uploaded_file($_FILES["feedback_file"]["tmp_name"], $feedback_file);
        }
    }

    $update_sql = "UPDATE Submissions SET status = ?, feedback = ?, feedback_file = ?, rating = ? WHERE submission_id = ? AND supervisor_id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "ssssss", $status, $feedback, $feedback_file, $rating, $submission_id, $user_id);

    if (mysqli_stmt_execute($stmt)) {
        $feedback_msg = '<div class="alert alert-success">Evaluation updated and email sent if applicable.</div>';

        // Fetch student's email and name to send notification
        $email_sql = "SELECT u.email, u.username, s.title FROM Users u JOIN Submissions s ON u.id = s.student_id WHERE s.submission_id = ?";
        $email_stmt = mysqli_prepare($conn, $email_sql);
        mysqli_stmt_bind_param($email_stmt, "i", $submission_id);
        mysqli_stmt_execute($email_stmt);
        $email_result = mysqli_stmt_get_result($email_stmt);
        if ($email_result && mysqli_num_rows($email_result) > 0) {
            $student = mysqli_fetch_assoc($email_result);
            // Send email notification
            sendStatusUpdateEmail($student['email'], $student['username'], $student['title'], $status, $feedback);
        }
        mysqli_stmt_close($email_stmt);

    } else {
        $feedback_msg = '<div class="alert alert-danger">Update failed.</div>';
    }
    mysqli_stmt_close($stmt);
}

// Fetch all submissions for this supervisor
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : "";

$sql = "SELECT s.*, u.username AS student_name, u.email AS student_email, ra.title AS assignment_title 
        FROM Submissions s
        JOIN Users u ON s.student_id = u.id
        JOIN ResearchAssignments ra ON s.assignment_id = ra.assignment_id
        WHERE s.supervisor_id = ?";

if (!empty($status_filter)) {
    $sql .= " AND s.status = ?";
}

$sql .= " ORDER BY s.submission_date DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($status_filter)) {
    mysqli_stmt_bind_param($stmt, "is", $user_id, $status_filter);
} else {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Faculty Submissions</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

    <style>
        /* Star rating styles */
        .star-rating {
            direction: rtl;
            font-size: 1.8rem;
            unicode-bidi: bidi-override;
            display: inline-block;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s ease-in-out;
            margin: 0 2px;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffc107;
        }
        /* Card header */
        .card-header {
            font-weight: 600;
            background-color: #f8f9fa;
        }
        /* Previous versions list */
        .version-list {
            font-size: 0.9rem;
            margin-top: 10px;
        }
        /* Feedback box */
        .feedback-box {
            background-color: #f9f9f9;
            border-left: 4px solid #17a2b8;
            padding: 12px 15px;
            margin-top: 15px;
            border-radius: 4px;
        }
        /* Status badge colors */
        .status-badge {
            text-transform: capitalize;
        }
        .status-under_review { background-color: #6c757d; color: white; }
        .status-accepted { background-color: #28a745; color: white; }
        .status-accepted_and_published { background-color: #007bff; color: white; }
        .status-rejected { background-color: #dc3545; color: white; }
        .status-needs_improvement { background-color: #ffc107; color: #212529; }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container my-5">

    <h3 class="mb-4">Evaluate Research Submissions</h3>

    <?= $feedback_msg ?>

    <form method="get" class=" mb-4 align-items-center">
        <label class="mr-2 font-weight-bold" for="status_filter">Filter by Status:</label>
        <select name="status_filter" id="status_filter" class="form-control mr-3">
            <option value="" <?= !isset($_GET['status_filter']) ? 'selected' : '' ?>>All</option>
            <option value="under_review" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'under_review') ? 'selected' : '' ?>>Under Review</option>
            <option value="accepted" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'accepted') ? 'selected' : '' ?>>Accepted</option>
            <option value="accepted_and_published" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'accepted_and_published') ? 'selected' : '' ?>>Accepted & Published</option>
            <option value="rejected" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'rejected') ? 'selected' : '' ?>>Rejected</option>
            <option value="needs_improvement" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'needs_improvement') ? 'selected' : '' ?>>Needs Improvement</option>
        </select>
        <button type="submit" class="mt-2 btn btn-primary">Filter</button>
    </form>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><?= htmlspecialchars($row['title']) ?></h5>
                        <small class="text-muted">(<?= htmlspecialchars($row['assignment_title']) ?>)</small>
                    </div>
                    <span class="badge status-badge status-<?= htmlspecialchars($row['status']) ?>">
                        <?= ucfirst(str_replace("_", " ", $row['status'])) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Student:</strong> <?= htmlspecialchars($row['student_name']) ?></p>
                            <p><strong>Category:</strong> <?= htmlspecialchars($row['category']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Submitted:</strong> <?= date('Y-m-d H:i', strtotime($row['submission_date'])) ?></p>
                            <p><strong>Study Program:</strong> <?= htmlspecialchars($row['domain']) ?></p>
                        </div>
                    </div>

                    <a href="../student/<?= htmlspecialchars($row['file_path']) ?>" class="btn btn-outline-primary btn-sm mb-3" target="_blank">View Current File</a>

                    <?php
                    // Previous Versions Query
                    $ver_sql = "SELECT * FROM SubmissionVersions WHERE submission_id = ? ORDER BY uploaded_at DESC";
                    $ver_stmt = mysqli_prepare($conn, $ver_sql);
                    mysqli_stmt_bind_param($ver_stmt, "i", $row['submission_id']);
                    mysqli_stmt_execute($ver_stmt);
                    $ver_result = mysqli_stmt_get_result($ver_stmt);
                    if (mysqli_num_rows($ver_result) > 0):
                    ?>
                        <button class="btn btn-link p-0 mb-3" type="button" data-toggle="collapse" data-target="#versions-<?= $row['submission_id'] ?>" aria-expanded="false" aria-controls="versions-<?= $row['submission_id'] ?>">
                            Previous Versions ▼
                        </button>
                        <div class="collapse version-list" id="versions-<?= $row['submission_id'] ?>">
                            <ul class="pl-3">
                            <?php while ($ver = mysqli_fetch_assoc($ver_result)): ?>
                                <li>
                                    <a href="../student/<?= htmlspecialchars($ver['file_path']) ?>" target="_blank">
                                        Version from <?= date('Y-m-d H:i', strtotime($ver['uploaded_at'])) ?>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($row['status'] !== 'accepted_and_published'): ?>
                        <form method="post" enctype="multipart/form-data" class="mt-4">
                            <input type="hidden" name="submission_id" value="<?= $row['submission_id'] ?>">

                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="status-<?= $row['submission_id'] ?>">Status</label>
                                    <select id="status-<?= $row['submission_id'] ?>" name="status" class="form-control status-select" required onchange="toggleFeedbackFile(this)">
                                        <option value="">Select</option>
                                        <option value="accepted">Accepted</option>
                                        <option value="accepted_and_published">Accepted & Published</option>
                                        <option value="under_review">Under Review</option>
                                        <option value="rejected">Rejected</option>
                                        <option value="needs_improvement">Needs Improvement</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-8">
                                    <label for="feedback-<?= $row['submission_id'] ?>">Feedback / Comments / Reason For Rejection</label>
                                    <textarea id="feedback-<?= $row['submission_id'] ?>" name="feedback" class="form-control" rows="3" required></textarea>
                                </div>
                            </div>

                            <div class="form-group feedback-file-group" style="display: none;">
                                <label for="feedback_file-<?= $row['submission_id'] ?>">Upload Commented File (PDF/DOC/DOCX, optional)</label>
                                <input id="feedback_file-<?= $row['submission_id'] ?>" type="file" name="feedback_file" class="form-control-file" accept=".pdf,.doc,.docx">
                            </div>

                            <div class="form-group star-rating-group" style="display: none;">
                                <label>Rate this research paper:</label>
                                <div class="star-rating">
                                    <input type="radio" id="star5-<?= $row['submission_id'] ?>" name="rating" value="5"><label for="star5-<?= $row['submission_id'] ?>">&#9733;</label>
                                    <input type="radio" id="star4-<?= $row['submission_id'] ?>" name="rating" value="4"><label for="star4-<?= $row['submission_id'] ?>">&#9733;</label>
                                    <input type="radio" id="star3-<?= $row['submission_id'] ?>" name="rating" value="3"><label for="star3-<?= $row['submission_id'] ?>">&#9733;</label>
                                    <input type="radio" id="star2-<?= $row['submission_id'] ?>" name="rating" value="2"><label for="star2-<?= $row['submission_id'] ?>">&#9733;</label>
                                    <input type="radio" id="star1-<?= $row['submission_id'] ?>" name="rating" value="1"><label for="star1-<?= $row['submission_id'] ?>">&#9733;</label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success btn-sm">Submit Evaluation</button>
                        </form>
                    <?php endif; ?>

                    <?php if (!empty($row['feedback'])): ?>
                        <div class="feedback-box">
                            <strong>Previous Feedback:</strong>
                            <p class="mb-1"><?= nl2br(htmlspecialchars($row['feedback'])) ?></p>
                            <?php if (!empty($row['feedback_file'])): ?>
                                <a href="<?= htmlspecialchars($row['feedback_file']) ?>" target="_blank" class="btn btn-outline-info btn-sm">View Feedback File</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="alert alert-info">No submissions found.</div>
    <?php endif; ?>

</div>

<!-- Bootstrap & jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function toggleFeedbackFile(selectElement) {
        const container = selectElement.closest('form');
        const fileGroup = container.querySelector('.feedback-file-group');
        const starGroup = container.querySelector('.star-rating-group');

        if (selectElement.value === 'rejected' || selectElement.value === 'needs_improvement') {
            fileGroup.style.display = 'block';
        } else {
            fileGroup.style.display = 'none';
        }

        if (selectElement.value === 'accepted_and_published') {
            starGroup.style.display = 'block';
        } else {
            starGroup.style.display = 'none';
            // Reset star rating on hide
            const stars = container.querySelectorAll('input[name="rating"]');
            stars.forEach(star => star.checked = false);
        }
    }
</script>

</body>
</html>
