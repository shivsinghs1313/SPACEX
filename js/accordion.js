/* ============================================
   SPACEX — Accordion Component
   ============================================ */

(function () {
  'use strict';

  function initAccordion(type, multiOpen) {
    const items = document.querySelectorAll(`.accordion-item[data-accordion="${type}"]`);

    items.forEach((item) => {
      const header = item.querySelector('.accordion-header');
      const content = item.querySelector('.accordion-content');

      if (!header || !content) return;

      header.addEventListener('click', () => {
        const isActive = item.classList.contains('active');

        // If single-open mode, close all others
        if (!multiOpen) {
          items.forEach((other) => {
            if (other !== item && other.classList.contains('active')) {
              other.classList.remove('active');
              const otherContent = other.querySelector('.accordion-content');
              if (otherContent) {
                otherContent.style.maxHeight = '0';
              }
            }
          });
        }

        // Toggle current
        if (isActive) {
          item.classList.remove('active');
          content.style.maxHeight = '0';
        } else {
          item.classList.add('active');
          content.style.maxHeight = content.scrollHeight + 'px';
        }
      });

      // Keyboard accessibility
      header.setAttribute('tabindex', '0');
      header.setAttribute('role', 'button');
      header.setAttribute('aria-expanded', 'false');

      header.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          header.click();
        }
      });

      // Update aria-expanded on state change
      const observer = new MutationObserver(() => {
        header.setAttribute(
          'aria-expanded',
          item.classList.contains('active') ? 'true' : 'false'
        );
      });

      observer.observe(item, {
        attributes: true,
        attributeFilter: ['class'],
      });
    });
  }

  // Initialize accordions
  // FAQ = single-open, Curriculum = multi-open
  document.addEventListener('DOMContentLoaded', () => {
    initAccordion('faq', false);
    initAccordion('curriculum', true);
  });
})();
