# GUCAIR

**Global University Consortium of AI Readiness**

## Introduction

GUCAIR is a beginner-friendly Web Engineering Lab application for recording and comparing university AI readiness. Universities can register, maintain a profile, submit a five-category assessment, and review their history. Visitors can browse the public university directory and assessment results.

The project runs locally in XAMPP at:

```text
http://localhost/gucair/
```

All university and assessment records shown in the pages are read from MongoDB (`gucair_database`). The pages themselves contain no fake records; empty collections show honest empty states.

---

## Objectives

1. Demonstrate a complete HTML5, CSS3, vanilla JavaScript, Core PHP, and MongoDB application.
2. Store real university and assessment records in MongoDB.
3. Validate every form in both the browser and PHP.
4. Protect private pages with PHP sessions and CSRF tokens.
5. Keep the code simple, readable, and suitable for a university lab submission.
6. Provide a clean, blue/white, responsive interface for desktop, tablet, and phone.

---

## Technologies

| Area | Technology |
|---|---|
| Markup | HTML5 |
| Styling | CSS3 with CSS variables, Flexbox, Grid, and media queries |
| Browser behavior | Vanilla JavaScript (`defer`, no framework) |
| Server logic | Core PHP 8 |
| Database | MongoDB Community Server |
| PHP MongoDB library | `mongodb/mongodb` installed with Composer |
| Web server | Apache from XAMPP |
| Local platform | Windows with XAMPP at `C:\xampp\htdocs\gucair\` |

No React, Vue, Laravel, Bootstrap, jQuery, CDN libraries, or CSS frameworks are used.

---

## Features

- Public home page with conditional Register/Login or Dashboard/Logout actions
- Registration with duplicate-email blocking and `password_hash()`
- Login with `password_verify()` and `session_regenerate_id(true)`
- CSRF-protected state-changing forms, including logout
- Private dashboard with live university count and latest assessment
- Public university directory with:
  - Name search using `preg_quote()` before the MongoDB regex
  - Country dropdown built from `distinct('country')`
  - Latest score and level per university
- Public university detail page showing registered information only
- Public assessment results table
- Private editable university profile
- Private assessment form with five ratings from 1 to 5
- Automatic average score and Beginner/Developing/Intermediate/Advanced level
- Private assessment history table, newest first
- Inline JavaScript and PHP validation with accessible error messages
- One-time flash messages after redirects
- Responsive navigation, forms, cards, and scrollable tables

---

## Folder structure

```text
gucair/
├── AGENTS.md
├── README.md
├── DEPLOYMENT.md                # MongoDB Atlas and Render deployment guide
├── START.bat                    # Starts Apache and MongoDB, then opens the site
├── Dockerfile                   # PHP 8.3 Apache image with MongoDB extension
├── docker-start.sh              # Makes Apache listen on Render's PORT
├── .gitignore
├── .dockerignore
├── composer.json
├── composer.lock
├── index.php                    # Home page
├── directory.php                # Public searchable university directory
├── university_view.php          # Public university detail page
├── assessments.php              # Public assessment results
├── register.php                 # Public registration
├── login.php                    # Public login
├── logout.php                   # Private POST-only logout action
├── dashboard.php                # Private dashboard
├── profile.php                  # Private editable profile
├── history.php                  # Private assessment history
├── assessment.php               # Private AI readiness assessment form
├── config/
│   └── database.php             # MongoDB connection and collection helpers
├── includes/
│   ├── auth.php                 # Sessions, require_login(), CSRF, logout form
│   ├── validation.php           # PHP validation and escaping helpers
│   ├── functions.php            # Level calculation and shared display helpers
│   ├── header.php
│   ├── navbar.php
│   └── footer.php
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       ├── main.js
│       └── validation.js
├── database/
│   ├── sample_data.json         # Optional development-only sample records
│   ├── import_sample_data.php   # Optional CLI importer
│   └── README.md
└── vendor/                      # Created by composer install
```

---

## Database structure

Database:

```text
gucair_database
```

Collections:

```text
universities
assessments
```

### `universities`

| Field | Type | Purpose |
|---|---|---|
| `_id` | ObjectId | Primary key; stored in the session as `user_id` |
| `name` | string | University name |
| `country` | string | Country used by the directory filter |
| `email` | string | Lowercase login email; duplicate check uses this field |
| `website` | string | Valid `http`/`https` URL |
| `contact_person` | string | Contact name shown on the detail page |
| `password_hash` | string | Hash from `password_hash()`; never displayed |
| `created_at` | UTCDateTime | Registration date |
| `updated_at` | UTCDateTime | Optional; set when the profile is updated |

### `assessments`

| Field | Type | Purpose |
|---|---|---|
| `_id` | ObjectId | Primary key |
| `university_id` | ObjectId | Matches `universities._id` |
| `assessment_year` | integer | Year the assessment was submitted |
| `score` | float | Average of five ratings, from `1.00` to `5.00` |
| `level` | string | Beginner, Developing, Intermediate, or Advanced |
| `categories` | object | Five integer ratings from `1` to `5` |
| `created_at` | UTCDateTime | Used for newest-first sorting |

Category keys:

```text
ai_education
ai_research
ai_infrastructure
faculty_knowledge
ai_policy
```

Level ranges:

| Average | Level |
|---|---|
| `1.00` – `2.00` | Beginner |
| `2.01` – `3.00` | Developing |
| `3.01` – `4.00` | Intermediate |
| `4.01` – `5.00` | Advanced |

---

## Installation

### 1. Install XAMPP

1. Download XAMPP for Windows from `https://www.apachefriends.org/`.
2. Install it in the default folder:

   ```text
   C:\xampp\
   ```

