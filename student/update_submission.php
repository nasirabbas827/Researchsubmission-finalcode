<?php
session_start();
include('config.php');

if (!isset($_SESSION['id']) || !isset($_POST['submission_id'])) {
    header('location: ../index.php');
    exit;
}

$submission_id = intval($_POST['submission_id']);
$user_id = $_SESSION['id'];
$remarks = trim($_POST['remarks']);
$new_file_path = '';

$allowed_types = ['pdf', 'doc', 'docx'];
$ext = strtolower(pathinfo($_FILES["new_file"]["name"], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed_types)) {
    die("Invalid file type.");
}

$upload_dir = "uploads/";
if (!is_dir($upload_dir)) mkdir($upload_dir);

$new_file_path = $upload_dir . time() . "_" . basename($_FILES["new_file"]["name"]);
move_uploaded_file($_FILES["new_file"]["tmp_name"], $new_file_path);

// Get current submission data
$query = "SELECT file_path, file_type FROM Submissions WHERE submission_id = ? AND student_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $submission_id, $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($res)) {
    // Log old version
    $insert = "INSERT INTO SubmissionVersions (submission_id, file_path, uploaded_by, file_type, remarks) 
               VALUES (?, ?, ?, ?, ?)";
    $stmt2 = mysqli_prepare($conn, $insert);
    mysqli_stmt_bind_param($stmt2, "isiss", $submission_id, $row['file_path'], $user_id, $row['file_type'], $remarks);
    mysqli_stmt_execute($stmt2);

    // Update latest file in Submissions table
    $update = "UPDATE Submissions SET file_path = ?, file_type = ?, submission_date = NOW(), status = 'under_review' WHERE submission_id = ?";
    $stmt3 = mysqli_prepare($conn, $update);
    mysqli_stmt_bind_param($stmt3, "ssi", $new_file_path, $ext, $submission_id);
    mysqli_stmt_execute($stmt3);

    header("location: view_submissions.php?updated=1");
} else {
    echo "Unauthorized or invalid submission.";
}
?>
