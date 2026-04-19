<?php
/**
 * SPACEX Trading Academy — WordPress Theme Functions
 * 
 * Registers menus, custom post types, enqueues assets,
 * and provides WordPress integration hooks.
 *
 * @package SPACEX
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

// Theme version
define('SPACEX_VERSION', '1.0.0');
define('SPACEX_DIR', get_template_directory());
define('SPACEX_URI', get_template_directory_uri());

/**
 * Theme Setup
 */
function spacex_setup() {
    // Add theme supports
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', [
        'height'      => 40,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ]);
    add_theme_support('html5', [
        'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script',
    ]);
    add_theme_support('customize-selective-refresh-widgets');
    add_theme_support('editor-styles');

    // Register navigation menus
    register_nav_menus([
        'primary'   => __('Primary Navigation', 'spacex'),
        'footer'    => __('Footer Navigation', 'spacex'),
    ]);

    // Image sizes
    add_image_size('course-thumbnail', 800, 450, true);
    add_image_size('founder-portrait', 700, 933, true);
    add_image_size('testimonial-avatar', 96, 96, true);
}
add_action('after_setup_theme', 'spacex_setup');


/**
 * Enqueue Styles & Scripts
 */
function spacex_enqueue_assets() {
    // CSS
    wp_enqueue_style('spacex-variables', SPACEX_URI . '/css/variables.css', [], SPACEX_VERSION);
    wp_enqueue_style('spacex-base', SPACEX_URI . '/css/base.css', ['spacex-variables'], SPACEX_VERSION);
    wp_enqueue_style('spacex-animations', SPACEX_URI . '/css/animations.css', ['spacex-base'], SPACEX_VERSION);
    wp_enqueue_style('spacex-components', SPACEX_URI . '/css/components.css', ['spacex-base'], SPACEX_VERSION);
    wp_enqueue_style('spacex-sections', SPACEX_URI . '/css/sections.css', ['spacex-components'], SPACEX_VERSION);
    wp_enqueue_style('spacex-responsive', SPACEX_URI . '/css/responsive.css', ['spacex-sections'], SPACEX_VERSION);
    wp_enqueue_style('spacex-style', get_stylesheet_uri(), [], SPACEX_VERSION);

    // Dashboard CSS (only on dashboard page)
    if (is_page_template('page-dashboard.php') || is_page('dashboard')) {
        wp_enqueue_style('spacex-dashboard', SPACEX_URI . '/css/dashboard.css', ['spacex-components'], SPACEX_VERSION);
        wp_enqueue_script('spacex-dashboard-js', SPACEX_URI . '/js/dashboard.js', [], SPACEX_VERSION, true);
    }

    // JS
    wp_enqueue_script('spacex-app', SPACEX_URI . '/js/app.js', [], SPACEX_VERSION, true);
    wp_enqueue_script('spacex-animations', SPACEX_URI . '/js/animations.js', ['spacex-app'], SPACEX_VERSION, true);
    wp_enqueue_script('spacex-accordion', SPACEX_URI . '/js/accordion.js', ['spacex-app'], SPACEX_VERSION, true);
    wp_enqueue_script('spacex-countdown', SPACEX_URI . '/js/countdown.js', [], SPACEX_VERSION, true);
    wp_enqueue_script('spacex-api', SPACEX_URI . '/js/api.js', [], SPACEX_VERSION, true);
    wp_enqueue_script('spacex-auth', SPACEX_URI . '/js/auth.js', ['spacex-api'], SPACEX_VERSION, true);

    // Localize script with API URL
    wp_localize_script('spacex-api', 'spacexConfig', [
        'apiUrl'  => home_url('/wp-json/spacex/v1'),
        'homeUrl' => home_url(),
        'nonce'   => wp_create_nonce('wp_rest'),
    ]);
}
add_action('wp_enqueue_scripts', 'spacex_enqueue_assets');


/**
 * Register Custom Post Types
 */
