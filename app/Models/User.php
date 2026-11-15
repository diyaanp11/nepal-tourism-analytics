<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use RuntimeException;

final class User
{
    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.id, u.name, u.email, u.role_id, u.is_active, u.created_at,
                    r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.*, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $email, string $password, string $name, string $roleName): int
    {
        $conn = Database::connection();

        $roleStmt = $conn->prepare('SELECT id FROM roles WHERE name = ? LIMIT 1');
        $roleStmt->execute([$roleName]);
        $role = $roleStmt->fetch();
        if (!$role) {
            throw new RuntimeException('Unknown role: ' . $roleName);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare(
            'INSERT INTO users (role_id, name, email, password_hash) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([(int)$role['id'], $name, $email, $hash]);
        return (int)$conn->lastInsertId();
    }

    public static function ensureTouristProfile(int $userId): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO tourist_profiles (user_id) VALUES (?)'
        );
        $stmt->execute([$userId]);
    }

    public static function logEvent(?int $userId, string $action, ?string $details = null): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}