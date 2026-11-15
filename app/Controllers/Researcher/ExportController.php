<?php
declare(strict_types=1);

namespace App\Controllers\Researcher;

use App\Core\Auth;
use App\Core\Database;
use App\Services\AnalyticsService;
use App\Services\Analytics\SeasonalIndex;

final class ExportController
{
    public function exportCsv(): void
    {
        Auth::requireRole('researcher');
        $type = $_GET['type'] ?? 'trend';
        $allowedTypes = ['trend', 'nationality', 'purpose', 'lifecycle', 'forecast', 'heatmap'];

        if (!in_array($type, $allowedTypes, true)) {
            http_response_code(400);
            echo "Invalid export type.";
            exit;
        }

        $pdo = Database::connection();
        $filterYear = !empty($_GET['year']) ? (int)$_GET['year'] : null;
        $filterRegion = !empty($_GET['region']) ? (int)$_GET['region'] : null;
        $filterCountry = !empty($_GET['nationality']) ? (int)$_GET['nationality'] : null;
        $filterPurpose = !empty($_GET['purpose']) ? (int)$_GET['purpose'] : null;
        $filterDest = !empty($_GET['destination']) ? (int)$_GET['destination'] : null;

        $filename = "export_{$type}_" . date('Ymd') . ".csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $out = fopen('php://output', 'w');

        if ($type === 'trend') {
            fputcsv($out, ['Year', 'Month', 'Arrivals']);
            $q = "SELECT year, month, arrivals FROM monthly_arrivals";
            $p = [];
            if ($filterYear) { $q .= " WHERE year = ?"; $p[] = $filterYear; }
            $q .= " ORDER BY year, month";
            $stmt = $pdo->prepare($q);
            $stmt->execute($p);
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) fputcsv($out, $row);
        } 
        elseif ($type === 'nationality') {
            if (!$filterYear) {
                $filterYear = (int)$pdo->query("SELECT MAX(year) FROM arrivals_by_nationality")->fetchColumn();
            }
            fputcsv($out, ['Year', 'Country', 'Arrivals']);
            $q = "SELECT a.year, c.name, a.arrivals FROM arrivals_by_nationality a JOIN countries c ON c.id = a.country_id WHERE a.year = ?";
            $p = [$filterYear];
            if ($filterCountry) { $q .= " AND c.id = ?"; $p[] = $filterCountry; }
            $q .= " ORDER BY a.arrivals DESC";
            $stmt = $pdo->prepare($q);
            $stmt->execute($p);
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) fputcsv($out, $row);
        }
        elseif ($type === 'purpose') {
            if (!$filterYear) {
                $filterYear = (int)$pdo->query("SELECT MAX(year) FROM arrivals_by_purpose")->fetchColumn();
            }
            fputcsv($out, ['Year', 'Purpose', 'Arrivals']);
            $q = "SELECT a.year, p.name, a.arrivals FROM arrivals_by_purpose a JOIN purposes p ON p.id = a.purpose_id WHERE a.year = ?";
            $p = [$filterYear];
            if ($filterPurpose) { $q .= " AND p.id = ?"; $p[] = $filterPurpose; }
            $q .= " ORDER BY a.arrivals DESC";
            $stmt = $pdo->prepare($q);
            $stmt->execute($p);
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) fputcsv($out, $row);
        }
        elseif ($type === 'lifecycle') {
            $analytics = new AnalyticsService();
            $result = $analytics->getLifecycleClassification();
            fputcsv($out, ['Destination', 'Growth (%)', 'Market Share (%)', 'Stage']);
            
            if ($result['status'] === 'ok') {
                $destRegions = [];
                foreach ($pdo->query("SELECT id, region_id FROM destinations")->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                    $destRegions[$row['id']] = $row['region_id'];
                }

                foreach ($result['classified'] as $row) {
                    if ($filterDest && $row['id'] != $filterDest) continue;
                    if ($filterRegion && ($destRegions[$row['id']] ?? null) != $filterRegion) continue;
                    fputcsv($out, [$row['name'], $row['growth'], $row['share'], $row['stage']]);
                }
            }
        }
        elseif ($type === 'forecast') {
            $analytics = new AnalyticsService();
            $q = "SELECT id, name FROM destinations WHERE 1=1";
            $p = [];
            if ($filterRegion) { $q .= " AND region_id = ?"; $p[] = $filterRegion; }
            if ($filterDest) { $q .= " AND id = ?"; $p[] = $filterDest; }
            $stmt = $pdo->prepare($q);
            $stmt->execute($p);
            $dests = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            fputcsv($out, ['Destination', 'Target Month', 'Target Year', 'Forecasted Arrivals']);
            foreach ($dests as $d) {
                $fc = $analytics->forecastNextMonth((int)$d['id']);
                if ($fc['status'] === 'ok') {
                    fputcsv($out, [$d['name'], $fc['target_month'], $fc['target_year'], $fc['forecast']]);
                }
            }
        }
        elseif ($type === 'heatmap') {
            $si = new SeasonalIndex();
            $q = "SELECT id, name FROM destinations WHERE 1=1";
            $p = [];
            if ($filterRegion) { $q .= " AND region_id = ?"; $p[] = $filterRegion; }
            if ($filterDest) { $q .= " AND id = ?"; $p[] = $filterDest; }
            $q .= " ORDER BY name ASC";
            $stmt = $pdo->prepare($q);
            $stmt->execute($p);
            $dests = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $headers = ['Destination'];
            for ($m = 1; $m <= 12; $m++) $headers[] = date('M', mktime(0,0,0,$m,1));
            fputcsv($out, $headers);

            foreach ($dests as $d) {
                $stmt = $pdo->prepare("SELECT year, month, arrivals FROM destination_demand WHERE destination_id = ?");
                $stmt->execute([$d['id']]);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $res = $si->compute($rows);
                
                $rowData = [$d['name']];
                for ($m = 1; $m <= 12; $m++) {
                    $rowData[] = ($res['status'] === 'ok') ? round($res['indices'][$m], 4) : 'N/A';
                }
                fputcsv($out, $rowData);
            }
        }

        fclose($out);
        exit;
    }
}