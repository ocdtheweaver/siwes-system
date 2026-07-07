# SIWES Placement, Tracking & Reporting System

A web-based system for managing Student Industrial Work Experience Scheme (SIWES)
placements: students register and get matched to companies by a rule-based
recommendation engine, apply for placement, keep a digital logbook, and submit
reports; supervisors review applicants and monitor assigned students; administrators
oversee the whole programme.

**Stack:** PHP (8.0+) with PDO/MySQLi, MySQL/MariaDB, plain HTML/CSS/vanilla JS
(no framework, no build step - this matters for the "Tools and Technologies Used"
and "Development Environment" sections of Chapter Four).

---

## 1. Requirements

- A local server stack with PHP 8.0+ and MySQL/MariaDB - the easiest option is
  **XAMPP** (Windows/macOS/Linux) or **WAMP** (Windows).
- A web browser.

## 2. Installation

**Don't want to use XAMPP?** See `RUN_WITHOUT_XAMPP.txt` for a Docker-based
setup (`docker compose up -d` and you're running) or a native PHP+MySQL
option. The steps below assume XAMPP/WAMP.

1. **Install XAMPP** (or WAMP) and start the **Apache** and **MySQL** services from
   its control panel.

2. **Copy this folder** (`siwes-system/`) into your server's web root:
   - XAMPP on Windows: `C:\xampp\htdocs\siwes-system`
   - XAMPP on macOS/Linux: `/Applications/XAMPP/htdocs/siwes-system` or `/opt/lampp/htdocs/siwes-system`
   - WAMP: `C:\wamp64\www\siwes-system`

3. **Create the database.**
   - Open **phpMyAdmin** (usually `http://localhost/phpmyadmin`).
   - Click **Import**, choose `database/schema.sql`, and run it.
   - This creates a database called `siwes_system` with all required tables.

4. **Check your database credentials.**
   - Open `config/db.php`. The defaults (`root` user, no password, host
     `localhost`) match a typical XAMPP install. If your MySQL root account has a
     password, update `DB_PASS` accordingly.

5. **Check your base URL.**
   - Open `config/config.php`. If you copied the folder in as `siwes-system`
     (so you visit `http://localhost/siwes-system/`), leave `BASE_URL` as
     `/siwes-system`. If this project folder IS your web root, set it to an
     empty string `''`.

6. **Create demo accounts (recommended for first run).**
   - In your browser, visit: `http://localhost/siwes-system/database/seed.php`
   - This creates a working admin account, two demo companies, and a demo
     student, and prints their login details on screen.
   - **Delete or rename `database/seed.php` afterwards** - leaving it
     reachable on a real server would let anyone re-run it.

7. **Open the system.**
   - Visit `http://localhost/siwes-system/` and log in, or register a new
     student/supervisor account.

## 3. Default Demo Logins (created by seed.php)

| Role       | Email                  | Password     |
|------------|-------------------------|--------------|
| Admin      | admin@aust.edu.ng       | Admin@123    |
| Supervisor | hr@brighttech.test      | Company@123  |
| Supervisor | hr@datawave.test        | Company@123  |
| Student    | student@aust.edu.ng     | Student@123  |

**Change these passwords (or delete the accounts) before any real/public use.**

## 4. Project Structure

```
siwes-system/
├── config/             Database + base URL configuration
├── database/           SQL schema and one-time seed script
├── includes/            Shared bootstrap, auth, helper functions, header/footer
├── assets/css/          Stylesheet
├── auth/                 Register, login, logout
├── student/              Dashboard, profile, browse/apply to companies, logbook, reports
├── supervisor/           Dashboard, company profile, applicants, view a student
└── admin/                Dashboard, manage students/companies/placements/reports
```

## 5. How the Recommendation Engine Works

`includes/functions.php` -> `compute_match_score()`:

- Splits both the student's `skills` field and the company's `skills_required`
  field into lower-cased keyword arrays (comma-separated).
- **80% of the score** = the proportion of the company's required skills that
  the student possesses.
- **20% of the score** = a bonus if the student's department text relates to
  the company's industry text (simple substring match).
- Returns a score from 0-100, shown to students when browsing companies and to
  supervisors as "recommended students" on their dashboard.

This is intentionally a transparent, rule-based score (not a machine-learning
black box) so that the matching logic is explainable in the project write-up
and to any user of the system.

## 6. Known Limitations (by design - see Chapter 1, Scope of the Project)

- The system does **not** automatically assign students to companies - a
  student applies, and a supervisor decides.
- It does **not** integrate with any external government or NYSC/ITF database.
- It does **not** replace an institution's official SIWES policies; it is a
  management tool that sits alongside them.