3. Make sure this project is at:

   ```text
   C:\xampp\htdocs\gucair\
   ```

### 2. Install MongoDB Community Server

1. Download MongoDB Community Server for Windows from `https://www.mongodb.com/`.
2. During setup, choose **Install MongoDB as a Windows Service**.
3. Keep the default service name `MongoDB` and port `27017`.
4. Confirm the service is running:

   ```cmd
   sc query MongoDB
   ```

### 3. Enable the MongoDB PHP extension

1. Open:

   ```text
   C:\xampp\php\php.ini
   ```

2. Find the MongoDB extension line and remove the leading `;` if present:

   ```ini
   extension=mongodb
   ```

   On some XAMPP versions the line may be:

   ```ini
   extension=php_mongodb.dll
   ```

3. Save `php.ini`.
4. Restart Apache after changing `php.ini`.
5. Check that the extension loaded:

   ```cmd
   C:\xampp\php\php.exe -m | findstr /I mongodb
   ```

   You should see `mongodb` in the output.

### 4. Install Composer dependencies

Install Composer for Windows if it is not already installed, then run:

```cmd
cd C:\xampp\htdocs\gucair
composer install
```

This creates `vendor/autoload.php`, which `config/database.php` requires.

---

## MongoDB configuration

The connection is centralized in `config/database.php`.

Default local connection:

```text
mongodb://127.0.0.1:27017
```

Database:

```text
gucair_database
```

To use a different MongoDB URI, set the environment variable `MONGODB_URI` (for example, a MongoDB Atlas connection string). If it is not set, `config/database.php` falls back to `mongodb://127.0.0.1:27017`.

The application uses these helper functions:

```php
get_mongo_client()
get_gucair_database()
get_gucair_collections()
get_universities_collection()
get_assessments_collection()
```

Connection errors are logged with `error_log()` and shown to users as friendly messages. Raw database errors are never printed in the page.

---

## Run instructions

For Docker, MongoDB Atlas, and Render deployment, see `DEPLOYMENT.md`.

### Option A — use `START.bat` (recommended)

1. Double-click `START.bat`.
2. Approve the Windows UAC prompt so the script can start services.
   You can also right-click it and choose **Run as administrator**.
3. The script:
   - Starts the XAMPP Apache service (`Apache2.4`)
   - Starts the MongoDB service (`MongoDB`)
   - Waits briefly for the site to respond
   - Opens the browser at `http://localhost/gucair/`

If your Apache or MongoDB Windows service has a different name, edit the service name variables near the top of `START.bat`, or pass replacements:

```cmd
START.bat Apache2.4 MongoDB
```

### Option B — start services manually

1. Open XAMPP Control Panel and start **Apache**.
2. Start MongoDB from Services or run:

   ```cmd
   net start MongoDB
   ```

3. Open your browser and visit:

   ```text
   http://localhost/gucair/
   ```

---

## Feature descriptions

| Page / file | Description |
|---|---|
| `index.php` | Introduces GUCAIR and shows the correct login/register or dashboard/logout actions. |
| `register.php` | Creates a university account. Validates six fields in JS and PHP, blocks duplicate emails, and stores a password hash. |
| `login.php` | Finds the university by email, verifies the hash, regenerates the session ID, and redirects safely. |
| `logout.php` | POST-only action that verifies CSRF, clears the session, and shows a one-time success message. |
| `dashboard.php` | Shows the logged-in university, country, live university count, latest assessment, and quick-link cards. |
| `directory.php` | Public searchable table of universities. Country options come from MongoDB. Shows latest score and level. |
| `university_view.php` | Public detail page. Never queries or displays the password hash. |
| `assessments.php` | Public table of saved assessments, newest first, with university names joined from MongoDB. |
| `assessment.php` | Private five-category rating form. PHP computes the average and level, then saves the record. |
| `profile.php` | Private view/update form for country, website, and contact person. |
| `history.php` | Private table of the current university’s assessments, newest first. |
| `assets/js/validation.js` | Form-specific browser validation with inline errors, `aria-invalid`, and first-field focus. |
| `includes/validation.php` | Authoritative PHP validation and the `e()` escaping helper. |
| `includes/functions.php` | `get_assessment_level()`, date formatting, shared category list, and safe URL check. |

