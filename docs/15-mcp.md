# 15. MCP Server

laravel-sisp ships an [official Laravel MCP](https://github.com/laravel/mcp) server so AI
agents can both integrate the package and operate the gateway. It is split into two servers by
risk profile and is disabled by default.

| Server | Handle | Transport | Surface |
| --- | --- | --- | --- |
| `SispDevServer` | `sisp-dev` | local | Read-only developer assistance: docs, config, enums, countries, sandbox |
| `SispOpsServer` | `sisp-ops` | local | Runtime payment operations, including refund and cancel |
| `SispWebOpsServer` | `sisp-ops` | web | Runtime operations over HTTP; destructive tools gated behind a flag |

## Enabling

The server is opt-in. Set the environment variables in the host application:

```dotenv
SISP_MCP_ENABLED=true
SISP_MCP_LOCAL=true

# Web transport (remote AI clients)
SISP_MCP_WEB_ENABLED=false
SISP_MCP_WEB_PATH=/sisp/mcp
SISP_MCP_WEB_DESTRUCTIVE=false
```

The matching config lives in `config/sisp.php` under the `mcp` key:

```php
'mcp' => [
    'enabled' => env('SISP_MCP_ENABLED', false),
    'local'   => env('SISP_MCP_LOCAL', true),
    'web' => [
        'enabled'    => env('SISP_MCP_WEB_ENABLED', false),
        'path'       => env('SISP_MCP_WEB_PATH', '/sisp/mcp'),
        'middleware' => ['auth:sanctum'],
        'ability'    => null,
        'expose_destructive' => env('SISP_MCP_WEB_DESTRUCTIVE', false),
    ],
],
```

When `mcp.enabled` is false the package never loads its `routes/ai.php`, so no MCP routes or
commands are registered.

## Local usage

Local servers run as Artisan commands and connect to coding agents on the developer machine:

```bash
php artisan mcp:start sisp-dev
php artisan mcp:start sisp-ops
```

Inspect a server interactively with the MCP Inspector:

```bash
php artisan mcp:inspector sisp-dev
```

## Web usage

With `mcp.web.enabled` true, the ops server is exposed at `mcp.web.path` behind the configured
middleware (default `auth:sanctum`). Clients send `Authorization: Bearer <token>`.

- `mcp.web.middleware` - guard(s) protecting the route. Override for Passport/OAuth or a custom guard.
- `mcp.web.ability` - optional Gate ability checked inside refund and cancel. When set, the
  authenticated user must pass `Gate::allows($ability, $transaction)`.
- `mcp.web.expose_destructive` - when false (default), refund and cancel are NOT registered on the
  web transport. The dev server is never exposed over the web.

## Tools

### Developer (sisp-dev, all read-only)

| Tool | Purpose |
| --- | --- |
| `search-docs-tool` | Search the package docs by keyword. |
| `get-doc-tool` | Return one documentation page by slug. |
| `config-reference-tool` | Explain `config/sisp.php` keys (secrets redacted). |
| `env-scaffold-tool` | Produce the `.env` variables to set, per environment. |
| `enum-reference-tool` | List cases and labels for a SISP enum. |
| `error-code-lookup-tool` | Resolve a SISP error code to label, category, and action. |
| `country-reference-tool` | Resolve or list supported countries. |
| `simulate-sandbox-callback-tool` | Build a sandbox callback payload for local testing (no writes). |
| `doctor-tool` | Invoice storage and configuration diagnostics. |

### Operations (sisp-ops)

| Tool | Annotation | Purpose |
| --- | --- | --- |
| `build-payment-request-tool` | read-only | Build the signed payment payload. Does not charge. |
| `query-transaction-status-tool` | read-only, idempotent | Query live status at SISP. |
| `get-transaction-tool` | read-only | Fetch one stored transaction. |
| `list-transactions-tool` | read-only | List/filter stored transactions. |
| `reconcile-transaction-tool` | idempotent | Re-sync and persist a transaction's status. |
| `refund-transaction-tool` | destructive | Refund a completed transaction. |
| `cancel-transaction-tool` | destructive | Cancel a pending transaction. |

## Resources and prompts

The dev server also exposes reference resources - `sisp://docs`, `sisp://enums`,
`sisp://countries`, `sisp://error-codes` - and two prompts: an integration walkthrough and a
payment-failure diagnosis.

## Security

Refund and cancel move money. They are annotated destructive, require authentication on the web
transport, support an optional Gate ability, and are hidden from the web transport unless
`expose_destructive` is enabled. Prefer keeping the web transport read-only and running destructive
operations through the local transport or your own audited application code.

---

[Previous: Idempotency](14-idempotency.md)
