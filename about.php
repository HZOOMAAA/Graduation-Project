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
    <!-- Google Fonts: Work Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/about.css">
</head>
<body>
    <?php include 'includes/nav2.php'; ?>  

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 text-center text-lg-start mb-5 mb-lg-0">
                    <!-- <span class="badge bg-white text-primary px-3 py-2 border rounded-pill mb-3" style="color: var(--action-blue) !important;">Welcome to COVERLY</span> -->
                    <h1 class="mb-4">Insurance Made <br><span class="text-highlight">Simple & Transparent.</span></h1>
                    <p class="mb-4 fs-5" style="color: var(--text-main); opacity: 0.8;">Finding the right insurance used to be confusing. We built COVERLY to help you compare policies, understand the benefits, and choose the best plan for you—all online, with zero hassle.</p>
                    <div class="d-flex gap-3 justify-content-center justify-content-lg-start">
                        <a href="#services" class="btn btn-primary px-4 py-2" style="background-color: var(--action-blue); border: none; border-radius: 8px;">Explore Services</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?q=80&w=1000&auto=format&fit=crop" alt="COVERLY Team" class="img-fluid rounded-4 shadow-lg">
                </div>
            </div>
        </div>
    </section>

    <!-- Our Mission -->
    <section class="section-padding bg-white">
        <div class="container">
            <div class="mission-section shadow-sm">
                <div class="row align-items-center">
                    <div class="col-lg-7 mb-4 mb-lg-0 pe-lg-5">
                        <!-- <span class="badge text-primary px-3 py-2 border rounded-pill mb-3" style="background-color: var(--white); color: var(--action-blue) !important;">Our Mission</span> -->
                        <h2 class="fw-bold mb-3" style="color: var(--primary-navy);">Why We Built This Platform</h2>
                        <p class="fs-5 mb-3" style="color: var(--text-main); opacity: 0.8;">We noticed that buying insurance involves too much paperwork, hidden terms, and confusing jargon. As a team, we decided to digitize and simplify the entire process.</p>
                        <p class="fs-5 mb-0" style="color: var(--text-main); opacity: 0.8;">COVERLY acts as your personal digital broker. We gather the best medical, car, and life insurance plans from top providers, simplify the details, and present them to you clearly.</p>
                    </div>
                    <div class="col-lg-5 text-center">
                        <img src="https://cdn-icons-png.flaticon.com/512/2058/2058309.png" alt="Trust" style="width: 200px; opacity: 0.9;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services -->
    <section id="services" class="section-padding" style="background-color: var(--bg-soft-gray);">
                    <div class="text-center mb-5">
                <h2 class="fw-bold" style="color: var(--primary-navy);">Insurance For Everyone</h2>
                <p class="fs-5" style="color: var(--text-main); opacity: 0.8;">Whether you are protecting your family or your business, we've got you covered.</p>
            </div>
        <div class="container">

            
            <div class="row g-4 justify-content-center">
                <div class="col-md-6">
                    <div class="service-card shadow-sm">
                        <div class="d-flex align-items-center mb-4">
                            <div class="info-icon m-0 me-3"><i class="fa-solid fa-user-shield"></i></div>
                            <h3 class="fw-bold m-0" style="color: var(--primary-navy);">For Individuals</h3>
                        </div>
                        <p class="mb-4" style="color: var(--text-main); opacity: 0.8;">Protect yourself and your loved ones with tailored plans that fit your budget.</p>
                        <ul class="list-unstyled service-list">
                            <li><i class="fa-solid fa-heart-pulse"></i> Medical & Health Insurance</li>
                            <li><i class="fa-solid fa-car"></i> Comprehensive Car Insurance</li>
                            <li><i class="fa-solid fa-house"></i> Home & Property Protection</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="service-card shadow-sm">
                        <div class="d-flex align-items-center mb-4">
                            <div class="info-icon m-0 me-3"><i class="fa-solid fa-building-shield"></i></div>
                            <h3 class="fw-bold m-0" style="color: var(--primary-navy);">For Businesses</h3>
                        </div>
                        <p class="mb-4" style="color: var(--text-main); opacity: 0.8;">Secure your company's assets and provide top-tier benefits for your employees.</p>
                        <ul class="list-unstyled service-list">
                            <li><i class="fa-solid fa-users"></i> Corporate Team Health Plans</li>
                            <li><i class="fa-solid fa-truck-fast"></i> Fleet & Logistics Insurance</li>
                            <li><i class="fa-solid fa-fire-shield"></i> Commercial Property Coverage</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Slider Section -->
<section class="team-slider-section">
    <!-- الـ Wrapper الأساسي الموحد البديل للـ Row -->
    <div class="team-slider-wrapper" id="team-slider-container">
        
        <!-- صندوق الصورة والـ Shapes في أقصى الشمال -->
        <div class="team-image-column">
            <div class="team-img-wrapper">
                <div class="shape-back"></div>
                <div class="shape-main">
                    <img id="team-img" src="https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?q=80&w=600&auto=format&fit=crop" alt="Team Member">
                </div>
                <div class="shape-cutout"></div>
            </div>
        </div>
        
        <!-- صندوق النصوص والأزرار في اليمين -->
        <div class="team-content-column">
            <h2 class="team-header-title">Meet <br> COVERLY Team</h2>
            
            <h3 id="team-name" class="member-name">Mahmoud Diaa</h3>
            <p id="team-desc" class="member-desc">
                Mahmoud is responsible for leading our exceptional team of developers. He plays a crucial role in overseeing all stages of development, ensuring our digital products perform at the highest levels of technical efficiency and security.
            </p>
            <h5 id="team-role" class="member-role">Lead Full-Stack Developer</h5>
            
            <!-- الأزرار هتنزل تحت براحتها بعيد عن بروز الصورة -->
            <div class="team-slider-controls">
                <button onclick="changeMember(-1)"><i class="fas fa-chevron-left"></i></button>
                <button onclick="changeMember(1)"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>

    </div>
</section>
    <?php include 'includes/footer.php'; ?>  

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/scriptabout.js"></script>

</body>
</html>