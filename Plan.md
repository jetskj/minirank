Now I have a clear picture of the existing project. Let me provide the comprehensive technical plan.

## 1. Proposed SQLite Database Schema

Based on the requirements (keywords CRUD, 30-day position history, 7-day trends), here's the exact schema:

**Table: `keywords`**
- `id` INTEGER PRIMARY KEY AUTOINCREMENT
- `phrase` TEXT NOT NULL UNIQUE
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP

**Table: `positions`**
- `id` INTEGER PRIMARY KEY AUTOINCREMENT
- `keyword_id` INTEGER NOT NULL
- `date` DATE NOT NULL
- `position` INTEGER NOT NULL CHECK(position >= 1 AND position <= 100)
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- FOREIGN KEY (`keyword_id`) REFERENCES `keywords`.`id` ON DELETE CASCADE
- UNIQUE(`keyword_id`, `date`)

**Index for performance:**
- `CREATE INDEX idx_positions_keyword_date ON positions(keyword_id, date DESC)`

## 2. File & Directory Structure

```
minirank/
├── data/                   ← (gitignored) SQLite DB stored here
├── public/
│   ├── index.php          ← Single front controller / entry point
│   ├── assets/
│   │   └── style.css
│   └── index.html         ← Basic HTML template (optional, can be inline in index.php)
├── routes.php             ← Route registration (simple array)
├── core/
│   ├── db.php             ← PDO connection + helper functions
│   └── auth.php           ← (empty/no-auth per M1)
├── seeds/
│   └── seed.php           ← CLI script for M2
├── www/                   ← Public-facing scripts (AJAX endpoints)
│   └── api/
│       ├── keywords.php   ← CRUD operations (M1)
│       └── positions.php  ← Refresh endpoint (M3/M5)
├── index.php              ← Handles routing (M1-M6)
└── composer.json          ← (optional, just autoload if needed)
```

*Note: Per AGENTS.md, single front controller in `public/index.php`. I'll keep routes lightweight.*

## 3. 8-Phase Implementation Plan

### Phase 1: Foundation & DB Connection
- **Create:** `public/index.php` (front controller, basic routing skeleton)
- **Create:** `core/db.php` — PDO SQLite connection (`data/minirank.sqlite`), `db_error()` handler, `conn()` function
- **Modify:** `.gitignore` — add `data/*.sqlite`
- **Task:** Set up PHP 8.0+ server route, initialize $router or simple dispatcher

### Phase 2: Database Schema Execution
- **Create:** `db/schema.sql` — the SQL schema from section 1 (keywords + positions tables + indexes)
- **Task:** Run `sqlite3 data/minirank.sqlite < db/schema.sql` or implement an `init_db()` function in `core/db.php` that executes the schema using `conn()->exec()`
- **Verify:** Open DB in CLI, confirm tables exist via `.tables`

### Phase 3: The Seed Script (M2)
- **Create:** `seeds/seed.php` — CLI script invoked via `php seeds/seed.php`
- **Functionality:**
  - Accept optional `--keywords=N` argument (default 10)
  - For each keyword, generate 30 random positions (date = today - 29..today, position = 1–100)
  - Use PDO prepared statements to INSERT INTO `positions` with `ON CONFLICT(date)` upsert logic so re-running doesn't duplicate
  - Insert default keywords if none exist
- **Verify:** `php seeds/seed.php` produces rows; query `SELECT count(*) FROM positions` ≈ 30×N

### Phase 4: Backend - Keyword CRUD (M1)
- **Create:** `www/api/keywords.php` — POST-only endpoint accepting `action` (add|edit|delete) and `phrase`
- **Functions:**
  - `add_phrase($pdo, $phrase)`: INSERT INTO keywords, return JSON {success, id}
  - `edit_phrase($pdo, $old, $new)`: UPDATE keywords WHERE phrase=old, return JSON
  - `delete_phrase($pdo, $id)`: DELETE FROM keywords WHERE id=$id, return JSON
- **All queries MUST use PDO prepared statements** (per AGENTS.md)
- **Create:** Route in `public/index.php` to dispatch POST requests to `keywords.php` with `?action=add`
- **Modify:** `public/index.php` to parse `$_POST['action']` and include `www/api/keywords.php`

### Phase 5: Backend - Position Refresh Logic (M3)
- **Create:** `www/api/positions.php` — POST endpoint
- **Functionality:**
  - Accept `keyword_id` (optional; if omitted, refresh ALL keywords)
  - For each target keyword, generate today's position: random integer 1–100
  - INSERT OR REPLACE INTO `positions` (`keyword_id`, `date=CURRENT_DATE`, `position`)
  - Return JSON: `{keyword_id, date, position, success}`
- **Create:** Route handler in `public/index.php` for `?refresh=1` or AJAX-aware dispatcher
- **Security:** Validate `keyword_id` is integer; use prepared statements

### Phase 6: Frontend - Dashboard UI & Trend Logic (M4)
- **Create:** `public/index.php` HTML markup for dashboard
  - `<input id="search">` for live client-side filter
  - `<table>` with columns: Phrase | Position | 7-day Trend | Actions
  - `<button id="refreshBtn">Refresh positions</button>`
- **JS Logic (vanilla):**
  - `fetch('/?view=dashboard')` or route that renders the table as HTML
  - 7-day trend calculation: compare position[0] vs position[6] from the last 7 rows ordered DESC by date
    - If pos[0] < pos[6] → "improved" (lower number = better rank)
    - If pos[0] > pos[6] → "declined"
    - Else → "stable"
  - Live text filter: `input` event → `filter` table rows by `.phrase` containing value (case-insensitive)
- **Style:** Basic plain CSS in `public/assets/style.css`

### Phase 7: Frontend - AJAX Integration (M3)
- **Modify:** `#refreshBtn` click handler in the JS from Phase 6
- **Action:** `fetch('www/api/positions.php', {method: 'POST', body: JSON.stringify({all: true})})`
- **Response handling:** Append new row to the table, recalculate trends for affected keywords, show toast/alert
- **No full page reload:** Use `preventDefault()` and `innerHTML` update or DOM insertion

### Phase 8: Frontend - Detail Page (M5)
- **Create:** `public/index.php?keyword=id` route (or `/keyword/12` hash routing)
- **Functionality:**
  - Fetch keyword details via `www/api/keywords.php?id=12`
  - Fetch position history via `www/api/positions.php?keyword_id=12`
  - Render `<table>` with columns: Date | Position
  - Add CSS styling for alternating rows, nice typography
- **URL handling:** Use query string `?keyword=12` since we have no hash/router; keep it simple per "Plain PHP" constraint

---

**This plan satisfies all 6 requirements (M1–M6)** while staying within Plain PHP 8, SQLite, and vanilla JS constraints. Each phase is minimally scoped and can be verified independently.