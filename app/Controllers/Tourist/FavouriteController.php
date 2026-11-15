<?php
declare(strict_types=1);

namespace App\Controllers\Tourist;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Database;
use App\Core\View;
use App\Services\Recommendation\RecommendationRepository;

final class FavouriteController
{
    public function toggle(int $id): void
    {
        Auth::requireRole('tourist');
        CSRF::checkOrAbort($_POST['_csrf'] ?? null);

        $pdo = Database::connection();
        $userId = Auth::id();

        $stmt = $pdo->prepare("SELECT id FROM destinations WHERE id = ? AND status = 'active'");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo "404 Not Found";
            exit;
        }

        $stmt = $pdo->prepare("SELECT 1 FROM favourites WHERE user_id = ? AND destination_id = ?");
        $stmt->execute([$userId, $id]);

        if ($stmt->fetch()) {
            $pdo->prepare("DELETE FROM favourites WHERE user_id = ? AND destination_id = ?")->execute([$userId, $id]);
        } else {
            $pdo->prepare("INSERT INTO favourites (user_id, destination_id) VALUES (?, ?)")->execute([$userId, $id]);
        }

        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        $base = config('app.url');
        if (str_starts_with($ref, $base)) {
            redirect(str_replace($base . '/', '', $ref));
        }
        redirect('tourist/dashboard');
    }

    public function index(): void
    {
        Auth::requireRole('tourist');
        
        $pdo = Database::connection();
        $stmt = $pdo->prepare("
            SELECT d.* FROM destinations d
            JOIN favourites f ON f.destination_id = d.id
            WHERE f.user_id = ? AND d.status = 'active'
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([Auth::id()]);
        $destinations = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $repo = new RecommendationRepository();
        $allActive = $repo->loadActiveDestinations();
        $byId = [];
        foreach ($allActive as $d) { $byId[$d['id']] = $d; }

        $favourites = [];
        foreach ($destinations as $d) {
            if (isset($byId[$d['id']])) $favourites[] = $byId[$d['id']];
        }

        $title = 'My Favourites';
        $content = View::render('tourist.favourites', ['favourites' => $favourites]);
        require BASE_PATH . '/app/Views/layouts/main.php';
    }
}