---

## Sample outputs

The text below is illustrative. It is not hardcoded in application pages. Records appear only after registration or after importing the optional `database/sample_data.json`.

### Empty database state

```text
University directory
No universities found yet.
```

```text
Assessment history
No assessments yet.
Submit your first assessment
```

### Assessment result after saving

```text
Assessment result
Average score:      3.40 / 5.00
Readiness level:    Intermediate
```

### Dashboard after importing optional sample data

```text
Welcome back, Northbridge University!

University:              Northbridge University
Country:                 Canada
Registered universities: 3
Latest assessment:       3.60 / 5.00
                         Intermediate
```

### Directory row after importing optional sample data

```text
University                  Country   Latest score   Level
Northbridge University      Canada    3.60 / 5.00    Intermediate
```

### Optional sample data and logins

`database/sample_data.json` contains three sample universities and five assessments for local testing. It is not loaded automatically. Import it with:

```cmd
cd C:\xampp\htdocs\gucair
php database\import_sample_data.php
```

Every sample account uses this password:

```text
SamplePass123!
```

Sample logins:

```text
registrar@northbridge.edu
admissions@riverton.edu.au
registry@solaris.ac.ke
```

The sample file stores only bcrypt hashes generated with `password_hash()`. No plaintext password is stored in MongoDB, and the application never displays password hashes. Remove the sample records before final submission if your assignment requires an empty database.

---

## Testing results table

| Test | Method | Result |
|---|---|---|
| PHP syntax | `php -l` on every project PHP file | Passed |
| Composer autoload | `vendor/autoload.php` present after install | Passed |
| MongoDB collection helpers | CLI smoke test of `get_gucair_collections()` | Passed |
| Sample JSON | `json_decode(..., JSON_THROW_ON_ERROR)` | Passed |
| Sample password hashes | `password_verify('SamplePass123!', hash)` | Passed |
| Level boundaries | `get_assessment_level()` tested at 1.00, 2.00, 2.01, 3.00, 3.01, 4.00, 4.01, 5.00 | Passed |
| Sample assessment averages | Recomputed from category ratings and compared with stored level | Passed |
| Static link targets | All `/gucair/...` page, CSS, and JS targets checked on disk | Passed |
| Missing assessments page | Created `assessments.php` and rechecked links | Fixed |
| Private page guards | Guests requesting dashboard/profile/history/assessment/logout get no page output | Passed |
| Logout CSRF | GET or invalid token rejected; valid POST clears session | Passed |
| PHP registration validation | Empty/invalid fields return field-specific errors | Passed |
| JavaScript validation | Empty register form shows six inline errors and focuses the first field | Passed |
| Browser console | Home, directory, assessments, register, login, detail, and protected redirect checked | No errors |
| Desktop overflow check | Browser check at 1000px showed no document overflow | Passed |
| Mobile CSS review | Single-column forms/filters, full-width buttons, wrapping rating controls, scrollable tables | Passed |
| Escaping review | MongoDB, GET, POST, and session values are passed through `e()` or `htmlspecialchars()` | Passed |
| Sample import side effects | Importer syntax checked; not run during verification | No records inserted |

---

## Limitations

- Designed for local XAMPP use, not production deployment.
- No password reset or email verification.
- No role-based access control; users can only manage their own university record.
- Duplicate-email protection is application-level; there is no MongoDB unique index by default.
- Assessment year is set from the current server date; users cannot backdate records.
- Assessments can be created and viewed, but not edited or deleted from the UI.
- Directory and history tables do not use pagination yet.
- Country filter uses exact stored values from `distinct('country')`.
- `START.bat` assumes the common service names `Apache2.4` and `MongoDB`; these may need editing on some installations.
- Optional sample data must be removed before final submission if the assignment requires an empty database.

---

## Future improvements

1. Add a unique MongoDB index on `universities.email`.
2. Add pagination and sortable columns to directory, assessments, and history tables.
3. Add password reset and email verification flow.
4. Add admin roles for reviewing or removing records.
5. Allow editing and deleting assessments.
6. Add charts for category comparison and year-over-year progress.
7. Add CSV export for lab demonstration.
8. Add a small automated PHP test script for validation and level calculation.
9. Improve accessibility with a formal WCAG review.
10. Add Docker-based setup as an alternative to XAMPP.
