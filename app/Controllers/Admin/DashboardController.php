<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class DashboardController
{
    public function index(): void
    {
        Auth::requireRole('admin');
        $pdo = Database::connection();

        $stats = [
            'total_destinations' => (int)$pdo->query("SELECT COUNT(*) FROM destinations WHERE status = 'active'")->fetchColumn(),
            'pending_destinations' => (int)$pdo->query("SELECT COUNT(*) FROM destinations WHERE status = 'pending'")->fetchColumn(),
            'total_users' => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'total_imports' => (int)$pdo->query("SELECT COUNT(*) FROM import_history")->fetchColumn(),
        ];

        $title = 'Dashboard';
        $content = View::render('admin.dashboard', ['stats' => $stats]);
        require BASE_PATH . '/app/Views/layouts/main.php';
    }
}