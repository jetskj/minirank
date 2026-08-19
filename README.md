# MiniRank

MiniRank is a miniature full-stack PHP keyword position tracker built with AI assistance as part of the rankingCoach AI Coding Assessment. It tracks search phrase ranking positions over time for configured websites.

## Tech Stack
- **Backend:** Plain PHP 8.x (no frameworks), SQLite via PDO with prepared statements.
- **Frontend:** Vanilla JavaScript (`fetch()`), basic HTML templates, plain CSS, Chart.js for visualization.
- **Architecture:** Single front controller in `public/index.php`.

---

## Prerequisites
- PHP 8.0 or higher with PDO and SQLite3 extensions enabled.
- CLI access (terminal).

---

## Setup & Quick Start (Foolproof Documentation)

### Step 1: Clone or Navigate to the Repository
```bash
cd minirank
```
*(Note: The repository includes `data/.gitkeep` to ensure the `data/` directory is created on clone, preventing SQLite "unable to open database file" errors).*

### Step 2: Initialize the Database Schema (Optional / Explicit)
You can create the SQLite database and initialize the tables using the schema file:
```bash
sqlite3 data/minirank.sqlite < db/schema.sql
```
*(Alternatively, connecting via PDO or running the seed script will automatically initialize missing tables and defaults).*

### Step 3: Seed the Database
Run the seed script to populate demo projects, keywords, and 30 days of historical position data:
```bash
php seeds/seed.php
```
**Expected Output:**
```
Seed completed: 10 keywords, 30 days positions each.
```

### Step 4: Start the Development Server
```bash
php -S localhost:8000 -t public
```
**Expected Output:**
```
[Wed Aug 19 12:00:00 2026] PHP 8.x.x Development Server (http://localhost:8000) started
```

### Step 5: Access the Application
Open your browser at `http://localhost:8000`.
- **Login / Registration:** You can register a new account or log in with the default seeded account (`admin` / `admin123`).

---

## Verification & Testing Commands

### 1. Inspect Database Tables via CLI
```bash
sqlite3 data/minirank.sqlite ".tables"
```
**Expected Output:**
```
keywords   positions  projects   users
```

### 2. Verify Seeded Row Counts
```bash
sqlite3 data/minirank.sqlite "SELECT COUNT(*) FROM keywords;"
sqlite3 data/minirank.sqlite "SELECT COUNT(*) FROM positions;"
```
**Expected Output:**
```
10
300
```

### 3. Test API Endpoint (Position Refresh)
```bash
curl -X POST http://localhost:8000/api/positions
```
**Expected Output:**
```json
[{"keyword_id":1,"date":"2026-08-19","position":42,"success":true},...]
```

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
  - Polished, modern SaaS dashboard aesthetic with card containers, soft shadows, clean typography, color-coded trend badges, and full responsiveness for mobile phone aspect ratios.
