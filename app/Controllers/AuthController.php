<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\User;

final class AuthController
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 900; // 15 minutes

    // ---------------- Login ----------------

    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('dashboard');
        }

        $old = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);

        $title   = 'Log In';
        $content = View::render('auth.login', ['old' => $old]);
        require BASE_PATH . '/app/Views/layouts/main.php';
    }

    public function login(): void
    {
        CSRF::checkOrAbort($_POST['_csrf'] ?? null);

        $email    = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        // Throttle check
        $attempts = $_SESSION['login_attempts']
            ?? ['count' => 0, 'locked_until' => 0];
        if ((int)$attempts['locked_until'] > time()) {
            $remaining = (int)$attempts['locked_until'] - time();
            flash('error', 'Too many failed attempts. Try again in '
                . (int)ceil($remaining / 60) . ' min.');
            redirect('login');
        }

        $v = new Validator(
            ['email' => $email, 'password' => $password],
            ['email' => 'required|email', 'password' => 'required']
        );
        if ($v->fails()) {
            $_SESSION['_old'] = ['email' => $email];
            flash('error', (string)$v->firstError());
            redirect('login');
        }

        if (!Auth::attempt($email, $password)) {
            $attempts['count'] = (int)$attempts['count'] + 1;
            if ($attempts['count'] >= self::MAX_ATTEMPTS) {
                $attempts['locked_until'] = time() + self::LOCK_SECONDS;
                $attempts['count']        = 0;
            }
            $_SESSION['login_attempts'] = $attempts;
            $_SESSION['_old'] = ['email' => $email];
            User::logEvent(null, 'login_failed', 'email=' . $email);
            flash('error', 'Invalid credentials.');
            redirect('login');
        }

        unset($_SESSION['login_attempts'], $_SESSION['_old']);
        flash('success', 'Welcome back, ' . (string)Session::get('user_name') . '!');
        redirect('dashboard');
    }

    // ---------------- Register ----------------

    public function showRegister(): void
    {
        if (Auth::check()) {
            redirect('dashboard');
        }

        $old = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);

        $title   = 'Sign Up';
        $content = View::render('auth.register', ['old' => $old]);
        require BASE_PATH . '/app/Views/layouts/main.php';
    }

        public function register(): void
    {
        CSRF::checkOrAbort($_POST['_csrf'] ?? null);

        $name     = trim((string)($_POST['name'] ?? ''));
        $email    = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['password_confirmation'] ?? '');
        $role     = (string)($_POST['role'] ?? 'tourist');

        // Security: Only allow 'tourist' or 'researcher' during sign-up. 
        // Admin accounts can only be created via the CLI or another Admin.
        if (!in_array($role, ['tourist', 'researcher'], true)) {
            $role = 'tourist';
        }

        $v = new Validator(
            [
                'name'                  => $name,
                'email'                 => $email,
                'password'              => $password,
                'password_confirmation' => $confirm,
            ],
            [
                'name'                  => 'required|max:100',
                'email'                 => 'required|email|max:190|unique:users,email',
                'password'              => 'required|min:8|max:255',
                'password_confirmation' => 'required|same:password',
            ]
        );

        if ($v->fails()) {
            // Save role to old input so the toggle stays selected on error
            $_SESSION['_old'] = ['name' => $name, 'email' => $email, 'role' => $role];
            flash('error', (string)$v->firstError());
            redirect('register');
        }

        // Create the user with the selected role
        $id = User::create($email, $password, $name, $role);
        
        // Only tourists get a tourist_profile
        if ($role === 'tourist') {
            User::ensureTouristProfile($id);
        }

        $user = User::findByEmail($email);
        if ($user !== null) {
            Auth::login($user);
        }

        unset($_SESSION['_old']);
        flash('success', 'Account created. Welcome, ' . $name . '!');
        redirect('dashboard');
    }

    // ---------------- Logout ----------------

    public function logout(): void
    {
        CSRF::checkOrAbort($_POST['_csrf'] ?? null);
        Auth::logout();
        flash('success', 'You have been logged out.');
        redirect('/');
    }
}