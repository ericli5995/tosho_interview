<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\App;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Entity\AdminUser;
use App\Repository\AdminUserRepository;
use App\Security\Auth;
use App\Security\Password;
use App\Validation\Validator;

/**
 * Admin accounts: list, create, reset password, delete. Every write is a
 * sensitive action, so on top of the session + CSRF middleware it re-checks
 * the caller's own password (`current_password`). No self-registration, no
 * roles, no email/name editing (out of scope).
 */
final class UserController extends Controller
{
    private const PASSWORD = 'required|min:8|max:72'; // 72: bcrypt's input limit
    private const LABELS = [
        'email' => 'メールアドレス', 'name' => '表示名', 'password' => 'パスワード', 'current_password' => '現在のパスワード',
    ];

    private AdminUserRepository $admins;

    public function __construct()
    {
        $this->admins = new AdminUserRepository(App::db());
    }

    /** GET /api/admin/users */
    public function index(): Response
    {
        return $this->json(['items' => array_map(static fn (AdminUser $u): array => $u->toArray(), $this->admins->all())]);
    }

    /** POST /api/admin/users  {email, name, password, current_password} */
    public function store(Request $request): Response
    {
        $data = [
            'email' => mb_strtolower(trim((string) $request->post('email', ''))),
            'name' => trim((string) $request->post('name', '')),
            'password' => (string) $request->post('password', ''),
        ];
        $errors = $this->validate($request, $data, ['email' => 'required|email|max:190', 'name' => 'string|max:120', 'password' => self::PASSWORD]);
        if (!isset($errors['email']) && $this->admins->findByEmail($data['email']) !== null) {
            $errors['email'][] = 'このメールアドレスは既に登録されています。';
        }
        if ($errors !== []) {
            return $this->error('入力内容を確認してください。', 422, $errors);
        }

        $id = $this->admins->create($data['email'], Password::hash($data['password']), $data['name'] !== '' ? $data['name'] : 'Administrator');

        return $this->json(['user' => $this->admins->find($id)?->toArray()], 201);
    }

    /** POST /api/admin/users/{id}/password  {password, current_password} - works on any admin, yourself included */
    public function resetPassword(Request $request, array $params): Response
    {
        $user = $this->admins->find((int) $params['id']);
        if ($user === null) {
            return $this->notFound();
        }
        $data = ['password' => (string) $request->post('password', '')];
        if ($errors = $this->validate($request, $data, ['password' => self::PASSWORD])) {
            return $this->error('入力内容を確認してください。', 422, $errors);
        }
        $this->admins->updatePassword($user->email, Password::hash($data['password']));

        return $this->json(['user' => $user->toArray()]);
    }

    /** DELETE /api/admin/users/{id}  {current_password} */
    public function destroy(Request $request, array $params): Response
    {
        $user = $this->admins->find((int) $params['id']);
        if ($user === null) {
            return $this->notFound();
        }
        if ($errors = $this->validate($request, [], [])) {
            return $this->error('入力内容を確認してください。', 422, $errors);
        }
        // You cannot delete yourself. Since the caller always exists, this also
        // guarantees the last admin can never be removed.
        if ($user->id === Auth::id()) {
            return $this->error('自分自身は削除できません。', 422, ['current_password' => ['自分自身は削除できません。']]);
        }
        $this->admins->delete($user->id);

        return Response::noContent();
    }

    /**
     * Field rules + the caller's current password. Returns field errors (empty when valid).
     *
     * @param array<string,mixed> $data
     * @param array<string,string> $rules
     * @return array<string,list<string>>
     */
    private function validate(Request $request, array $data, array $rules): array
    {
        $current = (string) $request->post('current_password', '');
        $validator = new Validator($data + ['current_password' => $current]);
        $validator->validate($rules + ['current_password' => 'required'], self::LABELS);
        $errors = $validator->errors();

        $me = Auth::user();
        if (!isset($errors['current_password']) && ($me === null || !Password::verify($current, $me->passwordHash))) {
            $errors['current_password'][] = '現在のパスワードが正しくありません。';
        }

        return $errors;
    }
}
