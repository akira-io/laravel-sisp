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
SISP_MCP_WEB_ABILITY=sisp-mcp
SISP_MCP_WEB_DESTRUCTIVE=false

# SISP status API calls from query and reconcile, per minute
SISP_MCP_GATEWAY_LIMIT_PER_CALLER=10
SISP_MCP_GATEWAY_LIMIT_GLOBAL=60
```

The matching config lives in `config/sisp.php` under the `mcp` key:

```php
'mcp' => [
    'enabled' => env('SISP_MCP_ENABLED', false),
    'local'   => env('SISP_MCP_LOCAL', true),
    'web' => [
        'enabled'    => env('SISP_MCP_WEB_ENABLED', false),
        'path'       => env('SISP_MCP_WEB_PATH', '/sisp/mcp'),
        'middleware' => ['auth:sanctum', 'throttle:60,1'],
        'ability'    => env('SISP_MCP_WEB_ABILITY', 'sisp-mcp'),
        'expose_destructive' => env('SISP_MCP_WEB_DESTRUCTIVE', false),
    ],
    'gateway_rate_limit' => [
        'per_caller' => env('SISP_MCP_GATEWAY_LIMIT_PER_CALLER', 10),
        'global'     => env('SISP_MCP_GATEWAY_LIMIT_GLOBAL', 60),
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
middleware (default `auth:sanctum` and `throttle:60,1`). Clients send `Authorization: Bearer <token>`.

- `mcp.web.middleware` - guard(s) and limits protecting the route. Override for Passport/OAuth or a
  custom guard. The throttle bounds every tool call; the SISP status API has its own limit below.
- `mcp.web.ability` - Gate ability every web tool call must pass. The package never defines it, so
  every call is denied until the host application does.
- `mcp.web.expose_destructive` - when false (default), reconcile, refund and cancel are NOT registered on the
  web transport. The dev server is never exposed over the web.

Define the ability in the host application. It receives the operation name and, for operations on a
single transaction, the transaction itself:

```php
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Gate;

Gate::define('sisp-mcp', function (User $user, string $operation, ?Transaction $transaction = null): bool {
    return match ($operation) {
        'view', 'list', 'query', 'reconcile', 'build' => $user->isFinanceOperator(),
        'refund', 'cancel' => $user->isFinanceAdmin(),
        default => false,
    };
});
```

The operations are `view`, `list`, `query`, `reconcile`, `build`, `refund` and `cancel`. Refund also
checks the `refund` policy ability, the same one the HTTP refund route uses, so an MCP refund is never
allowed where the HTTP route would refuse it.

`list` is asked once without a transaction, then every row it would return is asked for `view`, so a
gate that scopes `view` per merchant or per owner also scopes the list. On the web transport a missing
transaction and a denied one return the same error, so a caller cannot probe which references exist.

`reconcile` writes the status SISP reports back to the transaction, so the web transport only offers
it with `expose_destructive`, like refund and cancel.

A call over HTTP without an authenticated user is always denied, even if the middleware is removed.
Calls on the local transport run as the operator who started `php artisan mcp:start` and are not
checked against the Gate.

## Tools

### Developer (sisp-dev, all read-only)

| Tool | Purpose |
| --- | --- |
| `search-docs-tool` | Search the package docs by keyword. |
| `get-doc-tool` | Return one documentation page by slug. |
| `config-reference-tool` | Explain `config/sisp.php` keys. Any nested key containing `key`, `secret`, `password`, `passwd`, `token`, `autcode`, `authorization`, `cookie`, `card`, `cvv`, `cvc` or `pin` is redacted, the same rule request metadata uses. |
| `env-scaffold-tool` | Produce the `.env` variables to set, per environment. |
| `enum-reference-tool` | List cases and labels for a SISP enum. |
| `country-reference-tool` | Resolve or list supported countries. |
| `simulate-sandbox-callback-tool` | Build a signed sandbox callback for a stored transaction, or for a payment shape. With a transaction, the callback carries its reference, session and amount, so posting it to `/sisp/callback` completes or fails that transaction. Refuses outside sandbox mode; failures are signed with the error fingerprint formula. |
| `doctor-tool` | Invoice storage and configuration diagnostics. Never writes to the disk. |

### Operations (sisp-ops)

| Tool | Annotation | Purpose |
| --- | --- | --- |
| `build-payment-request-tool` | read-only | Preview the payment request fields. The fingerprint is left out and nothing is recorded, so the preview cannot start a payment; real payments go through the `sisp.payment` route. |
| `query-transaction-status-tool` | read-only, idempotent | Query live status at SISP for a stored transaction. |
| `get-transaction-tool` | read-only | Fetch one stored transaction. |
| `list-transactions-tool` | read-only | List stored transactions by status and creation time. `from` and `to` take ISO 8601 and are converted to the application timezone; a bare date as `to` includes that whole day. |
| `reconcile-transaction-tool` | idempotent, writes | Re-sync and persist a transaction's status. Web only with `expose_destructive`. |
| `refund-transaction-tool` | destructive | Refund a completed transaction by an explicit amount. |
| `cancel-transaction-tool` | destructive | Cancel a pending transaction. |

Transaction tools accept a transaction id or a merchant reference. A number that is both the id of one
transaction and the merchant reference of another is refused; prefix it with `id:` or `ref:` to choose. They return a summary with the status, amounts, gateway codes and a masked
customer email, plus `error_code` and `gateway_error` for refused payments.
They never return the merchant session, the request or callback payloads, the card number or 3-D Secure
data.

Refund takes the same payload as the HTTP refund route: `amount` is required and must be greater than
zero, `reason` is optional and at most 255 characters, and `idempotency_key` is optional. It runs through
`RefundTransactionAction`, which locks the transaction row, refuses anything but a completed transaction
and never refunds more than the remaining balance. Pass an `idempotency_key` and reuse it when retrying
after a timeout: the same key and amount refund once, and the same key with another amount is refused.
Without a key, a repeated partial refund is a second refund. Cancel runs through `CancelTransactionAction` and reports why a completed, failed, refunded or
already cancelled transaction cannot be cancelled.

## Resources and prompts

The dev server also exposes reference resources - `sisp://docs`, `sisp://enums` and
`sisp://countries` - and two prompts: an integration walkthrough and a payment-failure diagnosis.

The diagnosis prompt takes a transaction id or merchant reference and works from the stored
`error_code` and `gateway_error.message`. SISP's `messageType` is not an ISO-8583 code, and the package
keeps no catalogue of SISP error codes, so the MCP server does not map codes to causes: the message is the
refusal reason SISP sent for the customer, stored only when the callback fingerprint is valid.

## Security

Refund and cancel move money and reconcile writes status. They are hidden from the web transport unless
`expose_destructive` is enabled, and on the web transport pass the Gate ability like every other tool.
Prefer keeping the web transport read-only and running destructive operations through the local
transport or your own audited application code.

Tool results reach the AI provider behind the agent. That is why the transaction summary masks the
customer email and leaves the stored payloads out.

Text written by SISP reaches the agent in two places: `gateway_error` on the transaction summary and
`gateway` on `query-transaction-status-tool`. Both are objects marked `"untrusted": true`, with control
characters and markup (`` ` ``, `<`, `>`, `[`, `]`, `{`, `}`, `#`, `*`, `_`, `|`, `\`) replaced by spaces and the
text capped at 255 characters. The server instructions tell the agent to report that text and never follow
it. This narrows prompt injection; it cannot rule it out, so an agent with refund or cancel exposed should
still confirm every destructive call with a human.

Status queries and reconciliation each call the SISP status API with the portal credentials. On top of
the route throttle they share a per-minute limit per caller (`gateway_rate_limit.per_caller`, default 10,
keyed by user or `local`) and across all callers (`gateway_rate_limit.global`, default 60). Lower them if
the POS has a smaller quota.

The local `sisp-dev` and `sisp-ops` servers carry the same trust as a shell on the machine: anyone who can
start them can already read `.env`.

---

[Previous: Idempotency](14-idempotency.md)
