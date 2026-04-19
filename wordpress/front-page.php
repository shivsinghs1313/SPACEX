<?php
/**
 * SPACEX Trading Academy — Front Page Template
 * Template Name: SPACEX Home
 *
 * This template pulls dynamic content from the WordPress Customizer
 * and custom post types, reusing the same section structure as index.html.
 *
 * @package SPACEX
 */

if (!defined('ABSPATH')) exit;

get_header();

// Theme mod values
$hero_headline = get_theme_mod('spacex_hero_headline', 'Stop Gambling. Start Trading With a Proven System');
$hero_subtitle = get_theme_mod('spacex_hero_subtitle', 'The SPACEX Trading System gives you the discipline, clarity, and edge that 95% of traders never develop. Built on institutional-grade strategies refined over 8+ years in live markets.');
$price_original = get_theme_mod('spacex_price_original', '14999');
$price_discount = get_theme_mod('spacex_price_discount', '4999');
$discount_percent = round((1 - intval($price_discount) / intval($price_original)) * 100);
?>

  <!-- ============ HERO SECTION ============ -->
  <section class="hero" id="hero">
    <div class="hero-bg">
      <div class="gradient-orb orb-1"></div>
      <div class="gradient-orb orb-2"></div>
      <div class="gradient-orb orb-3"></div>
    </div>
    <div class="hero-grid"></div>

    <div class="hero-chart">
      <svg viewBox="0 0 1440 400" preserveAspectRatio="none">
        <defs>
          <linearGradient id="chartGradient" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" style="stop-color:rgba(0,255,136,0.3);stop-opacity:1" />
            <stop offset="100%" style="stop-color:rgba(0,255,136,0);stop-opacity:1" />
          </linearGradient>
        </defs>
        <path class="chart-area" d="M0,350 L0,280 Q80,260 160,240 T320,200 T480,180 T640,220 T800,160 T960,140 T1120,180 T1280,120 T1440,100 L1440,350 Z" />
        <path class="chart-line" d="M0,280 Q80,260 160,240 T320,200 T480,180 T640,220 T800,160 T960,140 T1120,180 T1280,120 T1440,100" />
      </svg>
    </div>

    <div class="container">
      <div class="hero-content">
        <div class="hero-badge">
          <span class="pulse"></span>
          Trusted by 5,000+ Traders Across India
        </div>

        <h1><?php echo esc_html($hero_headline); ?></h1>
        <p class="hero-subtitle"><?php echo esc_html($hero_subtitle); ?></p>

        <div class="hero-actions">
          <a href="#pricing" class="btn btn-primary btn-lg">
            Start Trading Like a Pro
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </a>
          <a href="#how-it-works" class="btn btn-secondary btn-lg">See How It Works</a>
        </div>

        <div class="hero-stats">
          <div class="hero-stat">
            <span class="stat-value">5,000+</span>
            <span class="stat-label">Students Enrolled</span>
          </div>
          <div class="hero-stat">
            <span class="stat-value">93%</span>
            <span class="stat-label">Success Rate</span>
          </div>
          <div class="hero-stat">
            <span class="stat-value">8+</span>
            <span class="stat-label">Years Experience</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <hr class="section-divider">

  <?php
  /**
   * Template parts for each section.
   * In a full WordPress build, each section below would be
   * extracted into its own template-parts/section-*.php file.
   * For now, they use the same static content as index.html
   * with dynamic values where applicable.
   */
  ?>

  <!-- Problem, Reality, Solution, How It Works, etc. -->
  <!-- These sections use the same HTML as index.html -->
  <!-- In production, use get_template_part() for each section -->

  <!-- ============ PRICING SECTION (Dynamic) ============ -->
  <section class="pricing-section" id="pricing">
    <div class="container">
      <div class="section-header animate-on-scroll">
        <div class="section-label"><span class="dot"></span> Invest In Yourself</div>
        <h2>One Investment. <span class="text-gradient">Lifetime Returns.</span></h2>
        <p>The cost of this course is less than one bad trade. The knowledge lasts forever.</p>
      </div>

      <div class="price-card animate-scale">
        <div class="badge-gold" style="display:inline-flex;padding:var(--space-2) var(--space-4);margin-bottom:var(--space-6);font-weight:600;">
          🔥 Limited Time — <?php echo esc_html($discount_percent); ?>% OFF
        </div>
        <div class="price-original">₹<?php echo esc_html(number_format(intval($price_original))); ?></div>
        <div class="price-current">₹<?php echo esc_html(number_format(intval($price_discount))); ?></div>
        <div class="price-period">One-time payment • Lifetime access</div>

        <ul class="price-features">
          <li><span class="check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span> 6 Comprehensive Modules (40+ Hours)</li>
          <li><span class="check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span> 50+ Real Trade Breakdowns</li>
          <li><span class="check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span> Downloadable Checklists & Templates</li>
          <li><span class="check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span> Private Community Access</li>
          <li><span class="check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span> Lifetime Updates & New Content</li>
          <li><span class="check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span> Certificate of Completion</li>
          <li><span class="check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span> 1-on-1 Doubt Resolution Support</li>
        </ul>

        <a href="<?php echo esc_url(wp_registration_url()); ?>" class="btn btn-primary btn-lg" style="width:100%;">
          Enroll Now — Start Trading Like a Pro
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
      </div>

      <div class="price-guarantee animate-on-scroll" style="margin-top: var(--space-8);">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        <span>7-Day Money Back Guarantee — Zero Risk</span>
      </div>
    </div>
  </section>

  <!-- ============ TESTIMONIALS (Dynamic from CPT) ============ -->
  <?php
  $testimonials = get_posts([
      'post_type'   => 'spacex_testimonial',
      'numberposts' => 10,
      'post_status' => 'publish',
  ]);

  if (!empty($testimonials)) : ?>
  <section class="testimonials-section" id="testimonials">
    <div class="container">
      <div class="section-header animate-on-scroll">
        <div class="section-label"><span class="dot"></span> Student Results</div>
        <h2>Real Traders, <span class="text-gradient">Real Results</span></h2>
      </div>
    </div>
    <div class="testimonials-track">
      <?php foreach ($testimonials as $testimonial) :
        $name   = $testimonial->post_title;
        $text   = $testimonial->post_content;
        $role   = get_post_meta($testimonial->ID, '_spacex_role', true);
        $initials = implode('', array_map(fn($w) => strtoupper($w[0]), explode(' ', $name)));
      ?>
      <div class="testimonial-card">
        <div class="testimonial-stars">★★★★★</div>
        <p class="testimonial-text">"<?php echo esc_html(wp_strip_all_tags($text)); ?>"</p>
        <div class="testimonial-author">
          <div class="testimonial-avatar"><?php echo esc_html(substr($initials, 0, 2)); ?></div>
          <div class="testimonial-author-info">
            <h6><?php echo esc_html($name); ?></h6>
            <?php if ($role) : ?><p><?php echo esc_html($role); ?></p><?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

<?php get_footer(); ?>
