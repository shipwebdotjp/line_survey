# Repository Guidelines

## Project Specs
Follow `docs/spec.md` as the project specs.

## Rueles
- Backward compatibility does not need to be maintained during this phase.


## AI Wiki

- `ai_wiki/` is the shared wiki for AI coding agents such as Codex, Jules, and OpenCode
- Use it for decisions, implementation notes, and repo memory that should persist across sessions
- Start from `ai_wiki/00-Index.md` when looking for the current map of that wiki

## Project Structure & Module Organization

Follow `docs/structure.md` as the source of truth for layout. The intended shape is:

- `public_html/` for the only public web root
- `backend/` for the PHP API and application logic
- `frontend/` for the React/Vite/TypeScript UI
- `docs/` for requirements, design, tasks, and structure notes
- `ai_wiki/` for AI agent-specific memory and working notes
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

## API Response Shapes

- `JsonResponse::success()` returns a `{ data: ... }` envelope, so frontend API helpers should usually unwrap `result.data` before returning to callers.
- Do not mix helper contracts between raw envelopes and unwrapped payloads; page components should not need to know the transport wrapper shape.

## Coding Style & Naming Conventions

- Use ASCII unless a file already requires otherwise
- Directories: `kebab-case`
- PHP classes and React components: `PascalCase`
- Functions, variables, and methods: `camelCase`
- Constants: `UPPER_SNAKE_CASE`

Prefer PSR-12 style for PHP and standard Vite/TypeScript formatting for frontend code. Keep entry files and bootstrap code minimal.

## Testing Guidelines

It's development stage currently, so don't need to create a test.

## Security & Configuration Tips

- Never commit secrets or local overrides
- Keep `.env` values out of version control
- Avoid committing generated artifacts such as `vendor/`, `node_modules/`, `dist/`, or log files
