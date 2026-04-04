<?php
session_start();
// --- Session Security Check ---
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset(); 
    session_destroy();
}

// --- Check if admin is logged in ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

$_SESSION['last_activity'] = time();

require_once 'functions.php'; // Include the new functions file
require_once __DIR__ . '/../db_connect.php'; // Correct path to root db_connect.php

$message = '';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header("Location: manage_products.php"); // Redirect if no valid ID is provided
    exit;
}

// --- Handle Product Update ---
if (isset($_POST['update_product'])) {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $current_image = trim($_POST['current_image']);
    $catalog_url = isset($_POST['current_catalog_url']) ? trim($_POST['current_catalog_url']) : '';

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

    // --- Check if a new catalog PDF is uploaded to Supabase ---
    if (isset($_FILES['catalog_pdf']) && $_FILES['catalog_pdf']['error'] == UPLOAD_ERR_OK) {
        $pdf_name = 'product_catalogs/' . time() . '_' . basename($_FILES['catalog_pdf']['name']); // Store in a subfolder
        $uploaded_url = uploadToSupabase($_FILES['catalog_pdf']['tmp_name'], $pdf_name);
        if ($uploaded_url) {
            $catalog_url = $uploaded_url;
        }
    }

    if (empty($message)) {
        $stmt = $conn->prepare("UPDATE products SET name = :name, category = :category, description = :description, image = :image, catalog_url = :catalog_url WHERE id = :id");
        if ($stmt->execute([
            ':name' => $name,
            ':category' => $category,
            ':description' => $description,
            ':image' => $image_name,
            ':catalog_url' => $catalog_url,
            ':id' => $product_id
        ])) {
            // Redirect to the main admin page to see the changes
            header("Location: manage_products.php?update=success");
            exit;
        } else {
            $errorInfo = $stmt->errorInfo();
            $message = '<div class="alert alert-danger">Error updating product: ' . $errorInfo[2] . '</div>';
        }
        $stmt = null; // Close statement
    }
}

// --- Fetch Product Data for the Form ---
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt = null;
if (!$product) {
    // If product not found, redirect
    header("Location: manage_products.php");
    $conn = null;
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
                <input type="hidden" name="current_catalog_url" value="<?php echo htmlspecialchars($product['catalog_url']); ?>">
                
                <div class="form-group"><label>Product Name:</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required></div>
                <div class="form-group">
                    <label for="category">Category:</label>
                    <select name="category" id="category" class="form-control" required>
                        <?php // Fetch categories using PDO
                        $categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['filter_class']); ?>" <?php if ($product['category'] == $cat['filter_class']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Description:</label><textarea name="description" class="form-control"><?php echo htmlspecialchars($product['description']); ?></textarea></div>
                
                <div class="form-group">
                    <label>Current Catalog PDF:</label><br>
                    <?php if (!empty($product['catalog_url'])): ?>
                        <a href="<?php echo htmlspecialchars($product['catalog_url']); ?>" target="_blank" class="btn btn-sm btn-info mb-2">View Existing PDF</a>
                    <?php else: ?>
                        <span class="text-muted">No catalog uploaded.</span>
                    <?php endif; ?>
                </div>
                <div class="form-group"><label>Upload New Catalog PDF (Optional):</label><input type="file" name="catalog_pdf" class="form-control-file" accept=".pdf"></div>
                
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
<?php $conn = null; ?>