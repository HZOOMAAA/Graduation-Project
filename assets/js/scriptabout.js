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
        desc: "Hazem translates complex insurance data into seamless, user-friendly interfaces. She focuses on providing our clients with an intuitive digital experience that makes comparing policies as easy as online shopping.",
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
        desc: "Mohamed bridges the gap between technology and the insurance market. By studying market needs and broker challenges, she ensures that COVERLY’s features solve real-world problems effectively.",
        img: "assets/img/medo.png"
    },
    {
        name: "Omar Hassan",
        role: "System Architect",
        desc: "Omar designs the robust infrastructure that powers COVERLY. He ensures our platform scales smoothly, maintaining 99.9% uptime even during peak usage and complex secure payment processing.",
        img: "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?q=80&w=600&auto=format&fit=crop"
    },
    {
        name: "Mahmoud Diaa",
        role: "Quality Assurance",
        desc: "Laila acts as our gatekeeper of quality. She meticulously tests every feature, button, and user journey to guarantee our clients experience a completely bug-free platform.",
        img: "https://images.unsplash.com/photo-1598550874175-4d0ef436c909?q=80&w=600&auto=format&fit=crop"
    }
];

let currentIndex = 0;

function changeMember(direction) {
    const container = document.getElementById("team-slider-container");

    // Reset animation
    container.classList.remove("fade-in");

    // Calculate new index
    currentIndex = (currentIndex + direction + teamMembers.length) % teamMembers.length;

    // Update content
    document.getElementById("team-name").innerText = teamMembers[currentIndex].name;
    document.getElementById("team-role").innerText = teamMembers[currentIndex].role;
    document.getElementById("team-desc").innerText = teamMembers[currentIndex].desc;
    document.getElementById("team-img").src = teamMembers[currentIndex].img;

    // Apply animation
    container.classList.add("fade-in");
}