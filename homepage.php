  <?php
  include __DIR__ . '/includes/nav2.php'; 
  ?>

    <!-- nav&hero -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- why choose -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/homepage.css">


    <div class="swiper mySwiper" id="services">
        <div class="swiper-wrapper">
            
            <div class="swiper-slide slide-health">
                <div class="hero-content">
                    <span class="hero-badge">Health Insurance</span>
                    <h1>Comprehensive Health Coverage</h1>
                    <p>Access top-tier medical networks and ensure your well-being with our tailored health insurance plans.</p>
                    <div class="hero-buttons">
                        <a href="category-health.php" class="btn btn-primary">Explore Health Plans</a>
                        <a href="#faq" class="btn btn-outline">Learn More</a>
                    </div>
                </div>
            </div>

            <div class="swiper-slide slide-car">
                <div class="hero-content">
                    <span class="hero-badge">Car Insurance</span>
                    <h1>Drive with Complete Confidence</h1>
                    <p>From minor accidents to total loss, our motor insurance keeps you fully protected on every road.</p>
                    <div class="hero-buttons">
                        <a href="category-car.php" class="btn btn-primary">Get Car Quote</a>
                        <a href="#faq" class="btn btn-outline">Learn More</a>
                    </div>
                </div>
            </div>

            <div class="swiper-slide slide-life">
                <div class="hero-content">
                    <span class="hero-badge">Life Insurance</span>
                    <h1>Secure Your Family&apos;s Future</h1>
                    <p>Peace of mind for the ones you love most. Discover life insurance plans designed for lifelong security.</p>
                    <div class="hero-buttons">
                        <a href="category-life.php" class="btn btn-primary">Explore Life Plans</a>
                        <a href="#faq" class="btn btn-outline">Learn More</a>
                    </div>
                </div>
            </div>

            <div class="swiper-slide slide-property">
                <div class="hero-content">
                    <span class="hero-badge">Property Insurance</span>
                    <h1>Protect Your Most Valuable Assets</h1>
                    <p>Shield your home and property against unexpected events with our comprehensive property insurance.</p>
                    <div class="hero-buttons">
                        <a href="category-property.php" class="btn btn-primary">Get Property Quote</a>
                        <a href="#faq" class="btn btn-outline">Learn More</a>
                    </div>
                </div>
            </div>

        </div>
        <!-- Navigation arrows -->
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
        <!-- Pagination dots -->
        <div class="swiper-pagination"></div>
    </div>

    <!-- Statistics Section -->
    <section class="stats-section" data-aos="fade-up">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item" data-aos="fade-up" data-aos-delay="100">
                    <span class="stat-number" data-count="15000">0</span>
                    <span class="stat-label">Happy Customers</span>
                </div>
                <div class="stat-item" data-aos="fade-up" data-aos-delay="200">
                    <span class="stat-number" data-count="50">0</span>
                    <span class="stat-label">Insurance Partners</span>
                </div>
                <div class="stat-item" data-aos="fade-up" data-aos-delay="300">
                    <span class="stat-number" data-count="98">0</span>
                    <span class="stat-suffix">%</span>
                    <span class="stat-label">Satisfaction Rate</span>
                </div>
                <div class="stat-item" data-aos="fade-up" data-aos-delay="400">
                    <span class="stat-number" data-count="24">0</span>
                    <span class="stat-suffix">/7</span>
                    <span class="stat-label">Customer Support</span>
                </div>
            </div>
        </div>
    </section>


<section class="why-choose-us py-5">
  <div class="container">

    <!-- Section Header -->
    <div class="row">
      <div class="col-12">
        <h2 class="text-center mb-2 section-title" data-aos="fade-up">
          Why choose Coverly?
        </h2>
        <p class="section-subtitle text-center mb-5" data-aos="fade-up" data-aos-delay="100">
          Your trusted digital insurance partner with everything you need in one place
        </p>
      </div>
    </div>

    <!-- Cards -->
    <div class="row g-4 text-center">

      <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
        <div class="feature-card">
          <div class="icon-circle mx-auto mb-3">
            <i class="bi bi-shield-check"></i>
          </div>
          <h4 class="feature-title">
            All your policies<br>in one dashboard
          </h4>
          <p class="feature-text text-muted">
            We offer a seamless digital experience where you can compare, buy, and manage a wide range of insurance solutions from top providers.
          </p>
        </div>
      </div>

      <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
        <div class="feature-card">
          <div class="icon-circle mx-auto mb-3">
            <i class="bi bi-award"></i>
          </div>
          <h4 class="feature-title">
            Unbiased market<br>expertise
          </h4>
          <p class="feature-text text-muted">
            As your trusted digital broker, we provide transparent comparisons and expert insights with industry-leading policies.
          </p>
        </div>
      </div>

      <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
        <div class="feature-card">
          <div class="icon-circle mx-auto mb-3">
            <i class="bi bi-person-check"></i>
          </div>
          <h4 class="feature-title">
            Tailored smart<br>recommendations
          </h4>
          <p class="feature-text text-muted">
            Our smart system analyzes your individual or corporate requirements to recommend highly personalized insurance plans.
          </p>
        </div>
      </div>

      <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="400">
        <div class="feature-card">
          <div class="icon-circle mx-auto mb-3">
            <i class="bi bi-headset"></i>
          </div>
          <h4 class="feature-title">
            After sale services<br>at ZERO cost
          </h4>
          <p class="feature-text text-muted">
            Our commitment doesn&apos;t end at the sale. From answering inquiries to fully managing your claims, our support comes at no extra cost.
          </p>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- How It Works Section -->
