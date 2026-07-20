/* js/script.js - Frontend interactions */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu Toggle
    const navToggle = document.querySelector('.nav-toggle');
    const navLinks = document.querySelector('.nav-links');

    if (navToggle && navLinks) {
        navToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            
            // Toggle hamburger icon between menu & close (optional if using simple text, we'll toggle aria/class)
            const isOpened = navLinks.classList.contains('active');
            navToggle.innerHTML = isOpened ? '&#10005;' : '&#9776;';
        });

        // Close mobile menu when a link is clicked
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('active');
                navToggle.innerHTML = '&#9776;';
            });
        });
    }

    // 2. Active Link Highlighting based on current scroll / page URL
    const currentPath = window.location.pathname;
    const navAnchors = document.querySelectorAll('.nav-links a');
    let matched = false;
    
    navAnchors.forEach(link => {
        const href = link.getAttribute('href');
        if (currentPath.includes(href) && href !== 'index.html') {
            link.classList.add('active');
            matched = true;
        } else {
            link.classList.remove('active');
        }
    });

    if (!matched && (currentPath.endsWith('/') || currentPath.includes('index.html') || currentPath.includes('index.php'))) {
        const homeLink = document.querySelector('.nav-links a[href="index.html"]');
        if (homeLink) homeLink.classList.add('active');
    }

    // 3. Scroll Reveal Animation using Intersection Observer
    const revealElements = document.querySelectorAll('.glass-panel, .section-header, .hero-content, .hero-visual');
    
    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    revealElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(25px)';
        el.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
        revealObserver.observe(el);
    });

    // 4. Animate Skill Progress Bars when in viewport
    const skillBars = document.querySelectorAll('.skill-progress');
    
    if (skillBars.length > 0) {
        const skillObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const bar = entry.target;
                    const percent = bar.getAttribute('data-percent');
                    bar.style.width = percent + '%';
                    observer.unobserve(bar);
                }
            });
        }, {
            threshold: 0.1
        });

        skillBars.forEach(bar => {
            skillObserver.observe(bar);
        });
    }

    // 5. Image Modal Viewer for Gallery items
    const galleryItems = document.querySelectorAll('.gallery-item');
    const modal = document.querySelector('.modal');
    
    if (modal) {
        const modalImg = modal.querySelector('.modal-img');
        const modalCaption = modal.querySelector('.modal-caption');
        const modalClose = modal.querySelector('.modal-close');

        galleryItems.forEach(item => {
            item.addEventListener('click', () => {
                const img = item.querySelector('img');
                const title = item.querySelector('.gallery-title') || item.querySelector('img').getAttribute('alt');
                
                modal.style.display = 'flex';
                modalImg.src = img.src;
                modalCaption.textContent = title ? title.textContent || title : '';
            });
        });

        // Close modal functions
        const closeModal = () => {
            modal.style.display = 'none';
            modalImg.src = '';
            modalCaption.textContent = '';
        };

        if (modalClose) {
            modalClose.addEventListener('click', closeModal);
        }

        // Close on clicking outside the content
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });

        // Close on Esc key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                closeModal();
            }
        });
    }

     // 6. Global Header & Footer Dynamic Profile Setup
    fetch('api.php?get=profile')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const profile = data.profile;
                
                // Update Logo
                const logoSpan = document.querySelector('.logo span');
                if (logoSpan) {
                    logoSpan.textContent = profile.full_name;
                }
                
                // Update Footer Copyright
                const footerText = document.querySelector('.footer p');
                if (footerText) {
                    footerText.innerHTML = `&copy; ${new Date().getFullYear()} ${profile.full_name}. All rights reserved.`;
                }

                // Dispatch event so other pages can bind their elements
                document.dispatchEvent(new CustomEvent('profileLoaded', { detail: data }));
            }
        })
        .catch(err => console.error("Error loading profile:", err));
});
