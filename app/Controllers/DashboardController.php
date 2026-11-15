<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;

final class DashboardController
{
    public function index(): void
    {
        Auth::requireAuth();

        $role = (string)Auth::role();

        if ($role === 'admin') {
            redirect('admin/dashboard');
        }
        if ($role === 'researcher') {
            redirect('researcher/dashboard');
        }

        $nextPhase = 'Phase 6 — Recommendations & Favourites';

        $title   = 'Dashboard';
        $content = View::render('dashboard.index', [
            'role'      => $role,
            'name'      => (string)Session::get('user_name'),
            'nextPhase' => $nextPhase,
        ]);
        require BASE_PATH . '/app/Views/layouts/main.php';
    }
}