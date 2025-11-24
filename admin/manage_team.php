<?php
session_start();

// --- Security & Session Management ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}
$_SESSION['last_activity'] = time();

require_once __DIR__ . '/../db_connect.php';
require_once 'functions.php';

$message = '';

// --- Handle Add Team Member ---
if (isset($_POST['add_member'])) {
    $name = trim($_POST['name']);
    $position = trim($_POST['position']);

    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK && !empty($name) && !empty($position)) {
        $target_dir = "../assets/img/team/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $image_name = time() . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;

        if (resizeAndCropImage($_FILES["image"]["tmp_name"], $target_file, 500, 500)) {
            $stmt = $conn->prepare("INSERT INTO team_members (name, position, image) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $position, $image_name);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Team member added successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Sorry, there was an error processing the image. Please upload a valid JPG, PNG, or GIF.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Name, position, and image are required.</div>';
    }
}

// --- Handle Delete Team Member ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt_select = $conn->prepare("SELECT image FROM team_members WHERE id = ?");
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    if ($row = $result->fetch_assoc()) {
        $image_path = '../assets/img/team/' . $row['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    $stmt_delete = $conn->prepare("DELETE FROM team_members WHERE id = ?");
    $stmt_delete->bind_param("i", $id);
    $stmt_delete->execute();
    header("Location: manage_team.php");
    exit;
}

$team_members = $conn->query("SELECT * FROM team_members ORDER BY display_order ASC, id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Team</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Manage Team</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
    <?php echo $message; ?>

    <!-- Add Member Form -->
    <div class="card my-4">
        <div class="card-header">Add New Team Member</div>
        <div class="card-body">
            <form action="manage_team.php" method="post" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="name">Full Name</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="position">Position / Role</label>
                        <input type="text" name="position" id="position" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="image">Member Picture (will be resized to 500x500)</label>
                    <input type="file" name="image" id="image" class="form-control-file" required>
                </div>
                <button type="submit" name="add_member" class="btn btn-primary">Add Member</button>
            </form>
        </div>
    </div>

    <!-- Existing Members List -->
    <div class="card">
        <div class="card-header">Existing Team Members</div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Picture</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $team_members->fetch_assoc()): ?>
                        <tr>
                            <td><img src="../assets/img/team/<?php echo htmlspecialchars($row['image']); ?>" width="80" alt="<?php echo htmlspecialchars($row['name']); ?>"></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['position']); ?></td>
                            <td>
                                <a href="edit_team_member.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                <a href="manage_team.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this member?')">Delete</a>
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