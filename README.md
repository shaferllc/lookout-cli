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

## Ship logs (project API key)

`ship-logs` sends **plain-text lines** to `POST /api/ingest/log` using the **project ingest API key** from Lookout (**not** the Sanctum token above). Use it to forward nginx, Apache, syslog, or any line-oriented log.

**Install** the same way as the rest of the CLI (`composer require lookout/cli` globally or in your app).

```bash
# Example: follow nginx access log (batch 100 lines per request)
export LOOKOUT_BASE_URL="https://your-lookout-host"
export LOOKOUT_PROJECT_API_KEY="your_project_api_key_from_settings"
tail -F /var/log/nginx/access.log | lookout ship-logs --source=nginx.access

# One-shot file
lookout ship-logs -f /var/log/apache2/error.log --source=apache.error

# Verify payload shape without POSTing
lookout ship-logs -f ./sample.log --dry-run
```

Options: `--batch-size` (1–200, default 100), `--path` (default `/api/ingest/log`), `--file`/`-f` (`-` = stdin). Env aliases for the key: `LOOKOUT_INGEST_API_KEY`.

**systemd** (runs under a dedicated user; tighten `chmod` on the unit file if you embed the key, or use `EnvironmentFile=`):

```ini
[Service]
ExecStart=/bin/sh -c 'tail -F -n0 /var/log/nginx/access.log | /usr/local/bin/lookout ship-logs --source=nginx.access'
Environment=LOOKOUT_BASE_URL=https://lookout.example.com
Environment=LOOKOUT_PROJECT_API_KEY=...
Restart=always
```

For **structured application logs** (level, message, attributes), POST JSON from your app or use a log forwarder (Vector, Fluent Bit) that maps fields to `POST /api/ingest/log` — see your Lookout host `/docs/ingest#logs`.

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
| `ship-logs` | Forward **lines** to log ingest (**project API key**); see [Ship logs](#ship-logs-project-api-key) |

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
