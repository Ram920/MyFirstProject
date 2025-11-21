<?php
session_start();

// --- Session Security Check ---
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset(); 
    session_destroy();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

$_SESSION['last_activity'] = time();

require_once __DIR__ . '/../db_connect.php';
require_once 'config.php'; // Include configuration

$message = '';
// Handle Add Category
if (isset($_POST['add_category'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $filter_class = $conn->real_escape_string($_POST['filter_class']);
    if (!empty($name) && !empty($filter_class)) {
        $stmt = $conn->prepare("INSERT INTO categories (name, filter_class) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $filter_class);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Category added successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
    }
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_categories.php");
    exit;
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Manage Categories</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
    <?php echo $message; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card my-4">
                <div class="card-header">Add New Category</div>
                <div class="card-body">
                    <form action="manage_categories.php" method="post">
                        <div class="form-group">
                            <label for="name">Category Name (e.g., "Hydraulic SPM")</label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="filter_class">Filter Class (e.g., "filter-spm")</label>
                            <input type="text" name="filter_class" id="filter_class" class="form-control" required>
                        </div>
                        <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card my-4">
                <div class="card-header">Existing Categories</div>
                <div class="card-body">
                    <table class="table">
                        <thead><tr><th>Name</th><th>Filter Class</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php while ($row = $categories->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['filter_class']); ?></td>
                                    <td><a href="manage_categories.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
