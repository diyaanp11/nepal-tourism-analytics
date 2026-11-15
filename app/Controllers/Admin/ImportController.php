<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\CsvImporter;
use App\Core\Database;
use App\Core\View;
use App\Services\AnalyticsService;

final class ImportController
{
    public function index(): void
    {
        Auth::requireRole('admin');
        
        $history = Database::connection()->query("
            SELECT h.*, u.name as admin_name 
            FROM import_history h JOIN users u ON u.id = h.admin_id 
            ORDER BY h.created_at DESC LIMIT 20
        ")->fetchAll();

        $importErrors = $_SESSION['import_errors'] ?? [];
        unset($_SESSION['import_errors']);

        $createdLookups = $_SESSION['created_lookups'] ?? [];
        unset($_SESSION['created_lookups']);

        $title = 'Import CSV Data';
        $content = View::render('admin.import', [
            'history' => $history, 
            'importErrors' => $importErrors,
            'createdLookups' => $createdLookups
        ]);
        require BASE_PATH . '/app/Views/layouts/main.php';
    }

    public function upload(): void
    {
        Auth::requireRole('admin');
        CSRF::checkOrAbort($_POST['_csrf'] ?? null);

        if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Please upload a valid file.');
            redirect('admin/import');
        }

        $type = $_POST['import_type'] ?? '';
        $file = $_FILES['csv_file'];

        if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'csv') {
            flash('error', 'Only .csv files are allowed.');
            redirect('admin/import');
        }

        $uploadDir = config('uploads.path');
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $dest = $uploadDir . '/' . uniqid() . '_' . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $dest);

        $result = CsvImporter::import($dest, $type, Auth::id());

        if ($result['success']) {
            $msg = "Import complete! Inserted: {$result['inserted']}, Failed: {$result['failed']}.";
            
            if ($type === 'destination_demand' && !empty($result['affected_destination_ids'])) {
                $service = new AnalyticsService();
                $updateResults = $service->updateBestMonthsForDestinations($result['affected_destination_ids']);
                $updated = 0;
                foreach ($updateResults as $r) {
                    if ($r['status'] === 'ok') $updated++;
                }
                $msg .= " Best months updated for {$updated} destination(s).";
            }
            
            flash('success', $msg);
            
            if (!empty($result['errors'])) {
                $_SESSION['import_errors'] = $result['errors'];
            }
            if (!empty($result['created_lookups'])) {
                $_SESSION['created_lookups'] = $result['created_lookups'];
            }
        } else {
            flash('error', $result['message']);
        }

        unlink($dest);
        redirect('admin/import');
    }
}