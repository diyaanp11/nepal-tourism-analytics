<?php
declare(strict_types=1);

namespace App\Services;

use App\Services\Analytics\BestMonths;
use App\Services\Analytics\DemandForecast;
use App\Services\Analytics\DestinationLifecycle;

final class AnalyticsService
{
    private AnalyticsRepository $repo;
    private BestMonths $bestMonths;
    private DemandForecast $forecast;
    private DestinationLifecycle $lifecycle;

    public function __construct(?AnalyticsRepository $repo = null)
    {
        $this->repo = $repo ?? new AnalyticsRepository();
        $this->bestMonths = new BestMonths();
        $this->forecast = new DemandForecast();
        $this->lifecycle = new DestinationLifecycle();
    }

    public function updateBestMonthsForDestination(int $destId): array
    {
        $rows = $this->repo->getDestinationDemand($destId);
        $result = $this->bestMonths->pick($rows);

        if ($result['status'] === 'ok') {
            $this->repo->clearBestMonths($destId);
            $this->repo->saveBestMonths($destId, $result['best_months']);
        } else {
            $this->repo->clearBestMonths($destId);
        }

        return $result;
    }

    public function updateBestMonthsForDestinations(array $ids): array
    {
        $results = [];
        foreach ($ids as $id) {
            $results[$id] = $this->updateBestMonthsForDestination($id);
        }
        return $results;
    }

    public function getLifecycleClassification(): array
    {
        $national = $this->repo->getNationalMonthlyArrivals();
        $destinations = $this->repo->getDestinationMonthlyArrivals();
        return $this->lifecycle->classify($national, $destinations);
    }

    public function forecastNextMonth(int $destId): array
    {
        $rows = $this->repo->getDestinationDemand($destId);
        return $this->forecast->compute($rows);
    }
}