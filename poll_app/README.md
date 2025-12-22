# Poll App (OOP PHP + MySQL + Bootstrap)

A feature-rich polling application with single and multiple choice support.

## Features

- **Single Choice & Multiple Choice Polls**: Radio or checkbox style voting
- **Voter Authentication**: Login required before voting (creates account on first login)
- **IP/Device Locking + Account Locking**: Duplicate votes blocked by voter account and IP per poll
- **Anonymous vs Public Voting**: Voters choose to display their name in results or stay anonymous
- **Admin Dashboard**: Manage polls, view results, toggle active status
- **Real-time Results**: Live vote counts with percentage bars
- **Vote Change Window**: Voters can change their vote within a configurable time window

## Setup

1. Import the SQL file:

   - Open phpMyAdmin
   - Create a database named `poll_app`
     - Import `sql/poll_app.sql`
     - If upgrading from an older version, also run `sql/add_multiple_choice.sql` and `sql/add_voter_auth.sql`

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

## Vote Change Window

You can allow voters to change their vote within a short time window after submitting.

- Configure the window in `includes/Config.php` using `VOTE_CHANGE_WINDOW_MINUTES` (default: 5).
- If a voter resubmits within this window, their previous choice(s) will be replaced and vote counts updated.
- After the window expires, votes are locked and cannot be changed.
