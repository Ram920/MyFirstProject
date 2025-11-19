<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php'; // Include configuration

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

// --- Handle Add Product ---
$message = '';
if (isset($_POST['add_product'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $category = $conn->real_escape_string($_POST['category']);
    $description = $conn->real_escape_string($_POST['description']);

    // Image Upload
    $target_dir = "../assets/img/portfolio/";
    $image_name = basename($_FILES["image"]["name"]);
    $target_file = $target_dir . $image_name;
    
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        $sql = "INSERT INTO products (name, category, image, description) VALUES ('$name', '$category', '$image_name', '$description')";
        if ($conn->query($sql)) {
            $message = '<div class="alert alert-success">Product added successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Sorry, there was an error uploading your file.</div>';
    }
}

// --- Handle Delete Product ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $res = $conn->query("SELECT image FROM products WHERE id = $id");
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $image_path = '../assets/img/portfolio/' . $row['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    $conn->query("DELETE FROM products WHERE id = $id");
    header("Location: manage_products.php");
    exit;
}

$products = $conn->query("SELECT * FROM products ORDER BY id DESC");
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
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
    <h2>Manage Products</h2>
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
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($cat['filter_class']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group"><textarea name="description" class="form-control" placeholder="Description (optional)"></textarea></div>
                <div class="form-group"><label>Product Image:</label><input type="file" name="image" class="form-control-file" required></div>
                <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
            </form>
        </div>
    </div>

    <!-- Product List -->
    <div class="card">
        <div class="card-header">Existing Products</div>
        <div class="card-body">
            <table class="table">
                <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Action</th></tr></thead>
                <tbody>
                    <?php while ($row = $products->fetch_assoc()): ?>
                        <tr>
                            <td><img src="../assets/img/portfolio/<?php echo htmlspecialchars($row['image']); ?>" width="100" alt=""></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td>
                                <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                <a href="manage_products.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
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
