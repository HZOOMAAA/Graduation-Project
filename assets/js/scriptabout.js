const teamMembers = [
    {
        name: "Mahmoud Diaa",
        role: "Front-End & System Developer",
        desc: "Mahmoud contributed to the development of the system's user interface and core functionalities. He worked on building responsive front-end components and participated in implementing key system features, helping deliver a smooth and efficient user experience.",
        img: "assets/img/mahmoud.png"
    },
    {
        name: "Hazem Yousry",
        role: "Back-End Developer",
        desc: "Hazem is responsible for developing and maintaining the server-side components of the system. He works on database management, API development, business logic implementation, and system integration to ensure secure, reliable, and efficient platform performance. ",
        img: "assets/img/hazem.png"
    },
    {
        name: "Marwan Wael",
        role: "Front-End Developer",
        desc: "Marwan is responsible for building and enhancing the user interface of the platform. He develops responsive, interactive, and visually appealing web pages, ensuring a smooth user experience across different devices and browsers.",
        img: "assets/img/marwan.png"
    },
    {
        name: "Mohamed Ahmed",
        role: "Front-End & System Developer",
        desc: "Mohamed contributed to both front-end development and system implementation. He worked on building responsive user interfaces, developing core system functionalities, and ensuring seamless integration between different components of the platform to deliver an efficient user experience.",
        img: "assets/img/medo.png"
    },
    {
        name: "Mohnad Azmy",
        role: "Business Analyst & System Developer",
        desc: "Mohnad contributed to analyzing business requirements and developing core system functionalities. He helped bridge the gap between user needs and technical implementation, ensuring that the platform delivers practical solutions while maintaining efficiency, reliability, and a seamless user experience.",
        img: "assets/img/mohnad.png"
    },
    {
        name: "Iman Hatem",
        role: "Business Analyst & System Developer",
        desc: "Iman contributed to analyzing business requirements and participating in system development. She worked on understanding user needs, supporting feature implementation, and ensuring that business objectives were effectively translated into practical system solutions.",
        img: "assets/img/iman.png"
    }
];

let currentIndex = 0;

function changeMember(direction) {
    const container = document.getElementById("team-slider-container");

    container.classList.remove("fade-in");

    currentIndex = (currentIndex + direction + teamMembers.length) % teamMembers.length;

    document.getElementById("team-name").innerText = teamMembers[currentIndex].name;
    document.getElementById("team-role").innerText = teamMembers[currentIndex].role;
    document.getElementById("team-desc").innerText = teamMembers[currentIndex].desc;
    document.getElementById("team-img").src = teamMembers[currentIndex].img;

    container.classList.add("fade-in");
}