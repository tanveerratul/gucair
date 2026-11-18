# AGENTS.md — GUCAIR

## Project identity

**GUCAIR** stands for **Global University Consortium of AI Readiness**.

This is a university **Web Engineering Lab** assignment. The goal is a simple, working, beginner-friendly web application that shows universities and their AI readiness assessments using real data from MongoDB.

Base URL in XAMPP:

```text
http://localhost/gucair/
```

The project folder is:

```text
C:\xampp\htdocs\gucair\
```

---

## Required stack

Use only these technologies:

- **HTML5** — page structure
- **CSS3** — styling and responsive layout
- **Vanilla JavaScript** — browser-side behavior and validation
- **Core PHP** — server-side logic, sessions, validation, rendering
- **MongoDB** — database storage

Database details:

```text
Database:    gucair_database
Collections: universities
             assessments
```

Do **not** use:

- React, Vue, Angular
- Laravel, Symfony, CodeIgniter
- Node.js, npm, Vite, Webpack
- Bootstrap, Tailwind, jQuery
- Any UI kit or CSS framework
- CDN-hosted libraries unless specifically approved

The official PHP MongoDB driver/library is allowed because it is the database layer, not an application framework.

---

## Core rules

1. Keep the code simple, readable, and suitable for beginners.
2. Add short, useful comments where the reasoning is not obvious.
3. Every visible university or assessment record must come from MongoDB.
4. Do not hardcode fake universities, scores, countries, users, or statistics.
5. Validate every form in **both** JavaScript and PHP.
6. Protect private pages with PHP sessions.
7. Keep the design blue, white, clean, responsive, and lightly animated.
8. Make sure the site works from `http://localhost/gucair/` in XAMPP.

Static interface text such as headings, labels, buttons, and instructions is fine. “No static data” means no fake or hardcoded **records** pretending to be database content.

---

## Suggested file structure

Keep pages at the project root so the URLs match `http://localhost/gucair/`.

```text
gucair/
├── AGENTS.md
├── README.md
├── composer.json             # Requires mongodb/mongodb
├── index.php                 # Home page
├── directory.php             # Public searchable university directory
├── university_view.php       # Public university detail page
├── assessments.php           # Public list of assessments
├── login.php
├── register.php
├── logout.php
├── dashboard.php             # Private page
├── profile.php               # Private editable university profile
├── history.php               # Private assessment history
├── assessment.php            # Private AI readiness assessment form
├── config/
│   └── database.php          # MongoDB connection and collection helpers
├── includes/
│   ├── auth.php              # Sessions, require_login(), CSRF helpers
│   ├── validation.php        # Shared PHP validation helpers
│   ├── functions.php         # General helpers, including get_assessment_level()
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
│   ├── sample_data.json      # Optional development-only sample records
│   ├── import_sample_data.php # Optional CLI importer for the sample file
│   └── README.md
└── vendor/                   # Created by `composer install`
```

Only put files in `includes/` that should not be requested directly by users.

---

## PHP conventions

- Use `<?php` for PHP files. Do not close `?>` at the end of pure PHP files.
- Call `session_start()` before any HTML output when sessions are needed.
- Use `require_once` to load shared files.
- Keep database connection code in `config/database.php`.
- Keep validation helpers in `includes/validation.php`.
- Keep login/session helpers in `includes/auth.php`.
- Use `header('Location: ...'); exit;` after redirects.
- Use `htmlspecialchars()` when printing any data from MongoDB, `$_POST`, `$_GET`, or `$_SESSION`.
- Do not show raw database errors to users. Log them and show a friendly message.
- Use clear variable names such as `$universities`, `$assessment`, `$errors`.
- Prefer simple `if` statements and small functions over advanced patterns.
- Use `password_hash()` when storing passwords and `password_verify()` when checking them.
- Never store plaintext passwords in MongoDB or sessions.

Example connection pattern:

```php
require_once __DIR__ . '/config/database.php';

$collections = get_gucair_collections();
$universities = $collections['universities']->find();
```

---

## MongoDB conventions

Connect to local MongoDB from PHP and select:

```text
gucair_database
```

Use these collections only unless the assignment requires more:

### `universities`

Store university profile information.

Suggested fields:

```text
name          string
country       string
website       string (valid http/https URL)
created_at    BSON UTCDateTime
```

### `assessments`

Store AI readiness assessment records.

Suggested fields:

```text
university_id   MongoDB ObjectId that matches a university
assessment_year integer
score           float average from 1.00 to 5.00
level           Beginner, Developing, Intermediate, or Advanced
categories      object with five integer ratings from 1 to 5
created_at      BSON UTCDateTime
```

The five category keys are `ai_education`, `ai_research`, `ai_infrastructure`,
`faculty_knowledge`, and `ai_policy`.

Rules:

- Query MongoDB for all displayed records.
- Do not use JavaScript arrays or PHP arrays as fake database results.
- If a collection is empty, show a friendly empty state such as “No universities found yet.”
- Keep field names consistent across all pages.
- Convert BSON dates/ObjectIds to display strings carefully.
- Use MongoDB filters built from validated values; never concatenate raw user input into queries.
- Escape user text used in MongoDB regular expressions with `preg_quote()`.
- Only query the fields a page needs when practical.
- Handle connection failures gracefully.

---

## Form validation

Validate forms in **two layers**.

### 1. JavaScript validation

Use vanilla JavaScript in `assets/js/validation.js`.

Requirements:

