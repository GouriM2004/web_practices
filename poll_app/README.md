# Poll App (OOP PHP + MySQL + Bootstrap)

A feature-rich polling application with single and multiple choice support.

## Features

- **Single Choice Polls**: Traditional polls where users select one option
- **Multiple Choice Polls**: Users can select multiple options in a single poll
- **IP-based Vote Tracking**: Prevents duplicate voting
- **Admin Dashboard**: Manage polls, view results, toggle active status
- **Real-time Results**: Live vote counts with percentage bars

## Setup

1. Import the SQL file:

   - Open phpMyAdmin
   - Create a database named `poll_app`
   - Import `sql/poll_app.sql`
   - If upgrading from an older version, also run `sql/add_multiple_choice.sql`

2. Configure database credentials:

   - Edit `includes/Config.php` and set DB_HOST, DB_USER, DB_PASS, DB_NAME as needed.

3. Place the `poll_app` folder in your web root (e.g. `htdocs/poll_app`).

4. Access the app:
   - Public poll: `http://localhost/poll_app/index.php`
   - Admin panel: `http://localhost/poll_app/admin/login.php`

## Default Admin Login

- Username: `admin`
- Password: `admin123`

Password is hashed in the SQL seed using `password_hash('admin123', PASSWORD_DEFAULT)`.

## Creating Multiple Choice Polls

When creating a new poll in the admin panel:

1. Enter your question
2. Add at least 2 options
3. Check the **"Allow multiple choice"** checkbox to enable multiple selections
4. Users will see checkboxes instead of radio buttons for multiple choice polls
