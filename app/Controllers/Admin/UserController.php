<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Database;
use App\Core\View;

final class UserController
{
    public function index(): void
    {
        Auth::requireRole('admin');
        $users = Database::connection()->query("
            SELECT u.*, r.name as role_name 
            FROM users u JOIN roles r ON r.id = u.role_id 
            ORDER BY u.created_at DESC
        ")->fetchAll();

        $title = 'Users';
        $content = View::render('admin.users', ['users' => $users]);
        require BASE_PATH . '/app/Views/layouts/main.php';
    }

    public function store(): void
    {
        Auth::requireRole('admin');
        CSRF::checkOrAbort($_POST['_csrf'] ?? null);

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';

        if (!in_array($role, ['admin', 'researcher'], true)) {
            flash('error', 'Invalid role for creation.');
            redirect('admin/users');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Invalid email address.');
            redirect('admin/users');
        }

        if ($name && $email && strlen($password) >= 8) {
            try {
                $stmtRole = Database::connection()->prepare("SELECT id FROM roles WHERE name = ?");
                $stmtRole->execute([$role]);
                $roleId = $stmtRole->fetchColumn();

                if ($roleId) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = Database::connection()->prepare("INSERT INTO users (role_id, name, email, password_hash) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$roleId, $name, $email, $hash]);
                    flash('success', 'User created successfully.');
                } else {
                    flash('error', 'Role not found in database.');
                }
            } catch (\Exception $e) {
                flash('error', 'Email already exists or database error.');
            }
        } else {
            flash('error', 'Invalid input. Password must be at least 8 characters.');
        }
        redirect('admin/users');
    }

    public function toggle(int $id): void
    {
        Auth::requireRole('admin');
        CSRF::checkOrAbort($_POST['_csrf'] ?? null);

        if ($id === Auth::id()) {
            flash('error', 'You cannot deactivate your own account.');
            redirect('admin/users');
        }

        $stmt = Database::connection()->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'User status toggled.');
        redirect('admin/users');
    }
}