<?php
session_start();

// --- Session Security Check ---
$is_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$is_session_active = isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] < 900);

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset();     // unset $_SESSION variable for the run-time 
    session_destroy();   // destroy session data in storage
    $is_logged_in = false; // Force logout
}

// Update activity time *only* if logged in
if ($is_logged_in) {
    $_SESSION['last_activity'] = time();
}

require_once __DIR__ . '/../db_connect.php';
require_once 'config.php'; // Include configuration

// --- Simple Password Protection ---
$error          = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Fetch the stored hash for the given username
    $stmt = $conn->prepare("SELECT password_hash FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $stored_password_hash = $user['password_hash'];

        // Verify the submitted password against the stored hash
        if (password_verify($password, $stored_password_hash)) {
        $_SESSION['loggedin'] = true;
        $_SESSION['last_activity'] = time(); // Set activity time on login
        $_SESSION['username'] = $username; // Store username in session

        // On successful login, redirect to the same page to clear POST data and show the dashboard.
        header("Location: index.php");
        exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Invalid username or password.';
    }
}
if (!$is_logged_in) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Admin Login</h2>
        <form action="index.php" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" class="form-control" required autofocus>
            </div>
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
    // Ensure status keys exist before assigning
    if (array_key_exists($row['status'], $enquiry_stats)) {
    $enquiry_stats[$row['status']] = $row['count'];
    }
}

$visits_result = $conn->query("SELECT SUM(visit_count) as total_visits FROM site_visits");
$total_visits = $visits_result->fetch_assoc()['total_visits'] ?? 0;

$today_visits_result = $conn->query("SELECT visit_count FROM site_visits WHERE visit_date = CURDATE()");
$today_visits = $today_visits_result->fetch_assoc()['visit_count'] ?? 0;

// Fetch inquiry type counts
$inquiry_type_result = $conn->query("SELECT inquiry_type, COUNT(*) as count FROM enquiries GROUP BY inquiry_type");
$inquiry_type_stats = [];
while ($row = $inquiry_type_result->fetch_assoc()) {
    $inquiry_type_stats[$row['inquiry_type']] = $row['count'];
}
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
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Admin Dashboard</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
    <p>Welcome to the admin panel. From here you can manage your website's content.</p>

    <div class="row">
        <!-- Management Links -->
        <div class="col-md-6">
            <!-- Product Management Card -->
            <div class="card mb-4">
                <div class="card-header">Product Management</div>
                <div class="list-group list-group-flush">
                    <a href="manage_products.php" class="list-group-item list-group-item-action">Manage Products</a>
                    <a href="manage_categories.php" class="list-group-item list-group-item-action">Manage Categories</a>
                </div>
            </div>

            <!-- Billing & Quotations Card -->
            <div class="card mb-4">
                <div class="card-header">Billing &amp; Quotations</div>
                <div class="list-group list-group-flush">
                    <a href="create_bill.php" class="list-group-item list-group-item-action">Create New Bill</a>
                    <a href="manage_bills.php" class="list-group-item list-group-item-action">Manage Bills</a>
                    <a href="generate_quote.php" class="list-group-item list-group-item-action" target="_blank">Create Custom Quote</a>
                </div>
            </div>

            <!-- Website Content Card -->
            <div class="card mb-4">
                <div class="card-header">Website Content</div>
                <div class="list-group list-group-flush">
                    <a href="manage_website.php" class="list-group-item list-group-item-action">Manage Website</a>
                </div>
            </div>

            <!-- Team Management Card -->
            <div class="card mb-4">
                <div class="card-header">Team Management</div>
                <div class="list-group list-group-flush">
                    <a href="manage_team.php" class="list-group-item list-group-item-action">Manage Team</a>
                </div>
            </div>

            <!-- Enquiries Card -->
            <div class="card mb-4">
                <div class="card-header">Customer Enquiries</div>
                <div class="list-group list-group-flush">
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
                    <li class="list-group-item d-flex justify-content-between align-items-center">General Contact Form <span class="badge badge-secondary badge-pill"><?php echo $inquiry_type_stats['General Contact Form'] ?? 0; ?></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">Quote Basket Email <span class="badge badge-secondary badge-pill"><?php echo $inquiry_type_stats['Quote Basket Email'] ?? 0; ?></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">Direct WhatsApp Inquiry <span class="badge badge-secondary badge-pill"><?php echo $inquiry_type_stats['Direct WhatsApp Inquiry'] ?? 0; ?></span></li>
                </ul>
            </div>
            <div class="alert alert-info mt-3"><strong>Note:</strong> The site visit counter is basic. For detailed analytics (unique visitors, traffic sources, etc.), integrating a service like Google Analytics is highly recommended.</div>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>