<?php

declare(strict_types=1);

use Akira\Sisp\Actions\ValidatePaymentResponseFingerprintAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Mcp\Servers\SispDevServer;
use Akira\Sisp\Mcp\Tools\Dev\ConfigReferenceTool;
use Akira\Sisp\Mcp\Tools\Dev\CountryReferenceTool;
use Akira\Sisp\Mcp\Tools\Dev\DoctorTool;
use Akira\Sisp\Mcp\Tools\Dev\EnumReferenceTool;
use Akira\Sisp\Mcp\Tools\Dev\EnvScaffoldTool;
use Akira\Sisp\Mcp\Tools\Dev\GetDocTool;
use Akira\Sisp\Mcp\Tools\Dev\SearchDocsTool;
use Akira\Sisp\Mcp\Tools\Dev\SimulateSandboxCallbackTool;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Illuminate\Support\Facades\Storage;

it('searches the documentation', function (): void {
    SispDevServer::tool(SearchDocsTool::class, ['query' => 'idempotency'])
        ->assertOk()
        ->assertSee('idempotency');
});

it('rejects an empty docs query', function (): void {
    SispDevServer::tool(SearchDocsTool::class, ['query' => '  '])
        ->assertHasErrors(['non-empty "query"']);
});

it('lists enum cases', function (): void {
    SispDevServer::tool(EnumReferenceTool::class, ['enum' => 'transaction_status'])
        ->assertOk()
        ->assertSee('completed')
        ->assertSee('refunded');
});

it('rejects an unknown enum', function (): void {
    SispDevServer::tool(EnumReferenceTool::class, ['enum' => 'nope'])
        ->assertHasErrors(['Unknown enum "nope"']);
});

it('returns the numeric code for a country', function (): void {
    SispDevServer::tool(CountryReferenceTool::class, ['alpha2' => 'cv'])
        ->assertOk()
        ->assertSee('132');
});

it('describes a config key', function (): void {
    SispDevServer::tool(ConfigReferenceTool::class, ['key' => 'currency'])
        ->assertOk()
        ->assertSee('currency');
});

it('redacts the pos auth code in the config reference', function (): void {
    SispDevServer::tool(ConfigReferenceTool::class, ['key' => 'posAutCode'])
        ->assertOk()
        ->assertSee('redacted');
});

it('scaffolds env variables', function (): void {
    SispDevServer::tool(EnvScaffoldTool::class, ['mode' => 'production'])
        ->assertOk()
        ->assertSee('SISP_POS_ID');
});

it('runs diagnostics without throwing', function (): void {
    SispDevServer::tool(DoctorTool::class, [])
        ->assertOk()
        ->assertSee('invoices');
});

it('simulates a sandbox callback without persisting a transaction', function (): void {
    config()->set('sisp.sandbox', true);

    SispDevServer::tool(SimulateSandboxCallbackTool::class, ['amount' => 100, 'status' => 'success'])
        ->assertOk();

    expect(Transaction::query()->count())->toBe(0);
});

it('redacts every credential nested in the config reference', function (): void {
    config()->set('sisp.posAutCode', 'POS-AUT-SECRET');
    config()->set('sisp.transaction_status.portal_password', 'PORTAL-SECRET');
    config()->set('sisp.geolocation.maxmind_key', 'MAXMIND-SECRET');
    config()->set('sisp.geolocation.ip_api_key', 'IPAPI-SECRET');

    SispDevServer::tool(ConfigReferenceTool::class, [])
        ->assertOk()
        ->assertSee(['transaction-status', 'request_keys'])
        ->assertDontSee(['POS-AUT-SECRET', 'PORTAL-SECRET', 'MAXMIND-SECRET', 'IPAPI-SECRET']);
});

it('refuses to sign a callback outside sandbox mode', function (): void {
    config()->set('sisp.sandbox', false);
    config()->set('sisp.driver', 'production');

    SispDevServer::tool(SimulateSandboxCallbackTool::class, ['amount' => 100])
        ->assertHasErrors(['sandbox mode']);
});

it('signs a simulated failure with the error fingerprint formula', function (): void {
    config()->set('sisp.sandbox', true);

    $response = SispDevServer::tool(SimulateSandboxCallbackTool::class, ['amount' => 100, 'status' => 'failed'])
        ->assertOk();

    $content = new ReflectionProperty($response, 'response')->getValue($response)->toArray()['result']['content'][0]['text'];
    $callback = CallbackPayload::from(json_decode((string) $content, true)['callback']);

    expect($callback->isError())->toBeTrue()
        ->and(resolve(ValidatePaymentResponseFingerprintAction::class)->handle($callback))->toBeTrue();
});

it('diagnoses storage without writing to the invoice disk', function (): void {
    Storage::fake('public');

    SispDevServer::tool(DoctorTool::class, [])
        ->assertOk()
        ->assertSee('accessible');

    expect(Storage::disk('public')->allFiles())->toBe([])
        ->and(Storage::disk('public')->allDirectories())->toBe([]);
});

