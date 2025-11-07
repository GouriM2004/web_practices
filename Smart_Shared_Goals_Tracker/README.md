# Smart Shared Goals Tracker

Minimal starter scaffold for Smart Shared Goals Tracker (PHP + MySQL + PWA).

Quick start (XAMPP on Windows):

1. Copy this folder to `d:/xampp_new/htdocs/Smart_Shared_Goals_Tracker` (already done).
2. Start Apache and MySQL via XAMPP Control Panel.
3. Create database and import schema:

   - Open phpMyAdmin (http://localhost/phpmyadmin) and create a database named `smart_goals`.
   - Import `sql/smart_goals.sql`.

   Or from PowerShell (adjust if using different credentials):

```powershell
mysql -u root -p < .\sql\smart_goals.sql
```

4. Configure DB credentials in `src/bootstrap.php`.
5. Open the app at: http://localhost/Smart_Shared_Goals_Tracker/public/

Browser pages (for quick manual testing):

- `public/register.php` — register a new user (posts to `api.php/register`)
- `public/login.php` — login (posts to `api.php/login`)
- `public/dashboard.php` — simple protected page that calls `api.php/me`

What is included:

- `public/` — public web root (index, api, manifest, service worker, assets)
- `src/` — PHP OOP skeleton (Controllers, Models, Services)
- `sql/` — database schema

Next steps:

- Implement controllers and full API endpoints.
- Implement IndexedDB outbox and sync logic in the PWA (client).
- Add authentication sessions or JWT handling.

Notes:

- This is a starting scaffold. Do not run in production without securing configuration and using HTTPS.
