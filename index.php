<?php
  // Start the session.
  if (session_status() == PHP_SESSION_NONE) {
    session_start();
  }


  // Include the database connection first as it's needed for the site visit tracker.
  require_once 'admin/config.php'; // Load config first, as sendemail.php depends on it.
  require_once 'db_connect.php';

  // --- Basic Site Visit Tracker ---
  $today = date("Y-m-d");
  $stmt = $conn->prepare("INSERT INTO site_visits (visit_date, visit_count) VALUES (?, 1) ON DUPLICATE KEY UPDATE visit_count = visit_count + 1");
  $stmt->bind_param("s", $today);
  $stmt->execute();

  // --- Fetch dynamic website settings ---
  $youtube_url_result = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'youtube_video_url'");
  $youtube_url = $youtube_url_result->fetch_assoc()['setting_value'] ?? 'https://www.youtube.com/watch?v=jDDaplaOz7Q'; // Fallback URL

  $clients_result = $conn->query("SELECT * FROM clients ORDER BY id DESC");

  $team_members_result = $conn->query("SELECT * FROM team_members ORDER BY display_order ASC, id ASC");

  // Do NOT close the connection here. It will be closed later in the script.
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>NUSH MECHANICAL &amp; FABRICATOR WORKS</title>
  <meta content="NUSH MECHANICAL & FABRICATOR WORKS specializes in the design and manufacturing of hydraulic presses, cylinders, power packs, and provides machine fabrication services." name="description">
  <meta content="hydraulic press, hydraulic cylinder, machine fabrication, NUSH MECHANICAL, fabricator works, Anil Sharma" name="keywords">

  <!-- Favicons -->
  <link href="images/Logo.png" rel="icon">
  <link href="images/Logo.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Raleway:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/icofont/icofont.min.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/venobox/venobox.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets/vendor/owl.carousel/assets/owl.carousel.min.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <link href="assets/css/about.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.1/css/all.min.css">

  <!-- =======================================================
  * Template Name: Dewi - v2.2.1
  * Template URL: https://bootstrapmade.com/dewi-free-multi-purpose-html-template/
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
</head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="fixed-top ">
    <div class="container-fluid d-flex align-items-center justify-content-between">

      <!--<h1 class="logo"><a href="index.html">Dewi</a></h1>-->
      <!-- Uncomment below if you prefer to use an image logo -->
      <a href="index.php" class="logo">
        <img src="images/Logo-white.png" alt="NUSH MECHANICAL Logo" class="img-fluid">
      </a>

      <nav class="nav-menu d-none d-lg-block">
        <ul>
          <li class="active"><a href="index.php">Home</a></li>
          <li><a href="#about">About</a></li>
          <li><a href="#services">Services</a></li>
          <li><a href="#portfolio">Portfolio</a></li>
          <li><a href="#team">Team</a></li>
          <li><a href="cart.php" id="quote-basket-link">Quote Basket (<span id="cart-count"><?php echo count($_SESSION['cart'] ?? []); ?></span>)</a></li>
          <li><a href="#contact">Contact</a></li>

        </ul>
      </nav><!-- .nav-menu -->

     <a href="#about" class="get-started-btn scrollto">Get Started</a>

    </div>
  </header><!-- End Header -->

  <!-- ======= Hero Section ======= -->
  <section id="hero">
    <div class="hero-container" data-aos="fade-up" data-aos-delay="150">
      <h1>NUSH MECHANICAL &amp; FABRICATOR WORKS</h1>
      <h2>We have a team of talented workers</h2>
      <div class="d-flex">
       <!-- <a href="#about" class="btn-get-started scrollto">Get Started</a>-->
       <!--<a href="https://www.youtube.com/watch?v=jDDaplaOz7Q" class="venobox btn-watch-video" data-vbtype="video" data-autoplay="true"> Watch Video <i class="icofont-play-alt-2"></i></a>-->
      </div>
    </div>
  </section><!-- End Hero -->
 
    <!-- whatsapp api-->
    <div id="whatsapp">
    <!--<a href="https://api.whatsapp.com/send?phone=+918600222111" class="float" target="_blank">-->
        <!--<a href="https://api.whatsapp.com/send?phone=+918600222111" class="float" target="_blank">-->
            <a href="https://wa.me/+918600222111" class="float" target="_blank">
