<?php
session_start();

// --- Session Security Check ---
$is_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset();     // unset $_SESSION variable for the run-time 
    session_destroy();   // destroy session data in storage
    $is_logged_in = false; // Force logout
}

require_once 'config.php'; // Include configuration
require_once __DIR__ . '/../db_connect.php';

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
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <h2>Admin Login</h2>
                    <div id="auth-error" class="alert alert-danger" style="display:none;"></div>
                    <form id="login-form">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" class="form-control" required autofocus>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Login with Supabase</button>
                    </form>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
        <script>
            const SUPABASE_URL = <?php echo json_encode(SUPABASE_URL); ?>;
            const SUPABASE_ANON_KEY = <?php echo json_encode(SUPABASE_ANON_KEY); ?>;
            const sbClient = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

            document.getElementById('login-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                const errorDiv = document.getElementById('auth-error');

                const { data, error } = await sbClient.auth.signInWithPassword({
                    email: email,
                    password: password,
                });

                if (error) {
                    errorDiv.textContent = error.message;
                    errorDiv.style.display = 'block';
                } else {
                    // Sync with PHP Session
                    const formData = new FormData();
                    formData.append('access_token', data.session.access_token);
                    
                    fetch('set_session.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => {
                        if (res.ok) {
                            window.location.reload();
                        } else {
                            alert('Supabase Auth worked, but PHP failed to create a session. Ensure set_session.php exists.');
                        }
                    })
                    .catch(err => {
                        alert('Network error while syncing session: ' + err.message);
                    });
                }
            });
        </script>
    </body>
    </html>
    <?php
        exit;
    }

    // Update activity time *only* if logged in
    $_SESSION['last_activity'] = time();

    // --- Fetch stats for dashboard ---
    $enquiry_stats_raw = $conn->query("SELECT status, COUNT(*) as count FROM enquiries GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    $enquiry_stats = ['New' => 0, 'Contacted' => 0, 'Quoted' => 0, 'Closed' => 0];
    foreach ($enquiry_stats_raw as $row) {
        // Ensure status keys exist before assigning
        if (array_key_exists($row['status'], $enquiry_stats)) {
            $enquiry_stats[$row['status']] = $row['count'];
        }
    }

    $total_visits_row = $conn->query("SELECT SUM(visit_count) as total_visits FROM site_visits")->fetch(PDO::FETCH_ASSOC);
    $total_visits = $total_visits_row['total_visits'] ?? 0;

    $today_visits_row = $conn->query("SELECT visit_count FROM site_visits WHERE visit_date = CURRENT_DATE")->fetch(PDO::FETCH_ASSOC);
    $today_visits = ($today_visits_row && isset($today_visits_row['visit_count'])) ? $today_visits_row['visit_count'] : 0;

    // Fetch inquiry type counts
    $inquiry_type_raw = $conn->query("SELECT inquiry_type, COUNT(*) as count FROM enquiries GROUP BY inquiry_type")->fetchAll(PDO::FETCH_ASSOC);
    $inquiry_type_stats = [];
    foreach ($inquiry_type_raw as $row) {
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
                        <a href="manage_catalog.php" class="list-group-item list-group-item-action">Manage Catalog (PDF)</a>
                    </div>
                </div>

                <!-- Team Management Card -->
                <div class="card mb-4">
                    <div class="card-header">Team Management</div>
                    <div class="list-group list-group-flush">
                        <a href="manage_team.php" class="list-group-item list-group-item-action">Manage Team</a>
                    </div>
                </div>

                <!-- Employee Management Card -->
                <div class="card mb-4">
                    <div class="card-header">Human Resources</div>
                    <div class="list-group list-group-flush">
                        <a href="manage_employees.php" class="list-group-item list-group-item-action">Manage Employees</a>
                        <a href="manage_salaries.php" class="list-group-item list-group-item-action">Manage Salaries</a>
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
<?php $conn = null; ?>