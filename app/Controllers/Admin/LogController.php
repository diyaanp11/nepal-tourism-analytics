<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class LogController
{
    public function index(): void
    {
        Auth::requireRole('admin');
        
        $logs = Database::connection()->query("
            SELECT l.*, u.name as user_name 
            FROM system_logs l 
            LEFT JOIN users u ON u.id = l.user_id 
            ORDER BY l.created_at DESC LIMIT 100
        ")->fetchAll();

        $title = 'System Logs';
        $content = View::render('admin.logs', ['logs' => $logs]);
        require BASE_PATH . '/app/Views/layouts/main.php';
    }
}