<section class="how-it-works-section">
  <div class="container">
    <h2 class="text-center section-title" data-aos="fade-up">How It Works</h2>
    <p class="section-subtitle text-center mb-5" data-aos="fade-up" data-aos-delay="100">
      Get insured in three simple steps
    </p>
    
    <div class="steps-container">
      <div class="step-item" data-aos="fade-up" data-aos-delay="100">
        <div class="step-number">1</div>
        <div class="step-icon">
          <i class="bi bi-search"></i>
        </div>
        <h3 class="step-title">Compare Plans</h3>
        <p class="step-description">Browse and compare insurance plans from top providers tailored to your needs.</p>
      </div>
      
      <div class="step-connector" data-aos="fade-up" data-aos-delay="150">
        <i class="bi bi-arrow-right"></i>
      </div>
      
      <div class="step-item" data-aos="fade-up" data-aos-delay="200">
        <div class="step-number">2</div>
        <div class="step-icon">
          <i class="bi bi-check2-circle"></i>
        </div>
        <h3 class="step-title">Choose & Apply</h3>
        <p class="step-description">Select the perfect plan and complete your application online in minutes.</p>
      </div>
      
      <div class="step-connector" data-aos="fade-up" data-aos-delay="250">
        <i class="bi bi-arrow-right"></i>
      </div>
      
      <div class="step-item" data-aos="fade-up" data-aos-delay="300">
        <div class="step-number">3</div>
        <div class="step-icon">
          <i class="bi bi-shield-check"></i>
        </div>
        <h3 class="step-title">Get Covered</h3>
        <p class="step-description">Receive your policy instantly and enjoy comprehensive protection.</p>
      </div>
    </div>
  </div>
</section>

   <section class="purpose-section">
    <div class="purpose-container">
        <h6 class="section-subtitle text-center" data-aos="fade-up">OUR PURPOSE</h6>
        
        <div class="cards-wrapper">
            <!-- Mission Card -->
            <div class="purpose-card" data-aos="fade-up" data-aos-delay="100">
                <div class="purpose-icon">
                    <i class='bx bx-rocket'></i>
                </div>
                <h2 class="card-title">Our Mission</h2>
                <p class="card-text">
                    To simplify the complex world of insurance. We provide a transparent, fast, and secure digital platform where anyone can quickly check their eligibility and discover insurance plans that truly fit their life.
                </p>
            </div>

            <!-- Vision Card -->
            <div class="purpose-card" data-aos="fade-up" data-aos-delay="200">
                <div class="purpose-icon">
                    <i class='bx bxs-binoculars'></i>
                </div>
                <h2 class="card-title">Our Vision</h2>
                <p class="card-text">
                    To become the most trusted and user-friendly digital insurance brokerage in the region - bridging the gap between insurance providers and customers through smart technology and real-time eligibility matching.
                </p>
            </div>
        </div>
    </div>
</section>



<section class="partners" data-aos="fade-up">
  <div class="container">
    <h2>Trusted by Leading Insurance Partners</h2>
    <p class="partners-subtitle">We work with the best insurance providers to bring you comprehensive coverage options</p>

    <!-- Slider -->
    <div class="slider">
      <div class="slide-track">

        <div class="slide"><img src="assets/img/download.png" alt="Insurance Partner"></div>
        <div class="slide"><img src="assets/img/axa.png" alt="AXA Insurance"></div>
        <div class="slide"><img src="assets/img/met.png" alt="MetLife Insurance"></div>
        <div class="slide"><img src="assets/img/masr.png" alt="Misr Insurance"></div>

        <!-- duplicate for smooth loop -->
        <div class="slide"><img src="assets/img/download.png" alt="Insurance Partner"></div>
        <div class="slide"><img src="assets/img/axa.png" alt="AXA Insurance"></div>
        <div class="slide"><img src="assets/img/met.png" alt="MetLife Insurance"></div>
        <div class="slide"><img src="assets/img/masr.png" alt="Misr Insurance"></div>
        <div class="slide"><img src="assets/img/download.png" alt="Insurance Partner"></div>
        <div class="slide"><img src="assets/img/axa.png" alt="AXA Insurance"></div>
        <div class="slide"><img src="assets/img/met.png" alt="MetLife Insurance"></div>
        <div class="slide"><img src="assets/img/masr.png" alt="Misr Insurance"></div>

      </div>
    </div>
  </div>
