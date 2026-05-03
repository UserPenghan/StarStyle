<?php

declare(strict_types=1);

namespace App\Controllers;

final class AuthController extends BaseController
{
    public function login(): void
    {
        if ($this->auth()->check('internal')) {
            $this->redirect('/dashboard');
        }

        $this->view('pages/admin/login', [
            'title' => 'Login Internal',
            'page' => '/login',
            'success' => flash('success'),
            'error' => flash('error'),
        ], 'guest');
    }

    public function authenticate(): void
    {
        verify_csrf();
        remember_old_input($_POST);

        $ok = $this->auth()->attempt((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''), 'internal');

        if (!$ok) {
            flash('error', 'Email atau password internal tidak sesuai.');
            $this->redirect('/login');
        }

        clear_old_input();
        flash('success', 'Selamat datang kembali di portal internal StarStyle.');
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        $this->auth()->logout('internal');
        flash('success', 'Anda berhasil logout.');
        $this->redirect('/login');
    }
}
