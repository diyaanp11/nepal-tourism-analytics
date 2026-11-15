<?php
declare(strict_types=1);

namespace App\Controllers\Tourist;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Database;
use App\Core\View;

final class ReviewController
{
    public function store(int $destId): void
    {
        Auth::requireRole('tourist');
        CSRF::checkOrAbort($_POST['_csrf'] ?? null);

        $pdo = Database::connection();
        $stmt = $pdo->prepare("SELECT id FROM destinations WHERE id = ? AND status = 'active'");
        $stmt->execute([$destId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo "404 Not Found";
            exit;
        }

        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($rating < 1 || $rating > 5) {
            flash('error', 'Rating must be between 1 and 5 stars.');
            redirect('tourist/destination/' . $destId);
        }
        if (strlen($comment) > 1000) {
            flash('error', 'Comment must be 1000 characters or less.');
            redirect('tourist/destination/' . $destId);
        }

        $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND destination_id = ?");
        $stmt->execute([Auth::id(), $destId]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE id = ?");
            $stmt->execute([$rating, $comment, $existing]);
            flash('success', 'Review updated.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO reviews (user_id, destination_id, rating, comment) VALUES (?, ?, ?, ?)");
            $stmt->execute([Auth::id(), $destId, $rating, $comment]);
            flash('success', 'Review added.');
        }

        redirect('tourist/destination/' . $destId);
    }

    public function delete(int $destId): void
    {
        Auth::requireRole('tourist');
        CSRF::checkOrAbort($_POST['_csrf'] ?? null);

        $pdo = Database::connection();
        $stmt = $pdo->prepare("SELECT id FROM destinations WHERE id = ? AND status = 'active'");
        $stmt->execute([$destId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo "404 Not Found";
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM reviews WHERE user_id = ? AND destination_id = ?");
        $stmt->execute([Auth::id(), $destId]);
        
        flash('success', 'Review deleted.');
        redirect('tourist/destination/' . $destId);
    }

    public function index(): void
    {
        Auth::requireRole('tourist');
        
        $stmt = Database::connection()->prepare("
            SELECT r.*, d.name as dest_name, d.image as dest_image 
            FROM reviews r JOIN destinations d ON d.id = r.destination_id 
            WHERE r.user_id = ? ORDER BY r.created_at DESC
        ");
        $stmt->execute([Auth::id()]);
        $reviews = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $title = 'My Reviews';
        $content = View::render('tourist.reviews', ['reviews' => $reviews]);
        require BASE_PATH . '/app/Views/layouts/main.php';
    }
}