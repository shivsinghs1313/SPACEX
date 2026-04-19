# SPACEX Trading Platform

SPACEX Trading Platform is a dynamic, premium space-themed educational platform offering exclusive trading courses. The platform features advanced video streaming with purchase-gated access, integrated Razorpay payment processing, and a comprehensive user dashboard.

## Features

- **Premium UI/UX:** Responsive, modern space-themed design using pure HTML, CSS, and JS.
- **Purchase-Gated Content:** Advanced video player for streaming exclusive trading academy videos, accessible only to students who have purchased the course.
- **Payment Processing:** Full integration with Razorpay payment gateway for secure transactions.
- **User Authentication:** Registration, Login, and secure session handling managed by a custom PHP backend.
- **Admin & User Dashboard:** Analytics, progress tracking, and user management capabilities.

## Tech Stack

- **Frontend:** HTML5, CSS3, JavaScript
- **Backend:** PHP with PDO (Database Abstraction)
- **Database:** MySQL
- **Payments:** Razorpay API

## Project Structure

- `/backend` - Core backend logic, including API endpoints, configuration files (`config/`), database connection, and routing.
- `/css` - Vanilla CSS styles and custom UI abstractions.
- `/js` - Interactive elements, video player controllers, dynamic data loading, and auth handling.
- `/videos` - Storage for video assets.
- `/assets` - Images, icons, and graphic assets for the web UI.
- `index.html` - The landing page.
- `login.html` & `register.html` - User authentication forms.
- `dashboard.html` - Individual student access panel.
- `admin.html` - Administrative controls and metrics.

## Setup Instructions

### Pre-requisites
- A local server environment like XAMPP, WAMP, or standalone PHP & MySQL server.
- Visual Studio Code or any code editor.
- Node.js (Optional, if implementing advanced bundling in the future).

### Steps
1. **Clone the repository:**
   ```bash
   git clone <your-repository-url>
   ```

2. **Database Setup:**
   - Create a MySQL database (e.g., `spacex_academy`).
   - Import the required SQL schemas located in `backend/sql/`.

3. **Configure Environment:**
   - Duplicate `backend/config/.env.example` and rename it to `backend/config/.env`.
   - Open `backend/config/.env` and insert your database credentials and Razorpay API keys:
     ```env
     # Database config
     DB_HOST=localhost
     DB_USER=root
     DB_PASS=
     DB_NAME=spacex_academy

     # Razorpay config
     RAZORPAY_KEY_ID=your_key_id
     RAZORPAY_KEY_SECRET=your_key_secret
     ```

4. **Running the Application:**
   - Host the directory within the root of your local server (e.g., `htdocs` for XAMPP).
   - Alternatively, start a development server from the directory using PHP:
     ```bash
     php -S localhost:8000
     ```
   - Access the site navigating to HTTP server URL (e.g., `http://localhost:8000`).

## Security Considerations
Ensure that environment variables mapped in `.config/.env` are not exposed in production. Use the provided validate-php script for configuration checks.
