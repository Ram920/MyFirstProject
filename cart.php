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
    <style>
        /* Custom styles for valid form fields */
        .form-control.is-valid {
            border-color: #28a745; /* Green border */
        }
        .form-control.is-valid:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
    </style>
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
                    <li class="active"><a href="cart.php" id="quote-basket-link-cart">Quote Basket (<?php echo count($_SESSION['cart'] ?? []); ?>)</a></li>
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
                <?php
                // --- Display Success or Error Messages ---
                if (isset($_GET['status'])) {
                    if ($_GET['status'] == 'success' && isset($_GET['message'])) {
                        echo '<div class="alert alert-success">' . htmlspecialchars($_GET['message']) . '</div>';
                    } elseif ($_GET['status'] == 'error') {
                        echo '<div class="alert alert-danger">';
                        if (isset($_GET['message'])) {
                            echo htmlspecialchars($_GET['message']) . '<br>';
                        }
                        if (isset($_GET['errors']) && is_array($_GET['errors'])) {
                            foreach ($_GET['errors'] as $error) {
                                echo htmlspecialchars($error) . '<br>';
                            }
                        }
                        echo '</div>';
                    }
                }
                ?>
                <?php // db_connect.php is already included by sendemail.php at the top
                $cart_items = $_SESSION['cart'] ?? [];
                
                if (empty($cart_items)) {
                    echo "<p>Your quote basket is empty. Please add products from our portfolio.</p>";
                    // The success message will be displayed here by JS after submission
                } else {
                echo '<div id="cart-contents">'; // Wrapper for AJAX update
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

                    <form action="sendemail.php" method="post" role="form" class="php-email-form" id="quote-basket-form" enctype="multipart/form-data">
                        
                        <div class="form-row">
                            <div class="col-md-6 form-group">
                                <input type="text" name="name" class="form-control" placeholder="Your Name" required />
                                <div class="invalid-feedback">Please enter your name (letters and spaces only).</div>
                            </div>
                            <div class="col-md-6 form-group">
                                <input type="email" class="form-control" name="email" placeholder="Your Email" required />
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-control" name="company_name" placeholder="Company Name" required />
                            <div class="invalid-feedback">Company Name is required.</div>
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-control" name="subject" value="Quote Request for Products" required />
                            <div class="invalid-feedback">Subject is required.</div>
                        </div>
                        <div class="form-group">
                            <input type="tel" class="form-control" name="phone" placeholder="Mobile (10 digits, Optional)" maxlength="10" pattern="[0-9]{10}" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);" title="Please enter a 10-digit mobile number." />
                            <div class="invalid-feedback">Please enter a valid 10-digit mobile number.</div>
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-control" name="delivery_location" placeholder="Delivery Location" />
                        </div>
                        <div class="form-group">
                            <textarea class="form-control" name="required_customization" rows="3" placeholder="Required Customization (e.g., dimensions, materials)"></textarea>
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-control" name="quantity" placeholder="Quantity (e.g., 5, 100)" oninput="this.value = this.value.replace(/[^0-9]/g, '');" />
                            <div class="invalid-feedback">Quantity must be a number.</div>
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
                            <div class="progress mt-3" style="display: none;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                            </div>
                            <div class="sent-message">Your quote request has been sent. Thank you!</div>
                        </div>
                        <div class="text-center"><button type="submit" name="submit" id="submit-button">Enquire Now</button></div>
                    </form>
                <?php 
                echo '</div>'; // End of the wrapper div
                } // end of else
                ?>
                <!-- This div will be used by JavaScript to display the success message after quote basket submission -->
                <div id="quote-basket-success-message" class="sent-message" style="display: none; margin-top: 15px;"></div>
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
    <!-- Template Main JS File -->
    <script src="assets/js/main.js"></script>

    <script>
    $(document).ready(function() {
        const form = $('#quote-basket-form'); // The form
        const submitButton = $('#submit-button'); // The submit button
        const loadingDiv = form.find('.loading');
        const errorDiv = form.find('.error-message');
        const successDiv = form.find('.sent-message');
        const progressDiv = form.find('.progress');
        const progressBar = form.find('.progress-bar');

        // --- Field Validation Function ---
        function validateField(field) {
            const $field = $(field);
            let isValid = true;
            let value = $field.val().trim();

            // Check for required fields
            if ($field.prop('required') && value === '') {
                isValid = false;
            }

            // Specific validation for email
            if (isValid && $field.attr('name') === 'email') {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                }
            }

            // Specific validation for phone (optional, but must be valid if present)
            if (isValid && $field.attr('name') === 'phone' && value !== '') {
                const phoneRegex = /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/;
                if (!phoneRegex.test(value)) {
                    isValid = false;
                }
            }

            // Update field styling
            if (isValid) {
                $field.removeClass('is-invalid').addClass('is-valid');
            } else {
                $field.removeClass('is-valid').addClass('is-invalid');
            }
            return isValid;
        }

        // --- Event Listeners for Real-time Feedback ---
        // Validate on 'blur' (when user leaves a field)
        form.find('input[required], input[name="email"], input[name="phone"]').on('blur', function() {
            validateField(this);
        });

        // Remove validation styles on 'keyup' (when user starts typing)
        form.find('input, textarea').on('keyup', function() {
            $(this).removeClass('is-invalid is-valid');
        });

        // --- Form Submission Handler ---
        form.on('submit', function(e) {
            e.preventDefault();

            // Validate all fields on submit
            let isFormValid = true;
            form.find('input[required], input[name="email"], input[name="phone"]').each(function() {
                if (!validateField(this)) {
                    isFormValid = false;
                }
            });

            if (!isFormValid) {
                errorDiv.html('Please fill in all required fields correctly.').show();
                return;
            }

            // If form is valid, proceed with AJAX submission
            errorDiv.hide();
            successDiv.hide();
            submitButton.prop('disabled', true);

            // Show loading text if no file is being uploaded, otherwise show progress bar
            if ($('#drawing')[0].files.length === 0) {
                loadingDiv.show();
            } else {
                progressDiv.show();
                progressBar.width('0%').attr('aria-valuenow', 0).text('0%');
            }

            var formData = new FormData(this);

            $.ajax({
                url: form.attr('action'),
                type: form.attr('method'),
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'text',
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(evt) {
                        if (evt.lengthComputable) {
                            var percentComplete = Math.round((evt.loaded / evt.total) * 100);
                            progressBar.width(percentComplete + '%');
                            progressBar.attr('aria-valuenow', percentComplete);
                            progressBar.text(percentComplete + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    loadingDiv.hide();
                    progressDiv.hide();
                    if (response.trim() === 'OK') {
                        $('#cart-contents').slideUp();
                        successDiv.html('Your quote request has been sent successfully. We will get back to you shortly.').show();
                        $('#quote-basket-success-message').html('Your quote request has been sent. Thank you!').show();
                        $('#quote-basket-link-cart').text('Quote Basket (0)');
                    } else {
                        errorDiv.html(response).slideDown();
                        submitButton.prop('disabled', false);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    let errorText = 'An error occurred. Please try again.';
                    if (jqXHR.responseText) {
                        errorText = jqXHR.responseText;
                    }
                    errorDiv.html(errorText).slideDown();
                    submitButton.prop('disabled', false);
                    progressDiv.hide();
                }
            });
        });
    });
    </script>
</body>
</html>