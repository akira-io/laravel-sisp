<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\DTOs\PaymentRequestParams;

class PaymentRequestUrl
{
    private string $url;

    public function __construct(protected PaymentRequestParams $params)
    {
        $this->url = config('sisp.url');
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
}
