const swiper = new Swiper('.mySwiper', {
    // Basic settings
    loop: true,
    speed: 800,
    autoplay: {
     delay: 4000, // Changes every 5 seconds
        disableOnInteraction: false,
    },

    // Navigation arrows
    navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
    },

    // Pagination (the dots)
    pagination: {
        el: '.swiper-pagination',
        clickable: true,
    },
    
    // Smooth transition effect
    effect: 'fade', 
    fadeEffect: {
        crossFade: true
    },
});