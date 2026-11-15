<?php
declare(strict_types=1);

namespace App\Services\Analytics;

final class DemandForecast
{
    public function compute(array $rows): array
    {
        $n = count($rows);
        if ($n < 12) {
            return [
                'status' => 'insufficient_data',
                'reason' => "Need at least 12 observations, got {$n}.",
                'forecast' => null
            ];
        }

        usort($rows, fn($a, $b) => [$a['year'], $a['month']] <=> [$b['year'], $b['month']]);

        $first = $rows[0];
        $firstT = (int)$first['year'] * 12 + (int)$first['month'];

        $data = [];
        $sumT = 0; $sumY = 0; $sumTY = 0; $sumT2 = 0;

        foreach ($rows as $r) {
            $t = ((int)$r['year'] * 12 + (int)$r['month']) - $firstT + 1;
            $y = (float)$r['arrivals'];
            $data[] = ['t' => $t, 'y' => $y, 'year' => (int)$r['year'], 'month' => (int)$r['month']];
            $sumT += $t;
            $sumY += $y;
            $sumTY += $t * $y;
            $sumT2 += $t * $t;
        }
        // Step 3: Growth per month (slope)
        $denom = $n * $sumT2 - $sumT * $sumT;
        if ($denom == 0) {
            return [
                'status' => 'insufficient_data',
                'reason' => 'Cannot compute trend (zero denominator).',
                'forecast' => null
            ];
        }

        $growth = ($n * $sumTY - $sumT * $sumY) / $denom;
        // Step 4: Starting baseline
        $baseline = ($sumY - $growth * $sumT) / $n;
        // Step 5 & 6: Trend and Ratio
        $ratiosByMonth = array_fill(1, 12, []);
        foreach ($data as $dp) {
            $trend = $baseline + $growth * $dp['t'];
            if ($trend > 0) {
                $ratiosByMonth[$dp['month']][] = $dp['y'] / $trend;
            }
        }
        // Step 7: Seasonal Index per calendar month
        $indices = [];
        for ($m = 1; $m <= 12; $m++) {
            $indices[$m] = empty($ratiosByMonth[$m]) 
                ? null 
                : array_sum($ratiosByMonth[$m]) / count($ratiosByMonth[$m]);
        }

        // Target month = month after last data month
        $last = end($data);
        $lastYear = $last['year'];
        $lastMonth = $last['month'];
        
        $targetYear = $lastMonth == 12 ? $lastYear + 1 : $lastYear;
        $targetMonth = $lastMonth == 12 ? 1 : $lastMonth + 1;
        $targetT = ($targetYear * 12 + $targetMonth) - $firstT + 1;

        if ($indices[$targetMonth] === null) {
            return [
                'status' => 'insufficient_data',
                'reason' => "Target month {$targetMonth} has no historical ratio.",
                'growth' => round($growth, 6),
                'baseline' => round($baseline, 6),
                'forecast' => null
            ];
        }
        // Step 8 & 9: Future Trend and Final Forecast
        $futureTrend = $baseline + $growth * $targetT;
        $forecast = $futureTrend * $indices[$targetMonth];

        return [
            'status' => 'ok',
            'reason' => '',
            'growth' => round($growth, 6),
            'baseline' => round($baseline, 6),
            'n' => $n,
            'seasonal_indices' => array_map(fn($v) => $v === null ? null : round($v, 6), $indices),
            'target_year' => $targetYear,
            'target_month' => $targetMonth,
            'target_t' => $targetT,
            'future_trend' => round($futureTrend, 6),
            'forecast' => round($forecast, 6)
        ];
    }
}