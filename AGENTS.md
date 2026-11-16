# Repository Guidelines

## Project Structure & Module Organization

This repository is currently in the planning stage. `plan.md` is the sole development plan; update it directly when scope, architecture, or acceptance criteria change. Application code, tests, and assets have not been scaffolded.

Follow the proposed layout in `plan.md` when implementation begins:

- `web/`: Laravel 13 / PHP 8.4 application, with business modules in `app/` and routes in `routes/`.
- `web/resources/js/user/` and `admin/`: separate Inertia/Vue 3 + TypeScript entry points using Tailwind CSS and Naive UI respectively; shared logic belongs in `shared/`.
- `ai/control-plane/` and `ai/workers/{image,video}/`: Python FastAPI orchestration and GPU workers.
- `contracts/`: versioned JSON Schema, OpenAPI, and task events.
- `deploy/`: containers, cluster configuration, and environment templates.

## Build, Test, and Development Commands

No dependency manifests, build scripts, or runnable tests exist yet. Documentation changes can be reviewed directly in `plan.md`; do not report application checks as passing.

When scaffolding, document exact setup and verification commands alongside the manifests. Expected Laravel/Vite commands include `php artisan serve` (local Web server), `npm run dev` (frontend development), `npm run build` (production assets), and `php artisan test` (PHP tests), run from `web/` only after configuration. Define Python commands separately for each service.

## Coding Style & Naming Conventions

Until formatter configurations exist, use four spaces for PHP/Python and two for Vue/TypeScript. Use PascalCase Vue component filenames and Python snake_case modules. Prefer Vue Composition API. Keep user and admin styles isolated. Select and configure formatters and linters during scaffolding; none are currently established.

## Testing Guidelines

No test framework or numerical coverage threshold is configured. Add PHP unit/feature tests, Vue type checks, browser workflow tests, and cross-service contract tests as implementation lands. Prioritize authorization, private assets, wallet settlement, duplicate payment callbacks, retries, and stale task attempts. Use descriptive behavior-based test names and record executed checks in pull requests.

## Commit & Pull Request Guidelines

Git history is unavailable in this checkout, so no existing message convention can be verified. Use concise imperative subjects, such as `docs: clarify GPU recovery criteria`. Describe the change, reference relevant module IDs (for example, W08 or A01), link applicable issues, and provide validation evidence. Include screenshots for UI changes and migration or configuration notes where relevant.

## Security & Configuration

Keep credentials, model weights, and private media out of version control. Avoid logging private photos or sensitive prompts. Preserve module ownership: W08 owns the wallet ledger; AI workers report execution results without changing balances.
