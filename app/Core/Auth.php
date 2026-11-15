<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    private static ?array $cachedUser   = null;
    private static ?int   $cachedUserId = null;

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if ($user === null || !(int)$user['is_active']) {
            return false;
        }
        if (!password_verify($password, (string)$user['password_hash'])) {
            return false;
        }
        self::login($user);
        return true;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        Session::set('user_id',   (int)$user['id']);
        Session::set('user_role', (string)$user['role_name']);
        Session::set('user_name', (string)$user['name']);
        self::$cachedUser   = $user;
        self::$cachedUserId = (int)$user['id'];
        User::logEvent((int)$user['id'], 'login');
    }

    public static function logout(): void
    {
        $id = self::id();
        if ($id !== null) {
            User::logEvent($id, 'logout');
        }
        self::$cachedUser   = null;
        self::$cachedUserId = null;
        Session::forget('user_id');
        Session::forget('user_role');
        Session::forget('user_name');
        session_regenerate_id(true);
    }

    public static function id(): ?int
    {
        $id = Session::get('user_id');
        return $id ? (int)$id : null;
    }

    /**
     * Authoritative current user (cached per request).
     * Hits the DB once; later calls return from cache.
     * Returns null if the user was deleted or is inactive.
     */
    public static function user(): ?array
    {
        $id = self::id();
        if ($id === null) {
            return null;
        }
        if (self::$cachedUserId === $id && self::$cachedUser !== null) {
            return self::$cachedUser;
        }
        self::$cachedUserId = $id;
        $row = User::find($id);
        if ($row === null || !(int)$row['is_active']) {
            self::$cachedUser = null;
            return null;
        }
        self::$cachedUser = $row;
        return $row;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function role(): ?string
    {
        $u = self::user();
        return $u ? (string)$u['role_name'] : null;
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            flash('error', 'Please log in to continue.');
            redirect('login');
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireAuth();
        if (!in_array(self::role(), $roles, true)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            exit('403 Forbidden');
        }
    }
}