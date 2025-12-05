<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;

it('redirects duplicate callback requests', function (): void {
    $t = Transaction::factory()->create([
        'merchant_ref' => 'MR-CB-DUP',
        'merchant_session' => 'MS-CB-DUP',
        'transaction_id' => 'T-EXISTS',
    ]);

    config()->set('sisp.redirect_url', '/home');

    $this->post(route('sisp.callback'), [
        'merchantRespMerchantRef' => 'MR-CB-DUP',
        'merchantRespMerchantSession' => 'MS-CB-DUP',
    ])->assertRedirect('/home');
});
