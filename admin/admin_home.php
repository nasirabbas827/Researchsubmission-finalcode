<?php
session_start();
include('config.php');

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Submission status counts
$status_sql = "SELECT status, COUNT(*) as count FROM Submissions GROUP BY status";
$status_result = mysqli_query($conn, $status_sql);
$status_data = [];
while ($row = mysqli_fetch_assoc($status_result)) {
    $status_data[$row['status']] = $row['count'];
}

// User status counts
$user_status_sql = "SELECT status, COUNT(*) as count FROM Users GROUP BY status";
$user_status_result = mysqli_query($conn, $user_status_sql);
$user_status_data = [];
while ($row = mysqli_fetch_assoc($user_status_result)) {
    $user_status_data[$row['status']] = $row['count'];
}

// Pending evaluations
$pending_sql = "SELECT s.*, u.username AS student_name, ra.title AS assignment_title 
                FROM Submissions s
                JOIN Users u ON s.student_id = u.id
                JOIN ResearchAssignments ra ON s.assignment_id = ra.assignment_id
                WHERE s.status = 'under_review'
                ORDER BY s.submission_date DESC";
$pending_result = mysqli_query($conn, $pending_sql);

// Pending users
$pending_users_sql = "SELECT id, username, email, phone, user_type, status 
                      FROM Users 
                      WHERE status = 'pending'";
$pending_users_result = mysqli_query($conn, $pending_users_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">


    <style>

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
        .btn-action {
            margin-right: 5px;
        }
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
        <h2 class="text-center mb-4 text-primary fw-bold">Admin Dashboard</h2>

        <!-- Submission Status Summary and Pie Chart -->
        <div class="row mb-5">
            <div class="col-md-6">
                <h4 class="text-center mb-3">Submission Status</h4>
                <div class="row">
                    <?php
                    $statuses = [
                        'pending' => ['secondary', '#6c757d'],
                        'under_review' => ['info', '#17a2b8'],
                        'accepted' => ['success', '#28a745'],
                        'accepted_and_published' => ['primary', '#007bff'],
                        'rejected' => ['danger', '#dc3545'],
                        'needs_improvement' => ['warning', '#ffc107']
                    ];
                    foreach ($statuses as $status => $data):
                        $color = $data[0];
                        $hex = $data[1];
                    ?>
                        <div class="col-md-4 mb-3">
                            <div class="card border-<?= $color ?>">
                                <div class="card-body text-center">
                                    <h5 class="card-title"><?= ucwords(str_replace('_', ' ', $status)) ?></h5>
                                    <span class="badge bg-<?= $color ?> p-2">
                                        <?= isset($status_data[$status]) ? $status_data[$status] : 0 ?> Submissions
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <canvas id="submissionChart"></canvas>
                </div>
            </div>
        </div>



        <!-- Pending Evaluations -->
        <div class="mt-5">
            <h4 class="mb-3">Pending Evaluations (Under Review)</h4>
            <?php if (mysqli_num_rows($pending_result) > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Submissions Under Review</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Assignment</th>
                                    <th>Title</th>
                                    <th>Submitted On</th>
                                    <th>File</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($pending_result)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['student_name']) ?></td>
                                        <td><?= htmlspecialchars($row['assignment_title']) ?></td>
                                        <td><?= htmlspecialchars($row['title']) ?></td>
                                        <td><?= date('Y-m-d H:i', strtotime($row['submission_date'])) ?></td>
                                        <td>
                                            <a href="../student/<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No submissions are currently under review.</div>
            <?php endif; ?>
        </div>

        <!-- Pending Users -->
        <div class="mt-5">
            <h4 class="mb-3">Pending User Approvals</h4>
            <?php if (mysqli_num_rows($pending_users_result) > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Users Awaiting Approval</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>User Type</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($pending_users_result)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['username']) ?></td>
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td><?= htmlspecialchars($row['phone']) ?></td>
                                        <td><?= htmlspecialchars($row['user_type']) ?></td>
                                        <td>
                                            <form action="approve_user.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-success btn-action">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger btn-action">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No users are awaiting approval.</div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Submission Status Pie Chart
        const submissionCtx = document.getElementById('submissionChart').getContext('2d');
        new Chart(submissionCtx, {
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
                        <?= isset($status_data['pending']) ? $status_data['pending'] : 0 ?>,
                        <?= isset($status_data['under_review']) ? $status_data['under_review'] : 0 ?>,
                        <?= isset($status_data['accepted']) ? $status_data['accepted'] : 0 ?>,
                        <?= isset($status_data['accepted_and_published']) ? $status_data['accepted_and_published'] : 0 ?>,
                        <?= isset($status_data['rejected']) ? $status_data['rejected'] : 0 ?>,
                        <?= isset($status_data['needs_improvement']) ? $status_data['needs_improvement'] : 0 ?>
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