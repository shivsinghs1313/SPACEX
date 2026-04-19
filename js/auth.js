/* ============================================
   SPACEX — Auth Forms (Login/Register)
   ============================================ */

(function () {
  'use strict';

  const API_BASE = 'backend/api';

  // ---- Form Validation ----
  function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  function validatePassword(password) {
    return password.length >= 8;
  }

  function getPasswordStrength(password) {
    let score = 0;
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
    if (/\d/.test(password)) score++;
    if (/[^a-zA-Z\d]/.test(password)) score++;

    if (score <= 1) return { level: 'weak', label: 'Weak', color: '#FF4466' };
    if (score <= 2) return { level: 'fair', label: 'Fair', color: '#FFD700' };
    if (score <= 3) return { level: 'good', label: 'Good', color: '#4488FF' };
    return { level: 'strong', label: 'Strong', color: '#00FF88' };
  }

  function showError(input, message) {
    const group = input.closest('.form-group');
    if (!group) return;

    // Remove existing error
    const existingError = group.querySelector('.form-error');
    if (existingError) existingError.remove();

    input.style.borderColor = 'var(--color-error)';

    const errorEl = document.createElement('span');
    errorEl.className = 'form-error';
    errorEl.textContent = message;
    errorEl.style.cssText =
      'color: var(--color-error); font-size: var(--text-xs); margin-top: var(--space-1); display: block;';
    group.appendChild(errorEl);
  }

  function clearError(input) {
    const group = input.closest('.form-group');
    if (!group) return;

    input.style.borderColor = '';
    const existingError = group.querySelector('.form-error');
    if (existingError) existingError.remove();
  }

  // ---- Login Form ----
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const email = loginForm.querySelector('#loginEmail');
      const password = loginForm.querySelector('#loginPassword');
      const submitBtn = loginForm.querySelector('button[type="submit"]');
      let isValid = true;

      // Clear errors
      clearError(email);
      clearError(password);

      if (!validateEmail(email.value.trim())) {
        showError(email, 'Please enter a valid email address');
        isValid = false;
      }

      if (!password.value) {
        showError(password, 'Password is required');
        isValid = false;
      }

      if (!isValid) return;

      // Submit
      submitBtn.disabled = true;
      submitBtn.textContent = 'Signing in...';

      try {
        const response = await fetch(`${API_BASE}/auth.php?action=login`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            email: email.value.trim(),
            password: password.value,
          }),
        });

        const data = await response.json();

        if (data.success) {
          localStorage.setItem('spacex_token', data.data.token);
          localStorage.setItem('spacex_user', JSON.stringify(data.data.user));
          window.location.href = 'dashboard.html';
        } else {
          showError(email, data.message || 'Invalid credentials');
        }
      } catch (err) {
        showError(email, 'Connection error. Please try again.');
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Sign In';
      }
    });
  }

  // ---- Register Form ----
  const registerForm = document.getElementById('registerForm');
  if (registerForm) {
    // Password strength indicator
    const passwordInput = registerForm.querySelector('#registerPassword');
    const strengthBar = registerForm.querySelector('#strengthBar');
    const strengthText = registerForm.querySelector('#strengthText');

    if (passwordInput && strengthBar) {
      passwordInput.addEventListener('input', () => {
        const strength = getPasswordStrength(passwordInput.value);
        const widthMap = { weak: '25%', fair: '50%', good: '75%', strong: '100%' };
        
        strengthBar.style.width = widthMap[strength.level];
        strengthBar.style.background = strength.color;
        if (strengthText) {
          strengthText.textContent = strength.label;
          strengthText.style.color = strength.color;
        }
      });
    }

    registerForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const name = registerForm.querySelector('#registerName');
      const email = registerForm.querySelector('#registerEmail');
      const password = registerForm.querySelector('#registerPassword');
      const confirmPassword = registerForm.querySelector('#registerConfirmPassword');
      const terms = registerForm.querySelector('#termsAccept');
      const submitBtn = registerForm.querySelector('button[type="submit"]');
      let isValid = true;

      // Clear errors
      [name, email, password, confirmPassword].forEach(clearError);

      if (!name.value.trim()) {
        showError(name, 'Name is required');
        isValid = false;
      }

      if (!validateEmail(email.value.trim())) {
        showError(email, 'Please enter a valid email address');
        isValid = false;
      }

      if (!validatePassword(password.value)) {
        showError(password, 'Password must be at least 8 characters');
        isValid = false;
      }

      if (password.value !== confirmPassword.value) {
        showError(confirmPassword, 'Passwords do not match');
        isValid = false;
      }

      if (terms && !terms.checked) {
        showError(terms, 'You must accept the terms');
        isValid = false;
      }

      if (!isValid) return;

      // Submit
      submitBtn.disabled = true;
      submitBtn.textContent = 'Creating account...';

      try {
        const response = await fetch(`${API_BASE}/auth.php?action=register`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            name: name.value.trim(),
            email: email.value.trim(),
            password: password.value,
          }),
        });

        const data = await response.json();

        if (data.success) {
          localStorage.setItem('spacex_token', data.data.token);
          localStorage.setItem('spacex_user', JSON.stringify(data.data.user));
          window.location.href = 'dashboard.html';
        } else {
          showError(email, data.message || 'Registration failed');
        }
      } catch (err) {
        showError(email, 'Connection error. Please try again.');
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Create Account';
      }
    });
  }

  // ---- Check Auth State ----
  window.SpacexAuth = {
    isLoggedIn() {
      return !!localStorage.getItem('spacex_token');
    },
    getToken() {
      return localStorage.getItem('spacex_token');
    },
    getUser() {
      const user = localStorage.getItem('spacex_user');
      return user ? JSON.parse(user) : null;
    },
    logout() {
      localStorage.removeItem('spacex_token');
      localStorage.removeItem('spacex_user');
      window.location.href = 'login.html';
    },
  };
})();
