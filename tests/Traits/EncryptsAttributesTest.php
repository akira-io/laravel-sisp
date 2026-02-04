<?php

declare(strict_types=1);

use Akira\Sisp\Traits\EncryptsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class TmpEncrypted extends Model
{
    use EncryptsAttributes;
    use Illuminate\Database\Eloquent\Factories\HasFactory;

    public $timestamps = false;

    protected $table = 'tmp_encrypts';

    protected $fillable = ['secret'];

    protected function encryptable(): array
    {
        return ['secret'];
    }
}

final class TmpEncryptedSelective extends Model
{
    use EncryptsAttributes;
    use Illuminate\Database\Eloquent\Factories\HasFactory;

    public $timestamps = false;

    protected $table = 'tmp_encrypts_selective';

    protected $fillable = ['secret', 'note'];

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
    use Illuminate\Database\Eloquent\Factories\HasFactory;

    public $timestamps = false;

    protected $table = 'tmp_encrypts_all';

    protected $fillable = ['notes'];
}

final class TmpEncryptedAccessor extends Model
{
    use EncryptsAttributes;
    use Illuminate\Database\Eloquent\Factories\HasFactory;

    public $timestamps = false;

    protected $table = 'tmp_encrypts_accessor';

    protected $fillable = ['secret'];

    protected function encryptable(): array
    {
        return ['secret'];
    }

    protected function getSecretAttribute($value): string
    {
        return Crypt::encryptString('accessor-secret');
    }
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

it('decrypts when accessor returns encrypted value and raw attribute is null', function (): void {
    Schema::create('tmp_encrypts_accessor', function (Blueprint $table): void {
        $table->id();
        $table->text('secret')->nullable();
    });

    $m = TmpEncryptedAccessor::query()->create(['secret' => null]);
    $found = TmpEncryptedAccessor::query()->find($m->id);
    expect($found->secret)->toBe('accessor-secret');
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

    $found = (new TmpEncrypted())->setTable('tmp_encrypts_non_str')->find($m->id);
    expect($found->secret)->toBeNull();
});

it('does not double-encrypt values already encrypted', function (): void {
    Schema::create('tmp_encrypts2', function (Blueprint $table): void {
        $table->id();
        $table->text('secret')->nullable();
    });

    $already = Crypt::encryptString('already');

    $m = new TmpEncrypted();
    $m->setTable('tmp_encrypts2');
    $m->secret = $already;
    $m->save();

    $raw = DB::table('tmp_encrypts2')->where('id', $m->id)->value('secret');
    expect($raw)->toBe($already);

    $found = (new TmpEncrypted())->setTable('tmp_encrypts2')->find($m->id);
    expect($found->secret)->toBe('already');
});

it('does not encrypt attributes not in encryptable list', function (): void {
    Schema::create('tmp_encrypts_selective', function (Blueprint $table): void {
        $table->id();
        $table->text('secret')->nullable();
        $table->text('note')->nullable();
    });

    $m = TmpEncryptedSelective::query()->create([
        'secret' => 'top-secret',
        'note' => 'plain-note',
    ]);

    $row = DB::table('tmp_encrypts_selective')->where('id', $m->id)->first();
    expect($row->secret)->not->toBe('top-secret')
        ->and($row->note)->toBe('plain-note');

    $found = TmpEncryptedSelective::query()->find($m->id);
    expect($found->secret)->toBe('top-secret')
        ->and($found->note)->toBe('plain-note');
});

it('falls back when raw attribute decryption fails and returns original', function (): void {
    Schema::create('tmp_encrypts_fail', function (Blueprint $table): void {
        $table->id();
        $table->text('secret')->nullable();
    });

    $id = DB::table('tmp_encrypts_fail')->insertGetId(['secret' => 'bogus']);

    $model = new TmpEncrypted();
    $model->setTable('tmp_encrypts_fail');
    $found = $model->find($id);

    expect($found->secret)->toBe('bogus');
});

it('falls back when payload is tampered (MAC invalid) but looks encrypted', function (): void {
    Schema::create('tmp_encrypts_tamper', function (Blueprint $table): void {
        $table->id();
        $table->text('secret')->nullable();
    });

    // Create valid encrypted string
    $valid = Crypt::encryptString('valid');
    // Decode, modify, re-encode to invalidate MAC but keep structure
    $payload = json_decode(base64_decode($valid), true);
    $payload['value'] = base64_encode('tampered'); // Change value without updating MAC
    $tampered = base64_encode(json_encode($payload));

    $id = DB::table('tmp_encrypts_tamper')->insertGetId(['secret' => $tampered]);

    $model = new TmpEncrypted();
    $model->setTable('tmp_encrypts_tamper');
    $found = $model->find($id);

    // Should return raw because decryption failed (MAC mismatch)
    expect($found->secret)->toBe($tampered);
});

it('does not assume encrypted if keys are missing in JSON', function (): void {
    Schema::create('tmp_encrypts_keys', function (Blueprint $table): void {
        $table->id();
        $table->text('secret')->nullable();
    });

    // Base64 encoded JSON but missing 'iv'/'mac'
    $fake = base64_encode(json_encode(['foo' => 'bar']));
    $id = DB::table('tmp_encrypts_keys')->insertGetId(['secret' => $fake]);

    $model = new TmpEncrypted();
    $model->setTable('tmp_encrypts_keys');
    $found = $model->find($id);

    // Should be treated as plain text (not decrypted)
    expect($found->secret)->toBe($fake);
});

it('encrypts values starting with JSON brackets if not encrypted structure', function (): void {
    Schema::create('tmp_encrypts_json_start', function (Blueprint $table): void {
        $table->id();
        $table->text('secret')->nullable();
    });

    // Use invalid JSON to ensure it comes back as string (auto-json-decoding won't happen)
    // This tests that just starting with '{' doesn't trick isEncrypted into thinking it's already encrypted
    $json = '{ not json }';
    $m = new TmpEncrypted();
    $m->setTable('tmp_encrypts_json_start');
    $m->secret = $json;
    $m->save();

    $raw = DB::table('tmp_encrypts_json_start')->where('id', $m->id)->value('secret');
    expect($raw)->not->toBe($json); // Should be encrypted

    $found = (new TmpEncrypted())->setTable('tmp_encrypts_json_start')->find($m->id);
    expect($found->secret)->toBe($json);
});
