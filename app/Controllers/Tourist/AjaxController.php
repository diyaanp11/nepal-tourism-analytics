<?php
declare(strict_types=1);

namespace App\Controllers\Tourist;

use App\Core\Auth;
use App\Core\Database;

final class AjaxController
{
    public function newDestinations(): void
    {
        Auth::requireRole('tourist');
        header('Content-Type: application/json');

        $since = $_GET['since'] ?? date('Y-m-d H:i:s', strtotime('-1 day'));
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT d.id, d.name, d.image, d.avg_cost_npr,
                   (SELECT GROUP_CONCAT(it.name) FROM destination_types dt JOIN interest_types it ON it.id = dt.interest_type_id WHERE dt.destination_id = d.id) as type_names
            FROM destinations d
            WHERE d.status = 'active' AND d.activated_at > ?
            ORDER BY d.activated_at DESC LIMIT 10
        ");
        $stmt->execute([$since]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $r) {
            $result[] = [
                'id' => (int)$r['id'],
                'name' => $r['name'],
                'image' => image_src($r['image']),
                'type_names' => $r['type_names'] ? explode(',', $r['type_names']) : []
            ];
        }

        echo json_encode([
            'destinations' => $result,
            'server_time' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
}