<?php
session_start();

// --- Session Security Check ---
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset(); session_destroy();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

$_SESSION['last_activity'] = time();

require_once __DIR__ . '/../db_connect.php';
require_once 'config.php'; // Include configuration
require_once 'functions.php'; // Include the new functions file

// --- Handle Add Product ---
$message = '';
if (isset($_POST['add_product'])) {
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "../assets/img/portfolio/";
        $image_name = time() . '_' . basename($_FILES["image"]["name"]); // Create a unique name
        $target_file = $target_dir . $image_name;

        if (resizeAndCropImage($_FILES["image"]["tmp_name"], $target_file)) {
            $catalog_url = '';
            // --- Handle Catalog PDF Upload to Supabase ---
            if (isset($_FILES['catalog_pdf']) && $_FILES['catalog_pdf']['error'] == UPLOAD_ERR_OK) {
                $pdf_name = 'product_catalogs/' . time() . '_' . basename($_FILES['catalog_pdf']['name']); // Store in a subfolder
                $uploaded_url = uploadToSupabase($_FILES['catalog_pdf']['tmp_name'], $pdf_name);
                if ($uploaded_url) {
                    $catalog_url = $uploaded_url;
                }
            }

            // Use prepared statement to prevent SQL injection
            $stmt = $conn->prepare("INSERT INTO products (name, category, image, description, catalog_url) VALUES (:name, :category, :image, :description, :catalog_url)");
            if ($stmt->execute([
                ':name' => $_POST['name'],
                ':category' => $_POST['category'],
                ':image' => $image_name,
                ':description' => $_POST['description'],
                ':catalog_url' => $catalog_url
            ])) {
                $message = '<div class="alert alert-success">Product added successfully!</div>';
            } else {
                $errorInfo = $stmt->errorInfo();
                $message = '<div class="alert alert-danger">Error: ' . $errorInfo[2] . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Sorry, there was an error processing your image. Please upload a valid JPG, PNG, or GIF.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Sorry, there was an error uploading your file.</div>';
    }
}

// --- Handle Delete Product ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $conn->beginTransaction(); // Start transaction
    try {
        // Use prepared statement to securely fetch the image name
        $stmt = $conn->prepare("SELECT image FROM products WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $image_path = '../assets/img/portfolio/' . $row['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
        
        // Use prepared statement to securely delete the product
        $stmt_delete = $conn->prepare("DELETE FROM products WHERE id = :id");
        $stmt_delete->execute([':id' => $id]);
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        $message = '<div class="alert alert-danger">Error deleting product: ' . $e->getMessage() . '</div>';
    }
    header("Location: manage_products.php");
    exit;
}

$products = $conn->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <a href="index.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Manage Products</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
    <?php echo $message; ?>

    <!-- Add Product Form -->
    <div class="card my-4">
        <div class="card-header">Add New Product</div>
        <div class="card-body">
            <form action="manage_products.php" method="post" enctype="multipart/form-data">
                <div class="form-group"><input type="text" name="name" class="form-control" placeholder="Product Name" required></div>
                <div class="form-group">
                    <label for="category">Category:</label>
                    <select name="category" id="category" class="form-control" required>
                        <option value="">-- Select a Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['filter_class']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><textarea name="description" class="form-control" placeholder="Description (optional)"></textarea></div>
                <div class="form-group"><label>Product Image (will be resized to 800x600):</label><input type="file" name="image" class="form-control-file" required></div>
                <div class="form-group"><label>Catalog PDF (Optional, uploads to Supabase):</label><input type="file" name="catalog_pdf" class="form-control-file" accept=".pdf"></div>
                <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
            </form>
        </div>
    </div>

    <!-- Product List -->
    <div class="card">
        <div class="card-header">Existing Products</div>
        <div class="card-body">
            <table class="table">
                <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Catalog</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($products as $row): ?>
                        <tr>
                            <td><img src="../assets/img/portfolio/<?php echo htmlspecialchars($row['image']); ?>" width="100" alt=""></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td>
                                <?php if (!empty($row['catalog_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($row['catalog_url']); ?>" target="_blank" class="badge badge-info">View PDF</a>
                                <?php else: ?>
                                    <span class="text-muted">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                <a href="manage_products.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn = null; ?>
