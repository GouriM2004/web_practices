# Poll App (OOP PHP + MySQL + Bootstrap)

## Setup

1. Import the SQL file:
   - Open phpMyAdmin
   - Create a database named `poll_app`
   - Import `sql/poll_app.sql`

2. Configure database credentials:
   - Edit `includes/Config.php` and set DB_HOST, DB_USER, DB_PASS, DB_NAME as needed.

3. Place the `poll_app_oop` folder in your web root (e.g. `htdocs/poll_app_oop`).

4. Access the app:
   - Public poll: `http://localhost/poll_app_oop/index.php`
   - Admin panel: `http://localhost/poll_app_oop/admin/login.php`

## Default Admin Login

- Username: `admin`
- Password: `admin123`

Password is hashed in the SQL seed using `password_hash('admin123', PASSWORD_DEFAULT)`.
