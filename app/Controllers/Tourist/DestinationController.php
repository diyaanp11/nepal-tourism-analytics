<?php
declare(strict_types=1);

namespace App\Controllers\Tourist;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class DestinationController
{
    public function show(int $id): void
    {
        Auth::requireRole('tourist');
        $pdo = Database::connection();

        $stmt = $pdo->prepare("SELECT * FROM destinations WHERE id = ? AND status = 'active'");
        $stmt->execute([$id]);
        $dest = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$dest) {
            http_response_code(404);
            echo "404 Not Found";
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT it.name FROM destination_types dt 
            JOIN interest_types it ON it.id = dt.interest_type_id 
            WHERE dt.destination_id = ?
        ");
        $stmt->execute([$id]);
        $dest['types'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("SELECT month_number FROM destination_best_months WHERE destination_id = ?");
        $stmt->execute([$id]);
        $months = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $bestMonthsStr = empty($months) ? 'Not enough data yet' : implode(', ', array_map(fn($m) => date('M', mktime(0,0,0,$m,1)), $months));
        $dest['best_months_str'] = $bestMonthsStr;

        $stmt = $pdo->prepare("
            SELECT r.*, u.name as user_name 
            FROM reviews r JOIN users u ON u.id = r.user_id 
            WHERE r.destination_id = ? ORDER BY r.created_at DESC
        ");
        $stmt->execute([$id]);
        $reviews = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $avgRating = null;
        $userReview = null;
        if (!empty($reviews)) {
            $avgRating = array_sum(array_column($reviews, 'rating')) / count($reviews);
            foreach ($reviews as $r) {
                if ($r['user_id'] == Auth::id()) {
                    $userReview = $r;
                    break;
                }
            }
        }

        $stmt = $pdo->prepare("SELECT 1 FROM favourites WHERE user_id = ? AND destination_id = ?");
        $stmt->execute([Auth::id(), $id]);
        $isFavourite = (bool)$stmt->fetchColumn();

        $title = $dest['name'];
        $content = View::render('tourist.destination', [
            'dest' => $dest,
            'reviews' => $reviews,
            'avgRating' => $avgRating,
            'userReview' => $userReview,
            'isFavourite' => $isFavourite
        ]);
        require BASE_PATH . '/app/Views/layouts/main.php';
    }
}