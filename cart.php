<?php
// Start the session to access the cart.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'admin/config.php'; // Load config for db connection
require_once 'db_connect.php';   // Connect to the database
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Quote Basket - NUSH MECHANICAL</title>
    <meta content="Review your selected products and submit a quote request." name="description">
    <meta content="inquiry cart, hydraulic products, NUSH MECHANICAL" name="keywords">

    <!-- Favicons -->
    <link href="images/Logo.png" rel="icon">
    <link href="images/Logo.png" rel="apple-touch-icon">

    <!-- Google Fonts, Vendor CSS, and Main CSS -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Raleway:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/icofont/icofont.min.css" rel="stylesheet">
    <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
    <link href="assets/vendor/venobox/venobox.css" rel="stylesheet">
    <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
    <link href="assets/vendor/owl.carousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body>

    <!-- ======= Header ======= -->
    <header id="header" class="fixed-top header-inner-pages">
        <div class="container-fluid d-flex align-items-center justify-content-between">
            <a href="index.php" class="logo">
                <img src="images/Logo.png" alt="NUSH MECHANICAL Logo" class="img-fluid">
            </a>
            <nav class="nav-menu d-none d-lg-block">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="index.php#about">About</a></li>
                    <li><a href="index.php#portfolio">Portfolio</a></li>
                    <li><a href="index.php#contact">Contact</a></li>
                    <li class="active"><a href="cart.php">Quote Basket (<?php echo count($_SESSION['cart'] ?? []); ?>)</a></li>
                </ul>
            </nav>
            <a href="index.php#about" class="get-started-btn scrollto">Get Started</a>
        </div>
    </header><!-- End Header -->

    <main id="main">
        <!-- ======= Breadcrumbs ======= -->
        <section class="breadcrumbs">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Quote Basket</h2>
                    <ol>
                        <li><a href="index.php">Home</a></li>
                        <li>Quote Basket</li>
                    </ol>
                </div>
            </div>
        </section><!-- End Breadcrumbs -->

        <section class="inner-page">
            <div class="container">
                <?php // db_connect.php is already included by sendemail.php at the top
                $cart_items = $_SESSION['cart'] ?? [];
                
                if (empty($cart_items)) {
                    echo "<p>Your quote basket is empty. Please add products from our portfolio.</p>";
                } else {
                echo '<div id="cart-contents">'; // Start of the wrapper div
                ?>
                    <?php
                    // Prepare WhatsApp message only if the cart is not empty
                    $whatsapp_link = '#';
                    if (!empty($cart_items)) {
                        $whatsapp_phone = '+918600222111'; // Your WhatsApp number
                        $whatsapp_message_prefix = "Hello, I would like to inquire about the following products from your Quote Basket:\n\n";
                        $whatsapp_product_list = "";
                        
                        // Securely fetch product names for WhatsApp message
                        $placeholders = implode(',', array_fill(0, count($cart_items), '?'));
                        $stmt = $conn->prepare("SELECT name FROM products WHERE id IN ($placeholders)");
                        $stmt->bind_param(str_repeat('i', count($cart_items)), ...$cart_items);
                        $stmt->execute();
                        $whatsapp_result = $stmt->get_result();
                        $product_count = 1;
                        while ($wp_product = $whatsapp_result->fetch_assoc()) {
                            $whatsapp_product_list .= $product_count . ". " . htmlspecialchars($wp_product['name']) . "\n";
                            $product_count++;
                        }
                        $full_whatsapp_message = urlencode($whatsapp_message_prefix . $whatsapp_product_list . "\nMy Name: \nCompany: \nPhone: ");
                        $whatsapp_link = "https://wa.me/{$whatsapp_phone}?text={$full_whatsapp_message}";
                    }
                    ?>

                    <a href="<?php echo $whatsapp_link; ?>" class="btn btn-success btn-lg mb-4" target="_blank"><i class="fab fa-whatsapp"></i> Enquire via WhatsApp</a>
                    <h3>Products in your Basket</h3>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Use a prepared statement to fetch product details securely
                            $placeholders = implode(',', array_fill(0, count($cart_items), '?'));
                            $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
                            $stmt->bind_param(str_repeat('i', count($cart_items)), ...$cart_items);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            while ($product = $result->fetch_assoc()) {
                            ?>
                            <tr>
                                <td><img src="assets/img/portfolio/<?php echo htmlspecialchars($product['image']); ?>" width="100" alt="<?php echo htmlspecialchars($product['name']); ?>"></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><a href="cart_handler.php?action=remove&id=<?php echo $product['id']; ?>" class="btn btn-danger btn-sm">Remove</a></td>
                            </tr>
                            <?php } 
                            ?>
                        </tbody>
                    </table>

                    <hr>

                    <h3>Submit Your Quote Request</h3>
                    <p>Please fill out the form below, and we will get back to you with a quote for the products you've selected.</p>

                    <form action="sendemail.php" method="post" role="form" class="php-email-form" enctype="multipart/form-data">
                        
                        <div class="form-row">
                            <div class="col-md-6 form-group">
                                <input type="text" name="name" class="form-control" placeholder="Your Name" required />
                            </div>
                            <div class="col-md-6 form-group">
                                <input type="email" class="form-control" name="email" placeholder="Your Email" required />
                            </div>
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-control" name="company_name" placeholder="Company Name" required />
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-control" name="subject" value="Quote Request for Products" required />
                        </div>
                        <div class="form-group">
                            <input type="tel" class="form-control" name="phone" placeholder="Mobile (Optional)" />
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-control" name="delivery_location" placeholder="Delivery Location" />
                        </div>
                        <div class="form-group">
                            <textarea class="form-control" name="required_customization" rows="3" placeholder="Required Customization (e.g., dimensions, materials)"></textarea>
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-control" name="quantity" placeholder="Quantity (e.g., 5 units, 100/month)" />
                        </div>
                        <div class="form-group">
                            <label for="drawing">Upload Drawing (Optional):</label>
                            <input type="file" class="form-control-file" name="drawing" id="drawing">
                        </div>
                        <div class="form-group">
                            <textarea class="form-control" name="additional_requirements" rows="5" placeholder="Additional requirements or comments..."></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="loading">Loading</div>
                            <div class="error-message"></div>
                            <div class="sent-message">Your quote request has been sent. Thank you!</div>
                        </div>
                        <div class="text-center"><button type="submit" name="submit">Enquire Now</button></div>
                    </form>
                <?php 
                echo '</div>'; // End of the wrapper div
                } // end of else
                ?>
            </div>
        </section>
    </main><!-- End #main -->

    <!-- ======= Footer ======= -->
    <footer id="footer">
        <div class="container">
            <div class="copyright"> 
                &copy; Copyright <strong><span>NUSH MECHANICAL &amp; FABRICATOR WORKS</span></strong>. All Rights Reserved
            </div>
            <div class="credits">
                Designed by <a href="">Ramnarayan Sharma</a>
            </div>
        </div>
    </footer><!-- End Footer -->

    <!-- Vendor JS Files -->
    <script src="assets/vendor/jquery/jquery.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/jquery.easing/jquery.easing.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>
    <script src="assets/vendor/venobox/venobox.min.js"></script>
    <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <!-- Template Main JS File -->
    <script src="assets/js/main.js"></script>
</body>
</html>