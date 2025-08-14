<?php
require_once __DIR__ . '/../config/subscription_plans.php';
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
    <div class="container">
        <!-- Navbar Brand -->
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>">
            <img src="<?= BASE_URL ?>images/logo-backlinks-validator.png" alt="Backlinks Validator" width="80" class="me-2">
        </a>

        <!-- Toggler for mobile view -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNavbar" aria-controls="publicNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Menu -->
        <div class="collapse navbar-collapse" id="publicNavbar">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>index.php#features">
                        Features
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>index.php#testimonials">
                        Testimonials
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>index.php#pricing">
                        Pricing
                    </a>
                </li>
            </ul>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>login.php" class="btn btn-outline-primary">
                    Sign In
                </a>
                <a href="<?= BASE_URL ?>register.php" class="btn btn-primary">
                    Get Started
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Scroll to Top Button -->
<button id="scrollToTop" class="btn btn-primary rounded-circle shadow-sm" aria-label="Scroll to top">
    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-chevron-up" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
        <path d="M6 15l6 -6l6 6" />
    </svg>
</button>

<style>
    .navbar {
        padding: 1rem 0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1030;
        background-color: rgba(255, 255, 255, 0.98);
        height: 80px;
        /* Fixed height for navbar */
    }

    /* Add scroll margin for sections */
    section[id] {
        scroll-margin-top: 100px;
    }

    .navbar.scrolled {
        background-color: rgba(255, 255, 255, 0.98) !important;
        box-shadow: 0 1px 5px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(5px);
    }

    .nav-link {
        font-weight: 500;
        padding: 0.5rem 1rem;
        transition: color 0.2s ease-in-out;
    }

    .nav-link:hover {
        color: var(--tblr-primary);
    }

    /* Scroll to Top Button */
    #scrollToTop {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 1000;
        padding: 0;
    }

    #scrollToTop svg {
        width: 20px;
        height: 20px;
        stroke-width: 2.5px;
        margin: 0;
        display: block;
    }

    #scrollToTop.visible {
        opacity: 1;
        visibility: visible;
    }

    #scrollToTop:hover {
        transform: translateY(-3px);
    }

    @media (max-width: 991.98px) {
        .navbar-collapse {
            padding: 1rem 0;
        }

        .d-flex.gap-2 {
            margin-top: 1rem;
        }

        .btn {
            width: 100%;
        }

        #scrollToTop {
            bottom: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
        }

        #scrollToTop svg {
            width: 18px;
            height: 18px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const navbar = document.querySelector('.navbar');
        const scrollToTopBtn = document.getElementById('scrollToTop');
        const navbarHeight = 80; // Match the navbar height

        // Function to handle scroll events
        function handleScroll() {
            // Add/remove scrolled class to navbar
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
                scrollToTopBtn.classList.add('visible');
            } else {
                navbar.classList.remove('scrolled');
                scrollToTopBtn.classList.remove('visible');
            }
        }

        // Add scroll event listener
        window.addEventListener('scroll', handleScroll);

        // Scroll to top when button is clicked
        scrollToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Handle smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    });
</script>