<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Db;
use App\Entity\AdminUser;

final class AdminUserRepository
{
    public function __construct(private Db $db)
    {
    }

    public function find(int $id): ?AdminUser
    {
        $row = $this->db->fetch('SELECT * FROM admin_users WHERE id = ?', [$id]);

        return $row === null ? null : AdminUser::fromRow($row);
    }

    public function findByEmail(string $email): ?AdminUser
    {
        $row = $this->db->fetch('SELECT * FROM admin_users WHERE email = ?', [$email]);

        return $row === null ? null : AdminUser::fromRow($row);
    }

    public function create(string $email, string $passwordHash, string $name): int
    {
        return $this->db->insert('admin_users', [
            'email' => $email,
            'password_hash' => $passwordHash,
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function updatePassword(string $email, string $passwordHash): void
    {
        $this->db->execute(
            'UPDATE admin_users SET password_hash = ? WHERE email = ?',
            [$passwordHash, $email]
        );
    }

    public function touchLogin(int $id): void
    {
        $this->db->execute(
            'UPDATE admin_users SET last_login_at = ? WHERE id = ?',
            [date('Y-m-d H:i:s'), $id]
        );
    }
}
