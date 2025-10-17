<?php
session_start();

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['phone']);
    $destination = htmlspecialchars($_POST['destination']);
    $message = htmlspecialchars($_POST['message']);
    
    // In a real application, you would save this to a database or send an email
    $success_message = "Thank you, $name! Your inquiry has been submitted successfully. We'll contact you soon.";
}

// Handle package booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_package'])) {
    $package_name = htmlspecialchars($_POST['package_name']);
    $customer_name = htmlspecialchars($_POST['customer_name']);
    $customer_email = htmlspecialchars($_POST['customer_email']);
    $travel_date = htmlspecialchars($_POST['travel_date']);
    $travelers = htmlspecialchars($_POST['travelers']);
    
    $booking_message = "Booking request for $package_name has been submitted for $customer_name. We'll confirm your booking soon!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WanderLust Travel Agency - Discover Your Next Adventure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #e74c3c;
            --accent-color: #f39c12;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-3: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
        }

        /* Navbar Styles */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.8rem;
            color: var(--primary-color) !important;
        }

        .navbar-nav .nav-link {
            font-weight: 500;
            color: var(--dark-color) !important;
            margin: 0 10px;
            transition: color 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            color: var(--primary-color) !important;
        }

        /* Hero Section */
        .hero-section {
            height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)),
                        url('https://images.unsplash.com/photo-1469474968028-56623f02e42e?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80') center/cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            position: relative;
        }

        .hero-content h1 {
            font-size: 4rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .hero-content p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .btn-hero {
            background: var(--gradient-1);
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: transform 0.3s ease;
        }

        .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        /* Floating elements */
        .floating-element {
            position: absolute;
            animation: float 6s ease-in-out infinite;
        }

        .floating-element.plane {
            top: 20%;
            right: 10%;
            font-size: 2rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .floating-element.compass {
            bottom: 30%;
            left: 15%;
            font-size: 1.5rem;
            color: rgba(255, 255, 255, 0.6);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        /* Section Styling */
        .section {
            padding: 80px 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }

        .section-title p {
            font-size: 1.1rem;
            color: #6c757d;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Package Cards */
        .package-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
        }

        .package-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .package-image {
            height: 250px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .package-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--secondary-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .package-content {
            padding: 25px;
        }

        .package-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-color);
        }

        .package-description {
            color: #6c757d;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .package-features {
            list-style: none;
            margin-bottom: 20px;
        }

        .package-features li {
            padding: 5px 0;
            color: #6c757d;
        }

        .package-features li i {
            color: var(--accent-color);
            margin-right: 8px;
        }

        .package-price {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }

        .price {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .btn-book {
            background: var(--gradient-2);
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-book:hover {
            transform: scale(1.05);
            color: white;
        }

        /* Contact Form */
        .contact-section {
            background: var(--gradient-3);
            color: white;
        }

        .contact-form {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .form-control {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            color: white;
            padding: 12px 15px;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
            color: white;
        }

        .btn-contact {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-contact:hover {
            background: white;
            color: var(--primary-color);
        }

        /* Footer */
        .footer {
            background: var(--dark-color);
            color: white;
            text-align: center;
            padding: 40px 0;
            margin-top: auto;
        }

        .footer h5 {
            margin-bottom: 20px;
            color: var(--primary-color);
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin: 8px 0;
        }

        .footer-links a {
            color: #bdc3c7;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary-color);
        }

        .social-icons {
            margin-top: 20px;
        }

        .social-icons a {
            display: inline-block;
            margin: 0 10px;
            color: #bdc3c7;
            font-size: 1.5rem;
            transition: color 0.3s ease;
        }

        .social-icons a:hover {
            color: var(--primary-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .hero-content p {
                font-size: 1.1rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .contact-form {
                padding: 25px;
            }
        }

        /* Animation Classes */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Alert Styles */
        .alert-custom {
            border: none;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="main-content">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg fixed-top">
            <div class="container">
                <a class="navbar-brand" href="#home">
                    <i class="bi bi-airplane"></i> WanderLust
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="#packages">Packages</a></li>
                        <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                        <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-custom" style="position: fixed; top: 80px; right: 20px; z-index: 1050; max-width: 300px;">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($booking_message)): ?>
            <div class="alert alert-info alert-custom" style="position: fixed; top: 80px; right: 20px; z-index: 1050; max-width: 300px;">
                <i class="bi bi-info-circle-fill me-2"></i><?php echo $booking_message; ?>
            </div>
        <?php endif; ?>

        <!-- Hero Section -->
        <section id="home" class="hero-section">
            <div class="floating-element plane">
                <i class="bi bi-airplane"></i>
            </div>
            <div class="floating-element compass">
                <i class="bi bi-compass"></i>
            </div>
            
            <div class="hero-content">
                <h1 class="fade-in">Discover Your Next Adventure</h1>
                <p class="fade-in">Explore the world with our carefully curated travel packages and create memories that last a lifetime</p>
                <a href="#packages" class="btn btn-hero btn-lg fade-in">
                    <i class="bi bi-compass me-2"></i>Explore Destinations
                </a>
            </div>
        </section>

        <!-- Travel Packages Section -->
        <section id="packages" class="section">
            <div class="container">
                <div class="section-title fade-in">
                    <h2>Featured Travel Packages</h2>
                    <p>Discover amazing destinations with our handpicked travel packages designed for every type of traveler</p>
                </div>

                <div class="row g-4">
                    <!-- Package 1 -->
                    <div class="col-lg-4 col-md-6">
                        <div class="package-card fade-in">
                            <div class="package-image" style="background-image: url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80')">
                                <span class="package-badge">Popular</span>
                            </div>
                            <div class="package-content">
                                <h3 class="package-title">Santorini Paradise</h3>
                                <p class="package-description">Experience the breathtaking beauty of Santorini with stunning sunsets, white-washed buildings, and crystal-clear waters.</p>
                                <ul class="package-features">
                                    <li><i class="bi bi-check-circle-fill"></i> 7 Days / 6 Nights</li>
                                    <li><i class="bi bi-check-circle-fill"></i> 4-Star Hotel Stay</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Daily Breakfast</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Island Tours Included</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Airport Transfers</li>
                                </ul>
                                <div class="package-price">
                                    <span class="price">$1,299</span>
                                    <button class="btn btn-book" data-bs-toggle="modal" data-bs-target="#bookingModal" data-package="Santorini Paradise" data-price="$1,299">
                                        Book Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Package 2 -->
                    <div class="col-lg-4 col-md-6">
                        <div class="package-card fade-in">
                            <div class="package-image" style="background-image: url('https://images.unsplash.com/photo-1552733407-5d5c46c3bb3b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80')">
                                <span class="package-badge">Best Value</span>
                            </div>
                            <div class="package-content">
                                <h3 class="package-title">Bali Adventure</h3>
                                <p class="package-description">Immerse yourself in Bali's rich culture, lush landscapes, and pristine beaches. Perfect for adventure seekers.</p>
                                <ul class="package-features">
                                    <li><i class="bi bi-check-circle-fill"></i> 10 Days / 9 Nights</li>
                                    <li><i class="bi bi-check-circle-fill"></i> 3-Star Resort Stay</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Half Board Meals</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Temple Tours</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Volcano Trekking</li>
                                </ul>
                                <div class="package-price">
                                    <span class="price">$899</span>
                                    <button class="btn btn-book" data-bs-toggle="modal" data-bs-target="#bookingModal" data-package="Bali Adventure" data-price="$899">
                                        Book Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Package 3 -->
                    <div class="col-lg-4 col-md-6">
                        <div class="package-card fade-in">
                            <div class="package-image" style="background-image: url('https://images.unsplash.com/photo-1539650116574-75c0c6d73200?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80')">
                                <span class="package-badge">Luxury</span>
                            </div>
                            <div class="package-content">
                                <h3 class="package-title">Swiss Alps Escape</h3>
                                <p class="package-description">Experience the majestic Swiss Alps with luxurious accommodations and breathtaking mountain views.</p>
                                <ul class="package-features">
                                    <li><i class="bi bi-check-circle-fill"></i> 5 Days / 4 Nights</li>
                                    <li><i class="bi bi-check-circle-fill"></i> 5-Star Chalet</li>
                                    <li><i class="bi bi-check-circle-fill"></i> All Meals Included</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Ski Pass Included</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Private Transfers</li>
                                </ul>
                                <div class="package-price">
                                    <span class="price">$2,499</span>
                                    <button class="btn btn-book" data-bs-toggle="modal" data-bs-target="#bookingModal" data-package="Swiss Alps Escape" data-price="$2,499">
                                        Book Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Package 4 -->
                    <div class="col-lg-4 col-md-6">
                        <div class="package-card fade-in">
                            <div class="package-image" style="background-image: url('https://images.unsplash.com/photo-1564507592333-c60657eea523?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80')">
                                <span class="package-badge">Adventure</span>
                            </div>
                            <div class="package-content">
                                <h3 class="package-title">African Safari</h3>
                                <p class="package-description">Experience the wild beauty of Africa with guided safaris and luxury camps in the heart of the savanna.</p>
                                <ul class="package-features">
                                    <li><i class="bi bi-check-circle-fill"></i> 8 Days / 7 Nights</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Luxury Safari Lodge</li>
                                    <li><i class="bi bi-check-circle-fill"></i> All Meals & Drinks</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Game Drives</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Professional Guide</li>
                                </ul>
                                <div class="package-price">
                                    <span class="price">$3,199</span>
                                    <button class="btn btn-book" data-bs-toggle="modal" data-bs-target="#bookingModal" data-package="African Safari" data-price="$3,199">
                                        Book Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Package 5 -->
                    <div class="col-lg-4 col-md-6">
                        <div class="package-card fade-in">
                            <div class="package-image" style="background-image: url('https://images.unsplash.com/photo-1493976040374-85c8e12f0c0e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80')">
                                <span class="package-badge">Cultural</span>
                            </div>
                            <div class="package-content">
                                <h3 class="package-title">Japan Discovery</h3>
                                <p class="package-description">Discover the perfect blend of ancient traditions and modern innovation in the Land of the Rising Sun.</p>
                                <ul class="package-features">
                                    <li><i class="bi bi-check-circle-fill"></i> 12 Days / 11 Nights</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Traditional Ryokan</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Authentic Meals</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Temple Visits</li>
                                    <li><i class="bi bi-check-circle-fill"></i> JR Pass Included</li>
                                </ul>
                                <div class="package-price">
                                    <span class="price">$2,799</span>
                                    <button class="btn btn-book" data-bs-toggle="modal" data-bs-target="#bookingModal" data-package="Japan Discovery" data-price="$2,799">
                                        Book Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Package 6 -->
                    <div class="col-lg-4 col-md-6">
                        <div class="package-card fade-in">
                            <div class="package-image" style="background-image: url('https://images.unsplash.com/photo-1499856871958-5b9627545d1a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80')">
                                <span class="package-badge">Romantic</span>
                            </div>
                            <div class="package-content">
                                <h3 class="package-title">Paris Romance</h3>
                                <p class="package-description">Fall in love with the City of Lights. Perfect for couples seeking romance and elegance in the heart of France.</p>
                                <ul class="package-features">
                                    <li><i class="bi bi-check-circle-fill"></i> 6 Days / 5 Nights</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Boutique Hotel</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Seine River Cruise</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Eiffel Tower Dinner</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Museum Passes</li>
                                </ul>
                                <div class="package-price">
                                    <span class="price">$1,899</span>
                                    <button class="btn btn-book" data-bs-toggle="modal" data-bs-target="#bookingModal" data-package="Paris Romance" data-price="$1,899">
                                        Book Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="section contact-section">
            <div class="container">
                <div class="section-title fade-in">
                    <h2>Plan Your Dream Vacation</h2>
                    <p>Get in touch with our travel experts to customize your perfect getaway</p>
                </div>

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="contact-form fade-in">
                            <form method="POST" action="">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" placeholder="Your Name" name="name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="email" class="form-control" placeholder="Email Address" name="email" required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="tel" class="form-control" placeholder="Phone Number" name="phone" required>
                                    </div>
                                    <div class="col-md-6">
                                        <select class="form-control" name="destination" required>
                                            <option value="">Preferred Destination</option>
                                            <option value="santorini">Santorini, Greece</option>
                                            <option value="bali">Bali, Indonesia</option>
                                            <option value="swiss">Swiss Alps</option>
                                            <option value="africa">African Safari</option>
                                            <option value="japan">Japan</option>
                                            <option value="paris">Paris, France</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <textarea class="form-control" rows="5" placeholder="Tell us about your dream vacation..." name="message" required></textarea>
                                    </div>
                                    <div class="col-12 text-center">
                                        <button type="submit" name="submit_contact" class="btn btn-contact btn-lg">
                                            <i class="bi bi-send me-2"></i>Send Inquiry
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="row mt-5 text-center">
                    <div class="col-md-4 fade-in">
                        <div class="contact-info">
                            <i class="bi bi-geo-alt fs-1 mb-3"></i>
                            <h5>Visit Our Office</h5>
                            <p>123 Travel Street<br>Adventure City, AC 12345</p>
                        </div>
                    </div>
                    <div class="col-md-4 fade-in">
                        <div class="contact-info">
                            <i class="bi bi-telephone fs-1 mb-3"></i>
                            <h5>Call Us</h5>
                            <p>+1 (555) 123-4567<br>+1 (555) 987-6543</p>
                        </div>
                    </div>
                    <div class="col-md-4 fade-in">
                        <div class="contact-info">
                            <i class="bi bi-envelope fs-1 mb-3"></i>
                            <h5>Email Us</h5>
                            <p>info@wanderlust.com<br>support@wanderlust.com</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5><i class="bi bi-airplane me-2"></i>WanderLust Travel</h5>
                    <p class="text-muted">Your trusted partner in creating unforgettable travel experiences around the world.</p>
                    <div class="social-icons">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-twitter"></i></a>
                        <a href="#"><i class="bi bi-instagram"></i></a>
                        <a href="#"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#packages">Packages</a></li>
                        <li><a href="#contact">Contact</a></li>
                        <li><a href="#about">About Us</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5>Popular Destinations</h5>
                    <ul class="footer-links">
                        <li><a href="#">Santorini</a></li>
                        <li><a href="#">Bali</a></li>
                        <li><a href="#">Swiss Alps</a></li>
                        <li><a href="#">African Safari</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5>Travel Services</h5>
                    <ul class="footer-links">
                        <li><a href="#">Flight Booking</a></li>
                        <li><a href="#">Hotel Reservation</a></li>
                        <li><a href="#">Tour Packages</a></li>
                        <li><a href="#">Travel Insurance</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p>&copy; 2025 WanderLust Travel Agency. All rights reserved. | Designed with ❤️ for travelers</p>
            </div>
        </div>
    </footer>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--gradient-1); color: white;">
                    <h5 class="modal-title" id="bookingModalLabel">
                        <i class="bi bi-calendar-check me-2"></i>Book Your Package
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Package</label>
                                <input type="text" class="form-control" id="packageName" name="package_name" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="customer_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="customer_email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Preferred Travel Date</label>
                                <input type="date" class="form-control" name="travel_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Number of Travelers</label>
                                <select class="form-control" name="travelers" required>
                                    <option value="">Select...</option>
                                    <option value="1">1 Person</option>
                                    <option value="2">2 People</option>
                                    <option value="3">3 People</option>
                                    <option value="4">4 People</option>
                                    <option value="5+">5+ People</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="book_package" class="btn" style="background: var(--gradient-1); color: white;">
                            <i class="bi bi-check-circle me-2"></i>Confirm Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Fade in animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });

        // Auto-hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Booking modal functionality
        const bookingModal = document.getElementById('bookingModal');
        bookingModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const packageName = button.getAttribute('data-package');
            const packagePrice = button.getAttribute('data-price');
            
            const modalTitle = bookingModal.querySelector('.modal-title');
            const packageInput = bookingModal.querySelector('#packageName');
            
            modalTitle.innerHTML = `<i class="bi bi-calendar-check me-2"></i>Book ${packageName} - ${packagePrice}`;
            packageInput.value = `${packageName} - ${packagePrice}`;
        });

        // Navbar background change on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            }
        });

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.style.borderColor = '#e74c3c';
                    } else {
                        field.style.borderColor = '';
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                }
            });
        });
    </script>
</body>
</html>
