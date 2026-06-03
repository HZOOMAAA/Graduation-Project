// Team Members Data
const teamMembers = [
    {
        name: "Mahmoud Diaa",
        role: "Lead Full-Stack Developer",
        desc: "Mahmoud is responsible for leading our exceptional team of developers. He plays a crucial role in overseeing all stages of development, ensuring our digital products perform at the highest levels of technical efficiency and security.",
        img: "assets/img/mahmoud.png"
    },
    {
        name: "Hazem Yousry",
        role: "UI/UX Designer",
        desc: "Hazem translates complex insurance data into seamless, user-friendly interfaces. He focuses on providing our clients with an intuitive digital experience that makes comparing policies as easy as online shopping.",
        img: "assets/img/hazem.png"
    },
    {
        name: "Marwan Wael",
        role: "Database Engineer",
        desc: "Marwan architects our data systems, guaranteeing that millions of records are stored safely. He implements strict security protocols to ensure that all client data remains absolutely confidential and quickly accessible.",
        img: "assets/img/marwan.png"
    },
    {
        name: "Mohamed Ahmed",
        role: "Business Analyst",
        desc: "Mohamed bridges the gap between technology and the insurance market. By studying market needs and broker challenges, he ensures that COVERLY's features solve real-world problems effectively.",
        img: "assets/img/medo.png"
    },
    {
        name: "Mohnad Azmy",
        role: "System Architect",
        desc: "Mohnad designs the robust infrastructure that powers COVERLY. He ensures our platform scales smoothly, maintaining 99.9% uptime even during peak usage and complex secure payment processing.",
        img: "assets/img/mohnad.png"
    },
    {
        name: "Iman Hatem",
        role: "Quality Assurance",
        desc: "Iman acts as our gatekeeper of quality. She meticulously tests every feature, button, and user journey to guarantee our clients experience a completely bug-free platform.",
        img: "assets/img/iman.png"
    }
];

let currentIndex = 0;

// Initialize slider dots
function initSliderDots() {
    const dotsContainer = document.getElementById("slider-dots");
    if (!dotsContainer) return;
    
    dotsContainer.innerHTML = '';
    teamMembers.forEach((_, index) => {
        const dot = document.createElement('div');
        dot.className = `slider-dot ${index === 0 ? 'active' : ''}`;
        dot.addEventListener('click', () => goToMember(index));
        dotsContainer.appendChild(dot);
    });
}

// Go to specific member
function goToMember(index) {
    currentIndex = index;
    updateMemberDisplay();
}

// Change member with direction
function changeMember(direction) {
    currentIndex = (currentIndex + direction + teamMembers.length) % teamMembers.length;
    updateMemberDisplay();
}

// Update member display
function updateMemberDisplay() {
    const container = document.getElementById("team-slider-container");
    const memberInfo = document.querySelector('.team-member-info');
    
    // Remove animation class
    if (memberInfo) {
        memberInfo.classList.remove("fade-in");
    }

    // Update content
    document.getElementById("team-name").innerText = teamMembers[currentIndex].name;
    document.getElementById("team-role").innerText = teamMembers[currentIndex].role;
    document.getElementById("team-desc").innerText = teamMembers[currentIndex].desc;
    document.getElementById("team-img").src = teamMembers[currentIndex].img;
    
    // Update dots
    const dots = document.querySelectorAll('.slider-dot');
    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentIndex);
    });

    // Apply animation
    if (memberInfo) {
        void memberInfo.offsetWidth; // Trigger reflow
        memberInfo.classList.add("fade-in");
    }
}

// Animated counter function
function animateCounters() {
    const counters = document.querySelectorAll('.stat-number[data-count]');
    
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-count'));
        const duration = 2000; // 2 seconds
        const start = 0;
        const increment = target / (duration / 16);
        let current = start;
        
        const updateCounter = () => {
            current += increment;
            if (current < target) {
                counter.textContent = Math.floor(current).toLocaleString();
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target.toLocaleString();
            }
        };
        
        // Start animation when element is in viewport
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    updateCounter();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        observer.observe(counter);
    });
}

// Auto-slide functionality
let autoSlideInterval;

function startAutoSlide() {
    autoSlideInterval = setInterval(() => {
        changeMember(1);
    }, 5000); // Change every 5 seconds
}

function stopAutoSlide() {
    clearInterval(autoSlideInterval);
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-out-cubic',
            once: true,
            offset: 50
        });
    }
    
    // Initialize slider dots
    initSliderDots();
    
    // Initialize counters
    animateCounters();
    
    // Start auto slide
    startAutoSlide();
    
    // Pause auto slide on hover
    const teamSection = document.querySelector('.team-slider-section');
    if (teamSection) {
        teamSection.addEventListener('mouseenter', stopAutoSlide);
        teamSection.addEventListener('mouseleave', startAutoSlide);
    }
    
    // Navbar scroll effect
    const navbar = document.querySelector('header');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
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
});

// Keyboard navigation for slider
document.addEventListener('keydown', function(e) {
    const teamSection = document.querySelector('.team-slider-section');
    if (!teamSection) return;
    
    const rect = teamSection.getBoundingClientRect();
    const isVisible = rect.top < window.innerHeight && rect.bottom > 0;
    
    if (isVisible) {
        if (e.key === 'ArrowLeft') {
            changeMember(-1);
            stopAutoSlide();
        } else if (e.key === 'ArrowRight') {
            changeMember(1);
            stopAutoSlide();
        }
    }
});
