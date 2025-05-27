<?php
session_start();
include('config.php');

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Initialize filters
$filters = [];
$params = [];
$search_sql = "SELECT s.*, u.username AS student_name 
               FROM submissions s 
               JOIN users u ON s.student_id = u.id 
               WHERE s.status = 'accepted_and_published'";

// Apply filters
if (!empty($_GET['student_id'])) {
    $filters[] = "s.student_id = ?";
    $params[] = $_GET['student_id'];
}
if (!empty($_GET['study_program'])) { // Actually filtering s.domain
    $filters[] = "s.domain LIKE ?";
    $params[] = "%" . $_GET['study_program'] . "%";
}
if (!empty($_GET['category'])) {
    $filters[] = "s.category LIKE ?";
    $params[] = "%" . $_GET['category'] . "%";
}
if (!empty($_GET['date'])) {
    $filters[] = "DATE(s.submission_date) = ?";
    $params[] = $_GET['date'];
}
if (!empty($_GET['author'])) {
    $filters[] = "u.username LIKE ?";
    $params[] = "%" . $_GET['author'] . "%";
}

if (!empty($filters)) {
    $search_sql .= " AND " . implode(" AND ", $filters);
}

$stmt = mysqli_prepare($conn, $search_sql);
if (!empty($params)) {
    $types = str_repeat("s", count($params));
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Submission status counts for pie chart
$status_sql = "SELECT status, COUNT(*) as count FROM submissions GROUP BY status";
$status_result = mysqli_query($conn, $status_sql);
$status_data = [
    'pending' => 0,
    'under_review' => 0,
    'accepted' => 0,
    'rejected' => 0,
    'needs_improvement' => 0,
    'accepted_and_published' => 0
];
while ($row = mysqli_fetch_assoc($status_result)) {
    $status_data[$row['status']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Published Research Submissions</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary: #007bff;
            --secondary: #6c757d;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
            --accent: #ff6f61; /* Coral for buttons and highlights */
            --highlight: #6f42c1; /* Purple for secondary accents */
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--light) 0%, #e0e7ff 100%);
            color: var(--dark);
        }
        .navbar {
            background-color: var(--dark);
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }
        .navbar-brand, .nav-link {
            color: white !important;
        }
        .navbar-brand:hover, .nav-link:hover {
            color: var(--accent) !important;
        }
        .container {
            max-width: 1200px;
        }
        .section-title {
            color: var(--highlight);
            margin-bottom: 30px;
            font-weight: 700;
            position: relative;
        }
        .section-title::after {
            content: '';
            width: 60px;
            height: 4px;
            background-color: var(--accent);
            position: absolute;
            bottom: -10px;
            left: 0;
        }
        .search-form {
            background-color: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
            border-left: 5px solid var(--accent);
            margin-bottom: 40px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
            transition: transform 0.3s, box-shadow 0.3s;
            background-color: white;
        }
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .card-img-top {
            height: 220px;
            object-fit: cover;
            border-radius: 15px 15px 0 0;
        }
        .chart-container {
            max-width: 450px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
            margin-bottom: 40px;
        }
        .btn-primary {
            background-color: var(--accent);
            border-color: var(--accent);
        }
        .btn-primary:hover {
            background-color: var(--highlight);
            border-color: var(--highlight);
        }
        .btn-success {
            background-color: var(--success);
            border-color: var(--success);
        }
        .btn-success:hover {
            background-color: var(--highlight);
            border-color: var(--highlight);
        }
        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 8px rgba(255, 111, 97, 0.3);
        }
        .alert {
            border-radius: 10px;
            border-left: 5px solid var(--accent);
        }
        .star-rating {
            color: var(--warning);
            font-size: 1.2rem;
        }
        .star-rating .fa-star-half-alt {
            color: var(--warning);
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .fade-in {
            animation: fadeIn 1s ease-in-out;
        }
    </style>
</head>
<body>
    <?php include('admin_navbar.php'); ?>

    <div class="container mt-5 mb-5">
        <h2 class="section-title fade-in">Explore Published Research</h2>

        <!-- Search Form -->
        <div class="search-form fade-in">
            <form method="get">
                <div class="row g-3">
                    <div class="col-md-2">
                        <input type="text" name="student_id" class="form-control" placeholder="Student ID" value="<?= htmlspecialchars($_GET['student_id'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="study_program" class="form-control" placeholder="Study Program" value="<?= htmlspecialchars($_GET['study_program'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="category" class="form-control" placeholder="Category" value="<?= htmlspecialchars($_GET['category'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($_GET['date'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="author" class="form-control" placeholder="Author Name" value="<?= htmlspecialchars($_GET['author'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-2"></i>Search</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Submission Cards -->
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="row">
                <?php while ($row = mysqli_fetch_assoc($result)):
                    // Convert rating to stars (assuming rating is 0–5)
                    $rating = floatval($row['rating']);
                    $full_stars = floor($rating);
                    $half_star = ($rating - $full_stars) >= 0.5 ? 1 : 0;
                    $empty_stars = 5 - $full_stars - $half_star;
                ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 fade-in">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($row['title']) ?></h5>
                                <p class="card-text">
                                    <strong>Author:</strong> <?= htmlspecialchars($row['student_name']) ?><br>
                                    <strong>Study Program:</strong> <?= htmlspecialchars($row['domain']) ?><br>
                                    <strong>Category:</strong> <?= htmlspecialchars($row['category']) ?><br>
                                    <strong>Ratings:</strong>
                                    <span class="star-rating">
                                        <?php for ($i = 0; $i < $full_stars; $i++): ?>
                                            <i class="fas fa-star"></i>
                                        <?php endfor; ?>
                                        <?php if ($half_star): ?>
                                            <i class="fas fa-star-half-alt"></i>
                                        <?php endif; ?>
                                        <?php for ($i = 0; $i < $empty_stars; $i++): ?>
                                            <i class="far fa-star"></i>
                                        <?php endfor; ?>
                                    </span><br>
                                    <strong>Submitted On:</strong> <?= date('Y-m-d', strtotime($row['submission_date'])) ?>
                                </p>
                                <p class="card-text"><?= nl2br(htmlspecialchars(substr($row['abstract'], 0, 100))) ?>...</p>
                            </div>
                            <div class="card-footer bg-light">
                                <a href="../student/<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="btn btn-sm btn-success"><i class="fas fa-download me-2"></i>Download</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center fade-in">No submissions found for the given filters.</div>
        <?php endif; ?>
    </div>


</body>
</html>