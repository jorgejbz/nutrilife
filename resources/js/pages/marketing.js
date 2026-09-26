/**
 * ==========================================================
 * MARKETING LAYOUT
 * Menú móvil y efecto de navbar al hacer scroll.
 * Usado por resources/views/layouts/marketing.blade.php
 * ==========================================================
 */

const menuButton = document.getElementById('mobileMenuButton');
const nav = document.getElementById('landingNav');

menuButton?.addEventListener('click', function () {
    nav.classList.toggle('active');
});

window.addEventListener('scroll', function () {
    const navbar = document.querySelector('.landing-navbar');
    navbar.classList.toggle('scrolled', window.scrollY > 20);
});