function spacex_register_post_types() {
    // Courses
    register_post_type('spacex_course', [
        'labels' => [
            'name'               => __('Courses', 'spacex'),
            'singular_name'      => __('Course', 'spacex'),
            'add_new_item'       => __('Add New Course', 'spacex'),
            'edit_item'          => __('Edit Course', 'spacex'),
            'view_item'          => __('View Course', 'spacex'),
            'search_items'       => __('Search Courses', 'spacex'),
            'not_found'          => __('No courses found', 'spacex'),
        ],
        'public'              => true,
        'has_archive'         => true,
        'show_in_rest'        => true,
        'supports'            => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
        'menu_icon'           => 'dashicons-welcome-learn-more',
        'menu_position'       => 5,
        'rewrite'             => ['slug' => 'courses'],
    ]);

    // Lessons
    register_post_type('spacex_lesson', [
        'labels' => [
            'name'               => __('Lessons', 'spacex'),
            'singular_name'      => __('Lesson', 'spacex'),
            'add_new_item'       => __('Add New Lesson', 'spacex'),
            'edit_item'          => __('Edit Lesson', 'spacex'),
        ],
        'public'              => true,
        'has_archive'         => false,
        'show_in_rest'        => true,
        'supports'            => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'menu_icon'           => 'dashicons-video-alt3',
        'menu_position'       => 6,
        'rewrite'             => ['slug' => 'lessons'],
    ]);

    // Testimonials
    register_post_type('spacex_testimonial', [
        'labels' => [
            'name'               => __('Testimonials', 'spacex'),
            'singular_name'      => __('Testimonial', 'spacex'),
            'add_new_item'       => __('Add New Testimonial', 'spacex'),
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_rest'        => true,
        'supports'            => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'menu_icon'           => 'dashicons-format-quote',
        'menu_position'       => 7,
    ]);
}
add_action('init', 'spacex_register_post_types');


/**
 * Register Custom Taxonomies
 */
function spacex_register_taxonomies() {
    register_taxonomy('course_module', 'spacex_lesson', [
        'labels' => [
            'name'          => __('Course Modules', 'spacex'),
            'singular_name' => __('Module', 'spacex'),
            'add_new_item'  => __('Add New Module', 'spacex'),
        ],
        'hierarchical'  => true,
        'show_in_rest'  => true,
        'rewrite'       => ['slug' => 'module'],
    ]);
}
add_action('init', 'spacex_register_taxonomies');


/**
 * Register Widget Areas
 */
function spacex_widgets_init() {
    register_sidebar([
        'name'          => __('Footer Widget Area', 'spacex'),
        'id'            => 'footer-widgets',
        'before_widget' => '<div class="footer-widget">',
        'after_widget'  => '</div>',
        'before_title'  => '<h6>',
        'after_title'   => '</h6>',
    ]);
}
add_action('widgets_init', 'spacex_widgets_init');


/**
 * Register REST API Routes
 */
function spacex_register_rest_routes() {
    register_rest_route('spacex/v1', '/courses', [
        'methods'  => 'GET',
        'callback' => 'spacex_api_get_courses',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('spacex/v1', '/progress', [
        'methods'  => 'POST',
        'callback' => 'spacex_api_update_progress',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);
}
add_action('rest_api_init', 'spacex_register_rest_routes');


function spacex_api_get_courses() {
    $courses = get_posts([
        'post_type'   => 'spacex_course',
        'numberposts' => -1,
        'post_status' => 'publish',
    ]);

    $data = [];
    foreach ($courses as $course) {
        $data[] = [
            'id'          => $course->ID,
            'title'       => $course->post_title,
            'description' => $course->post_excerpt,
            'price'       => get_post_meta($course->ID, '_spacex_price', true),
            'discount'    => get_post_meta($course->ID, '_spacex_discount_price', true),
            'thumbnail'   => get_the_post_thumbnail_url($course->ID, 'course-thumbnail'),
        ];
    }

    return $data;
}


function spacex_api_update_progress($request) {
    $user_id   = get_current_user_id();
    $lesson_id = intval($request->get_param('lesson_id'));
    $completed = (bool) $request->get_param('completed');

    if (!$lesson_id) {
        return new WP_Error('invalid_lesson', 'Lesson ID required', ['status' => 400]);
    }

    update_user_meta($user_id, "_spacex_lesson_{$lesson_id}_completed", $completed ? 1 : 0);

    if ($completed) {
        update_user_meta($user_id, "_spacex_lesson_{$lesson_id}_completed_at", current_time('mysql'));
    }

    return ['success' => true, 'lesson_id' => $lesson_id, 'completed' => $completed];
}


/**
 * Add Theme Customizer Settings
 */
function spacex_customize_register($wp_customize) {
    // Hero Section
    $wp_customize->add_section('spacex_hero', [
        'title'    => __('SPACEX Hero Section', 'spacex'),
        'priority' => 30,
    ]);

    $wp_customize->add_setting('spacex_hero_headline', [
        'default' => 'Stop Gambling. Start Trading With a Proven System',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    $wp_customize->add_control('spacex_hero_headline', [
        'label'   => __('Hero Headline', 'spacex'),
        'section' => 'spacex_hero',
        'type'    => 'textarea',
    ]);

    $wp_customize->add_setting('spacex_hero_subtitle', [
        'default' => 'The SPACEX Trading System gives you the discipline, clarity, and edge that 95% of traders never develop.',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    $wp_customize->add_control('spacex_hero_subtitle', [
        'label'   => __('Hero Subtitle', 'spacex'),
        'section' => 'spacex_hero',
        'type'    => 'textarea',
    ]);

    // Pricing Section
    $wp_customize->add_section('spacex_pricing', [
        'title'    => __('SPACEX Pricing', 'spacex'),
        'priority' => 40,
    ]);

    $wp_customize->add_setting('spacex_price_original', [
        'default' => '14999',
        'sanitize_callback' => 'absint',
    ]);

    $wp_customize->add_control('spacex_price_original', [
        'label'   => __('Original Price (₹)', 'spacex'),
        'section' => 'spacex_pricing',
        'type'    => 'number',
    ]);

    $wp_customize->add_setting('spacex_price_discount', [
        'default' => '4999',
        'sanitize_callback' => 'absint',
    ]);

    $wp_customize->add_control('spacex_price_discount', [
        'label'   => __('Discounted Price (₹)', 'spacex'),
        'section' => 'spacex_pricing',
        'type'    => 'number',
    ]);
}
add_action('customize_register', 'spacex_customize_register');


/**
 * Custom Meta Boxes for Courses
 */
function spacex_add_course_meta_boxes() {
    add_meta_box(
        'spacex_course_details',
        __('Course Details', 'spacex'),
        'spacex_course_meta_box_html',
        'spacex_course',
        'side'
    );
}
add_action('add_meta_boxes', 'spacex_add_course_meta_boxes');


function spacex_course_meta_box_html($post) {
    $price = get_post_meta($post->ID, '_spacex_price', true);
    $discount = get_post_meta($post->ID, '_spacex_discount_price', true);
    $duration = get_post_meta($post->ID, '_spacex_duration', true);
    wp_nonce_field('spacex_course_meta', 'spacex_course_nonce');
    ?>
    <p>
        <label for="spacex_price"><strong>Price (₹):</strong></label><br>
        <input type="number" id="spacex_price" name="spacex_price" value="<?php echo esc_attr($price); ?>" style="width:100%;">
    </p>
    <p>
        <label for="spacex_discount"><strong>Discount Price (₹):</strong></label><br>
        <input type="number" id="spacex_discount" name="spacex_discount_price" value="<?php echo esc_attr($discount); ?>" style="width:100%;">
    </p>
    <p>
        <label for="spacex_duration"><strong>Total Duration:</strong></label><br>
        <input type="text" id="spacex_duration" name="spacex_duration" value="<?php echo esc_attr($duration); ?>" placeholder="e.g. 40+ Hours" style="width:100%;">
    </p>
    <?php
}


function spacex_save_course_meta($post_id) {
    if (!isset($_POST['spacex_course_nonce']) || !wp_verify_nonce($_POST['spacex_course_nonce'], 'spacex_course_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $fields = ['spacex_price' => '_spacex_price', 'spacex_discount_price' => '_spacex_discount_price', 'spacex_duration' => '_spacex_duration'];
    
    foreach ($fields as $input => $meta) {
        if (isset($_POST[$input])) {
            update_post_meta($post_id, $meta, sanitize_text_field($_POST[$input]));
        }
    }
}
add_action('save_post_spacex_course', 'spacex_save_course_meta');


/**
 * Performance: Remove unnecessary WP items
 */
function spacex_cleanup() {
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
}
add_action('init', 'spacex_cleanup');


/**
 * Add preconnect for Google Fonts
 */
function spacex_preconnect_fonts($urls, $relation_type) {
    if ($relation_type === 'preconnect') {
        $urls[] = ['href' => 'https://fonts.googleapis.com', 'crossorigin' => ''];
        $urls[] = ['href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous'];
    }
    return $urls;
}
add_filter('wp_resource_hints', 'spacex_preconnect_fonts', 10, 2);
