<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>


    <!-- nav&hero -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- why choose -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/homepage.css">

</head>
<body>
    <header>
        <div class="container">
            <a href="#" class="logo">COVERLY</a>
            <nav>
                <ul class="nav-links">
                    <li><a href="#">Home</a></li>
                    <li class="dropdown">
                        <a href="#">Categories <i class="fas fa-chevron-down" style="font-size: 12px;"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="health.html">Health</a></li>
                            <li><a href="car.html">Car</a></li>
                            <li><a href="life.html">Life</a></li>
                            <li><a href="property.html">Property</a></li>
                        </ul>
                    </li>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="profile.html"><i class="fa-regular fa-circle-user" style="font-size: 20px;"></i></a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="swiper mySwiper">
        <div class="swiper-wrapper">
            
            <div class="swiper-slide slide-health">
                <div class="hero-content">
                    <h1>Comprehensive Health Coverage</h1>
                    <p>Access top-tier medical networks and ensure your well-being with our tailored health insurance plans.</p>
                    <a href="health.html" class="btn">Explore Health</a>
                </div>
            </div>

            <div class="swiper-slide slide-car">
                <div class="hero-content">
                    <h1>Drive with Complete Confidence</h1>
                    <p>From minor accidents to total loss, our motor insurance keeps you fully protected on every road.</p>
                    <a href="car.html" class="btn">Get Car Quote</a>
                </div>
            </div>

            <div class="swiper-slide slide-life">
                <div class="hero-content">
                    <h1>Secure Your Family's Future</h1>
                    <p>Peace of mind for the ones you love most. Discover life insurance plans designed for lifelong security.</p>
                    <a href="life.html" class="btn">Learn About Life</a>
                </div>
            </div>

            <div class="swiper-slide slide-property">
                <div class="hero-content">
                    <h1>Protect Your Most Valuable Assets</h1>
                    <p>Shield your home and property against unexpected events with our comprehensive property insurance.</p>
                    <a href="property.html" class="btn">Protect Property</a>
                </div>
            </div>

        </div>

 
    </div>


<section class="why-choose-us py-5">
  <div class="container">

    <!-- العنوان فوق -->
    <div class="row">
      <div class="col-12">
        <h2 class="text-center mb-5 section-title">
          Why choose Coverly?
        </h2>
      </div>
    </div>

    <!-- الكروت -->
    <div class="row g-4 text-center">

      <div class="col-md-6 col-lg-3">
        <div class="icon-circle mx-auto mb-3">
          <i class="bi bi-shield-check"></i>
        </div>
        <h4 class="feature-title">
          All your policies<br>in one dashboard
        </h4>
        <p class="feature-text text-muted">
          We offer a seamless digital experience where you can compare, buy, and manage a wide range of insurance solutions from top providers. Get a complete overview of your portfolio in one centralized, easy-to-use platform.
        </p>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="icon-circle mx-auto mb-3">
          <i class="bi bi-award"></i>
        </div>
        <h4 class="feature-title">
          Unbiased market<br>expertise
        </h4>
        <p class="feature-text text-muted">
          As your trusted digital broker, we provide transparent comparisons and expert insights. Our deep understanding of the market ensures we connect you with industry-leading policies that offer the best value.
        </p>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="icon-circle mx-auto mb-3">
          <i class="bi bi-person-check"></i>
        </div>
        <h4 class="feature-title">
          Tailored smart<br>recommendations
        </h4>
        <p class="feature-text text-muted">
          You are our top priority. Our smart system analyzes your individual or corporate requirements to recommend highly personalized insurance plans. We take a proactive approach to ensure you are perfectly covered.
        </p>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="icon-circle mx-auto mb-3">
          <i class="bi bi-headset"></i>
        </div>
        <h4 class="feature-title">
          After sale services<br>at ZERO cost
        </h4>
        <p class="feature-text text-muted">
          Our commitment doesn't end at the sale. We act as your dedicated intermediary between you and the insurance providers. From answering inquiries to fully managing your claims, our support comes at no extra cost.
        </p>
      </div>

    </div>
  </div>
</section>

   <section class="purpose-section">
    <div class="purpose-container">
        <h6 class="section-subtitle ">OUR PURPOSE</h6>
        
        <div class="cards-wrapper">
            <!-- Mission Card -->
            <div class="purpose-card">
                <div class="icon-wrapper">
                    <span class="icon">🎯</span>
                </div>
                <h2 class="card-title">Our Mission</h2>
                <p class="card-text">
                    To simplify the complex world of insurance. We provide a transparent, fast, and secure digital platform where anyone can quickly check their eligibility and discover insurance plans that truly fit their life — without the jargon, delays, or friction of traditional brokers.
                </p>
            </div>

            <!-- Vision Card -->
            <div class="purpose-card">
                <div class="icon-wrapper">
                    <span class="icon">🔭</span>
                </div>
                <h2 class="card-title">Our Vision</h2>
                <p class="card-text">
                    To become the most trusted and user-friendly digital insurance brokerage in the region — bridging the gap between insurance providers and customers through smart technology, real-time eligibility matching, and a platform designed with people, not paperwork, at its core.
                </p>
            </div>
        </div>
    </div>
