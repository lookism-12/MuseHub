// MUSEHUB-MASTER - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    console.log('MUSEHUB-MASTER loaded successfully!');
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
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

    // Add active class to current nav item
    const currentLocation = location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentLocation) {
            link.classList.add('active');
        }
    });

    // Artwork card click handler (placeholder)
    document.querySelectorAll('.artwork-card').forEach(card => {
        card.addEventListener('click', function() {
            console.log('Artwork card clicked');
            // TODO: Navigate to artwork detail page
        });
    });

    // Like button handler (placeholder)
    document.querySelectorAll('.fa-heart').forEach(icon => {
        icon.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('fas');
            this.classList.toggle('far');
            console.log('Like toggled');
            // TODO: Send AJAX request to update like count
        });
    });
});
