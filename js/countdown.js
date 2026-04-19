/* ============================================
   SPACEX — Countdown Timer
   ============================================ */

(function () {
  'use strict';

  // Target: 3 days from now (resets for each visitor)
  function getTargetDate() {
    const storageKey = 'spacex_countdown_target';
    let target = localStorage.getItem(storageKey);

    if (!target || new Date(parseInt(target, 10)) < new Date()) {
      // Set new target: 3 days from now
      const newTarget = Date.now() + 3 * 24 * 60 * 60 * 1000;
      localStorage.setItem(storageKey, newTarget.toString());
      target = newTarget.toString();
    }

    return parseInt(target, 10);
  }

  const targetDate = getTargetDate();

  const daysEl = document.getElementById('countdown-days');
  const hoursEl = document.getElementById('countdown-hours');
  const minutesEl = document.getElementById('countdown-minutes');
  const secondsEl = document.getElementById('countdown-seconds');

  if (!daysEl || !hoursEl || !minutesEl || !secondsEl) return;

  function pad(num) {
    return num.toString().padStart(2, '0');
  }

  function updateCountdown() {
    const now = Date.now();
    const diff = targetDate - now;

    if (diff <= 0) {
      daysEl.textContent = '00';
      hoursEl.textContent = '00';
      minutesEl.textContent = '00';
      secondsEl.textContent = '00';

      // Reset countdown
      localStorage.removeItem('spacex_countdown_target');
      return;
    }

    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

    // Animate value change
    animateValue(daysEl, pad(days));
    animateValue(hoursEl, pad(hours));
    animateValue(minutesEl, pad(minutes));
    animateValue(secondsEl, pad(seconds));
  }

  function animateValue(el, newValue) {
    if (el.textContent !== newValue) {
      el.style.transform = 'scale(1.1)';
      el.textContent = newValue;
      setTimeout(() => {
        el.style.transform = 'scale(1)';
      }, 150);
    }
  }

  // Initial update
  updateCountdown();

  // Update every second
  setInterval(updateCountdown, 1000);
})();