</section>

<section class="custom-testimonials-section">
    <div class="custom-testimonials-container">
        <h2 class="custom-testimonials-title" data-aos="fade-up">What Our Clients Say</h2>
        <p class="section-subtitle text-center mb-5" data-aos="fade-up" data-aos-delay="100">
            Real stories from real customers who trust Coverly
        </p>

        <div class="custom-testimonial-grid">
            <div class="custom-testimonial-item" data-aos="fade-up" data-aos-delay="100">
                <div class="quote-icon">
                    <i class="bi bi-quote"></i>
                </div>
                <p class="custom-client-quote">Getting my insurance sorted through COVERLY was a breeze! The platform is clear, fast, and I feel so much more secure now with my new plan. Thank you for making this process so effortless!</p>
                <div class="client-info">
                    <img src="assets/img/person1.jfif" alt="Sara Marwan" class="custom-client-photo">
                    <div class="client-details">
                        <h3 class="custom-client-name">Sara Marwan</h3>
                        <span class="client-role">Health Insurance Client</span>
                    </div>
                </div>
                <div class="custom-client-rating">
                    <span class="custom-star">&#9733;</span><span class="custom-star">&#9733;</span><span class="custom-star">&#9733;</span><span class="custom-star">&#9733;</span><span class="custom-star">&#9733;</span>
                </div>
            </div>

            <div class="custom-testimonial-item featured" data-aos="fade-up" data-aos-delay="200">
                <div class="quote-icon">
                    <i class="bi bi-quote"></i>
                </div>
                <p class="custom-client-quote">I could not be happier with the policy I got. The whole process was smooth and transparent. It is great to finally find a reliable platform that genuinely cares about getting you the best coverage!</p>
                <div class="client-info">
                    <img src="assets/img/person2.jfif" alt="Ahmed Hassan" class="custom-client-photo">
                    <div class="client-details">
                        <h3 class="custom-client-name">Ahmed Hassan</h3>
                        <span class="client-role">Car Insurance Client</span>
                    </div>
                </div>
                <div class="custom-client-rating">
                    <span class="custom-star">&#9733;</span><span class="custom-star">&#9733;</span><span class="custom-star">&#9733;</span><span class="custom-star">&#9733;</span><span class="custom-star">&#9733;</span>
                </div>
            </div>

            <div class="custom-testimonial-item" data-aos="fade-up" data-aos-delay="300">
                <div class="quote-icon">
                    <i class="bi bi-quote"></i>
                </div>
                <p class="custom-client-quote">The coverage I got through COVERLY is exactly what I was looking for. The platform takes great care to ensure all insurance plans meet the highest standards of reliability. Thank you!</p>
                <div class="client-info">
                    <img src="assets/img/person3.jfif" alt="Othmane Ahmed" class="custom-client-photo">
                    <div class="client-details">
                        <h3 class="custom-client-name">Othmane Ahmed</h3>
                        <span class="client-role">Life Insurance Client</span>
                    </div>
                </div>
                <div class="custom-client-rating">
                    <span class="custom-star">&#9733;</span><span class="custom-star">&#9733;</span><span class="custom-star">&#9733;</span><span class="custom-star">&#9733;</span><span class="custom-star">&#9733;</span>
                </div>
            </div>
        </div>
    </div>
