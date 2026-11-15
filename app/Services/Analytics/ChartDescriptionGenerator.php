<?php
declare(strict_types=1);

namespace App\Services\Analytics;

final class ChartDescriptionGenerator
{
    public static function trend(array $data): string
    {
        if (empty($data)) return "No arrival data available.";

        $peakRow = $data[0];
        foreach ($data as $row) {
            if ((int)$row['arrivals'] > (int)$peakRow['arrivals']) {
                $peakRow = $row;
            }
        }

        $peakYear = (int)$peakRow['year'];
        $peakMonth = (int)$peakRow['month'];
        $peakVal = (int)$peakRow['arrivals'];
        $monthName = date('F', mktime(0, 0, 0, $peakMonth, 10));

        $prevVal = 0;
        $prevYear = $peakYear - 1;
        foreach ($data as $row) {
            if ((int)$row['year'] === $prevYear && (int)$row['month'] === $peakMonth) {
                $prevVal = (int)$row['arrivals'];
                break;
            }
        }

        $changeStr = "";
        if ($prevVal > 0) {
            $pct = round((($peakVal - $prevVal) / $prevVal) * 100);
            $changeStr = ", " . ($pct >= 0 ? "up" : "down") . " " . abs($pct) . "% from {$monthName} {$prevYear}";
        }

        return "Arrivals peaked in {$monthName} {$peakYear} at " . number_format($peakVal) . "{$changeStr}. The overall trend shows how tourism volume changes month-to-month.";
    }

    public static function nationality(array $data, int $totalYearArrivals, int $year): string
    {
        if (empty($data)) return "No nationality data available for {$year}.";
        
        $top = $data[0];
        $topPct = $totalYearArrivals > 0 ? round(($top['total'] / $totalYearArrivals) * 100) : 0;
        
        $secondStr = "";
        if (isset($data[1])) {
            $secondPct = $totalYearArrivals > 0 ? round(($data[1]['total'] / $totalYearArrivals) * 100) : 0;
            $secondStr = ", followed by {$data[1]['name']} ({$secondPct}%)";
        }

        return "{$top['name']} is the largest source market with {$topPct}% of arrivals in {$year}{$secondStr}.";
    }

    public static function lifecycle(array $data): string
    {
        if (empty($data)) return "Insufficient data to calculate destination lifecycles.";
        
        $stars = 0;
        foreach ($data as $d) {
            if ($d['stage'] === 'Star') $stars++;
        }
        
        return "{$stars} destinations are classified as Stars (high growth, high market share). Note: A 'Dog' status means growth is below the national average and market share is below the destination average, which does not necessarily mean the destination is declining.";
    }

    public static function forecast(array $data): string
    {
        if (empty($data)) return "Insufficient data to generate forecasts. Ensure destinations have 12+ months of data.";
        
        usort($data, fn($a, $b) => $b['forecast'] <=> $a['forecast']);
        $top = $data[0];
        $monthName = date('M Y', mktime(0, 0, 0, $top['target_month'], 1, $top['target_year']));

        return "{$top['name']} is expected to have the highest demand next month ({$monthName}) at approximately " . number_format($top['forecast']) . " arrivals.";
    }

    public static function purpose(array $data, int $totalYearArrivals, int $year): string
    {
        if (empty($data)) return "No purpose data available for {$year}.";
        
        $top = $data[0];
        $pct = $totalYearArrivals > 0 ? round(($top['total'] / $totalYearArrivals) * 100) : 0;

        return "The most common purpose of visit in {$year} was '{$top['purpose']}', accounting for {$pct}% of all arrivals.";
    }

    public static function heatmap(string $destName, array $result): string
    {
        if ($result['status'] !== 'ok') {
            return "Not enough data yet to calculate the seasonal index for {$destName}.";
        }

        $indices = $result['indices'];
        arsort($indices);
        $top = array_slice(array_keys($indices), 0, 3);
        $months = array_map(fn($m) => date('M', mktime(0, 0, 0, $m, 1)), $top);

        return "The busiest months for {$destName} are " . implode(', ', $months) . " based on historical visitor data.";
    }
}