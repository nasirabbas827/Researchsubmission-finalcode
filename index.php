<?php
include('config.php');

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
    <title>Research Submission System</title>
       <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
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
        .hero-section {
            height: 600px;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('./images/hotel.jpg') center/cover no-repeat;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            border-radius: 0 0 30px 30px;
            animation: fadeIn 1.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .hero-section .btn-primary {
            background-color: var(--accent);
            border-color: var(--accent);
            padding: 12px 30px;
            font-size: 1.2rem;
            transition: transform 0.3s, background-color 0.3s;
        }
        .hero-section .btn-primary:hover {
            background-color: var(--highlight);
            border-color: var(--highlight);
            transform: scale(1.05);
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
        .search-form {
            background-color: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
            margin-bottom: 40px;
            border-left: 5px solid var(--accent);
        }
        .section {
            padding: 80px 0;
        }
        .section-title {
            color: var(--highlight);
            margin-bottom: 40px;
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
            left: 50%;
            transform: translateX(-50%);
        }
        .feature-icon {
            font-size: 3em;
            color: var(--accent);
            margin-bottom: 20px;
            transition: color 0.3s;
        }
        .card:hover .feature-icon {
            color: var(--highlight);
        }
        .chart-container {
            max-width: 450px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        footer {
            background-color: var(--dark);
            color: white;
            padding: 30px 0;
            border-top: 4px solid var(--accent);
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
    </style>
</head>
<body>
    <?php include('navbar.php'); ?>

    <!-- Hero Section -->
    <div class="hero-section">
        <h1 class="display-4 fw-bold">Research Submission System</h1>
        <p class="lead mb-4">Discover, Share, and Explore Cutting-Edge Research</p>
        <?php if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true): ?>
            <a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt me-2"></i>Login to Explore</a>
        <?php else: ?>
            <a href="dashboard.php" class="btn btn-primary"><i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard</a>
        <?php endif; ?>
    </div>

    <!-- About Section -->
    <div class="section bg-light">
        <div class="container">
            <h2 class="text-center section-title">About the Platform</h2>
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="lead">The Research Submission System is a dynamic platform designed to streamline academic research workflows. It empowers students, faculty, and administrators to collaborate seamlessly, submit research papers, and showcase published works to a global audience.</p>
                    <p>Our mission is to foster innovation, promote knowledge-sharing, and provide a robust, user-friendly interface for managing the entire research lifecycle—from submission to publication.</p>
                </div>
                <div class="col-md-6">
                    <div class="chart-container">
                        <h4 class="text-center mb-4">Submission Status Distribution</h4>
                        <canvas id="submissionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="section">
        <div class="container">
            <h2 class="text-center section-title">Key Features</h2>
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-upload feature-icon"></i>
                            <h5 class="card-title">Seamless Submission</h5>
                            <p class="card-text">Effortlessly upload research papers in PDF, DOC, or DOCX formats with our intuitive submission system.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-search feature-icon"></i>
                            <h5 class="card-title">Powerful Search</h5>
                            <p class="card-text">Explore published research by filtering on student ID, study program, category, date, or author name.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-users feature-icon"></i>
                            <h5 class="card-title">Collaborative Workflow</h5>
                            <p class="card-text">Connect with students, supervisors, and admins to streamline reviews and approvals.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- How It Works Section -->
    <div class="section bg-light">
        <div class="container">
            <h2 class="text-center section-title">How It Works</h2>
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-user-plus feature-icon"></i>
                            <h5 class="card-title">1. Register & Login</h5>
                            <p class="card-text">Sign up or log in as a student, faculty, or admin to access the platform’s features.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-file-upload feature-icon"></i>
                            <h5 class="card-title">2. Submit Research</h5>
                            <p class="card-text">Upload your research, provide metadata, and submit for faculty and admin review.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-check-circle feature-icon"></i>
                            <h5 class="card-title">3. Review & Publish</h5>
                            <p class="card-text">Receive feedback, make revisions, and see your research published for the community.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Published Submissions -->
    <div class="section">
        <div class="container">
            <h2 class="text-center section-title">Search Published Research</h2>
            <form method="get" class="search-form">
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

            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="row">
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($row['title']) ?></h5>
                                    <p class="card-text">
                                        <strong>Author:</strong> <?= htmlspecialchars($row['student_name']) ?><br>
                                        <strong>Study Program:</strong> <?= htmlspecialchars($row['domain']) ?><br>
                                        <strong>Category:</strong> <?= htmlspecialchars($row['category']) ?><br>
                                        <strong>Submitted On:</strong> <?= date('Y-m-d', strtotime($row['submission_date'])) ?>
                                    </p>
                                    <p class="card-text"><?= nl2br(htmlspecialchars(substr($row['abstract'], 0, 100))) ?>...</p>
                                </div>
                                <div class="card-footer bg-light">
                                    <a href="student/<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="btn btn-sm btn-success"><i class="fas fa-download me-2"></i>Download</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">No submissions found for the given filters.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center">
        <div class="container">
            <p class="mb-0">© 2025 Research Submission System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Submission Status Pie Chart
        const ctx = document.getElementById('submissionChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: [
                    'Pending',
                    'Under Review',
                    'Accepted',
                    'Rejected',
                    'Needs Improvement',
                    'Accepted and Published'
                ],
                datasets: [{
                    data: [
                        <?= $status_data['pending'] ?>,
                        <?= $status_data['under_review'] ?>,
                        <?= $status_data['accepted'] ?>,
                        <?= $status_data['rejected'] ?>,
                        <?= $status_data['needs_improvement'] ?>,
                        <?= $status_data['accepted_and_published'] ?>
                    ],
                    backgroundColor: [
  '#6c757d',  // --secondary (gray)
  '#0dcaf0',  // --info (cyan)
  '#198754',  // --success (green)
  '#dc3545',  // --danger (red)
  '#ffc107',  // --warning (yellow)
  '#0d6efd'   // --primary (blue)
],

                    borderColor: 'rgba(255,255,255,0.8)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 14,
                                family: 'Segoe UI'
                            },
                            color: 'var(--dark)'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'var(--dark)',
                        titleFont: { size: 16 },
                        bodyFont: { size: 14 }
                    }
                }
            }
        });
    </script>
</body>
</html>