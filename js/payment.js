/* ============================================
   SPACEX — Razorpay Payment Gateway Integration
   Premium checkout flow with order summary modal
   ============================================ */

(function () {
  'use strict';

  const API_BASE = 'backend/api';
  let razorpayLoaded = false;

  // ---- Load Razorpay SDK dynamically ----
  function loadRazorpaySDK() {
    return new Promise((resolve, reject) => {
      if (razorpayLoaded || window.Razorpay) {
        razorpayLoaded = true;
        resolve();
        return;
      }
      const script = document.createElement('script');
      script.src = 'https://checkout.razorpay.com/v1/checkout.js';
      script.async = true;
      script.onload = () => { razorpayLoaded = true; resolve(); };
      script.onerror = () => reject(new Error('Failed to load Razorpay SDK'));
      document.head.appendChild(script);
    });
  }

  // ---- Toast Notification ----
  function showToast(message, type = 'info') {
    const existing = document.querySelector('.spacex-toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `spacex-toast spacex-toast-${type}`;
    toast.innerHTML = `
      <div class="toast-icon">${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</div>
      <div class="toast-message">${message}</div>
    `;

    Object.assign(toast.style, {
      position: 'fixed', bottom: '24px', right: '24px', zIndex: '10000',
      display: 'flex', alignItems: 'center', gap: '12px',
      padding: '16px 24px', borderRadius: '12px',
      background: type === 'success' ? 'rgba(0,255,136,0.15)' : type === 'error' ? 'rgba(255,68,102,0.15)' : 'rgba(68,136,255,0.15)',
      border: `1px solid ${type === 'success' ? 'rgba(0,255,136,0.3)' : type === 'error' ? 'rgba(255,68,102,0.3)' : 'rgba(68,136,255,0.3)'}`,
      color: '#fff', fontSize: '14px', fontFamily: 'Inter, sans-serif',
      backdropFilter: 'blur(20px)', boxShadow: '0 8px 32px rgba(0,0,0,0.3)',
      transform: 'translateY(100px)', opacity: '0', transition: 'all 0.4s cubic-bezier(0.16,1,0.3,1)',
    });

    document.body.appendChild(toast);
    requestAnimationFrame(() => {
      toast.style.transform = 'translateY(0)';
      toast.style.opacity = '1';
    });

    setTimeout(() => {
      toast.style.transform = 'translateY(100px)';
      toast.style.opacity = '0';
      setTimeout(() => toast.remove(), 400);
    }, 4000);
  }

  // ---- Order Summary Modal ----
  function showOrderModal(orderData) {
    return new Promise((resolve, reject) => {
      const overlay = document.createElement('div');
      overlay.className = 'payment-modal-overlay';
      overlay.id = 'paymentModal';
      
      const discountHtml = orderData.discount > 0 
        ? `<div class="order-row order-discount">
             <span>Discount Applied</span>
             <span class="text-neon">-₹${orderData.discount.toLocaleString('en-IN')}</span>
           </div>`
        : '';

      overlay.innerHTML = `
        <div class="payment-modal">
          <div class="payment-modal-header">
            <div class="payment-modal-icon">⚡</div>
            <h3>Order Summary</h3>
            <button class="payment-modal-close" id="closePaymentModal">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12"/>
              </svg>
            </button>
          </div>
          
          <div class="payment-modal-body">
            <div class="order-course-info">
              <div class="order-course-icon">📈</div>
              <div>
                <h4>${orderData.course_title}</h4>
                <p>Lifetime Access • 6 Modules • 30 Lessons</p>
              </div>
            </div>

            <div class="order-summary">
              <div class="order-row">
                <span>Course Price</span>
                <span class="order-original-price">₹${orderData.original_price.toLocaleString('en-IN')}</span>
              </div>
              ${discountHtml}
              <div class="order-divider"></div>
              <div class="order-row order-total">
                <span>Total</span>
                <span>₹${orderData.amount.toLocaleString('en-IN')}</span>
              </div>
            </div>

            <div class="order-features">
              <div class="order-feature"><span class="check-icon">✓</span> Instant access after payment</div>
              <div class="order-feature"><span class="check-icon">✓</span> 7-day money-back guarantee</div>
              <div class="order-feature"><span class="check-icon">✓</span> Secure payment via Razorpay</div>
            </div>

            ${orderData.is_test ? '<div class="order-test-badge">🧪 Test Mode — No real charges</div>' : ''}
          </div>

          <div class="payment-modal-footer">
            <button class="btn btn-primary btn-lg payment-proceed-btn" id="proceedPaymentBtn">
              <span class="btn-text">Pay ₹${orderData.amount.toLocaleString('en-IN')}</span>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M5 12h14M12 5l7 7-7 7"/>
              </svg>
            </button>
            <div class="payment-secure-badge">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
              </svg>
              <span>256-bit SSL Encrypted • Powered by Razorpay</span>
            </div>
          </div>
        </div>
      `;

      document.body.appendChild(overlay);
      requestAnimationFrame(() => overlay.classList.add('active'));

      // Close button
      document.getElementById('closePaymentModal').addEventListener('click', () => {
        overlay.classList.remove('active');
        setTimeout(() => overlay.remove(), 300);
        reject(new Error('cancelled'));
      });

      // Overlay click to close
      overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
          overlay.classList.remove('active');
          setTimeout(() => overlay.remove(), 300);
          reject(new Error('cancelled'));
        }
      });

      // Proceed button
      document.getElementById('proceedPaymentBtn').addEventListener('click', () => {
        overlay.classList.remove('active');
        setTimeout(() => overlay.remove(), 300);
        resolve();
      });
    });
  }

  // ---- Main Checkout Function ----
  async function initCheckout(courseId) {
    // Check auth
    const token = localStorage.getItem('spacex_token');
    if (!token) {
      showToast('Please log in or create an account first', 'error');
      setTimeout(() => { window.location.href = 'login.html'; }, 1500);
      return;
    }

    // Load Razorpay SDK
    try {
      await loadRazorpaySDK();
    } catch (err) {
      showToast('Failed to load payment gateway. Please try again.', 'error');
      return;
    }

    // Create order via API
    showToast('Preparing your order...', 'info');

    try {
      const response = await fetch(`${API_BASE}/purchases.php?action=create`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({ course_id: courseId }),
      });

      const data = await response.json();

      if (!data.success) {
        if (data.message && data.message.includes('already purchased')) {
          showToast('You already own this course! Redirecting to dashboard...', 'success');
          setTimeout(() => { window.location.href = 'dashboard.html'; }, 1500);
          return;
        }
        showToast(data.message || 'Failed to create order', 'error');
        return;
      }

      const orderData = data.data;

      // Show order summary modal
      try {
        await showOrderModal(orderData);
      } catch (e) {
        return; // User cancelled
      }

      // Open Razorpay checkout
      openRazorpayCheckout(orderData);

    } catch (err) {
      console.error('Checkout error:', err);
      showToast('Something went wrong. Please try again.', 'error');
    }
  }

  // ---- Open Razorpay Checkout Modal ----
  function openRazorpayCheckout(orderData) {
    const options = {
      key: orderData.key_id,
      amount: orderData.amount * 100,
      currency: orderData.currency,
      name: 'SPACEX Trading Academy',
      description: orderData.course_title,
      order_id: orderData.order_id,
      prefill: {
        name: orderData.user_name || '',
        email: orderData.user_email || '',
      },
      theme: {
        color: '#00FF88',
        backdrop_color: 'rgba(10,10,15,0.85)',
      },
      modal: {
        ondismiss: function () {
          showToast('Payment was cancelled', 'info');
        },
      },
      handler: async function (response) {
        // Payment successful — verify on server
        await verifyPayment(response, orderData);
      },
    };

    try {
      const rzp = new window.Razorpay(options);

      rzp.on('payment.failed', function (response) {
        const errorMsg = response.error?.description || 'Payment failed';
        showToast(`Payment failed: ${errorMsg}`, 'error');
      });

      rzp.open();
    } catch (err) {
      console.error('Razorpay error:', err);

      // Fallback for development mode (simulated payments)
      if (orderData.is_test || orderData.order_id.startsWith('order_sim_')) {
        simulatePayment(orderData);
      } else {
        showToast('Payment gateway error. Please try again.', 'error');
      }
    }
  }

  // ---- Verify Payment on Server ----
  async function verifyPayment(razorpayResponse, orderData) {
    showToast('Verifying payment...', 'info');

    const token = localStorage.getItem('spacex_token');

    try {
      const response = await fetch(`${API_BASE}/purchases.php?action=verify`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({
          razorpay_order_id: razorpayResponse.razorpay_order_id,
          razorpay_payment_id: razorpayResponse.razorpay_payment_id,
          razorpay_signature: razorpayResponse.razorpay_signature,
        }),
      });

      const data = await response.json();

      if (data.success) {
        showPaymentSuccess(orderData);
      } else {
        showToast(data.message || 'Payment verification failed', 'error');
      }
    } catch (err) {
      console.error('Verification error:', err);
      showToast('Verification failed. Contact support if charged.', 'error');
    }
  }

  // ---- Simulated Payment for Dev Mode ----
  function simulatePayment(orderData) {
    showToast('🧪 Dev mode: Simulating payment...', 'info');

    setTimeout(async () => {
      const fakeResponse = {
        razorpay_order_id: orderData.order_id,
        razorpay_payment_id: 'pay_sim_' + Date.now(),
        razorpay_signature: 'simulated_signature',
      };
      await verifyPayment(fakeResponse, orderData);
    }, 1500);
  }

  // ---- Payment Success Screen ----
  function showPaymentSuccess(orderData) {
    const overlay = document.createElement('div');
    overlay.className = 'payment-modal-overlay active';
    overlay.id = 'paymentSuccessModal';

    overlay.innerHTML = `
      <div class="payment-modal payment-success-modal">
        <div class="success-animation">
          <div class="success-checkmark">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#00FF88" stroke-width="2">
              <circle cx="12" cy="12" r="10" opacity="0.2"/>
              <path d="M8 12l3 3 5-5" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
        </div>
        <h2 style="color:#00FF88;margin-bottom:8px;">Payment Successful! 🎉</h2>
        <p style="color:var(--color-text-secondary,#8a8f98);margin-bottom:8px;">
          You now have lifetime access to
        </p>
        <h4 style="margin-bottom:24px;">${orderData.course_title}</h4>
        <p style="color:var(--color-text-tertiary,#565a63);font-size:13px;margin-bottom:32px;">
          Payment ID: ${orderData.order_id}<br>
          Amount: ₹${orderData.amount.toLocaleString('en-IN')}
        </p>
        <a href="dashboard.html" class="btn btn-primary btn-lg" style="width:100%;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:8px;">
          Start Learning Now
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M5 12h14M12 5l7 7-7 7"/>
          </svg>
        </a>
      </div>
    `;

    document.body.appendChild(overlay);

    // Auto-redirect after 8 seconds
    setTimeout(() => {
      window.location.href = 'dashboard.html';
    }, 8000);
  }

  // ---- Public API ----
  window.SpacexPayment = {
    initCheckout,
    showToast,
    loadRazorpaySDK,
  };

})();
