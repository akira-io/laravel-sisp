# Security Policy

## Supported Versions

We release patches for security vulnerabilities for the following versions:

| Version | Supported          |
|---------|--------------------|
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

We take the security of Laravel SISP seriously. If you discover a security vulnerability, please follow these
guidelines:

### Please Do Not

- Open a public GitHub issue for security vulnerabilities
- Disclose the vulnerability publicly before it has been addressed
- Exploit the vulnerability beyond what is necessary to demonstrate it

### Please Do

1. **Email us directly** at kidiatoliny@akira-io.com with:
- Description of the vulnerability
- Steps to reproduce the issue
- Potential impact assessment
- Suggested fix (if available)

2. **Allow reasonable time** for us to respond and address the issue before public disclosure

3. **Act in good faith** - avoid privacy violations, data destruction, or service interruption

## Response Timeline

- **Initial Response**: Within 48 hours of report
- **Status Update**: Within 7 days with assessment and timeline
- **Resolution**: Depends on severity and complexity

## Security Best Practices

### Configuration

**Environment Variables**

Never commit sensitive credentials to version control:

```env
SISP_POS_ID=your_pos_id
SISP_POS_AUT_CODE=your_authorization_code
SISP_MERCHANT_ID=your_merchant_id
```

**Rate Limiting**

Enable and configure rate limiting to prevent abuse:

```php
'rate_limiting' => [
    'enabled' => true,
    'per_ip' => [
        'enabled' => true,
        'limit' => 100,
        'window_seconds' => 3600,
    ],
],
```

**Security Features**

Enable comprehensive security checks:

```php
'security' => [
    'collect_metadata' => true,
    'detect_vpn' => true,
    'detect_proxy' => true,
    'calculate_risk_score' => true,
    'block_vpn_proxy' => true,
],
```

### Data Protection

**Encryption**

Sensitive transaction payload data is automatically encrypted using the `EncryptsAttributes` trait. Ensure your
`APP_KEY` is properly configured and kept secure.

**Database Security**

- Use parameterized queries (handled by Eloquent)
- Implement proper database user permissions
- Enable SSL for database connections in production
- Regularly backup transaction data

### API Security

**Fingerprint Validation**

Always validate payment response fingerprints:

```php
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\CallbackPayload;

$payload = CallbackPayload::from($request->all());

if (!Sisp::validateCallback($payload)) {
    throw new InvalidPaymentResponseException();
}
```

**Webhook Protection**

The package includes middleware to prevent duplicate callbacks:

```php
Route::post('sisp/callback', CallbackController::class)
    ->middleware(PreventDuplicateCallback::class);
```

### Network Security

**HTTPS Only**

Always use HTTPS in production:

```php
URL::forceScheme('https');
```

**CORS Configuration**

Restrict CORS if using API endpoints:

```php
'allowed_origins' => [env('APP_URL')],
```

### Payment Security

**Amount Validation**

Validate amounts server-side before processing:

```php
public function rules(): array
{
    return [
        'amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
    ];
}
```

**Transaction Limits**

Configure daily and monthly transaction limits:

```php
'security' => [
    'max_amount_per_day' => 100000,
    'max_amount_per_month' => 500000,
],
```

### Access Control

**Middleware Protection**

All payment routes are protected with middleware:

```php
Route::post('sisp/payment', PaymentController::class)
    ->middleware(ProtectPaymentRoute::class);
```

**Authentication**

Implement authentication for sensitive operations:

```php
Route::post('sisp/refund', RefundTransactionController::class)
    ->middleware(['auth', 'can:refund-transactions']);
```

### Monitoring and Logging

**Security Events**

Monitor for suspicious activity:

- Multiple failed payment attempts
- High-risk score transactions
- VPN/proxy usage patterns
- Blacklist hits

**Audit Trail**

All transactions are logged with:

- Request metadata (IP, user agent, geolocation)
- Timestamps
- Status changes
- Refund and cancellation history

### Compliance

**Data Retention**

Configure appropriate data retention policies:

- Keep transaction records as required by law
- Implement data deletion procedures
- Anonymize old customer data

**GDPR Compliance**

For EU customers:

- Implement data export functionality
- Provide data deletion capabilities
- Obtain explicit consent for data collection
- Document data processing activities

**PCI DSS**

- Never store full card numbers
- Never store CVV codes
- Let SISP handle card data
- Use tokenization when available

### Regular Maintenance

**Updates**

- Keep Laravel SISP updated to the latest version
- Monitor security advisories
- Update dependencies regularly
- Run `composer audit` to check for vulnerable dependencies

**Testing**

- Run security-focused tests
- Perform penetration testing periodically
- Review access logs regularly
- Test backup and recovery procedures

### Incident Response

If a security incident occurs:

1. **Isolate** - Contain the incident immediately
2. **Assess** - Determine the scope and impact
3. **Notify** - Contact affected parties as required
4. **Document** - Record all details and actions taken
5. **Review** - Conduct post-incident analysis
6. **Improve** - Update security measures

## Security Features

### Built-in Protection

- **Rate Limiting**: Prevent brute force attacks
- **Blacklist Management**: Block malicious actors
- **Fingerprint Validation**: Verify payment responses
- **Metadata Collection**: Track suspicious behavior
- **Risk Scoring**: Identify high-risk transactions
- **VPN/Proxy Detection**: Flag anonymous transactions
- **Duplicate Prevention**: Avoid processing same callback twice

### Customization

Extend security by implementing custom checks:

```php
// Custom security action
class CustomSecurityCheckAction
{
    public function handle(Request $request): void
    {
        // Your custom security logic
    }
}
```

## Third-Party Dependencies

This package uses trusted dependencies:

- `spatie/laravel-package-tools` - Package scaffolding
- `stevebauman/location` - Geolocation detection
- `akira/laravel-pdf-invoices` - Invoice generation

All dependencies are regularly audited for security vulnerabilities.

## Responsible Disclosure

We appreciate security researchers who:

- Follow responsible disclosure practices
- Give us time to address issues before public disclosure
- Provide detailed reports
- Suggest remediation when possible

Security contributors will be acknowledged in our release notes (unless they prefer to remain anonymous).

## Contact

For security concerns, contact:

**Email**: kidiatoliny@akira-io.com

**PGP Key**: Available upon request

---

**Last Updated**: December 2025
