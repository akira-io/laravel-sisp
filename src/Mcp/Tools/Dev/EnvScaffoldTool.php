<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Tools\Dev;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Produce the .env variables a host application must set to use laravel-sisp, tailored to sandbox or production.')]
final class EnvScaffoldTool extends Tool
{
    public function handle(Request $request): Response
    {
        $mode = $request->get('mode') === 'production' ? 'production' : 'sandbox';

        $lines = [
            '# laravel-sisp configuration ('.$mode.')',
            'SISP_URL='.($mode === 'production'
                ? 'https://mc.vinti4net.cv/biz_vbv_clientdata/biz_vbv_clientdata.jsp'
                : 'https://mc.vinti4net.cv/BizMPIOnUs_PP/CardPayment'),
            'SISP_POS_ID=your-pos-id',
            'SISP_POS_AUT_CODE=your-pos-authorisation-code',
            'SISP_MERCHANT_ID=your-merchant-id',
            'SISP_CURRENCY=132',
            'SISP_LANGUAGE_MESSAGES=PT',
            'SISP_IS_3DSEC='.($mode === 'production' ? '1' : '0'),
            'SISP_URL_MERCHANT_RESPONSE=https://your-app.test/sisp/callback',
        ];

        if ($mode === 'sandbox') {
            $lines[] = 'SISP_SANDBOX=true';
        }

        $guidance = [
            'POS credentials (SISP_POS_ID, SISP_POS_AUT_CODE, SISP_MERCHANT_ID) are issued by SISP.',
            'SISP_URL_MERCHANT_RESPONSE must be a publicly reachable HTTPS URL hitting the /sisp/callback route.',
            $mode === 'production'
                ? '3D Secure is enabled: collect customer email, country, city, address, and phone.'
                : 'Use the sisp-ops "build_payment_request" and the dev "simulate_sandbox_callback" tools to test without live credentials.',
        ];

        return Response::json([
            'mode' => $mode,
            'env' => implode("\n", $lines),
            'guidance' => $guidance,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'mode' => $schema->string()
                ->description('Target environment for the generated variables.')
                ->enum(['sandbox', 'production'])
                ->default('sandbox'),
        ];
    }
}
