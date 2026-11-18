/*
 * Small shared behaviors for GUCAIR.
 * Plain JavaScript only; no framework is used.
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const toggle = document.getElementById('nav-toggle');
    const navLinks = document.querySelectorAll('.nav-list a, .nav-list button');

    // Close the mobile menu after a link is chosen.
    navLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            if (toggle) {
                toggle.checked = false;
            }
        });
    });

    // Escape is a familiar way to close an open menu.
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && toggle && toggle.checked) {
            toggle.checked = false;
        }
    });
});
