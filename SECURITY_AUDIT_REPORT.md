# Security and Code Audit Report

## Issue 1: Security: DoS Vulnerability in `PreventDuplicateCallback` Middleware

**Description**
The `PreventDuplicateCallback` middleware executes database queries using input from the request (`merchantRespMerchantRef`, `merchantRespMerchantSession`) *before* the request signature is validated. This occurs because the middleware runs before the controller action where `Sisp::validateCallback` is called.

**Impact / Risk**
**High**. An attacker can exploit this to perform a Denial of Service (DoS) attack by flooding the application with requests containing random reference IDs, causing database exhaustion.

**Steps to Reproduce**
1. Send a POST request to `/sisp/callback` (or any route using this middleware).
2. Include random `merchantRespMerchantRef` and `merchantRespMerchantSession` parameters.
3. Observe that the database is queried for each request regardless of the validity of the payload signature.

**Suggested Direction**
Move the duplicate transaction check to *after* the signature validation has been performed. This likely requires removing the middleware and performing the check inside `HandleCallbackAction` or a new middleware that runs after signature verification.

## Issue 2: Security: IDOR Vulnerability in `RetryPaymentController`

**Description**
The `RetryPaymentController` takes a `transaction_id` (integer) from the request and retrieves the transaction using `Transaction::query()->findOrFail($id)`. There is no check to verify that the authenticated user owns or is authorized to access this transaction.

**Impact / Risk**
**High**. An Insecure Direct Object Reference (IDOR) allows any user to initiate a payment retry for any transaction in the system by guessing the sequential ID.

**Steps to Reproduce**
1. Authenticate as User A.
2. Send a POST request to `/sisp/retry-payment` with a `transaction_id` belonging to User B.
3. The system processes the request for User B's transaction.

**Suggested Direction**
Implement an ownership check. Ensure the transaction belongs to the currently authenticated user.

## Issue 3: Security: Missing Authorization Check in `RefundTransactionController`

**Description**
The `sisp/refund/{transaction}` route is protected by `auth` middleware (default config), but the `RefundTransactionController` does not verify if the authenticated user has permission to refund the specific transaction.

**Impact / Risk**
**High**. Any authenticated user (even a standard user) can refund any transaction if they know the ID.

**Steps to Reproduce**
1. Authenticate as a standard user without administrative privileges.
2. Send a POST request to `/sisp/refund/{id}`.
3. The refund is processed.

**Suggested Direction**
Add an explicit authorization check in the controller using Laravel's Policies or Gates.

## Issue 4: Bug: Route Parameter Mismatch in `CancelTransactionController`

**Description**
The route `sisp/cancel` is defined in `routes/web.php` without any route parameters. However, the `CancelTransactionController::__invoke` method signature requires a `Transaction $transaction` model binding.

**Impact / Risk**
**High**. Accessing the cancel route will result in a 500 Server Error or a 404 Not Found because Laravel cannot inject the `Transaction` model without a corresponding route segment.

**Steps to Reproduce**
1. Navigate to `/sisp/cancel`.
2. Observe the application crash or error.

**Suggested Direction**
Update the route definition to include the transaction parameter (e.g., `sisp/cancel/{transaction}`) or modify the controller to retrieve the transaction using the `merchantRef` query parameter.

## Issue 5: Security: Double JSON Encoding in `EncryptsAttributes` Trait

**Description**
The `Transaction` model casts the `payload` attribute to `array`, but also marks it as encryptable via the `EncryptsAttributes` trait. When saving, the trait encrypts the data to a Base64 string. Laravel's casting layer then JSON-encodes this string (because of the `array` cast). Upon retrieval, the double-encoding (JSON string of a Base64 string) may cause the decryption logic to fail or return the wrong type.

**Impact / Risk**
**Medium**. Potential data corruption or inability to read encrypted payloads correctly.

**Steps to Reproduce**
1. Create a `Transaction` with a `payload` array.
2. Save it to the database.
3. Retrieve the transaction and access `$transaction->payload`.
4. Verify if it returns the array or throws a decryption error.

**Suggested Direction**
Modify `EncryptsAttributes` to handle attributes that have Laravel casts, or remove the `array` cast from the model.

## Issue 6: Security: Missing CSRF Protection on `sisp/payment` Route

**Description**
The `sisp/payment` route (POST) is defined in the package but is not assigned the `web` middleware group (which provides CSRF protection). It uses `ProtectPaymentRoute` but lacks standard CSRF defenses.

**Impact / Risk**
**Medium**. Vulnerable to Cross-Site Request Forgery (CSRF).

**Steps to Reproduce**
1. Create an HTML form on an external site targeting the `/sisp/payment` endpoint.
2. Submit the form while the user is authenticated.
3. The request is processed without a CSRF token check.

**Suggested Direction**
Apply the `web` middleware group to the `sisp/payment` route in `routes/web.php` or `SispServiceProvider`.

## Issue 7: Performance: `Sisp::getTransactions` Loads Entire Table

**Description**
The `Sisp::getTransactions()` method executes `Transaction::query()->get()`, which retrieves every row in the `sisp_transactions` table.

**Impact / Risk**
**Medium**. As the database grows, this will cause significant memory usage and slow response times.

**Steps to Reproduce**
1. Populate the `sisp_transactions` table with 10,000+ records.
2. Call `Sisp::getTransactions()`.
3. Monitor memory usage.

**Suggested Direction**
Refactor the method to return an `Illuminate\Database\Eloquent\Builder` instance or implement pagination.

## Issue 8: Compatibility: PHP 8.4 Syntax in `StoreTransactionItemsAction`

**Description**
The file `src/Actions/StoreTransactionItemsAction.php` uses the PHP 8.4 syntax `new TransactionItem()->getTable()` (instantiation without parentheses). This causes a syntax error in PHP 8.3 and below.

**Impact / Risk**
**Low**. Limits the package's compatibility.

**Steps to Reproduce**
1. Run the package tests in a PHP 8.3 environment.
2. Observe the Parse Error.

**Suggested Direction**
Wrap the instantiation in parentheses: `(new TransactionItem())->getTable()`.