<i class="fab fa-whatsapp my-float"></i>
</a>
    </div>
    
  <main id="main">

    <!-- ======= About Section ======= -->
    <section id="about" class="about">
      <div class="container" data-aos="fade-up">

        <div class="row justify-content-end">
          <div class="col-lg-11">
            <div class="row justify-content-end">

              <div class="col-lg-3 col-md-5 col-6 d-md-flex align-items-md-stretch">
                <div class="count-box">
                  <i class="icofont-simple-smile"></i>
                  <!--<span data-toggle="counter-up">65</span>-->
                  <p>Happy Clients</p>
                </div>
              </div>

             <!-- <div class="col-lg-3 col-md-5 col-6 d-md-flex align-items-md-stretch">
                <div class="count-box">
                  <i class="icofont-document-folder"></i>
                  <span data-toggle="counter-up">85</span>
                  <p>Projects</p>
                </div>
              </div>-->

              <div class="col-lg-3 col-md-5 col-6 d-md-flex align-items-md-stretch">
                <div class="count-box">
                  <i class="icofont-clock-time"></i>
                  <span data-toggle="counter-up">12</span>
                  <p>Years of experience</p>
                </div>
              </div>

              <!--<div class="col-lg-3 col-md-5 col-6 d-md-flex align-items-md-stretch">
                <div class="count-box">
                  <i class="icofont-award"></i>
                  <span data-toggle="counter-up">15</span>
                  <p>Awards</p>
                </div>
              </div>-->

            </div>
          </div>
        </div>

        <div class="row">

          <div class="col-lg-6 video-box align-self-baseline" data-aos="zoom-in" data-aos-delay="100">
            <img src="assets/img/about.jpg" class="img-fluid" alt="">
            <a href="<?php echo htmlspecialchars($youtube_url); ?>" class="venobox play-btn mb-4" data-vbtype="video" data-autoplay="true"></a>
          </div>

          <div class="col-lg-6 pt-3 pt-lg-0 content">
           
            <p class="font-italic">
              We <b>“NUSH MECHANICAL & FABRICATOR WORKS”</b>, are a leading manufacturer and supplier of high-quality hydraulic products, including Hydraulic Presses, Hydraulic Cylinders, Hose Type Machines, Hydraulic Lift Tables, Hydraulic Power Packs, Hydraulic Bearing Puller Machines, Hydraulic Drilling Machines, Hydraulic SPM Machines, etc. Committed to quality, we craft these products from premium components and cutting-edge technology. Our hydraulic products are recognized for reliable performance, high efficiency, low maintenance, long lifespan, and robust design. To ensure client satisfaction, we offer these products in various specifications. Our dedicated quality experts rigorously assess the products against different parameters to maintain global quality standards. Furthermore, we provide these top-notch hydraulic products at competitive rates. Additionally, we offer Machine Fabrication Work to our customers.
            </p>
            <p>
             Under the headship of our Mentor, <b>“Anil Sharma”</b>, we have been able to achieve an invincible position in this industry. Founded in the year 2010, at Faridabad
