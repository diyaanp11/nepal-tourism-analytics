<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;

final class DashboardController
{
    public function index(): void
    {
        Auth::requireAuth();

        $role = Auth::role();
        if ($role === 'admin') {
            redirect('admin/dashboard');
        }
        if ($role === 'researcher') {
            redirect('researcher/dashboard');
        }
        if ($role === 'tourist') {
            redirect('tourist/dashboard');
        }

        http_response_code(403);
        exit('403 Forbidden');
    }
}