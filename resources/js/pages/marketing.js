/**
 * ==========================================================
 * MARKETING LAYOUT
 * Menú móvil y efecto de navbar al hacer scroll.
 * Usado por resources/views/layouts/marketing.blade.php
 *
 * - El menú móvil actualiza aria-expanded y su etiqueta.
 * - Se cierra al elegir una sección, al pulsar Escape o al
 *   hacer clic fuera de él.
 * ==========================================================
 */

const menuButton = document.getElementById('mobileMenuButton');
const nav = document.getElementById('landingNav');

function setMenu(open) {
    if (!menuButton || !nav) {
        return;
    }

    nav.classList.toggle('active', open);
    menuButton.setAttribute('aria-expanded', String(open));
    menuButton.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');

    const icon = menuButton.querySelector('i');
    if (icon) {
        icon.className = open ? 'fas fa-xmark' : 'fas fa-bars';
    }
}

menuButton?.addEventListener('click', function (event) {
    event.stopPropagation();
    setMenu(!nav.classList.contains('active'));
});

nav?.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', function () {
        setMenu(false);
    });
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && nav?.classList.contains('active')) {
        setMenu(false);
        menuButton.focus();
    }
});

document.addEventListener('click', function (event) {
    if (nav?.classList.contains('active') && !nav.contains(event.target)) {
        setMenu(false);
    }
});

window.addEventListener('scroll', function () {
    const navbar = document.querySelector('.landing-navbar');
    navbar?.classList.toggle('scrolled', window.scrollY > 20);
}, { passive: true });
