/* ============================================
   SPACEX — Dashboard Interactivity
   ============================================ */

(function () {
  'use strict';

  // ---- Auth Check ----
  // Uncomment below when PHP backend is active:
  // if (typeof SpacexAuth !== 'undefined' && !SpacexAuth.isLoggedIn()) {
  //   window.location.href = 'login.html';
  //   return;
  // }

  // ---- Sidebar Toggle (Mobile) ----
  const sidebar = document.querySelector('.dashboard-sidebar');
  const sidebarToggle = document.querySelector('.sidebar-toggle-mobile');

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', (e) => {
      if (
        sidebar.classList.contains('open') &&
        !sidebar.contains(e.target) &&
        !sidebarToggle.contains(e.target)
      ) {
        sidebar.classList.remove('open');
      }
    });
  }

  // ---- Sidebar Link Active State ----
  const sidebarLinks = document.querySelectorAll('.sidebar-link');

  sidebarLinks.forEach((link) => {
    link.addEventListener('click', (e) => {
      sidebarLinks.forEach((l) => l.classList.remove('active'));
      link.classList.add('active');

      // Show corresponding content section
      const target = link.getAttribute('data-section');
      if (target) {
        document.querySelectorAll('.dash-content-section').forEach((s) => {
          s.style.display = 'none';
        });
        const targetSection = document.getElementById(target);
        if (targetSection) targetSection.style.display = 'block';
      }

      // Close mobile sidebar
      if (sidebar && sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
      }
    });
  });

  // ---- Lesson Completion Toggle ----
  const lessonItems = document.querySelectorAll('.lesson-item');

  lessonItems.forEach((item) => {
    const check = item.querySelector('.lesson-check');
    if (!check) return;

    check.addEventListener('click', (e) => {
      e.stopPropagation();
      item.classList.toggle('completed');

      // Animate check
      if (item.classList.contains('completed')) {
        check.innerHTML =
          '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>';
        check.style.background = 'var(--color-neon)';
        check.style.borderColor = 'var(--color-neon)';
        check.style.color = 'var(--color-bg-primary)';
      } else {
        check.innerHTML = '';
        check.style.background = '';
        check.style.borderColor = '';
        check.style.color = '';
      }

      // Update progress
      updateProgressBar();

      // API call (if available)
      const lessonId = item.getAttribute('data-lesson-id');
      if (lessonId && typeof SpacexAPI !== 'undefined') {
        SpacexAPI.updateProgress(lessonId, item.classList.contains('completed'));
      }
    });

    // Click on item to set active
    item.addEventListener('click', () => {
      lessonItems.forEach((l) => l.classList.remove('active'));
      item.classList.add('active');
    });
  });

  // ---- Progress Bar Update ----
  function updateProgressBar() {
    const total = document.querySelectorAll('.lesson-item').length;
    const completed = document.querySelectorAll('.lesson-item.completed').length;

    if (total === 0) return;

    const percentage = Math.round((completed / total) * 100);

    const progressFills = document.querySelectorAll('.progress-bar-fill');
    progressFills.forEach((fill) => {
      fill.style.width = percentage + '%';
    });

    const progressText = document.querySelector('.progress-percentage');
    if (progressText) {
      progressText.textContent = percentage + '%';
    }

    const completedCount = document.querySelector('.completed-count');
    if (completedCount) {
      completedCount.textContent = `${completed}/${total} Lessons`;
    }
  }

  // Initialize progress on load
  document.addEventListener('DOMContentLoaded', updateProgressBar);

  // ---- Load User Data ----
  function loadUserData() {
    if (typeof SpacexAuth === 'undefined') return;
    
    const user = SpacexAuth.getUser();
    if (!user) return;

    const nameEl = document.querySelector('.sidebar-user-info h6');
    const avatarEl = document.querySelector('.sidebar-user-avatar');
    const welcomeEl = document.querySelector('.dashboard-welcome-name');

    if (nameEl) nameEl.textContent = user.name || 'Student';
    if (avatarEl) avatarEl.textContent = (user.name || 'S').charAt(0).toUpperCase();
    if (welcomeEl) welcomeEl.textContent = user.name || 'Student';
  }

  loadUserData();

  // ---- Logout Button ----
  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', (e) => {
      e.preventDefault();
      if (typeof SpacexAuth !== 'undefined') {
        SpacexAuth.logout();
      } else {
        localStorage.clear();
        window.location.href = 'login.html';
      }
    });
  }
})();
