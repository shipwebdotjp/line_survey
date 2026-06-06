# Repository Guidelines

## Project Structure & Module Organization

Follow `docs/structure.md` as the source of truth for layout. The intended shape is:

- `public_html/` for the only public web root
- `backend/` for the PHP API and application logic
- `frontend/` for the React/Vite/TypeScript UI
- `docs/` for requirements, design, tasks, and structure notes
- `tests/` for backend tests, frontend tests, and fixtures

Keep business logic out of `public_html/`. Public PHP entry points should stay thin and delegate into `backend/`.

## Build, Test, and Development Commands

This repository uses Docker and `make` targets.

- `make up` starts the containers
- `make build` rebuilds images without cache
- `make init` bootstraps the app after a fresh clone
- `make down` stops and removes containers
- `make test` runs the PHP test suite inside Docker
- `make migrate`, `make fresh`, `make seed` run database tasks
- `make npm-dev` or `make yarn-dev` starts the frontend dev server

Use the containerized commands from `Makefile`; do not run host-side tooling unless the repository explicitly adds it.

## Coding Style & Naming Conventions

- Use ASCII unless a file already requires otherwise
- Directories: `kebab-case`
- PHP classes and React components: `PascalCase`
- Functions, variables, and methods: `camelCase`
- Constants: `UPPER_SNAKE_CASE`

Prefer PSR-12 style for PHP and standard Vite/TypeScript formatting for frontend code. Keep entry files and bootstrap code minimal.

## Testing Guidelines

Don't need to create a test for now.

- Put backend tests in `tests/backend/`
- Put frontend tests in `tests/frontend/`
- Store shared fixtures in `tests/fixtures/`

Name tests after behavior or the unit under test. Add tests for API routes, domain rules, and regressions before merging changes. Run `make test` before opening a PR.

## Commit & Pull Request Guidelines

The current git history is minimal and uses short, plain subjects such as `first commit` and `rename`. Keep commit messages concise, imperative, and specific.

Pull requests should include:

- a short summary of the change
- the commands used to verify it
- screenshots for UI changes
- migration or environment notes when applicable

## Security & Configuration Tips

- Never commit secrets or local overrides
- Keep `.env` values out of version control
- Avoid committing generated artifacts such as `vendor/`, `node_modules/`, `dist/`, or log files

