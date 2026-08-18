# MiniRank

MiniRank is a miniature full-stack PHP keyword position tracker built with AI assistance as part of the rankingCoach AI Coding Assessment. It tracks search phrase ranking positions over time for configured websites.

## Tech Stack
- **Backend:** Plain PHP 8.x (no frameworks), SQLite via PDO with prepared statements.
- **Frontend:** Vanilla JavaScript (`fetch()`), basic HTML templates, plain CSS, Chart.js for visualization.
- **Architecture:** Single front controller in `public/index.php`.

---

## Prerequisites
- PHP 8.0 or higher with PDO and SQLite3 extensions enabled.
- CLI access.

---

## Setup & Quick Start (Runs in 5 minutes)

1. **Clone or navigate to the repository:**
   ```bash
   cd minirank
   ```

2. **Initialize / Seed the Database:**
   Run the seed script to populate demo projects, keywords, and 30 days of historical position data:
   ```bash
   php seeds/seed.php
   ```
   *(This creates `data/minirank.sqlite` and seeds 10 keywords across projects with 30 days of daily positions).*

3. **Start the Development Server:**
   ```bash
   php -S localhost:8000 -t public
   ```

4. **Access the Application:**
   Open your browser at `http://localhost:8000`.
   - **Default login credentials (or register a new account):**
     - You can register a new account or log in. (Seeded demo users/database are shared for testing).

---

## Features
- **Must-Haves:**
  - Keywords CRUD (Add, Edit, Delete) for configured websites.
  - Seeded 30-day position history.
  - AJAX-powered position refresh simulation without page reload.
  - Keyword list with current position, 7-day trend indicator (Improved / Declined / Stable), and instant client-side search/filter.
  - Keyword detail view with position history table.
  - Security best practices (PDO prepared statements, `htmlspecialchars()` output escaping, CSRF protection).
- **Stretch Goals:**
  - Line chart visualization on keyword detail pages using Chart.js.
  - Multiple projects/websites switching.
  - User accounts (register, login, logout, password hashing, sessions) with CSRF protection.
  - Filtering by position range (Top 3, Top 10, Top 50, 51+) and movement.
  - CSV export of keyword position history.
  - Project conventions defined in `AGENTS.md`.
