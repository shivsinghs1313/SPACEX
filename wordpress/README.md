# SPACEX Trading Academy — WordPress Theme Guide

## Overview

The SPACEX WordPress theme converts the static HTML/CSS/JS landing page into a full WordPress theme with dynamic content management.

---

## Installation

### 1. Prepare the Theme

Copy the entire `SPACEX/` directory into your WordPress `wp-content/themes/` folder:

```
wp-content/themes/spacex/
├── css/
├── js/
├── assets/
├── backend/
├── wordpress/
│   ├── style.css
│   ├── functions.php
│   ├── header.php
│   ├── footer.php
│   └── front-page.php
├── index.html → (rename to index.php or use front-page.php)
└── ...
```

### 2. Restructure for WordPress

Move the WordPress template files to the theme root:

```bash
# From the theme directory:
cp wordpress/style.css ./style.css
cp wordpress/functions.php ./functions.php
cp wordpress/header.php ./header.php
cp wordpress/footer.php ./footer.php
cp wordpress/front-page.php ./front-page.php
```

### 3. Activate the Theme

1. Log in to WordPress Admin → Appearance → Themes
2. Find "SPACEX Trading Academy" and click **Activate**

### 4. Configure

1. **Customizer** (Appearance → Customize):
   - Set hero headline and subtitle
   - Configure pricing (original and discounted)
   - Upload custom logo

2. **Menus** (Appearance → Menus):
   - Create a "Primary Navigation" menu
   - Create a "Footer Navigation" menu
   - Assign to respective locations

3. **Pages**:
   - Create a page and set it as the **Static Front Page** (Settings → Reading)
   - Set the front page template to "SPACEX Home"

---

## Content Management

### Courses (Admin → Courses)
- Add courses with title, description, and featured image
- Set pricing in the Course Details meta box (sidebar)
- Publish to make visible on the site

### Lessons (Admin → Lessons)
- Add lessons linked to modules
- Assign to Course Modules taxonomy
- Set video URL, duration, and PDF attachments via custom fields

### Testimonials (Admin → Testimonials)
- Add student testimonials with name (title) and quote (content)
- Add `_spacex_role` custom field for their designation
- These automatically appear in the testimonials section

---

## Required Plugins (Recommended)

| Plugin | Purpose |
|--------|---------|
| **Advanced Custom Fields (ACF)** | Enhanced custom fields for courses/lessons |
| **WooCommerce** | Optional — for payment processing integration |
| **Yoast SEO** | SEO optimization |
| **WP Super Cache** | Performance caching |
| **Wordfence** | Security |

---

## REST API Endpoints

The theme registers custom REST API endpoints:

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/wp-json/spacex/v1/courses` | GET | List published courses |
| `/wp-json/spacex/v1/progress` | POST | Update lesson progress (authenticated) |

---

## Database

The PHP backend's MySQL schema (`backend/sql/schema.sql`) can be used alongside WordPress. Import it into your WordPress database for the standalone course management system.

For full WordPress integration, the theme uses:
- Custom Post Types (`spacex_course`, `spacex_lesson`, `spacex_testimonial`)
- Custom Taxonomies (`course_module`)
- User Meta for progress tracking
- Post Meta for pricing and duration

---

## File Structure

```
spacex/
├── style.css               # WP theme header + CSS imports
├── functions.php           # Theme functions, CPTs, REST API
├── header.php              # <head>, navbar
├── footer.php              # Footer, sticky CTA, wp_footer()
├── front-page.php          # Homepage with all sections
├── css/                    # Design system
│   ├── variables.css       # Design tokens
│   ├── base.css            # Reset, typography
│   ├── animations.css      # Keyframes
│   ├── components.css      # UI components
│   ├── sections.css        # Section styles
│   ├── dashboard.css       # Student dashboard
│   └── responsive.css      # Breakpoints
├── js/                     # JavaScript
│   ├── app.js              # Main init
│   ├── animations.js       # Scroll animations
│   ├── accordion.js        # FAQ/curriculum
│   ├── countdown.js        # Timer
│   ├── auth.js             # Login/register
│   ├── api.js              # REST client
│   └── dashboard.js        # Dashboard
├── assets/images/          # Generated images
└── backend/                # Standalone PHP API
    ├── config/database.php
    ├── api/
    ├── includes/
    └── sql/schema.sql
```

---

## Customization

### Colors
Edit `css/variables.css` — all colors are defined as CSS custom properties.

### Typography
The theme uses Google Fonts (Outfit + Inter). Modify in `css/base.css`.

### Adding Sections
1. Create a new template part in `template-parts/section-name.php`
2. Include it in `front-page.php` using `get_template_part('template-parts/section', 'name')`

---

## Support

For questions or customization requests, contact: support@spacextrading.com
