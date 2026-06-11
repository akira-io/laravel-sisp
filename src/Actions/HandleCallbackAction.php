<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Pipelines\Callback\CallbackContext;
use Akira\Sisp\Pipelines\Callback\HandleCallbackPipeline;
use Akira\Sisp\ValueObjects\CallbackPayload;

final readonly class HandleCallbackAction
{
    public function __construct(private HandleCallbackPipeline $pipeline) {}

    public function handle(CallbackPayload $payload): Transaction
    {
        $context = $this->pipeline->run(new CallbackContext($payload));

        return $context->transaction();
    }
}
