<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | COVERLY</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/about.css">
</head>
<body>
    <?php include 'includes/nav2.php'; ?>  

    <!-- Hero Section -->
    <section class="about-hero">
        <div class="hero-bg-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 text-center text-lg-start mb-5 mb-lg-0" data-aos="fade-right" data-aos-duration="800">
                    <span class="hero-badge">About COVERLY</span>
                    <h1 class="hero-title">Insurance Made <br><span class="text-highlight">Simple & Transparent.</span></h1>
                    <p class="hero-subtitle">Finding the right insurance used to be confusing. We built COVERLY to help you compare policies, understand the benefits, and choose the best plan for you - all online, with zero hassle.</p>
                    <div class="hero-buttons">
                        <a href="homepage.php#services" class="btn btn-primary">Explore Services</a>
                        <a href="#team" class="btn btn-outline">Meet Our Team</a>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left" data-aos-duration="800" data-aos-delay="200">
                    <div class="hero-image-wrapper">
                        <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?q=80&w=1000&auto=format&fit=crop" alt="COVERLY Team collaborating" class="hero-image">
                        <div class="hero-image-badge">
                            <span class="badge-number">6+</span>
                            <span class="badge-text">Years Experience</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="about-stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <span class="stat-number" data-count="15000">0</span>
                    <span class="stat-label">Happy Customers</span>
                </div>
                <div class="stat-item" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-icon">
                        <i class="bi bi-building"></i>
                    </div>
                    <span class="stat-number" data-count="50">0</span>
                    <span class="stat-label">Insurance Partners</span>
                </div>
                <div class="stat-item" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-icon">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <span class="stat-number" data-count="98">0</span>
                    <span class="stat-suffix">%</span>
                    <span class="stat-label">Satisfaction Rate</span>
                </div>
                <div class="stat-item" data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <span class="stat-number" data-count="25000">0</span>
                    <span class="stat-label">Policies Issued</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Mission -->
    <section class="mission-section section-padding">
        <div class="container">
            <div class="mission-card" data-aos="fade-up">
                <div class="row align-items-center">
                    <div class="col-lg-7 mb-4 mb-lg-0 pe-lg-5">
                        <span class="section-badge">Our Mission</span>
                        <h2 class="section-title">Why We Built This Platform</h2>
                        <p class="section-text">We noticed that buying insurance involves too much paperwork, hidden terms, and confusing jargon. As a team, we decided to digitize and simplify the entire process.</p>
                        <p class="section-text mb-0">COVERLY acts as your personal digital broker. We gather the best medical, car, and life insurance plans from top providers, simplify the details, and present them to you clearly.</p>
                        <div class="mission-features">
                            <div class="mission-feature">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>100% Transparent Pricing</span>
                            </div>
                            <div class="mission-feature">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>No Hidden Fees</span>
                            </div>
                            <div class="mission-feature">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Expert Support 24/7</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 text-center" data-aos="zoom-in" data-aos-delay="200">
                        <div class="mission-image-wrapper">
                            <img src="https://cdn-icons-png.flaticon.com/512/2058/2058309.png" alt="Trust and Security" class="mission-image">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Values -->
    <section class="values-section section-padding">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-badge" data-aos="fade-up">Our Core Values</span>
                <h2 class="section-title text-center" data-aos="fade-up" data-aos-delay="100">What Drives Us Every Day</h2>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="bi bi-transparency"></i>
                        </div>
                        <h3 class="value-title">Transparency</h3>
                        <p class="value-text">No hidden terms, no surprise fees. What you see is exactly what you get.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="bi bi-heart-fill"></i>
                        </div>
                        <h3 class="value-title">Customer First</h3>
                        <p class="value-text">Every decision we make starts with one question: how does this help our customers?</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="bi bi-lightning-charge-fill"></i>
                        </div>
                        <h3 class="value-title">Innovation</h3>
                        <p class="value-text">We leverage technology to make insurance faster, smarter, and more accessible.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="400">
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <h3 class="value-title">Security</h3>
                        <p class="value-text">Your data is protected with bank-level encryption and strict privacy protocols.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services -->
    <section id="services" class="services-section section-padding">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-badge" data-aos="fade-up">Our Services</span>
                <h2 class="section-title text-center" data-aos="fade-up" data-aos-delay="100">Insurance For Everyone</h2>
                <p class="section-subtitle" data-aos="fade-up" data-aos-delay="150">Whether you are protecting your family or your business, we have got you covered.</p>
            </div>
            
            <div class="row g-4 justify-content-center">
                <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="service-card">
                        <div class="service-header">
                            <div class="service-icon"><i class="fa-solid fa-user-shield"></i></div>
                            <h3 class="service-title">For Individuals</h3>
                        </div>
                        <p class="service-description">Protect yourself and your loved ones with tailored plans that fit your budget.</p>
                        <ul class="service-list">
                            <li><i class="fa-solid fa-heart-pulse"></i> Medical & Health Insurance</li>
                            <li><i class="fa-solid fa-car"></i> Comprehensive Car Insurance</li>
                            <li><i class="fa-solid fa-house"></i> Home & Property Protection</li>
                        </ul>
                        <a href="homepage.php#services" class="service-link">Explore Plans <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
                <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="service-card">
                        <div class="service-header">
                            <div class="service-icon"><i class="fa-solid fa-building-shield"></i></div>
                            <h3 class="service-title">For Businesses</h3>
                        </div>
                        <p class="service-description">Secure your company assets and provide top-tier benefits for your employees.</p>
                        <ul class="service-list">
                            <li><i class="fa-solid fa-users"></i> Corporate Team Health Plans</li>
                            <li><i class="fa-solid fa-truck-fast"></i> Fleet & Logistics Insurance</li>
                            <li><i class="fa-solid fa-shield"></i> Commercial Property Coverage</li>
                        </ul>
                        <a href="homepage.php#services" class="service-link">Explore Plans <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Slider Section -->
