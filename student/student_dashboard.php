<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION["id"];
$email = htmlspecialchars($_SESSION["email"]);

// Stats queries
$total_submitted = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM submissions WHERE student_id = $user_id"))['total'];

// Submission status counts for pie chart
$status_sql = "SELECT status, COUNT(*) as count FROM submissions WHERE student_id = $user_id GROUP BY status";
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

$latest = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.title, s.status FROM submissions s WHERE student_id = $user_id ORDER BY submission_date DESC LIMIT 1"));

// Assignments with supervisors
$sql = "SELECT ra.assignment_id, ra.title, ra.description, ra.due_date, u.username AS supervisor, u.profile_picture, u.bio 
        FROM researchassignments ra 
        JOIN users u ON ra.created_by = u.id 
        ORDER BY ra.due_date ASC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
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
        .card-header {
            background-color: var(--highlight);
            color: white;
            border-radius: 15px 15px 0 0;
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
        .chart-container {
            max-width: 400px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
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
        .badge {
            font-size: 0.9rem;
            padding: 0.5em 1em;
        }
        .search-form {
            background-color: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
            border-left: 5px solid var(--accent);
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
    <?php include('navbar.php'); ?>

    <div class="container mt-5 mb-5">
        <h2 class="section-title fade-in">Hello, <?= $email ?></h2>

        <!-- Dashboard Stats and Pie Chart -->
        <div class="row mb-5">
            <div class="col-md-6">
                <div class="row text-center">
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-file-upload me-2"></i> Submitted</h5>
                                <h3><?= $total_submitted ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-hourglass-half me-2"></i> Needs Improvement</h5>
                                <h3><?= $status_data['needs_improvement'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-check-circle me-2"></i> Accepted & Published</h5>
                                <h3><?= $status_data['accepted_and_published'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-times-circle me-2"></i> Rejected</h5>
                                <h3><?= $status_data['rejected'] ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h4 class="text-center mb-4">Submission Status Distribution</h4>
                    <canvas id="submissionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Latest Submission -->
        <div class="row mb-5">
            <div class="col">
                <div class="alert alert-info shadow-sm fade-in">
                    <strong>Latest Submission:</strong>
                    <?php if ($latest): ?>
                        <?= htmlspecialchars($latest['title']) ?> — 
                        <span class="badge bg-<?php
                            $status_colors = [
                                'pending' => 'secondary',
                                'under_review' => 'info',
                                'accepted' => 'success',
                                'rejected' => 'danger',
                                'needs_improvement' => 'warning',
                                'accepted_and_published' => 'primary'
                            ];
                            echo $status_colors[$latest['status']];
                        ?>"><?= ucwords(str_replace('_', ' ', $latest['status'])) ?></span>
                    <?php else: ?>
                        No submissions yet.
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Search and Assignments -->
        <div class="section">
            <h2 class="section-title">Assignments for Submission</h2>
            <div class="search-form mb-4">
                <input type="text" id="searchInput" class="form-control" placeholder="Search assignments by title...">
            </div>
            <div class="row" id="assignmentContainer">
                <?php while ($row = mysqli_fetch_assoc($result)):
                    $due_date = strtotime($row['due_date']);
                    $now = time();
                    $is_due = $due_date > $now;
                ?>
                    <div class="col-md-4 mb-4 assignment-card">
                        <div class="card h-100">
                            <?php if (!empty($row['profile_picture'])): ?>
                                <img src="../faculty/<?= htmlspecialchars($row['profile_picture']) ?>" class="card-img-top" alt="Supervisor Picture">
                            <?php else: ?>
                                <img src="../images/placeholder.jpg" class="card-img-top" alt="Placeholder">
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($row['title']) ?></h5>
                                <p class="mb-1"><strong>Supervisor:</strong> <?= htmlspecialchars($row['supervisor']) ?></p>
                                <p class="mb-2 text-muted"><small><?= htmlspecialchars($row['bio'] ?? 'No bio available') ?></small></p>
                                <p class="mb-2"><strong>Due:</strong> <?= date('Y-m-d H:i', $due_date) ?></p>
                                <div class="mt-auto">
                                    <?php if ($is_due): ?>
                                        <a href="submit_assignment.php?assignment_id=<?= $row['assignment_id'] ?>" class="btn btn-primary btn-sm w-100"><i class="fas fa-upload me-2"></i>Submit Assignment</a>
                                    <?php else: ?>
                                        <div class="text-danger text-center fw-bold">Due date passed</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById("searchInput").addEventListener("input", function () {
            const filter = this.value.toLowerCase();
            const cards = document.querySelectorAll(".assignment-card");
            cards.forEach(card => {
                const title = card.querySelector(".card-title").textContent.toLowerCase();
                card.style.display = title.includes(filter) ? "" : "none";
            });
        });

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