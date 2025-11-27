<?php
session_start();

// --- Security & Session Management ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}
$_SESSION['last_activity'] = time();

require_once __DIR__ . '/../db_connect.php';
require_once 'functions.php'; // Include the image resize function

$message = '';
$member_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($member_id <= 0) {
    header("Location: manage_team.php");
    exit;
}

// --- Handle Update ---
if (isset($_POST['update_member'])) {
    $name = trim($_POST['name']);
    $position = trim($_POST['position']);
    $facebook_url = trim($_POST['facebook_url']);
    $twitter_url = trim($_POST['twitter_url']);
    $instagram_url = trim($_POST['instagram_url']);
    $linkedin_url = trim($_POST['linkedin_url']);
    $current_image = $_POST['current_image'];

    $image_name = $current_image;

    // Check for new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "../assets/img/team/";
        $new_image_name = time() . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $new_image_name;

        if (resizeAndCropImage($_FILES["image"]["tmp_name"], $target_file, 500, 500)) {
            // Delete old image
            if ($current_image && file_exists($target_dir . $current_image)) {
                unlink($target_dir . $current_image);
            }
            $image_name = $new_image_name;
        } else {
            $message = '<div class="alert alert-danger">Error processing new image. Please upload a valid JPG, PNG, or GIF.</div>';
        }
    }

    if (empty($message)) {
        $stmt = $conn->prepare("UPDATE team_members SET name=?, position=?, image=?, facebook_url=?, twitter_url=?, instagram_url=?, linkedin_url=? WHERE id=?");
        $stmt->bind_param("sssssssi", $name, $position, $image_name, $facebook_url, $twitter_url, $instagram_url, $linkedin_url, $member_id);
        if ($stmt->execute()) {
            header("Location: manage_team.php");
            exit;
        } else {
            $message = '<div class="alert alert-danger">Error updating member: ' . $conn->error . '</div>';
        }
    }
}

// --- Fetch Member Data ---
$stmt = $conn->prepare("SELECT * FROM team_members WHERE id = ?");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header("Location: manage_team.php");
    exit;
}
$member = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Team Member</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <a href="manage_team.php" class="btn btn-secondary mb-3">← Back to Manage Team</a>
    <h2>Edit Member: <?php echo htmlspecialchars($member['name']); ?></h2>
    <?php echo $message; ?>

    <div class="card my-4">
        <div class="card-body">
            <form action="edit_team_member.php?id=<?php echo $member_id; ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($member['image']); ?>">
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($member['name']); ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Position</label>
                        <input type="text" name="position" class="form-control" value="<?php echo htmlspecialchars($member['position']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Current Picture</label><br>
                    <img src="../assets/img/team/<?php echo htmlspecialchars($member['image']); ?>" width="150" class="mb-2">
                </div>
                <div class="form-group">
                    <label>Upload New Picture (Optional, will be resized to 500x500)</label>
                    <input type="file" name="image" class="form-control-file">
                </div>

                <hr>
                <h5>Social Media Links (Optional)</h5>
                <div class="form-group">
                    <label>Facebook URL</label>
                    <input type="url" name="facebook_url" class="form-control" value="<?php echo htmlspecialchars($member['facebook_url']); ?>">
                </div>
                <div class="form-group">
                    <label>Twitter URL</label>
                    <input type="url" name="twitter_url" class="form-control" value="<?php echo htmlspecialchars($member['twitter_url']); ?>">
                </div>
                <div class="form-group">
                    <label>Instagram URL</label>
                    <input type="url" name="instagram_url" class="form-control" value="<?php echo htmlspecialchars($member['instagram_url']); ?>">
                </div>
                <div class="form-group">
                    <label>LinkedIn URL</label>
                    <input type="url" name="linkedin_url" class="form-control" value="<?php echo htmlspecialchars($member['linkedin_url']); ?>">
                </div>
                
                <button type="submit" name="update_member" class="btn btn-primary">Update Member</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>