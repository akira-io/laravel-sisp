# laravel-sisp v2 Audit Report

Static read-only audit of the v2 codebase across five dimensions: idempotency/concurrency,
security, payment correctness, architecture, and test/docs coverage. Findings are sorted by
severity. Line numbers are indicative; verify against the current source before acting. This is
an analysis pass only - no code was changed by this report.

## Executive summary

| Severity | Count |
| --- | --- |
| Critical | 3 |
| High | 9 |
| Medium | 11 |
| Low / informational | 3 |

The highest-risk areas are the idempotency reservation path (a TOCTOU window in payment-intent
reservation) and authorization on the money-moving HTTP endpoints (retry, refund, cancel), which
default to weak or empty middleware and rely on a host-defined policy that may be absent. Payment
correctness is mostly sound; the main concern is float round-tripping in partial refunds.

## Critical

### C1 - TOCTOU race in payment-intent reservation
`src/Pipelines/Payment/Pipes/ApplyPaymentIntent.php` (reserve path). The reclaim-then-insert
sequence is not wrapped in a single atomic step, so two concurrent requests with the same
idempotency key can both miss the reclaim and then race on `insertOrIgnore`. The loser can land on
a `processing` intent with no `transaction_id` and throw `PaymentIntentAlreadyProcessingException`.
Fix: perform the reclaim/insert inside one `DB::transaction` with `lockForUpdate`, or rely on a
single upsert and branch on the resulting row.

### C2 - Missing authorization on retry / refund / cancel (IDOR)
`routes/web.php` plus `RetryPaymentController`, `RefundTransactionController`,
`CancelTransactionController`. Retry default middleware is empty (`sisp.middleware.retry => []`);
refund depends on a host `refund` policy that, if undefined, may not deny; cancel is protected only
by a signed URL, which validates URL integrity, not the actor. A user who knows a transaction id or
merchant reference may act on transactions that are not theirs. Fix: require authentication by
default, add an ownership/policy check in each controller, and document the expected policy.

### C3 - Partial-refund float round-trip
`src/Actions/RefundTransactionAction.php` (refund amount handling). The refund amount is converted
to thousandths and then divided back to a float before being handed to the refund request builder,
re-introducing float imprecision on values such as `8.03`. Fix: keep the canonical integer
(thousandths/cents) and pass it through without the float round-trip.

## High

- H1 - Retry attempt creation locks the transaction row but reads `max(attempt_number)` from the
  attempts relation in a way that can interleave under concurrency.
  `src/Actions/CreateRetryPaymentAttemptAction.php` / `CreateTransactionAttemptAction.php`. Lock the
  attempts in the same transaction before computing the next number.
- H2 - `superseded_at` marking and new-attempt insertion are separate writes; a callback can resolve
  a just-superseded attempt. `src/Actions/CreateTransactionAttemptAction.php`. Wrap both in one
  transaction and have the callback path assert the attempt is current.
- H3 - Legacy attempt lookup runs unlocked before falling back to a locked path.
  `src/Actions/Transaction/FindTransactionAttemptAction.php`. Lock on the first resolving query when
  idempotency matters.
- H4 - Idempotency key accepted with minimal validation (trim only); very short keys risk
  collisions. `src/Pipelines/Payment/Pipes/ApplyPaymentIntent.php`. Enforce a minimum length and a
  character allow-list.
- H5 - `PaymentContext` / `CallbackContext` expose public mutable state mutated in place across
  pipes. `src/Pipelines/*/`. Prefer private state with explicit setters or immutable `with*()`.
- H6 - `RefundBuilder` is `final` with mutable internal state; intent (mutable builder vs immutable)
  is undocumented. `src/Builders/RefundBuilder.php`.
- H7 - Refund replay/idempotency is untested; a retried refund callback could double-process.
  `tests/Actions/RefundTransactionActionTest.php`.
- H8 - `CancelTransactionAction` has no dedicated test for the non-cancellable transitions.
  `src/Actions/CancelTransactionAction.php`.
- H9 - Fingerprint validation tests cover only a narrow set of vectors (no missing/malformed/empty
  fingerprint). `tests/Actions/ValidateFingerprintActionTest.php`.

## Medium

- M1 - Callback route bypasses the `web` middleware group (`withoutMiddleware('web')`), so CSRF is
  off and replays are possible if fingerprint validation is ever reordered. `routes/web.php`. This
  is standard for gateway callbacks; keep fingerprint validation first and add replay/rate guards.
- M2 - Card PAN is persisted inside `callback_payload`. It is encrypted at rest via
  `EncryptsAttributes`, but is not masked. `src/Actions/Transaction/UpdateTransactionAttemptAction.php`.
  Consider masking before storage.
- M3 - Rate limiting keys on IP only; no merchant- or user-scoped limiting.
  `src/Pipelines/Payment/Pipes/EnforceRateLimits.php`.
- M4 - `PaymentIntent` submit (status -> submitted, set `transaction_id`) runs after the pipeline
  without failure handling; a failed update strands the intent in `processing`.
  `src/Pipelines/Payment/Pipes/ApplyPaymentIntent.php`.
- M5 - No explicit deadlock retry around the callback pipeline's nested transactions.
  `src/Pipelines/Callback/HandleCallbackPipeline.php`.
- M6 - Pre-pipeline `isAlreadyProcessed` check on the callback can let two identical callbacks both
  enter the pipeline (last write wins). `src/Http/Controllers/CallbackController.php`. Move the
  idempotency check inside the locked section.
- M7 - `MapTransactionStatusAction` treats a hardcoded `'10'` as success without an enum case.
  `src/Actions/Transaction/MapTransactionStatusAction.php`.
- M8 - `ThreeDSecureData` does not validate that country-code conversion succeeded.
  `src/ValueObjects/ThreeDSecureData.php`.
- M9 - Reconciliation lacks edge-case tests (already-completed no-op, conflicting result flags,
  repeated calls). `tests/Actions/ReconcileTransactionStatusActionTest.php`.
- M10 - `docs/05-transaction-management.md` carries a stale "before 1.0.0" note and lacks a
  cancellation-rules section.
- M11 - Type-coverage requirement (100%) and how to run it are undocumented.
  `docs/12-architecture.md`.

## Low / informational

- L1 - Fingerprint comparison correctly uses `hash_equals` (timing-safe).
  `src/Actions/ValidatePaymentResponseFingerprintAction.php`. No action.
- L2 - Retry loop already filters non-unique-constraint `QueryException`s correctly.
  `src/Actions/CreateRetryPaymentAttemptAction.php`. No action.
- L3 - Several narrative comments remain (`SispServiceProvider`, `EncryptsAttributes`,
  `ProtectPaymentRoute`); house style discourages explanatory comments.

## Prioritized backlog

1. C2 - Lock down authorization on retry/refund/cancel (highest real-world risk).
2. C1 / H1 / H2 / H3 - Make the intent and attempt lifecycle atomic under concurrency.
3. C3 - Remove the partial-refund float round-trip.
4. H7 / H8 / H9 / M9 - Close the refund/cancel/reconcile/fingerprint test gaps.
5. M-series - Hardening (PAN masking, rate-limit scope, deadlock retry, callback idempotency).

## Method and caveats

Findings come from two parallel static read passes (security/concurrency and
correctness/architecture/tests). Some line references are approximate and a few recommended fixes
are debatable design choices rather than defects; confirm each against the current source and the
test suite before changing behavior. The security-sensitive items (C1, C2, C3, H1-H3) should be
reproduced with a focused test before remediation.
