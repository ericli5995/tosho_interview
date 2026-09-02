<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Security\Auth;

final class SessionController extends Controller
{
    public function showLogin(Request $request): Response
    {
        if (Auth::check()) {
            return $this->redirect('/admin');
        }

        return $this->view('admin/login', ['title' => 'ログイン'], 'layouts/admin');
    }

    public function login(Request $request): Response
    {
        $email = trim((string) $request->post('email', ''));
        $password = (string) $request->post('password', '');

        if ($email === '' || $password === '' || !Auth::attempt($email, $password)) {
            flash_set('error', 'メールアドレスまたはパスワードが正しくありません。');
            set_old(['email' => $email]);

            return $this->redirect('/admin/login');
        }

        flash_set('success', 'ログインしました。');

        return $this->redirect('/admin');
    }

    public function logout(Request $request): Response
    {
        Auth::logout();
        flash_set('success', 'ログアウトしました。');

        return $this->redirect('/admin/login');
    }
}
