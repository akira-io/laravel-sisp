<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\DTOs\PaymentRequestParams;
use Exception;

class PaymentRequestUrl
{
    private string $url;

    /**
     * @throws Exception
     */
    public function __construct(protected PaymentRequestParams $params)
    {
        $this->validateUrl();
    }

    public static function make(PaymentRequestParams $params): self
    {
        return app(self::class, compact('params'));
    }

    public function url(): string
    {
        return $this->url.'?FingerPrint='.urlencode($this->params->fingerPrint).
            '&TimeStamp='.urlencode($this->params->timeStamp).
            '&FingerPrintVersion='.urlencode($this->params->fingerPrintVersion);
    }

    private function validateUrl(): void
    {

        if (empty(config('sisp.url'))) {
            throw new \Exception('SISP URL is not set');
        }

        $this->url = config('sisp.url');
    }
}
