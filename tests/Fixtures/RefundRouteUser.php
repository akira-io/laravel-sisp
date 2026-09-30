<?php

declare(strict_types=1);

namespace Akira\Sisp\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

final class RefundRouteUser implements Authenticatable
{
    public function __construct(
        public int $id = 1,
        public string $email = 'buyer@example.com',
    ) {}

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): int
    {
        return $this->id;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void {}

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    public function can(string $ability, mixed $arguments = []): bool
    {
        return Gate::forUser($this)->check($ability, $arguments);
    }
}
