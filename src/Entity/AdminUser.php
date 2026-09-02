<?php

declare(strict_types=1);

namespace App\Entity;

final class AdminUser
{
    public function __construct(
        public int $id = 0,
        public string $email = '',
        public string $passwordHash = '',
        public string $name = 'Administrator',
        public ?string $lastLoginAt = null,
        public ?string $createdAt = null,
    ) {
    }

    /** @param array<string,mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            email: (string) $row['email'],
            passwordHash: (string) $row['password_hash'],
            name: (string) ($row['name'] ?? 'Administrator'),
            lastLoginAt: $row['last_login_at'] ?? null,
            createdAt: $row['created_at'] ?? null,
        );
    }
}
