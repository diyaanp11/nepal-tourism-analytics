<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class AnalyticsRepository
{
    public function getDestinationDemand(int $destId): array
    {
        $stmt = Database::connection()->prepare("
            SELECT year, month, arrivals 
            FROM destination_demand 
            WHERE destination_id = ? 
            ORDER BY year, month
        ");
        $stmt->execute([$destId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function clearBestMonths(int $destId): void
    {
        $stmt = Database::connection()->prepare("DELETE FROM destination_best_months WHERE destination_id = ?");
        $stmt->execute([$destId]);
    }

    public function saveBestMonths(int $destId, array $months): void
    {
        $stmt = Database::connection()->prepare("INSERT INTO destination_best_months (destination_id, month_number) VALUES (?, ?)");
        foreach ($months as $m) {
            $stmt->execute([$destId, $m]);
        }
    }

    public function getNationalMonthlyArrivals(): array
    {
        $stmt = Database::connection()->query("SELECT year, month, arrivals FROM monthly_arrivals ORDER BY year, month");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDestinationMonthlyArrivals(): array
    {
        $stmt = Database::connection()->query("
            SELECT d.id as dest_id, d.name as dest_name, dd.year, dd.month, dd.arrivals
            FROM destination_demand dd
            JOIN destinations d ON d.id = dd.destination_id
            ORDER BY d.id, dd.year, dd.month
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}