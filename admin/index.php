<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php'; // Include configuration

// --- Simple Password Protection ---
$admin_password = ADMIN_PASSWORD; // Get from config.php
$error          = '';

if (isset($_POST['login'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['loggedin'] = true;
    } else {
        $error = 'Invalid password!';
    }
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Admin Login</h2>
        <form method="post">
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
            <button type="submit" name="login" class="btn btn-primary">Login</button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}

// --- Fetch stats for dashboard ---
$enquiries_result = $conn->query("SELECT status, COUNT(*) as count FROM enquiries GROUP BY status");
$enquiry_stats = ['New' => 0, 'Contacted' => 0, 'Quoted' => 0, 'Closed' => 0];
while ($row = $enquiries_result->fetch_assoc()) {
    $enquiry_stats[$row['status']] = $row['count'];
}

$visits_result = $conn->query("SELECT SUM(visit_count) as total_visits FROM site_visits");
$total_visits = $visits_result->fetch_assoc()['total_visits'] ?? 0;

$today_visits_result = $conn->query("SELECT visit_count FROM site_visits WHERE visit_date = CURDATE()");
$today_visits = $today_visits_result->fetch_assoc()['visit_count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Admin Dashboard</h2>
    <p>Welcome to the admin panel. From here you can manage your website's content.</p>

    <div class="row">
        <!-- Management Links -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Management</div>
                <div class="list-group list-group-flush">
                    <a href="manage_products.php" class="list-group-item list-group-item-action">Manage Products</a>
                    <a href="manage_categories.php" class="list-group-item list-group-item-action">Manage Categories</a>
                    <a href="view_enquiries.php" class="list-group-item list-group-item-action">View Enquiries</a>
                </div>
            </div>
        </div>
        <!-- Stats -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Statistics</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">Today's Site Visits <span class="badge badge-primary badge-pill"><?php echo $today_visits; ?></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">Total Site Visits <span class="badge badge-primary badge-pill"><?php echo $total_visits; ?></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">New Enquiries <span class="badge badge-info badge-pill"><?php echo $enquiry_stats['New']; ?></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">Closed Enquiries <span class="badge badge-success badge-pill"><?php echo $enquiry_stats['Closed']; ?></span></li>
                </ul>
            </div>
            <div class="alert alert-info mt-3"><strong>Note:</strong> The site visit counter is basic. For detailed analytics (unique visitors, traffic sources, etc.), integrating a service like Google Analytics is highly recommended.</div>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>