<?php
session_start();

// Sample events data (in real application, this would be from database)
$events = [
    [
        'id' => 1,
        'title' => 'Tech Innovation Summit 2024',
        'description' => 'Join industry leaders and tech enthusiasts for a day of innovation, networking, and insights into the future of technology.',
        'category' => 'Technology',
        'date' => '2024-12-15',
        'time' => '09:00',
        'duration' => '8 hours',
        'venue' => 'Convention Center, Hall A',
        'address' => '123 Business District, Tech City',
        'price' => 149.99,
        'capacity' => 500,
        'booked' => 342,
        'image' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400&h=250&fit=crop',
        'featured' => true,
        'status' => 'available',
        'organizer' => 'TechCorp Events',
        'tags' => ['Technology', 'Innovation', 'Networking'],
        'highlights' => ['Keynote Speakers', 'Interactive Workshops', 'Networking Lunch', 'Certificate of Attendance']
    ],
    [
        'id' => 2,
        'title' => 'Digital Marketing Masterclass',
        'description' => 'Learn the latest digital marketing strategies from industry experts. Perfect for marketers, entrepreneurs, and business owners.',
        'category' => 'Business',
        'date' => '2024-12-20',
        'time' => '14:00',
        'duration' => '4 hours',
        'venue' => 'Business Hub, Conference Room 1',
        'address' => '456 Marketing Avenue, Business District',
        'price' => 89.99,
        'capacity' => 100,
        'booked' => 76,
        'image' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?w=400&h=250&fit=crop',
        'featured' => false,
        'status' => 'available',
        'organizer' => 'Digital Pro Academy',
        'tags' => ['Marketing', 'Digital', 'Business'],
        'highlights' => ['Expert Speakers', 'Hands-on Exercises', 'Course Materials', 'Q&A Session']
    ],
    [
        'id' => 3,
        'title' => 'Startup Pitch Competition',
        'description' => 'Watch emerging startups pitch their innovative ideas to a panel of investors and industry experts.',
        'category' => 'Startup',
        'date' => '2024-12-25',
        'time' => '18:00',
        'duration' => '3 hours',
        'venue' => 'Innovation Hub Auditorium',
        'address' => '789 Startup Lane, Innovation District',
        'price' => 25.00,
        'capacity' => 200,
        'booked' => 156,
        'image' => 'https://images.unsplash.com/photo-1559136555-9303baea8ebd?w=400&h=250&fit=crop',
        'featured' => true,
        'status' => 'available',
        'organizer' => 'Startup Community',
        'tags' => ['Startup', 'Innovation', 'Investment'],
        'highlights' => ['Live Pitches', 'Investor Panel', 'Networking', 'Awards Ceremony']
    ],
    [
        'id' => 4,
        'title' => 'Photography Workshop',
        'description' => 'Master the art of photography with professional photographers. Learn composition, lighting, and post-processing techniques.',
        'category' => 'Arts',
        'date' => '2024-12-28',
        'time' => '10:00',
        'duration' => '6 hours',
        'venue' => 'Creative Studio, Workshop Space',
        'address' => '321 Arts Quarter, Creative District',
        'price' => 129.99,
        'capacity' => 30,
        'booked' => 28,
        'image' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=400&h=250&fit=crop',
        'featured' => false,
        'status' => 'almost_full',
        'organizer' => 'Photo Masters Guild',
        'tags' => ['Photography', 'Arts', 'Creative'],
        'highlights' => ['Professional Equipment', 'Outdoor Session', 'Editing Software Training', 'Portfolio Review']
    ],
    [
        'id' => 5,
        'title' => 'Web Development Bootcamp',
        'description' => 'Intensive web development training covering HTML, CSS, JavaScript, and modern frameworks.',
        'category' => 'Education',
        'date' => '2024-12-30',
        'time' => '09:00',
        'duration' => '2 days',
        'venue' => 'Tech Academy, Lab 1',
        'address' => '555 Learning Street, Education Hub',
        'price' => 299.99,
        'capacity' => 50,
        'booked' => 50,
        'image' => 'https://images.unsplash.com/photo-1517180102446-f3ece451e9d8?w=400&h=250&fit=crop',
        'featured' => false,
        'status' => 'sold_out',
        'organizer' => 'Code Academy Pro',
        'tags' => ['Programming', 'Web Development', 'Education'],
        'highlights' => ['Hands-on Projects', 'Industry Mentors', 'Job Placement Support', 'Certificate']
    ]
];