(Haryana, India).
            </p>
          </div>

        </div>

      </div>
    </section><!-- End About Section -->

    <!-- ======= About Boxes Section ======= -->
    <section id="about-boxes" class="about-boxes">
      <div class="container" data-aos="fade-up">
          
          <div class="row">
        <div class="feature-box col-lg-4">
          <i class="icon fas fa-check-circle fa-4x"></i>
          <h3 class="feature-title">Easy to order.</h3>
          <p>Hassle free order</p>
        </div>

        <div class="feature-box col-lg-4">
          <i class="icon fas fa-bullseye fa-4x"></i>
          <h3 class="feature-title">Meet target time</h3>
          <p>Deliver product on time with customed defined needs*</p>
        </div>

        <div class="feature-box col-lg-4">
          <i class="icon fas fa-heart fa-4x"></i>
          <h3 class="feature-title">Guaranteed to work</h3>
          <p>Everything starts with customer</p>
        </div>
      </div>
      </div>
    </section><!-- End About Boxes Section -->

    <!-- ======= Clients Section ======= -->
    <section id="clients" class="clients">
      <div class="container" data-aos="zoom-in">

        <div class="row">

          <?php while ($client = $clients_result->fetch_assoc()): ?>
          <div class="col-lg-2 col-md-4 col-6 d-flex align-items-center justify-content-center">
            <img src="assets/img/clients/<?php echo htmlspecialchars($client['image']); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($client['name']); ?>">
          </div>
          <?php endwhile; ?>
        </div>

      </div>
    </section><!-- End Clients Section -->

  
    <!-- ======= Services Section ======= -->
   <section id="services" class="services section-bg">
      <div class="container" data-aos="fade-up">

        <div class="section-title">
          <h2>Services</h2>
          <p>Check our Services</p>
        </div>

        <div class="row" data-aos="fade-up" data-aos-delay="200">
          <div class="col-md-6">
            <div class="icon-box">
              <i class="icofont-settings"></i>
              <h4><a href="#">Hydraulic Machine Manufacturing</a></h4>
              <p>We specialize in designing and manufacturing a wide range of custom hydraulic presses, cylinders, and power packs to meet your specific industrial requirements.</p>
            </div>
          </div>
          <div class="col-md-6 mt-4 mt-md-0">
            <div class="icon-box">
              <i class="icofont-automation"></i>
              <h4><a href="#">Conveyor Systems</a></h4>
              <p>We provide robust and efficient conveyor systems, designed to streamline your production line and material handling processes for maximum productivity.</p>
            </div>
          </div>
          <div class="col-md-6 mt-4 mt-md-0">
            <div class="icon-box">
              <i class="icofont-industries-alt-2"></i>
              <h4><a href="#">Custom Fabrication Works</a></h4>
              <p>Our expert team offers high-quality metal fabrication services, creating custom parts and structures with precision and durability for any application.</p>
            </div>
          </div>
          <div class="col-md-6 mt-4 mt-md-0">
            <div class="icon-box">
              <i class="icofont-tools-alt-2"></i>
              <h4><a href="#">Hydraulic SPM Machines</a></h4>
              <p>We build Special Purpose Machines (SPM) powered by hydraulic systems, engineered for unique tasks and high-performance manufacturing challenges.</p>
            </div>
          </div>
          <div class="col-md-6 mt-4 mt-md-0">
            <div class="icon-box">
              <i class="icofont-fast-delivery"></i>
              <h4><a href="#">Material Handling Solutions</a></h4>
              <p>From hydraulic lift tables to custom conveyors, we develop integrated solutions to improve your workflow and operational efficiency.</p>
            </div>
          </div>
          <div class="col-md-6 mt-4 mt-md-0">
            <div class="icon-box">
              <i class="icofont-tasks-alt"></i>
              <h4><a href="#">Repair &amp; Maintenance</a></h4>
              <p>We provide expert repair and maintenance services for hydraulic machinery and fabrication works to ensure longevity and optimal performance.</p>
            </div>
          </div>
        </div>

      </div>
    </section><!-- End Services Section -->

    <!-- ======= Testimonials Section ======= -->
    <section id="testimonials" class="testimonials">
      <div class="container" data-aos="zoom-in">

        <div class="owl-carousel testimonials-carousel">

          <div class="testimonial-item">
            <img src="assets/img/testimonials/testimonials-1.jpg" class="testimonial-img" alt="">
            <h3>Ratan Tata</h3>
            <h4>Industrialist &amp; Mechanical Engineer</h4>
            <p>
              <i class="bx bxs-quote-alt-left quote-icon-left"></i>
              I don't believe in taking the right decisions. I take decisions and then make then right.
              <i class="bx bxs-quote-alt-right quote-icon-right"></i>
            </p>
          </div>

          <div class="testimonial-item">
            <img src="assets/img/testimonials/testimonials-2.jpg" class="testimonial-img" alt="">
            <h3>Henry Ford</h3>
            <h4>Founder, Ford Motor Company</h4>
            <p>
              <i class="bx bxs-quote-alt-left quote-icon-left"></i>
              Quality means doing it right when no one is looking.
              <i class="bx bxs-quote-alt-right quote-icon-right"></i>
            </p>
          </div>

          <div class="testimonial-item">
            <img src="assets/img/testimonials/testimonials-3.jpg" class="testimonial-img" alt="">
            <h3>Dr. A.P.J.Abdul Kalam</h3>
            <h4>Aerospace Engineer &amp; Scientist</h4>
            <p>
              <i class="bx bxs-quote-alt-left quote-icon-left"></i>
              Excellence is a continuous process and not an accident.
              <i class="bx bxs-quote-alt-right quote-icon-right"></i>
            </p>
          </div>

          <div class="testimonial-item">
            <img src="assets/img/testimonials/testimonials-4.jpg" class="testimonial-img" alt="">
            <h3>Elon Musk</h3>
            <h4>Engineer, innovator &amp; manufacturer</h4>
            <p>
              <i class="bx bxs-quote-alt-left quote-icon-left"></i>
              Engineering is the closet thing to magic that exists in the world.
              <i class="bx bxs-quote-alt-right quote-icon-right"></i>
            </p>
          </div>

          <div class="testimonial-item">
            <img src="assets/img/testimonials/testimonials-5.jpg" class="testimonial-img" alt="">
            <h3>Isambard Kingdom Brunel</h3>
            <h4>Mechanical/Civil Engineer</h4>
            <p>
              <i class="bx bxs-quote-alt-left quote-icon-left"></i>
              I am opposed to the idea of impossibilities.
              <i class="bx bxs-quote-alt-right quote-icon-right"></i>
            </p>
          </div>

        </div>

      </div>
    </section><!-- End Testimonials Section -->

    <!-- ======= Portfolio Section ======= -->
    <section id="portfolio" class="portfolio">
      <div class="container" data-aos="fade-up">

        <div class="section-title">
          <h2>Portfolio</h2>
          <p>Check our Portfolio</p>
        </div>

        <div class="row" data-aos="fade-up" data-aos-delay="100">
          <div class="col-lg-12 d-flex justify-content-center">
            <ul id="portfolio-flters">
                <?php
                // --- Dynamic Portfolio Filters ---

                // First, check if there are any products at all.
                $product_count_result = $conn->query("SELECT COUNT(*) AS total FROM products");
                $product_count = $product_count_result->fetch_assoc()['total'];

                // Only show filters if there is at least one product.
                if ($product_count > 0) {
                    echo '<li data-filter="*" class="filter-active">All</li>';

                    // Fetch all categories from the database and create a filter for each one.
                    $categories_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
                    while ($category = $categories_result->fetch_assoc()) {
                        echo '<li data-filter=".' . htmlspecialchars($category['filter_class']) . '">' . htmlspecialchars($category['name']) . '</li>';
                    }
                }
                ?>
            </ul>
          </div>
        </div>

        <div class="row portfolio-container" data-aos="fade-up" data-aos-delay="200">
            <?php
              $result = $conn->query("SELECT * FROM products ORDER BY id DESC");
              while($product = $result->fetch_assoc()):
            ?>
            <div class="col-lg-4 col-md-6 portfolio-item <?php echo htmlspecialchars($product['category']); ?>">
              <div class="portfolio-wrap">
                <img src="assets/img/portfolio/<?php echo htmlspecialchars($product['image']); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <div class="portfolio-info">
                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                    <div class="portfolio-links mt-3">
                        <a href="assets/img/portfolio/<?php echo htmlspecialchars($product['image']); ?>" data-gall="portfolioGallery" class="venobox btn btn-sm btn-light" title="Preview"><i class="bx bx-plus"></i></a>
                        <a href="cart_handler.php?action=add&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>" title="Add to Inquiry"><i class="bx bx-cart-add"></i> Add to Basket</a>
                        <a href="cart_handler.php?action=whatsapp&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-success" title="Share on WhatsApp" target="_blank"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
              </div>
            </div>
            <?php
              endwhile;
            ?>

        </div>

      </div>
    </section><!-- End Portfolio Section -->

    <!-- ======= Team Section ======= -->
    <section id="team" class="team section-bg">
      <div class="container" data-aos="fade-up">

        <div class="section-title">
          <h2>Team</h2>
          <p>Check our Team</p>
        </div>
          
        <div class="row">
          <?php while ($member = $team_members_result->fetch_assoc()): ?>
          <div class="col-lg-4 col-md-6 d-flex align-items-stretch">
            <div class="member" data-aos="fade-up" data-aos-delay="100">
              <div class="pic"><img src="assets/img/team/<?php echo htmlspecialchars($member['image']); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($member['name']); ?>"></div>
              <div class="member-info">
                <h4><?php echo htmlspecialchars($member['name']); ?></h4>
                <span><?php echo htmlspecialchars($member['position']); ?></span>
                <div class="social">
                  <?php if (!empty($member['twitter_url'])): ?><a href="<?php echo htmlspecialchars($member['twitter_url']); ?>" target="_blank"><i class="icofont-twitter"></i></a><?php endif; ?>
                  <?php if (!empty($member['facebook_url'])): ?><a href="<?php echo htmlspecialchars($member['facebook_url']); ?>" target="_blank"><i class="icofont-facebook"></i></a><?php endif; ?>
                  <?php if (!empty($member['instagram_url'])): ?><a href="<?php echo htmlspecialchars($member['instagram_url']); ?>" target="_blank"><i class="icofont-instagram"></i></a><?php endif; ?>
                  <?php if (!empty($member['linkedin_url'])): ?><a href="<?php echo htmlspecialchars($member['linkedin_url']); ?>" target="_blank"><i class="icofont-linkedin"></i></a><?php endif; ?>
                </div>
              </div>
            </div>
          </div>
          <?php endwhile; ?>

        </div>

      </div>
    </section><!-- End Team Section -->

    <!-- ======= Contact Section ======= -->

    <section id="contact" class="contact">
      <div class="container" data-aos="fade-up">

    <div class="section-title">
        <h2>Contact</h2>
        <p>Contact Us</p>
      </div>

      <div class="row">

        <div class="col-lg-6">

          <div class="row">
            <div class="col-md-12">
              <div class="info-box">
                <i class="bx bx-map"></i>
                <h3>Our Address</h3>
                <p>Plot no.9158, Block-F, Sanjay colony, Sector 23, Faridabad-121005 (Haryana)</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="info-box mt-4">
                <i class="bx bx-envelope"></i>
                <h3>Email Us</h3>
                <p>nushmechanical@gmail.com</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="info-box mt-4">
                <i class="bx bx-phone-call"></i>
                <h3>Call Us</h3>
                  <p><a href="tel:+91 9667587686">+91 9667587686</a> / <a href="tel:+91 9718968844">+91 9718968844</a></p>
              </div>
            </div>
          </div>

        </div>

        <div class="col-lg-6 mt-4 mt-lg-0">
            <?php echo $alert; ?>
            <form action="sendemail.php" method="post" role="form" class="php-email-form">
              <div class="form-row">
                <div class="col-md-6 form-group">
                  <input type="text" name="name" class="form-control" id="name" placeholder="Your Name" required />
                </div>
                <div class="col-md-6 form-group">
                  <input type="email" class="form-control" name="email" id="email" placeholder="Your Email" required />
                </div>
              </div>
              <div class="form-group">
                <input type="text" class="form-control" name="subject" id="subject" placeholder="Subject" required />
              </div>
              <div class="form-group">
                <input type="tel" class="form-control" name="phone" id="phone" placeholder="Mobile (Optional)" />
              </div>
              <div class="form-group">
                <textarea class="form-control" name="message" rows="5" placeholder="Message" required></textarea>
              </div>
              <div class="mb-3">
                <div class="loading">Loading</div>
                <div class="error-message"></div>
                <div class="sent-message">Your message has been sent. Thank you!</div>
              </div>
              <div class="text-center"><button type="submit" name="submit">Send Message</button></div>
            </form>
        </div>

      </div>

      </div>
    </section><!-- End Contact Section -->

  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <footer id="footer">
    <div class="footer-top">
      <div class="container">
        <div class="row">

          <div class="col-lg-3 col-md-6">
            <div class="footer-info">
              <h3>NUSH MECHANICAL &amp; FABRICATOR WORKS</h3>
              <!--<p>
                Plot no.9158, Block-F,<br> Sanjay colony,<br> Sector 23, Faridabad-121005<br> (Haryana)<br>
                <strong>Phone:</strong> +91 9718968844<br>
                <strong>Email:</strong> nushmechanical@gmail.com<br>
              </p>-->
              <div class="social-links mt-3">
                <a href="#" class="twitter"><i class="bx bxl-twitter"></i></a>
                <a href="#" class="facebook"><i class="bx bxl-facebook"></i></a>
                <a href="#" class="instagram"><i class="bx bxl-instagram"></i></a>
               
              </div>
            </div>
          </div>

        <div class="col-lg-2 col-md-6 footer-links">
            <h4>Useful Links</h4>
            <ul>
              <li><i class="bx bx-chevron-right"></i> <a href="#">Home</a></li>
              <li><i class="bx bx-chevron-right"></i> <a href="#">About us</a></li>
             <!-- <li><i class="bx bx-chevron-right"></i> <a href="#">Services</a></li>-->
              <li><i class="bx bx-chevron-right"></i> <a href="#">Terms of service</a></li>
              <li><i class="bx bx-chevron-right"></i> <a href="#">Privacy policy</a></li>
            </ul>
          </div>

        <!--  <div class="col-lg-3 col-md-6 footer-links">
            <h4>Our Services</h4>
            <ul>
              <li><i class="bx bx-chevron-right"></i> <a href="#">Web Design</a></li>
              <li><i class="bx bx-chevron-right"></i> <a href="#">Web Development</a></li>
              <li><i class="bx bx-chevron-right"></i> <a href="#">Product Management</a></li>
              <li><i class="bx bx-chevron-right"></i> <a href="#">Marketing</a></li>
              <li><i class="bx bx-chevron-right"></i> <a href="#">Graphic Design</a></li>
            </ul>
          </div>-->

          <div class="col-lg-4 col-md-6 footer-newsletter">
            <h4>Our Newsletter</h4>
            <form action="mailto" method="post">
              <input type="email" name="email"><input type="submit" value="Subscribe">
            </form>

          </div>

        </div>
      </div>
    </div>

    <div class="container">
      <div class="copyright">
        &copy; Copyright <strong><span>NUSH MECHANICAL &amp; FABRICATOR WORKS</span></strong>. All Rights Reserved
      </div>
      <div class="credits">
        Designed by <a href="">Ramnarayan Sharma</a>
      </div>
    </div>
  </footer><!-- End Footer -->

  <a href="#" class="back-to-top"><i class="ri-arrow-up-line"></i></a>
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/jquery/jquery.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/jquery.easing/jquery.easing.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/waypoints/jquery.waypoints.min.js"></script>
  <script src="assets/vendor/counterup/counterup.min.js"></script>
  <script src="assets/vendor/venobox/venobox.min.js"></script>
  <script src="assets/vendor/owl.carousel/owl.carousel.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>
    
     <script type="text/javascript">
    if(window.history.replaceState){
      window.history.replaceState(null, null, window.location.href);
    }

    // AJAX for adding products to the cart without page refresh
    $(document).ready(function(){
        $('.add-to-cart-btn').on('click', function(e){
            e.preventDefault(); // Prevent the link from navigating

            var productId = $(this).data('product-id');
            var button = $(this);

            $.ajax({
                url: 'cart_handler.php',
                type: 'POST',
                data: {
                    action: 'add',
                    id: productId
                },
                dataType: 'json',
                success: function(response){
                    if(response.success) {
                        // Update the cart count in the header
                        $('#cart-count').text(response.cart_count);
                        
                        // Show toast notification
                        var toast = $('#toast-notification');
                        toast.fadeIn(400).delay(2500).fadeOut(400); // Show for 2.5 seconds
                    } else {
                        alert('Error: ' + response.message);
                    }
                }
            });
        });
    });
    </script>
    
    <!-- Toast Notification -->
    <div id="toast-notification" class="toast-notification">Product added to basket!</div>
    <?php
      // Close the database connection at the very end of the script.
      $conn->close();
    ?>
</body>

</html>