</section>



<section class="partners">

  <h2>Trusted by Leading Insurance Partners</h2>

  <!-- Slider -->
  <div class="slider">
    <div class="slide-track">

      <div class="slide"><img src="assets/img/download.png" alt=""></div>
      <div class="slide"><img src="assets/img/axa.png" alt=""></div>
      <div class="slide"><img src="assets/img/met.png" alt=""></div>
      <div class="slide"><img src="assets/img/masr.png" alt=""></div>

      <!-- duplicate for smooth loop -->
      <div class="slide"><img src="assets/img/download.png" alt=""></div>
      <div class="slide"><img src="assets/img/axa.png" alt=""></div>
      <div class="slide"><img src="assets/img/met.png" alt=""></div>
      <div class="slide"><img src="assets/img/masr.png" alt=""></div>
      <div class="slide"><img src="assets/img/download.png" alt=""></div>
      <div class="slide"><img src="assets/img/axa.png" alt=""></div>
      <div class="slide"><img src="assets/img/met.png" alt=""></div>
      <div class="slide"><img src="assets/img/masr.png" alt=""></div>

    

    </div>
  </div>

  <!-- Features -->
  <div class="features">

    <div class="card">
      <h3>🛡️ Wide Coverage</h3>
      <p>Insurance plans that fit all your needs.</p>
    </div>

    <div class="card">
      <h3>⚡ Fast Claims</h3>
      <p>Quick and easy claim processing anytime.</p>
    </div>

    <div class="card">
      <h3>🤝 Trusted Partners</h3>
      <p>We work only with reliable companies.</p>
    </div>

  </div>

</section>

   <section class="py-5" style="background-color: #f4f7f9;" id="faq">
    <div class="container">
        <!-- عنوان السكشن -->
        <h2 class="text-center mb-5 fw-bold text-navy">Frequently Asked Questions</h2>
        
        <!-- توسيط المحتوى وتحديد عرضه -->
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                
                <div class="accordion custom-accordion" id="faqAccordion">

                    <!-- السؤال الأول (مفتوح افتراضياً) -->
                    <div class="accordion-item mb-3 shadow-sm border-0">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                How long does it usually take to complete a project?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                The turnaround time depends on the size and requirements of the project, but on average, we deliver projects within 2 to 4 weeks.
                            </div>
                        </div>
                    </div>

                    <!-- السؤال التاني (مقفول) -->
                    <div class="accordion-item mb-3 shadow-sm border-0">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                Do you provide technical support after delivery?
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes, we provide ongoing technical support and maintenance to ensure everything runs smoothly after the final delivery.
                            </div>
                        </div>
                    </div>

                    <!-- السؤال التالت (مقفول) -->
                    <div class="accordion-item mb-3 shadow-sm border-0">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                What payment methods are available?
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                We accept various payment methods including bank transfers, credit cards, and PayPal. Payment terms are usually split into milestones.
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</section>


 <footer>
        <div class="container">
            <div class="footer-content">
                
                <div class="footer-section about">
                    <img src="images/DONE.jfif" alt="COVERLY" class="footer-logo">
                    <p>Providing high-end technical solutions to elevate business efficiency. Trust and innovation are our core values in every project we deliver.</p>
                    <div class="socials">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>

                <div class="footer-section links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="#">Home</a></li>
                        <li><a href="#">Our Services</a></li>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>

                <div class="footer-section contact-form-footer">
                    <h3>Send us a Message</h3>
                    <form id="footer-contact-form" action="#">
                        <input type="email" placeholder="Your Email Address" required>
                        <textarea rows="3" placeholder="How can we help you today?" required></textarea>
                        <button type="submit" class="footer-submit-btn">Send Message</button>
                        
                        <p id="success-msg" style="display: none; color: #27ae60; margin-top: 10px; font-size: 14px; font-weight: 500;">
                            Message sent successfully!
                        </p>
                    </form>
                </div>

            </div>

            <div class="footer-bottom">
                <p>&copy; 2026 COVERLY | Leading Tech Solutions. All Rights Reserved.</p>
            </div>
        </div>
    </footer>






































    <script src="assets/js/swiper-bundle.min.js "></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"> </script>
</body>
</html>