</section>



   <section class="faq-section" id="faq">
    <div class="faq-container">
        <!-- Section Title -->
        <h2 class="faq-title" data-aos="fade-up">Frequently Asked Questions</h2>
        <p class="section-subtitle text-center mb-5" data-aos="fade-up" data-aos-delay="100">
            Everything you need to know about our insurance services
        </p>
        
        <div class="faq-accordion" id="faqAccordion">

            <!-- Question 1 (open by default) -->
            <div class="faq-item active" data-aos="fade-up" data-aos-delay="100">
                <button class="faq-question" aria-expanded="true" data-target="faqAnswer1">
                    <span>What types of insurance do you offer?</span>
                    <svg class="faq-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
                <div class="faq-answer show" id="faqAnswer1">
                    <p>We offer comprehensive coverage including Health Insurance, Car Insurance, Life Insurance, and Property Insurance. Each category features plans from multiple trusted providers to ensure you find the perfect fit.</p>
                </div>
            </div>

            <!-- Question 2 -->
            <div class="faq-item" data-aos="fade-up" data-aos-delay="150">
                <button class="faq-question" aria-expanded="false" data-target="faqAnswer2">
                    <span>How do I file a claim?</span>
                    <svg class="faq-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
                <div class="faq-answer" id="faqAnswer2">
                    <p>Filing a claim is easy through our platform. Simply log into your dashboard, navigate to the Claims section, and follow the guided process. Our support team is available 24/7 to assist you at no extra cost.</p>
                </div>
            </div>

            <!-- Question 3 -->
            <div class="faq-item" data-aos="fade-up" data-aos-delay="200">
                <button class="faq-question" aria-expanded="false" data-target="faqAnswer3">
                    <span>Can I compare plans from different providers?</span>
                    <svg class="faq-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
                <div class="faq-answer" id="faqAnswer3">
                    <p>Yes! Our platform allows you to compare plans side-by-side from multiple insurance providers. View coverage details, premiums, and benefits all in one place to make an informed decision.</p>
                </div>
            </div>
            
            <!-- Question 4 -->
            <div class="faq-item" data-aos="fade-up" data-aos-delay="250">
                <button class="faq-question" aria-expanded="false" data-target="faqAnswer4">
                    <span>Is my personal information secure?</span>
                    <svg class="faq-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
                <div class="faq-answer" id="faqAnswer4">
                    <p>Absolutely. We use bank-level encryption and strict data protection protocols to ensure your personal information is always secure. We never share your data with third parties without your consent.</p>
                </div>
            </div>
          
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const faqItems = document.querySelectorAll('.faq-item');
        
        faqItems.forEach(item => {
            const btn = item.querySelector('.faq-question');
            btn.addEventListener('click', function() {
                const isActive = item.classList.contains('active');
                
                // Close all items
                faqItems.forEach(i => {
                    i.classList.remove('active');
                    i.querySelector('.faq-question').setAttribute('aria-expanded', 'false');
                    i.querySelector('.faq-answer').classList.remove('show');
                });
                
                // Open clicked item if it was closed
                if (!isActive) {
                    item.classList.add('active');
                    btn.setAttribute('aria-expanded', 'true');
                    item.querySelector('.faq-answer').classList.add('show');
                }
            });
        });
    });
    </script>
</section>



<section class="locations-section py-5">
    <div class="container my-4">
        <div class="row align-items-center justify-content-between">
            
            <div class="col-md-5 mb-5 mb-md-0" data-aos="fade-right">
                <span class="location-badge">Visit Us</span>
                <h2 class="mb-4 fw-bold location-title">
                    Our Location
                </h2>
                
                <div class="location-item ps-4 py-3">
                    <div class="location-detail">
                        <i class="bi bi-geo-alt-fill"></i>
                        <p class="mb-0">Capital Business Park, Sheikh Zayed, Giza, Egypt</p>
                    </div>
                    <div class="location-detail">
                        <i class="bi bi-telephone-fill"></i>
                        <p class="mb-0">+20 123 456 7890</p>
                    </div>
                    <div class="location-detail">
                        <i class="bi bi-envelope-fill"></i>
                        <p class="mb-0">contact@coverly.com</p>
                    </div>
                    <div class="location-detail">
                        <i class="bi bi-clock-fill"></i>
                        <p class="mb-0">Sun - Thu: 9:00 AM - 6:00 PM</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6" data-aos="fade-left">
                <div class="map-container">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3453.8127760261037!2d30.9841!3d30.0163!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzDCsDAwJzU4LjciTiAzMMKwNTknMDMuMSJF!5e0!3m2!1sen!2seg!4v1234567890"
                        width="100%" 
                        height="350" 
                        style="border:0; border-radius: 16px;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Coverly Office Location">
                    </iframe>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section" data-aos="fade-up">
    <div class="container">
        <div class="cta-content">
            <h2>Ready to Get Protected?</h2>
            <p>Join thousands of happy customers who trust Coverly for their insurance needs.</p>
            <div class="cta-buttons">
                <a href="#services" class="btn btn-primary">Get Started</a>
                <a href="#faq" class="btn btn-outline-light">Learn More</a>
            </div>
        </div>
    </div>
</section>


<?php
 include __DIR__ . '/includes/footer.php';
 ?> 

    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="assets/js/script.js"></script>
    <script src="assets/js/home.js"></script>
<script src="https://cdn.botpress.cloud/webchat/v3.6/inject.js"></script>
<script src="https://files.bpcontent.cloud/2026/05/31/18/20260531183941-FHTJORB6.js" defer></script>
