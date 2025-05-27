<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="student_dashboard.php">Research Submission System</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <?php
            if (isset($_SESSION["id"]) && !empty($_SESSION["id"])) {
                echo '<li class="nav-item"><a class="nav-link" href="student_dashboard.php">Home</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="update_profile.php">Profile</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="view_submissions.php">Submissions</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="public_submissions.php">Public Papers</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>';
            } else {
                echo '<li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="./admin/admin_login.php">Admin Login</a></li>';
            }
            ?>
        </ul>
    </div>
</nav>
