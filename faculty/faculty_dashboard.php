<?php
session_start();
include('config.php');

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION["id"];
$email = htmlspecialchars($_SESSION["email"]);

// Get stats
$assignment_count = $submission_count = 0;
$status_counts = [
    'accepted' => 0,
    'rejected' => 0,
    'needs_improvement' => 0,
    'accepted_and_published' => 0,
    'pending' => 0,
    'under_review' => 0
];

// Count assignments
$res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM researchassignments WHERE created_by = $user_id");
if ($res && $row = mysqli_fetch_assoc($res)) $assignment_count = $row['total'];

// Count submissions
$res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM submissions WHERE supervisor_id = $user_id");
if ($res && $row = mysqli_fetch_assoc($res)) $submission_count = $row['total'];

// Count by status
$res = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM submissions WHERE supervisor_id = $user_id GROUP BY status");
while ($row = mysqli_fetch_assoc($res)) {
    $status_counts[$row['status']] = $row['count'];
}

// Recent submissions
$recent_submissions = mysqli_query($conn, "
    SELECT s.title, u.username, s.status, s.submission_date
    FROM submissions s
    JOIN users u ON s.student_id = u.id
    WHERE s.supervisor_id = $user_id
    ORDER BY s.submission_date DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--light) 0%, #e0e7ff 100%);
            color: var(--dark);
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background-color: #007bff;
            color: white;
            border-radius: 10px 10px 0 0;
        }
        .table {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
        }
        .table th {
            background-color: #343a40;
            color: white;
        }
        .chart-container {
            max-width: 400px;
            margin: 0 auto;
        }
        .status-card {
            background-color: #ffffff;
            border-left: 4px solid;
        }
    </style>
</head>
<body>
    <?php include('navbar.php'); ?>

    <div class="container mt-5 mb-5">
        <h2 class="text-center mb-4 text-primary fw-bold">Welcome, <?= $email ?></h2>

        <!-- Stats and Pie Chart -->
        <div class="row mb-5">
            <div class="col-md-6">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h5 class="card-title"><i class="fas fa-tasks"></i> Total Assignments</h5>
                                <h3><?= $assignment_count ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card text-white bg-secondary">
                            <div class="card-body text-center">
                                <h5 class="card-title"><i class="fas fa-file-upload"></i> Total Submissions</h5>
                                <h3><?= $submission_count ?></h3>
                            </div>
                        </div>
                    </div>
                    <?php
                    $statuses = [
                        'pending' => ['#6c757d', 'Secondary'],
                        'under_review' => ['#17a2b8', 'Info'],
                        'accepted' => ['#28a745', 'Success'],
                        'accepted_and_published' => ['#007bff', 'Primary'],
                        'rejected' => ['#dc3545', 'Danger'],
                        'needs_improvement' => ['#ffc107', 'Warning']
                    ];
                    foreach ($status_counts as $status => $count):
                        $color = $statuses[$status][0];
                        $badge = $statuses[$status][1];
                    ?>
                        <div class="col-md-4 mb-3">
                            <div class="card status-card" style="border-left-color: <?= $color ?>;">
                                <div class="card-body text-center">
                                    <h6 class="card-title"><?= ucwords(str_replace("_", " ", $status)) ?></h6>
                                    <span class="badge bg-<?= strtolower($badge) ?> p-2"><?= $count ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-6">
                <h4 class="text-center mb-3">Submission Status Distribution</h4>
                <div class="chart-container">
                    <canvas id="submissionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Submissions -->
        <div class="mt-5">
            <h4 class="mb-3">Recent Submissions</h4>
            <?php if (mysqli_num_rows($recent_submissions) > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Latest Submissions</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Student</th>
                                    <th>Status</th>
                                    <th>Submitted At Soares</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($recent_submissions)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['title']) ?></td>
                                        <td><?= htmlspecialchars($row['username']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= strtolower($statuses[$row['status']][1]) ?>">
                                                <?= ucwords(str_replace("_", " ", $row['status'])) ?>
                                            </span>
                                        </td>
                                        <td><?= date('Y-m-d H:i', strtotime($row['submission_date'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No submissions found.</div>
            <?php endif; ?>
        </div>
    </div>

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
                    'Accepted and Published',
                    'Rejected',
                    'Needs Improvement'
                ],
                datasets: [{
                    data: [
                        <?= $status_counts['pending'] ?>,
                        <?= $status_counts['under_review'] ?>,
                        <?= $status_counts['accepted'] ?>,
                        <?= $status_counts['accepted_and_published'] ?>,
                        <?= $status_counts['rejected'] ?>,
                        <?= $status_counts['needs_improvement'] ?>
                    ],
                    backgroundColor: [
                        '#6c757d',
                        '#17a2b8',
                        '#28a745',
                        '#007bff',
                        '#dc3545',
                        '#ffc107'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>