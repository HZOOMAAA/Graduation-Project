<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COVERLY | Footer Section</title>
 <link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="assets/css/footer.css">
</head>
<body>



    <footer>
        <div class="container">
            <div class="footer-content">
                
                <div class="footer-section about">
                    <h2>COVERLY</h2>
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
                        <li><a href="homepage.php">Home</a></li>
                        <li><a href="homepage.php#services">Our Services</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                    </ul>
                </div>

                <div class="footer-section contact-form-footer">
                    <h3>Send us a Message</h3>
                    <form id="footer-contact-form" action="#">
                        <input type="text" name="name" placeholder="Your Name">
                        <input type="email" name="email" placeholder="Your Email Address">
                        <textarea name="message" rows="3" placeholder="How can we help you today?"></textarea>
                        <button type="submit" class="footer-submit-btn">Send Message</button>
                        
                        <p id="success-msg" style="display: none; color: #27ae60; margin-top: 10px; font-size: 14px; font-weight: 500;">
                            Message sent successfully!
                        </p>
                    </form>
                </div>

            </div>

            <div class="footer-bottom">
                <p class="text-white">&copy; 2026 COVERLY | Leading Tech Solutions. All Rights Reserved.</p>
            </div>
        </div>
    </footer>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('footer-contact-form');
    const successMsg = document.getElementById('success-msg');

    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(contactForm);

            // Use root relative path to always resolve correctly regardless of subfolder depth
            fetch('/Graduation-Project/includes/handle_contact.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    successMsg.style.display = 'block';
                    successMsg.style.color = '#27ae60';
                    successMsg.innerText = data.message;
                    contactForm.reset();
                    setTimeout(() => {
                        successMsg.style.display = 'none';
                    }, 5000);
                } else {
                    successMsg.style.display = 'block';
                    successMsg.style.color = '#e74c3c';
                    successMsg.innerText = 'Error: ' + data.message;
                }
            })
            .catch(err => {
                console.error('Submission error:', err);
                successMsg.style.display = 'block';
                successMsg.style.color = '#e74c3c';
                successMsg.innerText = 'Something went wrong. Please try again.';
            });
        });
    }
});
</script>

<script src="../assets/js/script.js"></script>
</body>
</html>
