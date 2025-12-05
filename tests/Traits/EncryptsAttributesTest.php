<?php

declare(strict_types=1);

use Akira\Sisp\Traits\EncryptsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class TmpEncrypted extends Model
{
    use EncryptsAttributes;

    public $timestamps = false;

    protected $table = 'tmp_encrypts';

    protected $fillable = ['secret'];

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
    $raw = DB::table('tmp_encrypts')->where('id', $m->id)->value('secret');
    expect($raw)->not->toBe('hello-world');

    $found = TmpEncrypted::query()->find($m->id);
    expect($found->secret)->toBe('hello-world');
});

it('encrypts and decrypts array attributes via trait', function (): void {
    Schema::create('tmp_encrypts', function (Blueprint $table): void {
        $table->id();
        $table->text('secret')->nullable();
    });

    $m = TmpEncrypted::query()->create(['secret' => ['hello-world', 'goodbye-world', 'secret' => 'secret-value']]);
    $raw = DB::table('tmp_encrypts')->where('id', $m->id)->value('secret');
    expect($raw)->not->toBe('hello-world');

    $found = TmpEncrypted::query()->find($m->id);
    expect($found->secret)->toBe(['hello-world', 'goodbye-world', 'secret' => 'secret-value']);
});

final class TmpEncryptAll extends Model
{
    use EncryptsAttributes;

    public $timestamps = false;

    protected $table = 'tmp_encrypts_all';

    protected $fillable = ['notes'];
}

it('encrypts all attributes when encryptable list is empty', function (): void {
    Schema::create('tmp_encrypts_all', function (Blueprint $table): void {
        $table->id();
        $table->text('notes')->nullable();
    });

    $m = TmpEncryptAll::query()->create(['notes' => 'secret-note']);
    $raw = DB::table('tmp_encrypts_all')->where('id', $m->id)->value('notes');
    expect($raw)->not->toBe('secret-note');

    $found = TmpEncryptAll::query()->find($m->id);
    expect($found->notes)->toBe('secret-note');
});

it('does not encrypt non-string values', function (): void {
    Schema::create('tmp_encrypts_non_str', function (Blueprint $table): void {
        $table->id();
        $table->text('secret')->nullable();
    });

    // Reuse TmpEncrypted but pass non-string
    $m = new TmpEncrypted();
    $m->setTable('tmp_encrypts_non_str');
    $m->secret = null; // not string, should pass through
    $m->save();

    $found = new TmpEncrypted()->setTable('tmp_encrypts_non_str')->find($m->id);
    expect($found->secret)->toBeNull();
});
