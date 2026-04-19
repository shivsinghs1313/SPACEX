/* ============================================
   SPACEX — API Client
   ============================================ */

(function () {
  'use strict';

  const API_BASE = 'backend/api';

  async function apiRequest(endpoint, method = 'GET', body = null) {
    const token = localStorage.getItem('spacex_token');

    const headers = {
      'Content-Type': 'application/json',
    };

    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    const options = { method, headers };

    if (body && (method === 'POST' || method === 'PUT')) {
      options.body = JSON.stringify(body);
    }

    try {
      const response = await fetch(`${API_BASE}/${endpoint}`, options);

      // Handle 401 — redirect to login
      if (response.status === 401) {
        localStorage.removeItem('spacex_token');
        localStorage.removeItem('spacex_user');
        window.location.href = 'login.html';
        return null;
      }

      const data = await response.json();
      return data;
    } catch (error) {
      console.error('API Error:', error);
      return {
        success: false,
        message: 'Network error. Please check your connection.',
        data: null,
      };
    }
  }

  // ---- Public API Methods ----
  window.SpacexAPI = {
    // Auth
    login: (email, password) =>
      apiRequest('auth.php?action=login', 'POST', { email, password }),
    register: (name, email, password) =>
      apiRequest('auth.php?action=register', 'POST', { name, email, password }),
    getProfile: () =>
      apiRequest('auth.php?action=me'),

    // Courses
    getCourses: () =>
      apiRequest('courses.php'),
    getCourse: (id) =>
      apiRequest(`courses.php?id=${id}`),

    // Lessons
    getLessons: (courseId) =>
      apiRequest(`lessons.php?course_id=${courseId}`),

    // Progress
    getProgress: (courseId) =>
      apiRequest(`progress.php?course_id=${courseId}`),
    updateProgress: (lessonId, completed) =>
      apiRequest('progress.php', 'POST', { lesson_id: lessonId, completed }),

    // Purchases
    createPurchase: (courseId) =>
      apiRequest('purchases.php?action=create', 'POST', { course_id: courseId }),
    verifyPayment: (paymentData) =>
      apiRequest('purchases.php?action=verify', 'POST', paymentData),
    getPurchaseHistory: () =>
      apiRequest('purchases.php?action=history'),

    // Admin
    getAdminStats: () =>
      apiRequest('admin.php?action=stats'),
    getUsers: (page = 1) =>
      apiRequest(`admin.php?action=users&page=${page}`),

    // Video Streaming
    getVideoToken: (lessonId) =>
      apiRequest(`stream.php?action=token&lesson_id=${lessonId}`),
    getVideoInfo: (lessonId) =>
      apiRequest(`stream.php?action=info&lesson_id=${lessonId}`),

    // Purchase Status
    checkPurchaseStatus: (courseId) =>
      apiRequest(`purchases.php?action=status&course_id=${courseId}`),

    // Health Check
    healthCheck: () =>
      apiRequest('health.php'),
  };
})();
