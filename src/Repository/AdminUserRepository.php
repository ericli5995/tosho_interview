<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Repository;
use App\Entity\AdminUser;

final class AdminUserRepository extends Repository
{
    public function findByEmail(string $email): ?AdminUser
    {
        $row = $this->fetch('SELECT * FROM admin_users WHERE email = ?', [$email]);

        return $row === null ? null : AdminUser::fromRow($row);
    }

    public function create(string $email, string $passwordHash, string $name): int
    {
        $this->execute(
            'INSERT INTO admin_users (email, password_hash, name, created_at) VALUES (?, ?, ?, ?)',
            [$email, $passwordHash, $name, date('Y-m-d H:i:s')]
        );

        return $this->lastId();
    }

    public function updatePassword(string $email, string $passwordHash): void
    {
        $this->execute(
            'UPDATE admin_users SET password_hash = ? WHERE email = ?',
            [$passwordHash, $email]
        );
    }

    public function touchLogin(int $id): void
    {
        $this->execute(
            'UPDATE admin_users SET last_login_at = ? WHERE id = ?',
            [date('Y-m-d H:i:s'), $id]
        );
    }

    public function count(): int
    {
        return (int) $this->scalar('SELECT COUNT(*) FROM admin_users');
    }
}
