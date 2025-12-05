<?php

declare(strict_types=1);

use Akira\Sisp\Traits\EncryptsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class TmpEncrypted extends Model
{
    use EncryptsAttributes;
    protected $table = 'tmp_encrypts';
    protected $fillable = ['secret'];
    public $timestamps = false;

    protected function encryptable(): array
    {
        return ['secret'];
    }
}

it('encrypts and decrypts string attributes via trait', function (): void {
    Schema::create('tmp_encrypts', function (Blueprint $table): void {
        $table->id();
        $table->text('secret')->nullable();
    });

    $m = TmpEncrypted::query()->create(['secret' => 'hello-world']);
    $raw = \DB::table('tmp_encrypts')->where('id', $m->id)->value('secret');
    expect($raw)->not->toBe('hello-world');

    $found = TmpEncrypted::query()->find($m->id);
    expect($found->secret)->toBe('hello-world');
});

