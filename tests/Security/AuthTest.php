<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Core\App;
use App\Core\Db;
use App\Entity\AdminUser;
use App\Security\Auth;
use App\Security\Password;
use PDO;
use PHPUnit\Framework\TestCase;


final class AuthTest extends TestCase
{
    /** 为了测试目的准备的创建admin_users的SQL语句 */
    private const ADMIN_USERS_TABLE = <<<'SQL'
        CREATE TABLE admin_users (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            email         TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            name          TEXT NOT NULL DEFAULT 'Administrator',
            last_login_at TEXT NULL,
            created_at    TEXT NOT NULL
        )
        SQL;

    private Db $db;

    /** 测试的准备工作 */
    protected function setUp(): void
    {
        /** 为session定义测试时的存储路径，创建测试用数据库 */
        defined('BASE_PATH') || define('BASE_PATH', sys_get_temp_dir());
        $this->db = new Db(new PDO('sqlite::memory:'));
        $this->db->execute(self::ADMIN_USERS_TABLE);
        App::bind('db', $this->db);
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    private function createAdmin(string $email = 'admin@example.com', string $password = 'password123', ?string $hash = null): int
    {
        return $this->db->insert('admin_users', [
            'email' => $email,
            'password_hash' => $hash ?? Password::hash($password),
            'name' => 'Administrator',
            'created_at' => '2026-01-01 00:00:00',
        ]);
    }



/** 以下为单元测试 */


    /** 测试正确的账户密码可以登录 */
    public function testAttemptWithCorrectCredentialsLogsIn(): void
    {
        $id = $this->createAdmin();

        $this->assertTrue(Auth::attempt('admin@example.com', 'password123'));
        $this->assertTrue(Auth::check());
        $this->assertSame($id, Auth::id());
        $this->assertInstanceOf(AdminUser::class, Auth::user());
        $this->assertSame('admin@example.com', Auth::user()?->email);
    }

    /** 错误的密码或者错误的邮箱其中有一样都无法成功登录 */

    public function testLoginWouldFail(): void
    {
        $this->createAdmin();

        $this->assertFalse(Auth::attempt('admin@example.com', 'wrong'));
        $this->assertFalse(Auth::attempt('nobody@example.com', 'password123'));
        $this->assertFalse(Auth::attempt('', ''));
        $this->assertFalse(Auth::check(), '');
    }

    /** 
     * 测试新的登陆会生成新的Session
     */
    public function testLoginRegenerateSessionId(): void
    {
        $this->createAdmin();
        Auth::check();               
        $oldSessionId = session_id();

        Auth::attempt('admin@example.com', 'password123');

        $newSessionId = session_id();
        $this->assertNotSame($oldSessionId, $newSessionId);
    }

    /** 测试退出登录会清除身份并生成新的Session ID */
    public function testLogOut(): void
    {
        $this->createAdmin();
        Auth::attempt('admin@example.com', 'password123');
        $oldSessionId = session_id();
        Auth::logout();
        $newSessionId = session_id();

        $this->assertFalse(Auth::check());
        $this->assertNull(Auth::id());
        $this->assertNull(Auth::user());
        $this->assertNotSame($oldSessionId, $newSessionId);
    }

    /** 过期的 session 会导致admin用户无法认证 */
    public function testExpiredSessionNoLongerAuthenticates(): void
    {
        $this->createAdmin();
        Auth::attempt('admin@example.com', 'password123');

        session_destroy();
        $this->assertFalse(Auth::check());
        $this->assertNull(Auth::user());
    }
}