// Sample bookings data
$bookings = [
    [
        'id' => 1,
        'event_id' => 1,
        'booking_reference' => 'EVT-001-2024',
        'customer_name' => 'John Doe',
        'customer_email' => 'john@example.com',
        'customer_phone' => '+1-555-0123',
        'tickets' => 2,
        'total_amount' => 299.98,
        'booking_date' => '2024-03-20',
        'status' => 'confirmed',
        'payment_method' => 'Credit Card'
    ]
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['book_event'])) {
        // Process event booking
        $event_id = intval($_POST['event_id']);
        $customer_name = htmlspecialchars($_POST['customer_name']);
        $customer_email = htmlspecialchars($_POST['customer_email']);
        $customer_phone = htmlspecialchars($_POST['customer_phone']);
        $tickets = intval($_POST['tickets']);
        $payment_method = htmlspecialchars($_POST['payment_method']);
        
        // Find the event
        $selected_event = array_filter($events, function($event) use ($event_id) {
            return $event['id'] == $event_id;
        });
        
        if (!empty($selected_event)) {
            $event = array_values($selected_event)[0];
            $total_amount = $event['price'] * $tickets;
            $booking_reference = 'EVT-' . str_pad($event_id, 3, '0', STR_PAD_LEFT) . '-' . date('Y');
            
            // In real application, save to database and process payment
            $booking_success = true;
            $success_message = "Booking confirmed! Your reference number is: " . $booking_reference;
        }
    }
}

