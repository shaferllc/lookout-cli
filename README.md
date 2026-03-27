# Lookout CLI

Terminal client for [Lookout](https://github.com/) error tracking. It calls the Lookout **`/api/v1/*`** REST API with a Sanctum personal access token.

## Install

From this monorepo (path repository):

```bash
composer require --dev lookout/cli
./vendor/bin/lookout list
```

Or global (after publishing or using a path repo in your global `composer.json`):

```bash
composer global require lookout/cli
# ensure `composer global config bin-dir --absolute` is on your PATH
lookout --version
```

## Auth

```bash
lookout login
# Base URL: https://your-lookout-host  (no trailing slash)
# Token: create in the app (Sanctum personal access token)

lookout logout
```

Overrides: `--base-url`, `--token`, or env `LOOKOUT_BASE_URL` / `LOOKOUT_API_TOKEN`.

## Output

- Default: tables / short summaries.
- `--json` or `--yaml` for automation.

## Commands

Run `lookout list` for the full set. Highlights:

| Command | Notes |
|--------|--------|
| `get-authenticated-user` (`me`) | User, orgs, teams |
| `list-projects` | `--organization-id`, pagination |
| `create-project` | `--organization-id`, `--name`, optional `--team-ids` |
| `get-project` | `--project-id` |
| `list-project-errors` | Grouped errors; `--filter-status` or `--status`; `--page-number`, `--page-size` |
| `get-project-error-count` | Distinct groups |
| `get-project-error-occurrence-count` | Raw events; `--from` / `--to` or `--time` |
| `list-error-occurrences` | `--error-id` (representative event id) |
| `get-error-occurrence` | `--occurrence-id` |
| `resolve-error`, `open-error` (`unresolve-error`), `ignore-error`, `snooze-error`, `unsnooze-error` | `--error-id` |

Performance monitoring commands (`get-monitoring-summary`, …) are **stubs** until Lookout exposes an API.

`delete-project` explains that deletion is only in the web UI for now.

## Agent skill

Portable instructions for AI agents (Cursor, etc.): run `lookout install-skill` or copy `packages/lookout-cli/resources/agent-skill.md` into your agent skills folder.

- **In the Lookout repo:** `.cursor/skills/lookout-cli/SKILL.md` is kept in sync with the CLI bundle (`packages/lookout-cli/resources/agent-skill.md`).
- **Other checkouts:** run `lookout install-skill` (default output `.cursor/skills/lookout-cli/SKILL.md`, override with `-o`).

## Develop

```bash
cd packages/lookout-cli && composer install && ./vendor/bin/phpunit
```
