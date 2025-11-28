# AI_Powered_Resume (Scaffold)

This repository contains a scaffold for a resume-tailer app:

- PHP (OOP) web app scaffold (controllers, models, services)
- MySQL schema in `sql/schema.sql`
- A minimal Flask parsing microservice in `python_parser/`
- Minimal frontend in `public/` for upload + analyze

Quick start (development on Windows with XAMPP):

1. Import the database (use phpMyAdmin or MySQL client):

```powershell
mysql -u root -p < sql/schema.sql
```

2. PHP app: drop this folder into your XAMPP `htdocs` (already placed here). Ensure `public/` is served by Apache.

3. Start the Python parsing microservice (recommended) in a separate terminal:

```powershell
cd python_parser
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
python app.py
```

The parser runs on `http://127.0.0.1:5000` and exposes `/parse`.

4. Upload a resume via `public/upload_resume.php` and use `public/analyze.php` to call analysis endpoints.

Notes:

- This is a scaffold with stubs. Parser and ATS logic are minimal and should be replaced with production-grade implementations (spaCy, OpenAI, etc.).
- Configure DB credentials in `src/bootstrap.php`.

Next steps you can ask me to do:

- Implement full parsing using spaCy and file upload handling
- Implement ATS scoring logic in `src/Services/ATSService.php`
- Add OpenAI-based tailoring in `TailorService.php`
