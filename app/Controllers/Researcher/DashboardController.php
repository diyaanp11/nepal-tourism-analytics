<?php
declare(strict_types=1);

namespace App\Controllers\Researcher;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Services\AnalyticsService;
use App\Services\Analytics\SeasonalIndex;
use App\Services\Analytics\ChartDescriptionGenerator;
use PDO;

final class DashboardController
{
    public function index(): void
    {
        Auth::requireRole('researcher');
        $pdo = Database::connection();
        $analytics = new AnalyticsService();

        $yearsQuery = $pdo->query("
            SELECT DISTINCT year FROM monthly_arrivals
            UNION
            SELECT DISTINCT year FROM arrivals_by_nationality
            UNION
            SELECT DISTINCT year FROM arrivals_by_purpose
            ORDER BY year DESC
        ");
        $availableYears = $yearsQuery->fetchAll(PDO::FETCH_COLUMN);

        $selectedYear = $_GET['year'] ?? '';
        if ($selectedYear !== '') {
            $filterYear = (int)$selectedYear;
        } elseif (!empty($availableYears)) {
            $filterYear = (int)$availableYears[0];
        } else {
            $filterYear = (int)date('Y');
        }

        $filterRegion = !empty($_GET['region']) ? (int)$_GET['region'] : null;
        $filterCountry = !empty($_GET['nationality']) ? (int)$_GET['nationality'] : null;
        $filterPurpose = !empty($_GET['purpose']) ? (int)$_GET['purpose'] : null;
        $filterDest = !empty($_GET['destination']) ? (int)$_GET['destination'] : null;

        // Trend
        $trendQuery = "SELECT year, month, arrivals FROM monthly_arrivals";
        $trendParams = [];
        if ($selectedYear !== '') {
            $trendQuery .= " WHERE year = ?";
            $trendParams[] = $filterYear;
        }
        $trendQuery .= " ORDER BY year ASC, month ASC";
        $stmt = $pdo->prepare($trendQuery);
        $stmt->execute($trendParams);
        $trendData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Nationality
        $natQuery = "SELECT c.name, SUM(a.arrivals) as total FROM arrivals_by_nationality a JOIN countries c ON c.id = a.country_id WHERE a.year = ?";
        $natParams = [$filterYear];
        if ($filterCountry) { $natQuery .= " AND c.id = ?"; $natParams[] = $filterCountry; }
        $natQuery .= " GROUP BY c.id, c.name ORDER BY total DESC LIMIT 8";
        $stmt = $pdo->prepare($natQuery);
        $stmt->execute($natParams);
        $nationalityData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT SUM(arrivals) FROM arrivals_by_nationality WHERE year = ?");
        $stmt->execute([$filterYear]);
        $nationalTotal = (int)$stmt->fetchColumn();

        // Purpose
        $purposeQuery = "SELECT p.name as purpose, SUM(a.arrivals) as total FROM arrivals_by_purpose a JOIN purposes p ON p.id = a.purpose_id WHERE a.year = ?";
        $purposeParams = [$filterYear];
        if ($filterPurpose) { $purposeQuery .= " AND p.id = ?"; $purposeParams[] = $filterPurpose; }
        $purposeQuery .= " GROUP BY p.id, p.name ORDER BY total DESC";
        $stmt = $pdo->prepare($purposeQuery);
        $stmt->execute($purposeParams);
        $purposeData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT SUM(arrivals) FROM arrivals_by_purpose WHERE year = ?");
        $stmt->execute([$filterYear]);
        $purposeTotal = (int)$stmt->fetchColumn();

        // Heatmap & Forecast
        $destQuery = "SELECT d.id, d.name, d.region_id FROM destinations d WHERE 1=1";
        $destParams = [];
        if ($filterRegion) { $destQuery .= " AND d.region_id = ?"; $destParams[] = $filterRegion; }
        if ($filterDest) { $destQuery .= " AND d.id = ?"; $destParams[] = $filterDest; }
        $destQuery .= " ORDER BY d.name ASC";
        $stmt = $pdo->prepare($destQuery);
        $stmt->execute($destParams);
        $activeDests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $heatmapData = [];
        $forecastData = [];
        $si = new SeasonalIndex();

        foreach ($activeDests as $d) {
            $stmt = $pdo->prepare("SELECT year, month, arrivals FROM destination_demand WHERE destination_id = ?");
            $stmt->execute([$d['id']]);
            $demandRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $siResult = $si->compute($demandRows);
            $heatmapData[] = [
                'name' => $d['name'],
                'status' => $siResult['status'],
                'indices' => $siResult['indices'],
                'desc' => ChartDescriptionGenerator::heatmap($d['name'], $siResult)
            ];

            $fc = $analytics->forecastNextMonth((int)$d['id']);
            if ($fc['status'] === 'ok') {
                $forecastData[] = [
                    'name' => $d['name'],
                    'forecast' => $fc['forecast'],
                    'target_month' => $fc['target_month'],
                    'target_year' => $fc['target_year']
                ];
            }
        }

        // Lifecycle
        $lifecycleResult = $analytics->getLifecycleClassification();
        $lifecycleData = $lifecycleResult['status'] === 'ok' ? $lifecycleResult['classified'] : [];
        
        if (($filterRegion || $filterDest) && !empty($lifecycleData)) {
            $destRegionStmt = $pdo->query("SELECT id, region_id FROM destinations");
            $destRegions = [];
            foreach ($destRegionStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $destRegions[$row['id']] = $row['region_id'];
            }

            // Use array_values so json_encode produces an array, not an object
            $lifecycleData = array_values(array_filter($lifecycleData, function($item) use ($filterRegion, $filterDest, $destRegions) {
                if ($filterDest && $item['id'] != $filterDest) return false;
                if ($filterRegion && ($destRegions[$item['id']] ?? null) != $filterRegion) return false;
                return true;
            }));
        }

        $descriptions = [
            'trend' => ChartDescriptionGenerator::trend($trendData),
            'nationality' => ChartDescriptionGenerator::nationality($nationalityData, $nationalTotal, $filterYear),
            'purpose' => ChartDescriptionGenerator::purpose($purposeData, $purposeTotal, $filterYear),
            'lifecycle' => ChartDescriptionGenerator::lifecycle($lifecycleData),
            'forecast' => ChartDescriptionGenerator::forecast($forecastData)
        ];

        $emptyReasons = [
            'trend' => empty($trendData) ? 'No arrival data available for the selected filters.' : '',
            'nationality' => empty($nationalityData) ? 'No nationality data available.' : '',
            'purpose' => empty($purposeData) ? 'No purpose data available.' : '',
            'lifecycle' => empty($lifecycleData) 
                ? ($lifecycleResult['status'] === 'ok' ? 'No destinations match the selected filters.' : $lifecycleResult['reason']) 
                : '',
            'forecast' => empty($forecastData) ? 'Insufficient data for forecasts. Requires 12+ months per destination.' : '',
        ];

        $regions = $pdo->query("SELECT id, name FROM regions ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        $countries = $pdo->query("SELECT id, name FROM countries ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        $purposes = $pdo->query("SELECT id, name FROM purposes ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        $destinations = $pdo->query("SELECT id, name FROM destinations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

        $title = 'Analytics Dashboard';
        $content = View::render('researcher.dashboard', [
            'nationalityData' => $nationalityData,
            'trendData' => $trendData,
            'purposeData' => $purposeData,
            'heatmapData' => $heatmapData,
            'lifecycleData' => $lifecycleData,
            'lifecycleCutoffs' => $lifecycleResult['status'] === 'ok' ? [
                'growth' => $lifecycleResult['growth_cutoff'],
                'share' => $lifecycleResult['share_cutoff']
            ] : null,
            'forecastData' => $forecastData,
            'descriptions' => $descriptions,
            'emptyReasons' => $emptyReasons,
            'latestYear' => $filterYear,
            'filters' => [
                'years' => $availableYears,
                'regions' => $regions, 'countries' => $countries, 'purposes' => $purposes, 'destinations' => $destinations,
                'current' => [
                    'year' => $selectedYear,
                    'region' => $filterRegion, 'nationality' => $filterCountry, 'purpose' => $filterPurpose, 'destination' => $filterDest
                ]
            ]
        ]);
        require BASE_PATH . '/app/Views/layouts/main.php';
    }
}