<?php
declare(strict_types=1);

namespace App\Controllers\Tourist;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Services\Recommendation\RecommendationRepository;
use App\Services\Recommendation\MultiFactorRecommender;
use App\Services\AnalyticsService;

final class DashboardController
{
    public function index(): void
    {
        Auth::requireRole('tourist');

        $repo = new RecommendationRepository();
        $allDestinations = $repo->loadActiveDestinations();
        $pdo = Database::connection();

        $search = trim($_GET['q'] ?? '');
        $showRec = isset($_GET['rec']) && $_GET['rec'] === '1';

        $profile = [];
        $weights = [];
        $recommendations = [];

        $stmt = $pdo->prepare("SELECT destination_id FROM favourites WHERE user_id = ?");
        $stmt->execute([Auth::id()]);
        $favourites = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $searchResults = [];
        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_') . '%';
            $stmt = $pdo->prepare("
                SELECT id FROM destinations WHERE status = 'active' AND name LIKE ?
                UNION
                SELECT dt.destination_id FROM destination_types dt JOIN interest_types it ON it.id = dt.interest_type_id WHERE it.name LIKE ?
            ");
            $stmt->execute([$like, $like]);
            $matchedIds = array_flip($stmt->fetchAll(\PDO::FETCH_COLUMN));
            
            $searchResults = array_filter($allDestinations, fn($d) => isset($matchedIds[$d['id']]));
        }

        if ($showRec) {
            $stmt = $pdo->prepare("
                SELECT tp.budget_npr as budget, tp.travel_month, GROUP_CONCAT(ti.interest_type_id) as interests
                FROM tourist_profiles tp
                LEFT JOIN tourist_interests ti ON ti.tourist_profile_id = tp.id
                WHERE tp.user_id = ?
                GROUP BY tp.id
            ");
            $stmt->execute([Auth::id()]);
            $profileRow = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($profileRow && !empty($profileRow['interests'])) {
                $profile = [
                    'budget' => (float)$profileRow['budget'],
                    'travel_month' => (int)$profileRow['travel_month'],
                    'interests' => array_map('intval', explode(',', $profileRow['interests']))
                ];

                $engine = new MultiFactorRecommender();
                $recResult = $engine->recommend($allDestinations, $profile, 5);
                
                if ($recResult['status'] === 'ok') {
                    $recommendations = $recResult['recommendations'];
                    $weights = $recResult['weights'];
                } else {
                    flash('info', $recResult['reason']);
                }
            }
        }

        usort($allDestinations, function($a, $b) {
            if ($a['popularity_arrivals'] === $b['popularity_arrivals']) {
                return strcmp($a['name'], $b['name']);
            }
            return $b['popularity_arrivals'] <=> $a['popularity_arrivals'];
        });

        $topPlaces = array_slice($allDestinations, 0, 10);
        $hasArrivalsData = false;
        foreach ($topPlaces as $tp) { if ($tp['popularity_arrivals'] > 0) $hasArrivalsData = true; }

        $analytics = new AnalyticsService();
        $lifecycle = $analytics->getLifecycleClassification();
        $mustKnow = [];
        if ($lifecycle['status'] === 'ok') {
            $starIds = array_column(array_filter($lifecycle['classified'], fn($c) => in_array($c['stage'], ['Star', 'Question Mark'])), 'id');
            $mustKnow = array_filter($allDestinations, fn($d) => in_array($d['id'], $starIds));
        }

        $becauseYouLike = [];
        $likeTypeName = '';
        if ($showRec && !empty($profile['interests'])) {
            $firstTypeId = $profile['interests'][0];
            $stmt = $pdo->prepare("SELECT name FROM interest_types WHERE id = ?");
            $stmt->execute([$firstTypeId]);
            $likeTypeName = $stmt->fetchColumn() ?: '';
            
            $recIds = array_map(fn($r) => $r['destination']['id'], $recommendations);
            
            $becauseYouLike = array_filter($allDestinations, fn($d) => in_array($firstTypeId, $d['types']) && !in_array($d['id'], $recIds));
            $becauseYouLike = array_slice($becauseYouLike, 0, 10);
        }

        $similar = [];
        if (!empty($searchResults)) {
            $firstResult = reset($searchResults);
            $resultIds = array_column($searchResults, 'id');
            $similar = array_filter($allDestinations, function($d) use ($firstResult, $resultIds) {
                return !in_array($d['id'], $resultIds) && count(array_intersect($d['types'], $firstResult['types'])) > 0;
            });
            $similar = array_slice($similar, 0, 10);
        }

        $title = 'Discover Nepal';
        $content = View::render('tourist.dashboard', [
            'topPlaces' => $topPlaces,
            'searchResults' => $searchResults,
            'similar' => $similar,
            'recommendations' => $recommendations,
            'weights' => $weights,
            'mustKnow' => $mustKnow,
            'becauseYouLike' => $becauseYouLike,
            'likeTypeName' => $likeTypeName,
            'favourites' => $favourites,
            'search' => $search,
            'hasArrivalsData' => $hasArrivalsData,
            'showRec' => $showRec
        ]);
        require BASE_PATH . '/app/Views/layouts/main.php';
    }
}