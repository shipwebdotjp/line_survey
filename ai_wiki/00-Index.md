# AI Wiki Index

This directory is the project wiki for AI coding agents.

## Purpose

- Record implementation decisions, environment-specific notes, and repo conventions
- Keep agent-facing context that should survive across Codex, Jules, and OpenCode sessions
- Serve as the lightweight project memory for AI-driven work

## Current Notes

- `APP_URL` is not used as a shared fallback value.
- Origin checking and external/public URLs are treated as separate concerns.
- Compatibility fallbacks are intentionally not maintained for this branch of work.

## Suggested File Layout

- `00-Index.md`: entry point and current map
- `10-Decisions.md`: resolved technical decisions
- `20-Worklog.md`: chronological notes from agent sessions
- `30-References.md`: links to specs, docs, and important files

## Recorded Notes

- [10-Decisions.md](./10-Decisions.md): LIFF の期限切れ IDToken クリア方針などの技術判断
- [20-Worklog.md](./20-Worklog.md): デプロイ時の環境変数、`.htaccess`、権限調整の作業記録

## Source References

- `AGENTS.md`
- `docs/spec.md`
- `docs/structure.md`
- `docs/task.md`
