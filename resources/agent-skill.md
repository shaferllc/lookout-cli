---
name: lookout-cli
description: Use the Lookout CLI and REST API to triage grouped errors, inspect occurrences (stack traces, context), resolve/snooze/ignore issues, and manage projects. Use when the user asks about Lookout errors, error tracking triage, production exceptions, or automating actions against their Lookout instance (not for generic Laravel debugging unless they named Lookout).
---

# Lookout agent skill

Teaches coding agents how to use the **Lookout CLI** against the **Lookout account REST API** (`/api/v1/*`, Sanctum bearer token): portable instructions so agents can run terminal commands instead of requiring MCP.

## Prerequisites

1. **CLI available**
   - In the Lookout monorepo: `./vendor/bin/lookout` (dev dependency `lookout/cli`).
   - Elsewhere: install `lookout/cli` via a Composer path/VCS repository, or `composer global require lookout/cli` when published.
2. **Auth**: `lookout login` — base URL (no trailing slash), e.g. `https://errors.example.com`, and a **Sanctum personal access token** from the Lookout app. Credentials live in `~/.lookout/config.json`.
3. **Overrides**: `LOOKOUT_BASE_URL`, `LOOKOUT_API_TOKEN`, or per-command `--base-url` / `--token`.

If the user is not logged in, run `lookout login` (interactive) or ask them for base URL + token and pass `--base-url` and `--token` on each command.

**Exception:** `lookout ship-logs` uses the **project ingest API key** (`LOOKOUT_PROJECT_API_KEY` or `LOOKOUT_INGEST_API_KEY`, or `--api-key`), not the Sanctum token — it posts to `POST /api/ingest/log` for nginx/Apache-style line logs.

## When to use this skill

- Triage: list open grouped errors, sort mentally by `occurrence_count` or `last_seen`, resolve or snooze in batch.
- Investigate: pull occurrence list, then `get-error-occurrence` for full stack, context, breadcrumbs.
- Ops: error counts, create projects, confirm org access via `me`.
- **Do not** assume other vendors’ CLIs; this is **Lookout** only. Monitoring-style commands exist in the CLI as **stubs** (not implemented server-side yet).

## Output for agents

- **Default**: human tables — parse visually or rerun with `--json`.
- **Scripting / reliable parsing**: add **`--json`** (or `--yaml`) to any command that supports it (all API-backed commands).

Example:

```bash
lookout list-project-errors --project-id=1 --filter-status=open --json | jq '.data[] | {id, occurrence_count, message}'
```

## ID semantics (critical)

| Concept | Lookout field | CLI / API |
|--------|----------------|-----------|
| **Group / “error”** | Fingerprint + representative row | `list-project-errors` column **`id`** = min event id for that group. Use as **`--error-id`** for resolve/snooze/occurrences list. |
| **Single occurrence** | `error_events.id` | `get-error-occurrence --occurrence-id=`. `list-error-occurrences --error-id=` lists all rows sharing the group’s fingerprint. |

## Command cheat sheet

| Goal | Command |
|------|---------|
| Who am I / orgs | `lookout me` or `lookout get-authenticated-user` |
| Projects | `lookout list-projects` [`--organization-id=`] [`--page-number=` `--page-size=`] |
| New project | `lookout create-project --organization-id= --name="..."` [`--team-ids=1,2`] |
| Project detail | `lookout get-project --project-id=` |
| Grouped errors | `lookout list-project-errors --project-id=` [`--filter-status=open`] [`--time=24h\|7d\|30d`] [`--search=`] … |
| Distinct group count | `lookout get-project-error-count --project-id=` |
| Raw event count | `lookout get-project-error-occurrence-count --project-id=` [`--from=` `--to=` or `--time=`] |
| Occurrences for group | `lookout list-error-occurrences --error-id=` |
| Full occurrence | `lookout get-error-occurrence --occurrence-id=` |
| Resolve / reopen / ignore | `lookout resolve-error --error-id=` [`--comment=`], `open-error`, `ignore-error` |
| Snooze | `lookout snooze-error --error-id=` [`--preset=1h\|8h\|24h\|7d` \| `--until=ISO8601`], `unsnooze-error` |
| Delete project | Not in API — UI only (`delete-project` command explains this) |
| Ship plain log lines | `lookout ship-logs` — project API key + `LOOKOUT_BASE_URL`; pipe `tail -F` or `-f` file |

Pagination: `--page-number`, `--page-size` (API max 100).

## Suggested workflows

### Triage open errors

1. `lookout list-projects --json` → pick `project.id`.
2. `lookout list-project-errors --project-id=X --filter-status=open --json`.
3. For each chosen `id` (representative error id): optional `lookout list-error-occurrences --error-id=id --json` then `get-error-occurrence` on latest `id`.
4. Apply `resolve-error`, `snooze-error`, or `ignore-error` with that same representative **`--error-id`**.

### Investigate one report

1. From UI or `list-project-errors`, note **representative error id** or any **occurrence id**.
2. If only occurrence id is known: `lookout get-error-occurrence --occurrence-id=N --json` → read `fingerprint`, `message`, `stack_trace`, `context`.
3. Map stack frames to local repo paths if paths match deployment layout.

### Create a project for a new app

1. `lookout me --json` → `organizations[].id`.
2. `lookout create-project --organization-id=ORG --name="My App" --json` → capture **`api_key`** once from response (shown at creation only).

## API details (for agents writing HTTP instead of CLI)

- Base: `{base_url}/api/v1/...`
- Header: `Authorization: Bearer {token}`, `Accept: application/json`
- Query auth also works on the server: `?api_token=` (middleware); prefer Bearer in scripts.
- List pagination: `page[number]`, `page[size]` or `page`, `per_page`.

## Limitations

- **No** performance monitoring API yet (CLI monitoring commands are placeholders).
- **No** team management via CLI beyond what `me` returns.
- **Project delete** only in the web UI.

## Installing this file elsewhere

From a machine with the CLI:

```bash
lookout install-skill
# or
lookout install-skill --output=/path/to/.cursor/skills/lookout-cli/SKILL.md
```

Re-run after upgrades if the skill content changes.
