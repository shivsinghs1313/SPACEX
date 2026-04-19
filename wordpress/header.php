<?php
/**
 * SPACEX Trading Academy — WordPress Header Template
 *
 * @package SPACEX
 */

if (!defined('ABSPATH')) exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#0a0a0f">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

  <!-- Navigation -->
  <nav class="navbar" id="navbar">
    <div class="container">
      <a href="<?php echo esc_url(home_url('/')); ?>" class="navbar-logo" aria-label="<?php bloginfo('name'); ?>">
        <?php if (has_custom_logo()) : ?>
          <?php the_custom_logo(); ?>
        <?php else : ?>
          <div class="logo-icon">⚡</div>
          <span><?php bloginfo('name'); ?></span>
        <?php endif; ?>
      </a>

      <?php if (has_nav_menu('primary')) : ?>
        <div class="nav-links" id="navLinks">
          <?php
          wp_nav_menu([
            'theme_location' => 'primary',
            'container'      => false,
            'items_wrap'     => '%3$s',
            'depth'          => 1,
            'fallback_cb'    => false,
          ]);
          ?>
          <div class="nav-cta">
            <a href="#pricing" class="btn btn-primary btn-sm">Enroll Now</a>
          </div>
        </div>
      <?php else : ?>
        <div class="nav-links" id="navLinks">
          <a href="#how-it-works">How It Works</a>
          <a href="#curriculum">Curriculum</a>
          <a href="#testimonials">Results</a>
          <a href="#pricing">Pricing</a>
          <a href="#faq">FAQ</a>
          <div class="nav-cta">
            <a href="#pricing" class="btn btn-primary btn-sm">Enroll Now</a>
          </div>
        </div>
      <?php endif; ?>

      <button class="nav-toggle" id="navToggle" aria-label="<?php esc_attr_e('Toggle navigation', 'spacex'); ?>">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
  </nav>
