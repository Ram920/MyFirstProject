<?php
session_start();

// --- Session Security Check ---
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) { // 900 seconds = 15 minutes
    session_unset();
    session_destroy();
}
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}
$_SESSION['last_activity'] = time();

require_once '../db_connect.php';
$page_title = "Manage Catalog";
$message = '';

// --- Handle Delete Action ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete') {
    // First, get the file path from the database
    $stmt_get = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'catalog_pdf_url'");
    $stmt_get->execute();
    $result = $stmt_get->get_result();
    if ($row = $result->fetch_assoc()) {
        $file_path = '../' . $row['setting_value'];
        // Delete the file from the server if it exists
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    $stmt_get->close();

    // Then, delete the record from the database
    $stmt_delete = $conn->prepare("DELETE FROM site_settings WHERE setting_key = 'catalog_pdf_url'");
    $stmt_delete->execute();
    $stmt_delete->close();
    $message = '<div class="alert alert-success">The catalog has been successfully deleted.</div>';
}
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["catalog_pdf"])) { // --- Handle File Upload ---
    
    // Check if a file was selected
    if ($_FILES["catalog_pdf"]["error"] == UPLOAD_ERR_NO_FILE) {
        $message = '<div class="alert alert-warning">Please select a PDF file to upload.</div>';
    } else if ($_FILES["catalog_pdf"]["error"] != UPLOAD_ERR_OK) {
        $message = '<div class="alert alert-danger">An error occurred during file upload. Please try again. Error code: ' . $_FILES["catalog_pdf"]["error"] . '</div>';
    } else {
        $target_dir = "../assets/catalog/";
        // Ensure the target directory exists
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $target_file = $target_dir . "NUSH_MECHANICAL_Product_Catalog.pdf"; // Static filename
        $file_type = strtolower(pathinfo($_FILES["catalog_pdf"]["name"], PATHINFO_EXTENSION));

        // Check if file is a PDF
        if ($file_type != "pdf") {
            $message = '<div class="alert alert-danger">Sorry, only PDF files are allowed.</div>';
        } else {
            // Try to upload file
            if (move_uploaded_file($_FILES["catalog_pdf"]["tmp_name"], $target_file)) {
                $file_url = "assets/catalog/" . basename($target_file);

                // Save the file path to the database
                $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('catalog_pdf_url', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("ss", $file_url, $file_url);
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">The catalog has been uploaded successfully.</div>';
                } else {
                    $message = '<div class="alert alert-danger">File uploaded, but failed to update the database.</div>';
                }
                $stmt->close();
            } else {
                $message = '<div class="alert alert-danger">Sorry, there was an error uploading your file.</div>';
            }
        }
    }
}

// Fetch the current catalog URL to display
$current_catalog_url = '';
$stmt_get = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
$setting_key = 'catalog_pdf_url';
$stmt_get->bind_param("s", $setting_key);
$stmt_get->execute();
$result = $stmt_get->get_result();
if ($row = $result->fetch_assoc()) {
    $current_catalog_url = $row['setting_value'];
}
$stmt_get->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Manage Website Catalog</h2>
        <div>
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
    <p>Here you can upload a new PDF to serve as the downloadable catalog on your website, or remove the existing one.</p>

    <?php echo $message; ?>

    <div class="row">
        <!-- Upload Form -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Upload New Catalog
                </div>
                <div class="card-body">
                    <form action="manage_catalog.php" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="catalog_pdf">Select PDF file to upload:</label>
                            <input type="file" class="form-control-file" name="catalog_pdf" id="catalog_pdf" accept=".pdf" required>
                            <small class="form-text text-muted">The new file will be named "NUSH_MECHANICAL_Product_Catalog.pdf" and will overwrite the existing one.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Upload Catalog</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Current Catalog Status -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Current Catalog
                </div>
                <div class="card-body">
                    <?php if (!empty($current_catalog_url)): ?>
                        <p>A catalog is currently available for download on the website.</p>
                        <a href="../<?php echo htmlspecialchars($current_catalog_url); ?>" class="btn btn-info" target="_blank">View Current Catalog</a>
                        <form action="manage_catalog.php" method="post" class="d-inline ml-2" onsubmit="return confirm('Are you sure you want to delete the catalog? This cannot be undone.');">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn btn-danger">Delete Catalog</button>
                        </form>
                    <?php else: ?>
                        <p class="text-muted">No catalog has been uploaded yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
<?php $conn->close(); ?>

```
**Note:** You will also need to add a link to this new `manage_catalog.php` page in your admin navigation (e.g., in `admin/includes/header.php`) so you can access it easily.

### Step 2: Add the Catalog Section to Your Website

Now, let's modify your `index.php` file to display the new "Our Catalog" section. This will fetch the catalog link from the database and show a download button if a catalog has been uploaded.

```diff
--- a/Users/ramnarayansharma/github_nush/MyFirstProject/index.php
+++ b/Users/ramnarayansharma/github_nush/MyFirstProject/index.php
@@ -28,6 +28,9 @@
 
   $team_members_result = $conn->query("SELECT * FROM team_members ORDER BY display_order ASC, id ASC");
 
+  $catalog_pdf_result = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'catalog_pdf_url'");
+  $catalog_pdf_url = $catalog_pdf_result->fetch_assoc()['setting_value'] ?? '';
+
   // Do NOT close the connection here. It will be closed later in the script.
 ?>
 <!DOCTYPE html>
@@ -350,6 +353,24 @@
 
       </div>
     </section><!-- End Services Section -->
+
+    <!-- ======= Catalog Section ======= -->
+    <?php if (!empty($catalog_pdf_url)): ?>
+    <section id="catalog" class="catalog">
+      <div class="container" data-aos="fade-up">
+
+        <div class="section-title">
+          <h2>Catalog</h2>
+          <p>Download Our Catalog</p>
+        </div>
+
+        <div class="row">
+          <div class="col-md-12 text-center">
+            <p>Get detailed information about all our products and services by downloading our complete catalog.</p>
+            <a href="<?php echo htmlspecialchars($catalog_pdf_url); ?>" class="btn-get-started" download>Download Catalog (PDF)</a>
+          </div>
+        </div>
+
+      </div>
+    </section>
+    <?php endif; ?>
+    <!-- End Catalog Section -->
 
     <!-- ======= Testimonials Section ======= -->
     <section id="testimonials" class="testimonials">

```

After applying these changes and uploading a PDF through the new admin page, a "Download Our Catalog" section will appear on your website.

Let me know if you have any other questions!<!--
[PROMPT_SUGGESTION]How can I add the new "Catalog" section to the main navigation menu?[/PROMPT_SUGGESTION]
[PROMPT_SUGGESTION]Can you help me secure the admin folder?[/PROMPT_SUGGESTION]
-->