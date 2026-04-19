/* ============================================
   SPACEX — Main Application
   ============================================ */

(function () {
  'use strict';

  // ---- Smooth Scroll for Anchor Links ----
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href');
      if (targetId === '#') return;
      
      const target = document.querySelector(targetId);
      if (target) {
        e.preventDefault();
        const navHeight = document.querySelector('.navbar').offsetHeight;
        const targetPos = target.getBoundingClientRect().top + window.pageYOffset - navHeight - 20;
        
        window.scrollTo({
          top: targetPos,
          behavior: 'smooth'
        });

        // Close mobile nav if open
        const navLinks = document.getElementById('navLinks');
        const navToggle = document.getElementById('navToggle');
        if (navLinks && navLinks.classList.contains('open')) {
          navLinks.classList.remove('open');
          navToggle.classList.remove('active');
          document.body.style.overflow = '';
        }
      }
    });
  });

  // ---- Mobile Nav Toggle ----
  const navToggle = document.getElementById('navToggle');
  const navLinks = document.getElementById('navLinks');

  if (navToggle && navLinks) {
    navToggle.addEventListener('click', () => {
      navLinks.classList.toggle('open');
      navToggle.classList.toggle('active');
      document.body.style.overflow = navLinks.classList.contains('open') ? 'hidden' : '';
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
      if (!navLinks.contains(e.target) && !navToggle.contains(e.target) && navLinks.classList.contains('open')) {
        navLinks.classList.remove('open');
        navToggle.classList.remove('active');
        document.body.style.overflow = '';
      }
    });
  }

  // ---- Sticky Navbar on Scroll ----
  const navbar = document.getElementById('navbar');
  let lastScroll = 0;

  function handleNavScroll() {
    const currentScroll = window.pageYOffset;
    
    if (navbar) {
      if (currentScroll > 80) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    }

    lastScroll = currentScroll;
  }

  // ---- Sticky CTA Bar ----
  const stickyCta = document.getElementById('stickyCta');
  
  function handleStickyCta() {
    if (!stickyCta) return;
    
    const heroSection = document.getElementById('hero');
    const footerSection = document.getElementById('footer');

    if (!heroSection) return;

    const heroBottom = heroSection.getBoundingClientRect().bottom;
    const footerTop = footerSection ? footerSection.getBoundingClientRect().top : Infinity;
    const viewportHeight = window.innerHeight;

    if (heroBottom < 0 && footerTop > viewportHeight) {
      stickyCta.classList.add('visible');
    } else {
      stickyCta.classList.remove('visible');
    }
  }

  // ---- Active Section Highlighting in Nav ----
  const navLinksAll = document.querySelectorAll('.nav-links a[href^="#"]');
  const sections = document.querySelectorAll('section[id]');

  function highlightActiveSection() {
    const scrollPos = window.pageYOffset + 200;

    sections.forEach(section => {
      const top = section.offsetTop;
      const bottom = top + section.offsetHeight;
      const id = section.getAttribute('id');

      navLinksAll.forEach(link => {
        if (link.getAttribute('href') === '#' + id) {
          if (scrollPos >= top && scrollPos < bottom) {
            link.style.color = 'var(--color-neon)';
          } else {
            link.style.color = '';
          }
        }
      });
    });
  }

  // ---- Button Ripple Effect ----
  document.querySelectorAll('.btn').forEach(btn => {
    btn.addEventListener('click', function (e) {
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
      ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
      
      this.appendChild(ripple);
      
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // ---- Throttled Scroll Handler ----
  let ticking = false;

  function onScroll() {
    if (!ticking) {
      requestAnimationFrame(() => {
        handleNavScroll();
        handleStickyCta();
        highlightActiveSection();
        ticking = false;
      });
      ticking = true;
    }
  }

  window.addEventListener('scroll', onScroll, { passive: true });

  // ---- Initialize on Load ----
  window.addEventListener('DOMContentLoaded', () => {
    handleNavScroll();
    handleStickyCta();
  });

  // ---- Lazy Loading Images ----
  if ('IntersectionObserver' in window) {
    const lazyImages = document.querySelectorAll('img[loading="lazy"]');
    const imageObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          if (img.dataset.src) {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
          }
          imageObserver.unobserve(img);
        }
      });
    }, { rootMargin: '200px' });

    lazyImages.forEach(img => imageObserver.observe(img));
  }

})();
