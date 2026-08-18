

We are strictly using Plain PHP 8 (no frameworks), SQLite via PDO with prepared statements, and vanilla JavaScript (no build steps). Keep the single front-controller architecture `public/index.php`).

Please acknowledge this plan, and then we will execute it ONE phase at a time in separate Build sessions to keep the diffs manageable.

### Phase 1: Complete M1 (Frontend CRUD & Configured Website)

- **Website Context:** Update `public/index.php` to display a hardcoded target website at the top of the dashboard (e.g., "Tracking keywords for: [example.com](http://example.com)") to satisfy the "one configured website" rule.
- **Add Form:** Add a simple HTML form above the dashboard table with a text input and an "Add Keyword" button.
- **Action Buttons:** Add "Edit" and "Delete" buttons to the "Actions" column for each keyword row.
- **Vanilla JS Integration:** Write vanilla JS in `public/assets/app.js` (or inline) to handle the Add form submission, and the Edit/Delete button clicks. These should make `fetch()` POST requests to our existing `www/api/keywords.php` endpoints and dynamically update the DOM without a page reload.



### Phase 2: Stretch Goal S4 (Filter by Movement)

- **UI Update:** Add a dropdown filter next to the text search box on the dashboard `public/index.php`). The dropdown should have options: "All", "Improved", "Declined", and "Stable".
- **JS Logic:** Update the existing vanilla JS filtering logic so that when the dropdown changes, it filters the table rows based on the 7-day trend indicator already present in the UI.



### Phase 3: Stretch Goal S1 (Line Chart on Detail Page)

- **UI Update:** On the keyword detail page view `public/index.php?keyword=id`), add a canvas element above the position history table.
- **Chart Logic:** Include a lightweight library like Chart.js via CDN. Fetch the 30-day position data for the keyword (which we already have via `www/api/positions.php?keyword_id=id`) and render a line chart showing the ranking trend over time. Remember that for rankings, a lower number (1) is better, so the Y-axis should ideally be inverted.



### Phase 4: Stretch Goal S5 (CSV Export)

- **Backend Endpoint:** Create a new endpoint at `www/api/export.php`. It should accept a `keyword_id` via GET request. It will query the SQLite database for that keyword's history and output it directly to the browser with `Content-Type: text/csv` and a `Content-Disposition: attachment` header.
- **UI Update:** Add a "Download CSV" link/button on the keyword detail page that points to this new endpoint.



### Phase 5: Stretch Goal S2 (Multiple Projects - DB Migration)

- **Schema Update:** Only if time permits, create a script to alter the database. Add a `projects` table `id`, `domain_name`) and add a `project_id` foreign key to the `keywords` table.
- **UI Update:** Add a project selector to the dashboard to switch contexts, and update the API queries to filter by `project_id`.

These are the exact requirements that are written in the doc for the project:

```markdown
M1 | **Keywords CRUD:** add/edit/delete the keywords (search phrases) tracked for one configured website. Single-user app — **no login**. |
```

```markdown
| S1 | A line chart on the keyword detail page (any JS library, or hand-rolled SVG/canvas) |
| S2 | Multiple projects/websites, each with its own keywords |
| S3 | User accounts — register, log in, log out (hashed passwords, sessions) — plus CSRF protection on forms |
| S4 | Filter keywords by position range or by movement (improved/declined) |
| S5 | CSV export of a keyword's position history |
| S6 | PHPUnit tests for the core logic (seeding, trend calculation) |
| S7 | Docker setup: `docker compose up` starts app + database |
| S8 | An `AGENTS.md` with real, hand-written project conventions (we grade its quality if present) |
```

