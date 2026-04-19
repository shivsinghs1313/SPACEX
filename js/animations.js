/* ============================================
   SPACEX — Scroll Animations & Interactions
   ============================================ */

(function () {
  'use strict';

  // ---- IntersectionObserver for scroll-triggered animations ----
  const animateElements = document.querySelectorAll(
    '.animate-on-scroll, .animate-slide-left, .animate-slide-right, .animate-scale'
  );

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            // Once animated, stop observing for performance
            observer.unobserve(entry.target);
          }
        });
      },
      {
        threshold: 0.1,
        rootMargin: '0px 0px -60px 0px',
      }
    );

    animateElements.forEach((el) => observer.observe(el));
  } else {
    // Fallback: show all elements immediately
    animateElements.forEach((el) => el.classList.add('is-visible'));
  }

  // ---- Counter Animation ----
  function animateCounter(element, target, duration = 2000) {
    const start = 0;
    const startTime = performance.now();
    const suffix = element.textContent.replace(/[\d,.]+/, '');

    function update(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);

      // Ease out cubic
      const eased = 1 - Math.pow(1 - progress, 3);
      const current = Math.floor(start + (target - start) * eased);

      element.textContent = current.toLocaleString('en-IN') + suffix;

      if (progress < 1) {
        requestAnimationFrame(update);
      }
    }

    requestAnimationFrame(update);
  }

  // Observe stat elements for counter animation
  const statValues = document.querySelectorAll('[data-count]');
  if (statValues.length > 0) {
    const counterObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const target = parseInt(entry.target.getAttribute('data-count'), 10);
            animateCounter(entry.target, target);
            counterObserver.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.5 }
    );

    statValues.forEach((el) => counterObserver.observe(el));
  }

  // ---- Parallax Effect on Hero Background ----
  const heroSection = document.querySelector('.hero');
  const heroOrbs = document.querySelectorAll('.hero-bg .gradient-orb');

  if (heroSection && heroOrbs.length > 0) {
    let rafId = null;

    function handleParallax() {
      const scrolled = window.pageYOffset;
      const heroHeight = heroSection.offsetHeight;

      if (scrolled < heroHeight * 1.5) {
        heroOrbs.forEach((orb, i) => {
          const speed = 0.1 + i * 0.05;
          orb.style.transform = `translateY(${scrolled * speed}px)`;
        });
      }
    }

    window.addEventListener(
      'scroll',
      () => {
        if (rafId) cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(handleParallax);
      },
      { passive: true }
    );
  }

  // ---- Tilt Effect on Glass Cards (Desktop) ----
  if (window.matchMedia('(min-width: 1024px)').matches) {
    const tiltCards = document.querySelectorAll('.glass-card, .price-card');

    tiltCards.forEach((card) => {
      card.addEventListener('mousemove', (e) => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;

        const rotateX = ((y - centerY) / centerY) * -3;
        const rotateY = ((x - centerX) / centerX) * 3;

        card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-4px)`;
      });

      card.addEventListener('mouseleave', () => {
        card.style.transform = '';
      });
    });
  }

  // ---- Mouse Glow Follow Effect on Hero ----
  if (heroSection && window.matchMedia('(min-width: 768px)').matches) {
    const glowEl = document.createElement('div');
    glowEl.style.cssText = `
      position: absolute;
      width: 400px;
      height: 400px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(0,255,136,0.06) 0%, transparent 70%);
      pointer-events: none;
      z-index: 1;
      transform: translate(-50%, -50%);
      transition: opacity 0.3s ease;
      opacity: 0;
    `;
    heroSection.appendChild(glowEl);

    heroSection.addEventListener('mousemove', (e) => {
      const rect = heroSection.getBoundingClientRect();
      glowEl.style.left = (e.clientX - rect.left) + 'px';
      glowEl.style.top = (e.clientY - rect.top) + 'px';
      glowEl.style.opacity = '1';
    });

    heroSection.addEventListener('mouseleave', () => {
      glowEl.style.opacity = '0';
    });
  }

  // ---- Trading Chart Line Animation ----
  const chartLine = document.querySelector('.hero-chart .chart-line');
  if (chartLine) {
    const lineLength = chartLine.getTotalLength ? chartLine.getTotalLength() : 2000;
    chartLine.style.strokeDasharray = lineLength;
    chartLine.style.strokeDashoffset = lineLength;

    const chartObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            chartLine.style.transition = 'stroke-dashoffset 3s ease-out';
            chartLine.style.strokeDashoffset = '0';
            chartObserver.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.3 }
    );

    chartObserver.observe(chartLine);
  }

})();