// Statistics
$total_events = count($events);
$total_bookings = count($bookings);
$featured_events = count(array_filter($events, function($event) { return $event['featured']; }));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventPro - Online Event Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3b82f6;
            --secondary-color: #8b5cf6;
            --accent-color: #06b6d4;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --border-color: #e2e8f0;
            --text-muted: #64748b;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            --gradient-accent: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--light-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
        }

        /* Navigation */
        .navbar {
            background: white;
            box-shadow: var(--shadow-md);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 900;
            font-size: 1.8rem;
            color: var(--primary-color) !important;
            text-decoration: none;
        }

        .nav-link {
            color: var(--dark-color) !important;
            font-weight: 500;
            padding: 8px 16px !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin: 0 2px;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color) !important;
            background: rgba(59, 130, 246, 0.1);
        }

        /* Hero Section */
        .hero-section {
            background: var(--gradient-primary);
            padding: 100px 0;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" preserveAspectRatio="none"><polygon fill="%23ffffff15" points="1000,100 1000,0 0,0 0,85"/></svg>') no-repeat bottom;
            background-size: cover;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 900;
            margin-bottom: 1.5rem;
            background: linear-gradient(45deg, #fff, #e0e7ff);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.1;
        }

        .hero-subtitle {
            font-size: 1.4rem;
            opacity: 0.9;
            margin-bottom: 2.5rem;
            font-weight: 400;
        }

        .hero-search {
            background: white;
            border-radius: 15px;
            padding: 10px;
            box-shadow: var(--shadow-xl);
            max-width: 600px;
            margin: 0 auto;
        }

        .search-input {
            border: none;
            padding: 15px 20px;
            font-size: 16px;
            border-radius: 10px;
        }

        .search-input:focus {
            outline: none;
            box-shadow: none;
        }

        .search-btn {
            background: var(--primary-color);
            border: none;
            border-radius: 10px;
            padding: 15px 25px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        /* Statistics Section */
        .stats-section {
            margin-top: -60px;
            position: relative;
            z-index: 10;
        }

        .stats-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            height: 100%;
        }

        .stats-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .stats-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
            color: white;
        }

        .stats-number {
            font-size: 3rem;
            font-weight: 900;
            color: var(--dark-color);
            margin-bottom: 10px;
        }

        .stats-label {
            color: var(--text-muted);
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Events Section */
        .events-section {
            padding: 80px 0;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark-color);
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-subtitle {
            text-align: center;
            color: var(--text-muted);
            font-size: 1.2rem;
            margin-bottom: 4rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 40px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .filter-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 20px;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 1px solid var(--border-color);
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }

        /* Event Cards */
        .event-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
        }

        .event-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
        }

        .event-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .event-card:hover .event-image {
            transform: scale(1.05);
        }

        .event-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--primary-color);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .featured-badge {
            background: var(--warning-color);
        }

        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-available {
            background: #d1fae5;
            color: #065f46;
        }

        .status-almost_full {
            background: #fef3c7;
            color: #92400e;
        }

        .status-sold_out {
            background: #fee2e2;
            color: #991b1b;
        }

        .event-content {
            padding: 25px;
        }

        .event-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .event-description {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .event-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            color: var(--text-muted);
            font-size: 13px;
        }

        .meta-item i {
            margin-right: 6px;
            color: var(--primary-color);
        }

        .event-price {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .availability-bar {
            background: #e2e8f0;
            border-radius: 10px;
            height: 6px;
            margin-bottom: 10px;
            overflow: hidden;
        }

        .availability-fill {
            height: 100%;
            background: var(--success-color);
            transition: width 0.3s ease;
        }

        .availability-text {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 15px;
        }

        /* Buttons */
        .btn {
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
        }

        .btn-success {
            background: var(--success-color);
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-lg {
            padding: 15px 30px;
            font-size: 16px;
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: var(--shadow-xl);
        }

        .modal-header {
            background: var(--gradient-primary);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 25px;
        }

        .modal-title {
            font-weight: 700;
            font-size: 1.3rem;
        }

        .btn-close {
            filter: invert(1);
        }

        .modal-body {
            padding: 30px;
        }

        .modal-footer {
            padding: 25px;
            border-top: 1px solid var(--border-color);
        }

        /* Event Details in Modal */
        .event-details {
            background: var(--light-color);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: var(--dark-color);
        }

        .detail-value {
            color: var(--text-muted);
        }

        /* Form Styles */
        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
        }

        .booking-summary {
            background: var(--light-color);
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .summary-total {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--primary-color);
            border-top: 1px solid var(--border-color);
            padding-top: 10px;
        }

        /* Alerts */
        .alert {
            border-radius: 15px;
            border: none;
            padding: 20px 25px;
            margin-bottom: 25px;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border-left: 4px solid var(--success-color);
        }

        /* Tags */
        .event-tags {
            margin-bottom: 15px;
        }

        .tag {
            display: inline-block;
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-color);
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            margin-right: 8px;
            margin-bottom: 5px;
        }

        /* Highlights */
        .event-highlights {
            background: var(--light-color);
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }

        .highlights-title {
            font-weight: 600;
            font-size: 14px;
            color: var(--dark-color);
            margin-bottom: 10px;
        }

        .highlight-item {
            display: flex;
            align-items: center;
            color: var(--text-muted);
            font-size: 13px;
            margin-bottom: 5px;
        }

        .highlight-item i {
            color: var(--success-color);
            margin-right: 8px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .stats-section {
                margin-top: -40px;
            }

            .stats-card {
                padding: 25px;
                margin-bottom: 20px;
            }

            .stats-number {
                font-size: 2.5rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .filter-section {
                padding: 20px;
            }

            .event-content {
                padding: 20px;
            }

            .modal-body {
                padding: 20px;
            }
        }

        /* Loading Animation */
        .loading {
            display: none;
            text-align: center;
            padding: 40px;
        }

        .spinner-border {
            color: var(--primary-color);
        }

        /* Custom Animations */
        .fade-in {
            animation: fadeIn 0.6s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-up {
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Footer */
        .footer {
            background: var(--dark-color);
            color: white;
            padding: 50px 0 30px;
            margin-top: auto;
        }

        .footer h6 {
            color: white;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .footer p,
        .footer a {
            color: #9ca3af;
            text-decoration: none;
            line-height: 1.8;
        }

        .footer a:hover {
            color: white;
        }

        .footer-bottom {
            border-top: 1px solid #374151;
            margin-top: 40px;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <a class="navbar-brand" href="#home">
                    <i class="bi bi-calendar-event me-2"></i>EventPro
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link active" href="#home">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#events">Events</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#categories">Categories</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#about">About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#contact">Contact</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="hero-section" id="home">
            <div class="hero-content" data-aos="fade-up">
                <div class="container">
                    <div class="row justify-content-center text-center">
                        <div class="col-lg-10">
                            <h1 class="hero-title">Discover Amazing Events</h1>
                            <p class="hero-subtitle">Find and book tickets for conferences, workshops, concerts, and more</p>
                            
                            <!-- Hero Search -->
                            <div class="hero-search">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <input type="text" class="form-control search-input" placeholder="Search events...">
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-select search-input">
                                            <option value="">All Categories</option>
                                            <option value="technology">Technology</option>
                                            <option value="business">Business</option>
                                            <option value="arts">Arts</option>
                                            <option value="education">Education</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="date" class="form-control search-input">
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn search-btn w-100">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Statistics Section -->
        <section class="stats-section" data-aos="fade-up">
            <div class="container">
                <div class="row g-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card">
                            <div class="stats-icon" style="background: var(--gradient-primary);">
                                <i class="bi bi-calendar-event"></i>
                            </div>
                            <div class="stats-number"><?php echo $total_events; ?></div>
                            <div class="stats-label">Total Events</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card">
                            <div class="stats-icon" style="background: var(--gradient-secondary);">
                                <i class="bi bi-ticket-perforated"></i>
                            </div>
                            <div class="stats-number">1,250</div>
                            <div class="stats-label">Tickets Sold</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card">
                            <div class="stats-icon" style="background: var(--gradient-accent);">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="stats-number">850</div>
                            <div class="stats-label">Happy Customers</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card">
                            <div class="stats-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                                <i class="bi bi-star-fill"></i>
                            </div>
                            <div class="stats-number">4.9</div>
                            <div class="stats-label">Average Rating</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Success Alert -->
        <?php if (isset($booking_success) && $booking_success): ?>
            <div class="container mt-4">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Events Section -->
        <section class="events-section" id="events">
            <div class="container">
                <h2 class="section-title" data-aos="fade-up">Featured Events</h2>
                <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">
                    Discover the most popular and highly-rated events happening near you
                </p>

                <!-- Filter Section -->
                <div class="filter-section" data-aos="fade-up" data-aos-delay="200">
                    <h5 class="filter-title"><i class="bi bi-funnel me-2"></i>Filter Events</h5>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <select class="form-select" id="categoryFilter">
                                <option value="">All Categories</option>
                                <option value="Technology">Technology</option>
                                <option value="Business">Business</option>
                                <option value="Startup">Startup</option>
                                <option value="Arts">Arts</option>
                                <option value="Education">Education</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="priceFilter">
                                <option value="">All Prices</option>
                                <option value="0-50">$0 - $50</option>
                                <option value="50-100">$50 - $100</option>
                                <option value="100-200">$100 - $200</option>
                                <option value="200+">$200+</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="dateFilter">
                                <option value="">All Dates</option>
                                <option value="today">Today</option>
                                <option value="tomorrow">Tomorrow</option>
                                <option value="this_week">This Week</option>
                                <option value="this_month">This Month</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-primary w-100" onclick="resetFilters()">
                                <i class="bi bi-arrow-clockwise me-2"></i>Reset Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Events Grid -->
                <div class="row g-4" id="eventsContainer">
                    <?php foreach ($events as $index => $event): ?>
                        <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo ($index * 100) + 300; ?>">
                            <div class="event-card" data-category="<?php echo $event['category']; ?>" data-price="<?php echo $event['price']; ?>">
                                <div class="position-relative">
                                    <img src="<?php echo $event['image']; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="event-image">
                                    <?php if ($event['featured']): ?>
                                        <span class="event-badge featured-badge">Featured</span>
                                    <?php else: ?>
                                        <span class="event-badge"><?php echo $event['category']; ?></span>
                                    <?php endif; ?>
                                    <span class="status-badge status-<?php echo $event['status']; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $event['status'])); ?>
                                    </span>
                                </div>
                                
                                <div class="event-content">
                                    <h5 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                    <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>
                                    
                                    <div class="event-tags">
                                        <?php foreach ($event['tags'] as $tag): ?>
                                            <span class="tag"><?php echo $tag; ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="event-meta">
                                        <div class="meta-item">
                                            <i class="bi bi-calendar3"></i>
                                            <?php echo date('M d, Y', strtotime($event['date'])); ?>
                                        </div>
                                        <div class="meta-item">
                                            <i class="bi bi-clock"></i>
                                            <?php echo date('h:i A', strtotime($event['time'])); ?>
                                        </div>
                                        <div class="meta-item">
                                            <i class="bi bi-geo-alt"></i>
                                            <?php echo $event['venue']; ?>
                                        </div>
                                        <div class="meta-item">
                                            <i class="bi bi-person"></i>
                                            <?php echo $event['organizer']; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="event-price">$<?php echo number_format($event['price'], 2); ?></div>
                                    
                                    <?php if ($event['status'] !== 'sold_out'): ?>
                                        <div class="availability-bar">
                                            <div class="availability-fill" style="width: <?php echo ($event['booked'] / $event['capacity']) * 100; ?>%"></div>
                                        </div>
                                        <div class="availability-text">
                                            <?php echo $event['capacity'] - $event['booked']; ?> of <?php echo $event['capacity']; ?> tickets remaining
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($event['status'] === 'sold_out'): ?>
                                        <button class="btn btn-secondary w-100" disabled>
                                            <i class="bi bi-x-circle me-2"></i>Sold Out
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-primary w-100" onclick="openBookingModal(<?php echo $event['id']; ?>)">
                                            <i class="bi bi-ticket-perforated me-2"></i>Book Now
                                        </button>
                                    <?php endif; ?>
                                    
                                    <div class="event-highlights">
                                        <div class="highlights-title">Event Highlights:</div>
                                        <?php foreach ($event['highlights'] as $highlight): ?>
                                            <div class="highlight-item">
                                                <i class="bi bi-check-circle-fill"></i>
                                                <?php echo $highlight; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </div>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingModalLabel">
                        <i class="bi bi-ticket-perforated me-2"></i>Book Event Ticket
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="bookingForm">
                    <div class="modal-body">
                        <div id="eventDetails" class="event-details">
                            <!-- Event details will be populated by JavaScript -->
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="customer_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="customer_email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="customer_phone" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Number of Tickets</label>
                                <select class="form-select" name="tickets" id="ticketsSelect" onchange="updateTotal()">
                                    <option value="1">1 Ticket</option>
                                    <option value="2">2 Tickets</option>
                                    <option value="3">3 Tickets</option>
                                    <option value="4">4 Tickets</option>
                                    <option value="5">5 Tickets</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Payment Method</label>
                                <select class="form-select" name="payment_method" required>
                                    <option value="">Select Payment Method</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Debit Card">Debit Card</option>
                                    <option value="PayPal">PayPal</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="booking-summary">
                            <h6>Booking Summary</h6>
                            <div class="summary-row">
                                <span>Ticket Price:</span>
                                <span id="ticketPrice">$0.00</span>
                            </div>
                            <div class="summary-row">
                                <span>Quantity:</span>
                                <span id="ticketQuantity">1</span>
                            </div>
                            <div class="summary-row">
                                <span>Service Fee:</span>
                                <span>$5.00</span>
                            </div>
                            <div class="summary-row summary-total">
                                <span>Total Amount:</span>
                                <span id="totalAmount">$0.00</span>
                            </div>
                        </div>
                        
                        <input type="hidden" name="event_id" id="selectedEventId">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="book_event" class="btn btn-success btn-lg">
                            <i class="bi bi-credit-card me-2"></i>Confirm Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h6><i class="bi bi-calendar-event me-2"></i>EventPro</h6>
                    <p>Your premier destination for discovering and booking amazing events. Connect with experiences that matter.</p>
                    <div class="social-links">
                        <a href="#" class="me-3"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="me-3"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="me-3"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="me-3"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>Quick Links</h6>
                    <p><a href="#events">Browse Events</a></p>
                    <p><a href="#categories">Categories</a></p>
                    <p><a href="#about">About Us</a></p>
                    <p><a href="#contact">Contact</a></p>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6>Event Categories</h6>
                    <p><a href="#">Technology</a></p>
                    <p><a href="#">Business</a></p>
                    <p><a href="#">Arts & Culture</a></p>
                    <p><a href="#">Education</a></p>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6>Contact Info</h6>
                    <p><i class="bi bi-envelope me-2"></i>events@eventpro.com</p>
                    <p><i class="bi bi-telephone me-2"></i>+1 (555) 123-4567</p>
                    <p><i class="bi bi-geo-alt me-2"></i>123 Event Street, City, State 12345</p>
                </div>
            </div>
            <div class="footer-bottom text-center">
                <p>&copy; 2024 EventPro. All rights reserved. | Privacy Policy | Terms of Service</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });

        // Events data for JavaScript
        const events = <?php echo json_encode($events); ?>;
        let selectedEvent = null;

        // Open booking modal
        function openBookingModal(eventId) {
            selectedEvent = events.find(event => event.id === eventId);
            if (!selectedEvent) return;

            // Populate event details
            const eventDetails = document.getElementById('eventDetails');
            eventDetails.innerHTML = `
                <h6>${selectedEvent.title}</h6>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">${new Date(selectedEvent.date).toLocaleDateString('en-US', { 
                        year: 'numeric', month: 'long', day: 'numeric' 
                    })}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time:</span>
                    <span class="detail-value">${selectedEvent.time}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Duration:</span>
                    <span class="detail-value">${selectedEvent.duration}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Venue:</span>
                    <span class="detail-value">${selectedEvent.venue}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Address:</span>
                    <span class="detail-value">${selectedEvent.address}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Organizer:</span>
                    <span class="detail-value">${selectedEvent.organizer}</span>
                </div>
            `;

            // Set event ID and update pricing
            document.getElementById('selectedEventId').value = eventId;
            updateTotal();

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
            modal.show();
        }

        // Update total calculation
        function updateTotal() {
            if (!selectedEvent) return;

            const tickets = parseInt(document.getElementById('ticketsSelect').value);
            const ticketPrice = selectedEvent.price;
            const serviceFee = 5.00;
            const totalAmount = (ticketPrice * tickets) + serviceFee;

            document.getElementById('ticketPrice').textContent = `$${ticketPrice.toFixed(2)}`;
            document.getElementById('ticketQuantity').textContent = tickets;
            document.getElementById('totalAmount').textContent = `$${totalAmount.toFixed(2)}`;
        }

        // Filter functionality
        function filterEvents() {
            const categoryFilter = document.getElementById('categoryFilter').value;
            const priceFilter = document.getElementById('priceFilter').value;
            const eventCards = document.querySelectorAll('.event-card');

            eventCards.forEach(card => {
                const cardCategory = card.dataset.category;
                const cardPrice = parseFloat(card.dataset.price);
                
                let showCard = true;

                // Category filter
                if (categoryFilter && cardCategory !== categoryFilter) {
                    showCard = false;
                }

                // Price filter
                if (priceFilter && showCard) {
                    switch (priceFilter) {
                        case '0-50':
                            showCard = cardPrice <= 50;
                            break;
                        case '50-100':
                            showCard = cardPrice > 50 && cardPrice <= 100;
                            break;
                        case '100-200':
                            showCard = cardPrice > 100 && cardPrice <= 200;
                            break;
                        case '200+':
                            showCard = cardPrice > 200;
                            break;
                    }
                }

                // Show/hide card
                const cardContainer = card.closest('.col-lg-4');
                if (showCard) {
                    cardContainer.style.display = 'block';
                } else {
                    cardContainer.style.display = 'none';
                }
            });
        }

        // Reset filters
        function resetFilters() {
            document.getElementById('categoryFilter').value = '';
            document.getElementById('priceFilter').value = '';
            document.getElementById('dateFilter').value = '';
            filterEvents();
        }

        // Event listeners
        document.getElementById('categoryFilter').addEventListener('change', filterEvents);
        document.getElementById('priceFilter').addEventListener('change', filterEvents);
        document.getElementById('dateFilter').addEventListener('change', filterEvents);

        // Form validation
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });

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

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
                navbar.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.1)';
            } else {
                navbar.style.background = 'white';
                navbar.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';
            }
        });

        // Auto-dismiss alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (alert.classList.contains('show')) {
                    alert.classList.remove('show');
                    setTimeout(function() {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 500);
                }
            });
        }, 5000);

        // Search functionality
        document.querySelector('.search-btn').addEventListener('click', function() {
            const searchTerm = document.querySelector('.search-input').value.toLowerCase();
            const eventCards = document.querySelectorAll('.event-card');
            
            eventCards.forEach(card => {
                const cardContent = card.textContent.toLowerCase();
                const cardContainer = card.closest('.col-lg-4');
                
                if (cardContent.includes(searchTerm) || searchTerm === '') {
                    cardContainer.style.display = 'block';
                } else {
                    cardContainer.style.display = 'none';
                }
            });
        });

        // Enter key search
        document.querySelector('.search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('.search-btn').click();
            }
        });
    </script>
</body>
</html>