<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;
use App\Models\User;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script can only be run from the command line.\n");
}

$email    = $argv[1] ?? null;
$password = $argv[2] ?? null;
$name     = $argv[3] ?? 'Administrator';

if (!$email || !$password) {
    fwrite(STDERR, "Usage: php database/seed_admin.php <email> <password> [full_name]\n");
    exit(1);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Invalid email.\n");
    exit(1);
}
if (strlen($password) < 8) {
    fwrite(STDERR, "Password must be at least 8 characters.\n");
    exit(1);
}

// System lookup: the three roles the app is designed around.
// This is not demo data — these role rows are structural requirements
// for RBAC and are referenced by users.role_id.
$conn = Database::connection();
$existing = $conn->query('SELECT name FROM roles')->fetchAll(\PDO::FETCH_COLUMN);
foreach (['admin', 'researcher', 'tourist'] as $role) {
    if (!in_array($role, $existing, true)) {
        $conn->prepare('INSERT INTO roles (name) VALUES (?)')->execute([$role]);
        echo "Created role: {$role}\n";
    }
}

if (User::findByEmail($email) !== null) {
    fwrite(STDERR, "A user with that email already exists.\n");
    exit(1);
}

$id = User::create($email, $password, $name, 'admin');
User::logEvent($id, 'admin_seeded', 'Initial admin account created via CLI');

echo "Admin created. id={$id}, email={$email}\n";