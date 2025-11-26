<?php
session_start();

// --- Security & Session Management ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset(); session_destroy();
    header("Location: index.php");
    exit;
}
$_SESSION['last_activity'] = time();

require_once __DIR__ . '/../db_connect.php';
require_once 'functions.php';

$message = '';

// --- Handle YouTube URL Update ---
if (isset($_POST['update_youtube_url'])) {
    $youtube_url = trim($_POST['youtube_url']);
    $stmt = $conn->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'youtube_video_url'");
    $stmt->bind_param("s", $youtube_url);
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">YouTube URL updated successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error updating YouTube URL.</div>';
    }
}

// --- Handle Add Client Logo ---
if (isset($_POST['add_client'])) {
    $client_name = trim($_POST['name']);
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK && !empty($client_name)) {
        $target_dir = "../assets/img/clients/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $image_name = time() . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;

        // Simple image validation
        $img_info = getimagesize($_FILES["image"]["tmp_name"]);
        if ($img_info) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $stmt = $conn->prepare("INSERT INTO clients (name, image) VALUES (?, ?)");
                $stmt->bind_param("ss", $client_name, $image_name);
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">Client logo added successfully!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
                }
            } else {
                 $message = '<div class="alert alert-danger">Sorry, there was an error uploading your file.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Invalid image file.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Client name and image are required.</div>';
    }
}

// --- Handle Delete Client Logo ---
if (isset($_GET['delete_client'])) {
    $id = (int)$_GET['delete_client'];
    $stmt = $conn->prepare("SELECT image FROM clients WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $image_path = '../assets/img/clients/' . $row['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    $stmt_delete = $conn->prepare("DELETE FROM clients WHERE id = ?");
    $stmt_delete->bind_param("i", $id);
    $stmt_delete->execute();
    header("Location: manage_website.php");
    exit;
}

// --- Fetch current data ---
$youtube_url_result = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'youtube_video_url'");
$youtube_url = $youtube_url_result->fetch_assoc()['setting_value'] ?? '';

$clients = $conn->query("SELECT * FROM clients ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Website Content</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Manage Website Content</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
    <?php echo $message; ?>

    <!-- YouTube URL Form -->
    <div class="card my-4">
        <div class="card-header">Manage Homepage YouTube Video</div>
        <div class="card-body">
            <form action="manage_website.php" method="post">
                <div class="form-group">
                    <label for="youtube_url">YouTube Video URL</label>
                    <input type="url" name="youtube_url" id="youtube_url" class="form-control" value="<?php echo htmlspecialchars($youtube_url); ?>" required>
                </div>
                <button type="submit" name="update_youtube_url" class="btn btn-primary">Update URL</button>
            </form>
        </div>
    </div>

    <!-- Client Logos Management -->
    <div class="card my-4">
        <div class="card-header">Manage Client Logos</div>
        <div class="card-body">
            <form action="manage_website.php" method="post" enctype="multipart/form-data" class="mb-4">
                <div class="form-group">
                    <label for="name">Client Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g., Client Corp" required>
                </div>
                <div class="form-group">
                    <label for="image">Client Logo Image</label>
                    <input type="file" name="image" class="form-control-file" required>
                </div>
                <button type="submit" name="add_client" class="btn btn-primary">Add Client Logo</button>
            </form>
        </div>
        <div class="card-footer">
            <h5>Existing Client Logos</h5>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th>Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $clients->fetch_assoc()): ?>
                        <tr>
                            <td class="align-middle"><img src="../assets/img/clients/<?php echo htmlspecialchars($row['image']); ?>" class="img-fluid" style="max-width: 100px; max-height: 50px; object-fit: contain;" alt="<?php echo htmlspecialchars($row['name']); ?>"></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td>
                                <a href="manage_website.php?delete_client=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this logo?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>