<?php
session_start();
// --- Session Timeout (15 minutes) ---
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset(); session_destroy();
    header("Location: index.php"); // Force redirect to login page
    exit;
}

require_once 'functions.php'; // Include the new functions file
require_once __DIR__ . '/../db_connect.php'; // Correct path to root db_connect.php

// --- Check if admin is logged in ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

// If we've reached here, the user is logged in. Now we can update their activity time.
$_SESSION['last_activity'] = time();
$message = '';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header("Location: manage_products.php"); // Redirect if no valid ID is provided
    exit;
}

// --- Handle Product Update ---
if (isset($_POST['update_product'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $category = $conn->real_escape_string($_POST['category']);
    $description = $conn->real_escape_string($_POST['description']);
    $current_image = $conn->real_escape_string($_POST['current_image']);

    $image_name = $current_image;

    // --- Check if a new image is uploaded ---
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "../assets/img/portfolio/";
        $new_image_name = time() . '_' . basename($_FILES["image"]["name"]); // Create a unique name
        $target_file = $target_dir . $new_image_name;

        if (resizeAndCropImage($_FILES["image"]["tmp_name"], $target_file)) {
            // Delete the old image if it's different from the new one
            if ($current_image && $current_image != $new_image_name) {
                $old_image_path = $target_dir . $current_image;
                if (file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
            }
            $image_name = $new_image_name;
        } else {
            $message = '<div class="alert alert-danger">Sorry, there was an error processing your new image. Please upload a valid JPG, PNG, or GIF.</div>';
        }
    }

    if (empty($message)) {
        $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, description = ?, image = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $category, $description, $image_name, $product_id);
        if ($stmt->execute()) {
            // Redirect to the main admin page to see the changes
            header("Location: manage_products.php?update=success");
            exit;
        } else {
            $message = '<div class="alert alert-danger">Error updating product: ' . $conn->error . '</div>';
        }
    }
}

// --- Fetch Product Data for the Form ---
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
} else {
    // If product not found, redirect
    header("Location: manage_products.php");
    $conn->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Edit Product</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
    <?php echo $message; ?>

    <div class="card my-4">
        <div class="card-header">Editing "<?php echo htmlspecialchars($product['name']); ?>"</div>
        <div class="card-body">
            <form action="edit_product.php?id=<?php echo $product_id; ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($product['image']); ?>">
                
                <div class="form-group"><label>Product Name:</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required></div>
                <div class="form-group">
                    <label for="category">Category:</label>
                    <select name="category" id="category" class="form-control" required>
                        <?php 
                        $categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
                        while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($cat['filter_class']); ?>" <?php if ($product['category'] == $cat['filter_class']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group"><label>Description:</label><textarea name="description" class="form-control"><?php echo htmlspecialchars($product['description']); ?></textarea></div>
                
                <div class="form-group">
                    <label>Current Image:</label><br>
                    <img src="../assets/img/portfolio/<?php echo htmlspecialchars($product['image']); ?>" width="150" alt="" class="mb-2">
                </div>
                <div class="form-group"><label>Upload New Image (optional, will be resized to 800x600):</label><input type="file" name="image" class="form-control-file"></div>
                
                <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                <a href="manage_products.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>