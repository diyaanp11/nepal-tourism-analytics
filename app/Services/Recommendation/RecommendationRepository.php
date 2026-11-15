<?php
declare(strict_types=1);

namespace App\Services\Recommendation;

use App\Core\Database;
use PDO;

final class RecommendationRepository
{
    public function loadActiveDestinations(): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("SELECT MAX(year) FROM destination_demand");
        $stmt->execute();
        $latestYear = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT d.id, d.name, d.slug, d.image, d.description, d.avg_cost_npr as cost,
                   r.name as region_name,
                   (SELECT SUM(dd.arrivals) FROM destination_demand dd WHERE dd.destination_id = d.id AND dd.year = :year) as popularity_arrivals,
                   (SELECT AVG(rating) FROM reviews WHERE destination_id = d.id) as avg_rating,
                   (SELECT COUNT(*) FROM reviews WHERE destination_id = d.id) as review_count
            FROM destinations d
            LEFT JOIN regions r ON r.id = d.region_id
            WHERE d.status = 'active'
        ");
        $stmt->execute(['year' => $latestYear]);
        
        $destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $typesStmt = $pdo->query("
            SELECT dt.destination_id, dt.interest_type_id, it.name 
            FROM destination_types dt
            JOIN interest_types it ON it.id = dt.interest_type_id
        ");
        $typesMap = [];
        foreach ($typesStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $typesMap[$row['destination_id']]['ids'][] = (int)$row['interest_type_id'];
            $typesMap[$row['destination_id']]['names'][] = $row['name'];
        }

        $monthsStmt = $pdo->query("SELECT destination_id, month_number FROM destination_best_months");
        $monthsMap = [];
        foreach ($monthsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $monthsMap[$row['destination_id']][] = (int)$row['month_number'];
        }

        foreach ($destinations as &$d) {
            $id = (int)$d['id'];
            $d['id'] = $id;
            $d['cost'] = (float)$d['cost'];
            $d['types'] = $typesMap[$id]['ids'] ?? [];
            $d['type_names'] = $typesMap[$id]['names'] ?? [];
            $d['best_months'] = $monthsMap[$id] ?? [];
            $d['popularity_arrivals'] = (int)($d['popularity_arrivals'] ?? 0);
            $d['avg_rating'] = $d['avg_rating'] !== null ? (float)$d['avg_rating'] : null;
            $d['review_count'] = (int)$d['review_count'];
        }
        
        return $destinations;
    }

    public function getSidebarData(int $userId): array
    {
        $pdo = Database::connection();
        
        $types = $pdo->query("SELECT id, name FROM interest_types ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        $maxCost = (float)$pdo->query("SELECT MAX(avg_cost_npr) FROM destinations WHERE status = 'active'")->fetchColumn();
        if ($maxCost <= 0) $maxCost = 5000;

        $stmt = $pdo->prepare("SELECT budget_npr, travel_month FROM tourist_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['budget_npr' => 0, 'travel_month' => 0];

        $stmt = $pdo->prepare("SELECT interest_type_id FROM tourist_interests WHERE tourist_profile_id = (SELECT id FROM tourist_profiles WHERE user_id = ?)");
        $stmt->execute([$userId]);
        $selectedInterests = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return [
            'types' => $types,
            'max_cost' => $maxCost,
            'profile' => $profile,
            'selected_interests' => array_map('intval', $selectedInterests)
        ];
    }
}