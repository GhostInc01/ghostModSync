# Ghost ModSync — Antigravity Workspace Rules

See [AGENTS.md](./AGENTS.md) and [PROJECT_CONTEXT.md](./PROJECT_CONTEXT.md) for the authoritative project rules and architecture baseline.

- Always read `PROJECT_CONTEXT.md` before significant work.
- Follow the approved Laravel 13 modular monolith architecture (`docs/03-system-architecture.md`) and database design (`docs/04-database-design.md`).
- Avoid unnecessary dependency installation.
- Never expose secrets from `.env`.
- Use forward-only migrations for all schema changes (`DB_DATABASE=ghostModsSync`).
- Never perform destructive database operations (`migrate:fresh`, `migrate:reset`, `db:wipe`, `DROP`) without explicit user approval.
- Use `FormRequest` validation and Laravel `Policies` on all Web and `/api/v1` controllers.
- Write automated PHPUnit tests for all features and authorization rules.
- Keep `PROJECT_CONTEXT.md` and `docs/01`–`07` updated as the system evolves.
- Explain important implementation decisions clearly.
