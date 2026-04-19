<?php
/**
 * SPACEX Trading Academy — WordPress Footer Template
 *
 * @package SPACEX
 */

if (!defined('ABSPATH')) exit;
?>

  <!-- Footer -->
  <footer class="site-footer" id="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <a href="<?php echo esc_url(home_url('/')); ?>" class="navbar-logo">
            <div class="logo-icon">⚡</div>
            <span><?php bloginfo('name'); ?></span>
          </a>
          <p><?php echo esc_html(get_bloginfo('description')); ?></p>
        </div>

        <div class="footer-column">
          <h6><?php esc_html_e('Quick Links', 'spacex'); ?></h6>
          <?php if (has_nav_menu('footer')) : ?>
            <?php wp_nav_menu([
              'theme_location' => 'footer',
              'container'      => false,
              'depth'          => 1,
              'fallback_cb'    => false,
            ]); ?>
          <?php else : ?>
            <ul>
              <li><a href="#how-it-works">How It Works</a></li>
              <li><a href="#curriculum">Curriculum</a></li>
              <li><a href="#testimonials">Results</a></li>
              <li><a href="#pricing">Pricing</a></li>
            </ul>
          <?php endif; ?>
        </div>

        <div class="footer-column">
          <h6><?php esc_html_e('Company', 'spacex'); ?></h6>
          <ul>
            <li><a href="#founder">About Us</a></li>
            <li><a href="#faq">FAQ</a></li>
            <li><a href="<?php echo esc_url(get_privacy_policy_url()); ?>">Privacy Policy</a></li>
            <li><a href="#">Terms of Service</a></li>
          </ul>
        </div>

        <div class="footer-column">
          <h6><?php esc_html_e('Contact', 'spacex'); ?></h6>
          <ul>
            <li><a href="mailto:support@spacextrading.com">support@spacextrading.com</a></li>
            <li><a href="tel:+919876543210">+91 98765 43210</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. <?php esc_html_e('All rights reserved.', 'spacex'); ?></p>
        <div class="footer-socials">
          <a href="#" aria-label="Instagram">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.5"/></svg>
          </a>
          <a href="#" aria-label="YouTube">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19.6 3.2H4.4C2.5 3.2.9 4.8.9 6.7v10.5c0 1.9 1.6 3.5 3.5 3.5h15.2c1.9 0 3.5-1.6 3.5-3.5V6.7c0-1.9-1.6-3.5-3.5-3.5zM10 15.5v-7l6 3.5-6 3.5z"/></svg>
          </a>
          <a href="#" aria-label="Twitter">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
          </a>
        </div>
      </div>
    </div>
  </footer>

  <!-- Sticky CTA Bar -->
  <div class="sticky-cta" id="stickyCta">
    <div class="container">
      <span class="sticky-cta-text">
        🔥 <strong>67% OFF</strong> — Limited seats at ₹<?php echo esc_html(get_theme_mod('spacex_price_discount', '4,999')); ?>
      </span>
      <a href="#pricing" class="btn btn-primary btn-sm" id="sticky-cta-btn">Enroll Now</a>
    </div>
  </div>

  <?php wp_footer(); ?>
</body>
</html>
