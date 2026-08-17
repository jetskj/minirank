# Project Conventions — MiniRank

## Tech Stack
- Plain PHP 8.x (No frameworks). Single front controller in `public/index.php`.
- Database: SQLite via PDO located at `data/minirank.sqlite`.
- Frontend: Vanilla JavaScript (`fetch()`), basic HTML templates, plain CSS. No build tools or node bundles.

## Security & Database Rules
- ALWAYS use PDO prepared statements for all database queries touching input variables.
- NEVER concatenate raw strings into SQL queries.
- ALWAYS wrap output rendered in HTML with `htmlspecialchars()`.
- Never commit `data/*.sqlite` files or secrets.

## Development Principles
- Keep file changes strictly scoped to the current task. Do not rewrite unrelated files.
- Keep code minimal, clean, and easily maintainable.# MiniRank