- Validate on submit.
- Show inline error messages beside each field.
- Use `aria-invalid` and `aria-describedby` where appropriate.
- Focus the first invalid field.
- Do not clear the form when validation fails.
- Do not rely on JavaScript alone.

### 2. PHP validation

Repeat all validation on the server in `includes/validation.php` or the receiving page.

Requirements:

- Reject empty required fields.
- Use `trim()` on text input.
- Validate email with `filter_var($email, FILTER_VALIDATE_EMAIL)`.
- Validate URLs with `filter_var($url, FILTER_VALIDATE_URL)`.
- Check each assessment rating is an integer from `1` to `5`.
- Check the computed average is between `1.00` and `5.00`.
- Check assessment year is a reasonable integer.
- Check passwords are at least 8 characters.
- Return field-specific errors and keep valid submitted values in the form.
- Escape all output with `htmlspecialchars()`.

Important: A user can disable JavaScript or send requests directly. PHP validation is the authoritative layer.

---

## Authentication and private pages

Use PHP sessions for login state.

Rules:

- Store only safe data in `$_SESSION`, for example `user_id`, `name`, and `email`.
- Never store passwords in `$_SESSION`.
- Call `session_start()` before output on pages that use sessions.
- Put a `require_login()` helper in `includes/auth.php`.
- Call `require_login()` at the top of every private page before rendering HTML.
- Redirect unauthenticated users to `login.php`.
- Use `session_regenerate_id(true)` after successful login.
- Clear and destroy the session on logout.
- Add CSRF tokens to state-changing forms and verify them in PHP.

Private pages include:

- `dashboard.php`
- `profile.php`
- `history.php`
- `assessment.php`
- any page that creates, edits, or deletes records

Public pages may list universities and assessments, but must not expose private session-only information.

---

## HTML conventions

- Start every page with `<!DOCTYPE html>`.
- Use `<html lang="en">`.
- Include `<meta charset="UTF-8">`.
- Include `<meta name="viewport" content="width=device-width, initial-scale=1.0">`.
- Use semantic elements: `<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<footer>`.
- Give every form field a real `<label>`.
- Use buttons with `type="submit"` or `type="button"` explicitly.
- Use tables for comparison-style assessment lists, or cards for university lists.
- Avoid inline `style=""` attributes.
- Avoid inline `onclick=""`, `onchange=""`, and similar handlers.

---

## CSS conventions

Use one main stylesheet:

```text
assets/css/style.css
```

Design direction:

- Blue and white color palette.
- White or very light background.
- Dark blue headings.
- Medium blue buttons and links.
- Clear spacing and readable font sizes.
- Cards/tables with soft borders or subtle shadows.
- Centered container with reasonable max width.
- Responsive layout with media queries.
- Single-column layout on phones.
- Multi-column layout on larger screens where appropriate.
- Minimal animations: short hover/focus transitions only.
- Respect `prefers-reduced-motion: reduce`.

Good CSS practices:

- Use CSS variables for colors and spacing.
- Use Flexbox or CSS Grid for layout.
- Use relative units such as `rem`, `%`, `em`, and `vw/vh` where suitable.
- Keep selectors simple and readable.
- Do not use `!important` unless absolutely necessary.

---

## JavaScript conventions

Use plain browser JavaScript only.

Rules:

- Load scripts with `defer`.
- Keep DOM code inside `DOMContentLoaded` when not using `defer`.
- Use `const` and `let`; avoid `var`.
- Use standard DOM APIs such as `querySelector`, `addEventListener`, and `classList`.
- Keep validation logic near the forms it supports.
- Do not use `alert()` for form errors; show inline messages.
- Do not put database details or secrets in JavaScript.
- Do not use `document.write()`.
- Handle missing elements safely if a script is loaded on multiple pages.

---

## No fake or static data

Do not add:

- Hardcoded university names
- Hardcoded assessment scores
- Hardcoded country lists presented as database content
- Fake dashboard statistics
- Placeholder rows such as “Test University 1”
- Client-side arrays pretending to be query results
- Lorem ipsum text in final pages
- Demo user accounts in application code

Instead:

- Populate data through the application forms.
- Read all records from MongoDB.
- Show honest empty states when collections are empty.
- If temporary test records are needed during development, keep them out of final page code and remove them before submission.

---

## XAMPP and local setup notes

- Place the project in `C:\xampp\htdocs\gucair\`.
- Access it at `http://localhost/gucair/`.
- Start Apache and MongoDB in XAMPP/services.
- Enable the PHP MongoDB extension in `php.ini` if needed.
- Run `composer install` in the project root so `vendor/autoload.php` exists.
- Restart Apache after changing `php.ini`.
- Keep paths relative to the project root.
- Do not assume a production web server configuration.

---

## Definition of done

Before finishing a task, check that:

- [ ] The page works at `http://localhost/gucair/`.
- [ ] Only the approved stack is used.
- [ ] Data comes from `gucair_database` in MongoDB.
- [ ] No fake/static records appear in the UI.
- [ ] Forms validate in both JavaScript and PHP.
- [ ] Output is escaped with `htmlspecialchars()`.
- [ ] Private pages call `require_login()`.
- [ ] Passwords are hashed and never displayed.
- [ ] CSRF tokens are present on state-changing forms.
- [ ] The layout works on desktop, tablet, and phone widths.
- [ ] The design is blue, white, clean, and responsive.
- [ ] Animations are minimal.
- [ ] Code is commented enough for a beginner to follow.
- [ ] The browser console has no unnecessary JavaScript errors.
