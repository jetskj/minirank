# AI Coding Assessment — rankingCoach Mentoring, Phase 8

**For:** mentoring candidates · **Window:** 14.08 – 18.08.2026, ~6–10h of actual effort · **Deadline:** Tuesday, 18.08.2026, 23:59

## Why this test

At rankingCoach, developers work with AI coding agents every day. This assessment measures the skill we actually care about: how you **plan, instruct, supervise and verify** an AI agent while building software — not how much PHP you have memorized. Many of you come from Java/JS backgrounds, and that's fine: we want to see how you use an agent to become productive in a stack that is new to you, because that is exactly what your first weeks here would look like.

You will build a small full-stack PHP application using the **opencode desktop app** with **free AI models**, following the setup guide provided separately. Everything you produce — code, commits, prompts, and a write-up of your process — is part of the assessment.

## The task: MiniRank

rankingCoach helps small businesses track how they perform online. You will build a miniature version of that idea: **MiniRank**, a keyword position tracker. All ranking data is simulated — no real search engines involved.

### Must-haves (all required)

| # | Requirement |
|---|---|
| M1 | **Keywords CRUD:** add/edit/delete the keywords (search phrases) tracked for one configured website. Single-user app — **no login**. |
| M2 | **Seeded history:** a seed script creates demo keywords with ~30 days of daily positions (1–100). |
| M3 | **Refresh simulation:** a "Refresh positions" button generates today's positions server-side and updates the page **via AJAX, without a full page reload**. |
| M4 | **Keyword list:** every keyword with its current position, a 7-day trend indicator (improved / declined / stable), and a text search. |
| M5 | **Keyword detail:** a page per keyword showing the position history as a table. |
| M6 | **Security basics:** parameterized queries (no SQL built by string concatenation), escaped output, and **no secrets committed to the repository**. |
| M7 | **Runs in 5 minutes:** README with setup steps, a seed command, and a one-command start (`php -S ...` or `docker compose up`). Database: SQLite (recommended — easiest for us to run) or MySQL. |
| M8 | **Responsive:** usable at phone width. Functional beats beautiful — we are not grading pixel-perfection. |

### Stretch goals (optional — attempt only after the must-haves work; this is where stronger candidates differentiate)

| # | Idea |
|---|---|
| S1 | A line chart on the keyword detail page (any JS library, or hand-rolled SVG/canvas) |
| S2 | Multiple projects/websites, each with its own keywords |
| S3 | User accounts — register, log in, log out (hashed passwords, sessions) — plus CSRF protection on forms |
| S4 | Filter keywords by position range or by movement (improved/declined) |
| S5 | CSV export of a keyword's position history |
| S6 | PHPUnit tests for the core logic (seeding, trend calculation) |
| S7 | Docker setup: `docker compose up` starts app + database |
| S8 | An `AGENTS.md` with real, hand-written project conventions (we grade its quality if present) |

### Technology rules

- **Backend: PHP** — any style: plain PHP, a micro-framework (e.g. Slim), or a full framework. One tip: free models have smaller context windows, and a large framework scaffold can work against you. Choose deliberately and explain the choice in your write-up.
- **Frontend: HTML/CSS/JS** — any libraries you like.
- **AI: the opencode desktop app with the model configuration from the setup guide.** All AI-assisted coding must go through opencode. You may use chat AIs (ChatGPT, etc.) for brainstorming, but disclose it in your process document.
- **Your own work.** Public tutorials, documentation and Composer packages are fine; code from other candidates is not.

## Deliverables

1. **GitHub repository** — created **private**, made **public at the deadline**, link submitted by replying to the email you received this brief in.
2. **Real commit history** — `git init` at the start, commit as you go, small commits with meaningful messages. **No squashing, no force-push, no single "final version" commit.** The history is part of the assessment.
3. **Session links** — share your substantial opencode sessions (`/share`, or the session menu → Share) and list the links in your process document. We will read your actual prompts, including the ones that failed. That is a good thing: recovering from a bad prompt is skill, not shame.
4. **process.html** — your process document, built with the agent, with three sections:
   - **Plan** — your plan and key decisions, and what you would change in hindsight;
   - **Prompts & fixes** — 3 real prompts that mattered, **including one that failed**, and concrete mistakes the AI made and how you caught them;
   - **Retrospective** — what you would do differently, plus roughly how many hours you spent.
5. **README.md** — per M7.

## Timeline

| When | What |
|---|---|
| Friday, 14.08.2026 | Test window opens — start as soon as your pre-flight passes. |
| By Saturday, 15.08.2026, end of day | **Day-0 pre-flight** from the setup guide: opencode installed, model authenticated, test prompt answered, confirmation sent. Setup problems are solved *now*, not during the test window. |
| Tuesday, 18.08.2026, 23:59 | Deadline: repository public, link + session links submitted. |
| 19.08 – 21.08.2026 | **Defense (shortlisted candidates, invited individually):** ~20 minutes. We run your app, pick a piece of your code and ask you to explain it, then ask for one small change made live with the agent. If you built it engaged, this is easy. |

## How you will be scored

| Category | Weight |
|---|---|
| Working product (must-haves demonstrably working) | 30% |
| Code quality & security (M6 especially) | 25% |
| AI process — planning, prompt quality, catching the AI's mistakes | 25% |
| Git & repository hygiene | 10% |
| Process document | 10% |
| Defense | pass/fail gate |

Three pieces of honest advice. **Plan before you prompt** — opencode has a Plan mode; use it. **Work in small steps** — giant "build everything" prompts produce giant unreviewable diffs and burn your free-model quota. **Verify everything** — free models will happily write SQL injection; finding it is your job, and we notice when you do.

For any questions, feel free to ask by replying to the email.
