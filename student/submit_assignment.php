<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

include('config.php');
session_start();

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

 

$user_id = $_SESSION["id"];
$submission_msg = "";

// Get assignment ID
if (!isset($_GET['assignment_id']) || !is_numeric($_GET['assignment_id'])) {
    die("Invalid assignment ID.");
}
$assignment_id = intval($_GET['assignment_id']);

// Fetch assignment + supervisor info including supervisor email
$sql = "SELECT ra.*, u.username AS supervisor_name, u.profile_picture, u.bio, u.id AS supervisor_id, u.email AS supervisor_email
        FROM ResearchAssignments ra 
        JOIN users u ON ra.created_by = u.id 
        WHERE ra.assignment_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $assignment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$assignment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$assignment) {
    die("Assignment not found.");
}
$supervisor_id = $assignment['supervisor_id'];
$supervisor_email = $assignment['supervisor_email'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $abstract = trim($_POST["abstract"]);
    $keywords = trim($_POST["keywords"]);
    $category = trim($_POST["category"]);
    $domain = trim($_POST["domain"]);
    $file_path = "";
    $file_type = "";

    if (!empty($_FILES["research_file"]["name"])) {
        $allowed_types = ['pdf', 'doc', 'docx'];
        $ext = strtolower(pathinfo($_FILES["research_file"]["name"], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed_types)) {
            $upload_dir = "uploads/";
            if (!is_dir($upload_dir)) mkdir($upload_dir);
            $file_path = $upload_dir . time() . "_" . basename($_FILES["research_file"]["name"]);
            if (move_uploaded_file($_FILES["research_file"]["tmp_name"], $file_path)) {
                $file_type = $ext;
            } else {
                $submission_msg = '<div class="alert alert-danger">Failed to upload file.</div>';
            }
        } else {
            $submission_msg = '<div class="alert alert-warning">Invalid file type. Allowed: PDF, DOC, DOCX.</div>';
        }
    }

    if ($file_path && $file_type) {
        $insert_sql = "INSERT INTO Submissions 
            (assignment_id, student_id, supervisor_id, title, abstract, keywords, category, domain, file_path, file_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "iiisssssss", $assignment_id, $user_id, $supervisor_id, $title, $abstract, $keywords, $category, $domain, $file_path, $file_type);

        if (mysqli_stmt_execute($insert_stmt)) {
            $submission_msg = '<div class="alert alert-success">Submission uploaded successfully.</div>';

            // Send notification email to supervisor
            if (!empty($supervisor_email)) {
                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    // $mail->SMTPDebug = 2; // Enable verbose debug output if needed
                    $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // Your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'nasiryt.827@gmail.com';  // SMTP username
        $mail->Password = 'mtvp ruzp aqfu tfxt';           // SMTP password
        $mail->SMTPSecure = 'tls';                    // or 'ssl'
        $mail->Port = 587;                            // TCP port

                    // Recipients
                    $mail->setFrom('nasiryt.827@gmail.com', 'Research Submission System');
                    $mail->addAddress($supervisor_email, $assignment['supervisor_name']);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'New Research Paper Submitted';
                    $mail->Body = "
                        <p>Dear {$assignment['supervisor_name']},</p>
                        <p>A new research paper has been submitted for your assignment titled <strong>" . htmlspecialchars($assignment['title']) . "</strong>.</p>
                        <p><strong>Submission Title:</strong> " . htmlspecialchars($title) . "</p>
                        <p><strong>Submitted By Student ID:</strong> {$user_id}</p>
                        <p>Please login to the system to review and evaluate the submission.</p>
                        <p>Regards,<br>Research Submission System</p>
                    ";

                    $mail->send();
                } catch (Exception $e) {
                    // Log error if needed, but don't break user flow
                    error_log("Mailer Error: {$mail->ErrorInfo}");
                }
            }
        } else {
            $submission_msg = '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
        }
        mysqli_stmt_close($insert_stmt);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit Research Assignment</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Submit Research Assignment</h4>
        </div>
        <div class="card-body p-4">
            <?php echo $submission_msg; ?>
            <div class="row">
                <!-- Submission Form -->
                <div class="col-md-6 border-right">
                    <h5 class="mb-3">Submission Form</h5>
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Research Title:</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Abstract:</label>
                            <textarea name="abstract" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Keywords:</label>
                            <input type="text" name="keywords" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Category:</label>
                            <input type="text" name="category" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Study Program:</label>
                            <input type="text" name="domain" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Upload File (PDF/DOC/DOCX):</label>
                            <input type="file" name="research_file" class="form-control-file" required>
                        </div>
                        <button type="submit" class="btn btn-success btn-block mt-3">Submit Assignment</button>
                    </form>
                </div>

                <!-- Assignment Info -->
                <div class="col-md-6">
                    <h5 class="mb-3">Assignment Information</h5>
                    <h6 class="text-primary"><?= htmlspecialchars($assignment['title']) ?></h6>
                    <p><?= nl2br(htmlspecialchars($assignment['description'])) ?></p>
                    <p><strong>Due Date:</strong> <?= date('Y-m-d H:i', strtotime($assignment['due_date'])) ?></p>
                    <hr>
                    <div class="d-flex align-items-center mt-3">
                        <?php if (!empty($assignment['profile_picture'])): ?>
                            <img src="../faculty/<?= $assignment['profile_picture'] ?>" class="rounded-circle mr-3" width="60" height="60">
                        <?php endif; ?>
                        <div>
                            <h6 class="mb-0"><?= htmlspecialchars($assignment['supervisor_name']) ?></h6>
                            <p class="text-muted mb-0"><?= htmlspecialchars($assignment['bio']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