<section class="team-slider-section" id="team">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-badge" data-aos="fade-up">Our Team</span>
            <h2 class="section-title text-center" data-aos="fade-up" data-aos-delay="100">Meet the COVERLY Team</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="150">The passionate people behind your insurance experience</p>
        </div>
    </div>
    
    <div class="team-slider-wrapper" id="team-slider-container">
        
        <!-- Image Column -->
        <div class="team-image-column" data-aos="fade-right" data-aos-duration="800">
            <div class="team-img-wrapper">
                <div class="shape-back"></div>
                <div class="shape-main">
                    <img id="team-img" src="assets/img/mahmoud.png" alt="Team Member">
                </div>
                <div class="shape-cutout"></div>
            </div>
        </div>
        
        <!-- Content Column -->
        <div class="team-content-column" data-aos="fade-left" data-aos-duration="800" data-aos-delay="200">
            <div class="team-member-info">
                <h3 id="team-name" class="member-name">Mahmoud Diaa</h3>
                <h5 id="team-role" class="member-role">Lead Full-Stack Developer</h5>
                <p id="team-desc" class="member-desc">
                    Mahmoud is responsible for leading our exceptional team of developers. He plays a crucial role in overseeing all stages of development, ensuring our digital products perform at the highest levels of technical efficiency and security.
                </p>
                <div class="member-social">
                    <a href="#" class="social-link"><i class="bi bi-linkedin"></i></a>
                    <a href="#" class="social-link"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="social-link"><i class="bi bi-envelope-fill"></i></a>
                </div>
            </div>
            
            <!-- Slider Controls -->
            <div class="team-slider-controls">
                <button onclick="changeMember(-1)" aria-label="Previous team member"><i class="fas fa-chevron-left"></i></button>
                <div class="slider-dots" id="slider-dots"></div>
                <button onclick="changeMember(1)" aria-label="Next team member"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>

    </div>
</section>

    <!-- CTA Section -->
    <section class="cta-section" data-aos="fade-up">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Get Started?</h2>
                <p>Join thousands of satisfied customers who trust COVERLY for their insurance needs.</p>
                <div class="cta-buttons">
                    <a href="homepage.php#services" class="btn btn-primary">Explore Services</a>
                    <a href="contact.php" class="btn btn-outline-light">Contact Us</a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>  

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/scriptabout.js"></script>

</body>
</html>