it('scaffolds only variables that config/sisp.php reads', function (string $mode): void {
    $config = (string) file_get_contents(dirname(__DIR__, 3).'/config/sisp.php');

    $response = SispDevServer::tool(EnvScaffoldTool::class, ['mode' => $mode])->assertOk();
    $env = json_decode(new ReflectionProperty($response, 'response')->getValue($response)->toArray()['result']['content'][0]['text'], true)['env'];

    preg_match_all('/^([A-Z0-9_]+)=/m', $env, $matches);

    expect($matches[1])->not->toBeEmpty()
        ->each(fn ($variable) => $variable->toBeIn(preg_match_all("/env\\('([A-Z0-9_]+)'/", $config, $read) ? $read[1] : []));
})->with(['sandbox', 'production']);

it('marks the sandbox scaffold as sandbox', function (): void {
    SispDevServer::tool(EnvScaffoldTool::class, [])
        ->assertOk()
        ->assertSee(['SISP_SANDBOX=true', 'simulate-sandbox-callback-tool']);
});

it('returns a documentation page by slug', function (): void {
    SispDevServer::tool(GetDocTool::class, ['doc' => '15-mcp'])
        ->assertOk()
        ->assertSee('# 15. MCP Server');
});

it('does not read files outside the docs folder', function (string $doc): void {
    SispDevServer::tool(GetDocTool::class, ['doc' => $doc])
        ->assertHasErrors(['Unknown doc'])
        ->assertDontSee(['<?php', '"name": "akira/laravel-sisp"']);
})->with(['../composer', '../../README', '../src/Sisp.php', '/etc/passwd', '']);

it('lists every supported country', function (): void {
    SispDevServer::tool(CountryReferenceTool::class, [])
        ->assertOk()
        ->assertSee('countries');
});

it('rejects an unknown country code', function (): void {
    SispDevServer::tool(CountryReferenceTool::class, ['alpha2' => 'zz'])
        ->assertHasErrors(['Unknown country code']);
});

it('rejects an unknown config key', function (): void {
    SispDevServer::tool(ConfigReferenceTool::class, ['key' => 'nope'])
        ->assertHasErrors(['Unknown config key']);
});

it('caps the number of documentation matches', function (): void {
    $response = SispDevServer::tool(SearchDocsTool::class, ['query' => 'sisp', 'limit' => 1])->assertOk();
    $matches = json_decode(new ReflectionProperty($response, 'response')->getValue($response)->toArray()['result']['content'][0]['text'], true)['matches'];

    expect($matches)->toHaveCount(1);
});

it('reports a documentation search with no match', function (): void {
    SispDevServer::tool(SearchDocsTool::class, ['query' => 'zzqqxx-no-such-term'])
        ->assertOk()
        ->assertSee('No documentation sections matched');
});

it('embeds the given payment shape in the simulated callback', function (): void {
    config()->set('sisp.sandbox', true);

    SispDevServer::tool(SimulateSandboxCallbackTool::class, [
        'amount' => 250,
        'currency' => '132',
        'locale' => 'pt',
        'customerEmail' => 'buyer@example.com',
    ])->assertOk()->assertSee('"merchantRespPurchaseAmount":250');
});

it('reports an unreachable invoice disk', function (): void {
    config()->set('sisp.invoice.disk', 'not-configured');

    SispDevServer::tool(DoctorTool::class, [])
        ->assertOk()
        ->assertSee(['"accessible":false', 'InvalidArgumentException']);
});

it('includes enum labels when the enum provides them', function (): void {
    SispDevServer::tool(EnumReferenceTool::class, ['enum' => 'transaction_code'])
        ->assertOk()
        ->assertSee('"label"');
});

it('answers a stored transaction so the callback pipeline accepts it', function (): void {
    config()->set('sisp.sandbox', true);
    config()->set('sisp.use_blade', false);
    $transaction = Transaction::factory()->pending()->create(['amount' => 1250.0, 'merchant_ref' => 'REF-SANDBOX']);

    $response = SispDevServer::tool(SimulateSandboxCallbackTool::class, ['transaction' => 'REF-SANDBOX'])->assertOk();
    $callback = json_decode(new ReflectionProperty($response, 'response')->getValue($response)->toArray()['result']['content'][0]['text'], true)['callback'];

    expect($callback['merchantRespMerchantRef'])->toBe('REF-SANDBOX')
        ->and($callback['merchantRespMerchantSession'])->toBe($transaction->merchant_session);

    $this->post(route('sisp.callback'), $callback);

    expect($transaction->fresh()->status)->toBe(TransactionStatus::completed);
});

it('reports an unknown transaction to simulate', function (): void {
    config()->set('sisp.sandbox', true);

    SispDevServer::tool(SimulateSandboxCallbackTool::class, ['transaction' => 'REF-NOPE'])
        ->assertHasErrors(['No transaction found for "REF-NOPE".']);
});

it('requires an amount or a transaction to simulate', function (): void {
    SispDevServer::tool(SimulateSandboxCallbackTool::class, [])
        ->assertHasErrors(['amount']);
});
