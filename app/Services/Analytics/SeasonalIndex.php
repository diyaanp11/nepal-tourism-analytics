<?php
declare(strict_types=1);

namespace App\Services\Analytics;

final class SeasonalIndex
{
    public function compute(array $rows): array
    {
        usort($rows, fn($a, $b) => [$a['year'], $a['month']] <=> [$b['year'], $b['month']]);

        $byMonth = array_fill(1, 12, []);
        foreach ($rows as $r) {
            $byMonth[(int)$r['month']][] = (float)$r['arrivals'];
        }

        for ($m = 1; $m <= 12; $m++) {
            if (empty($byMonth[$m])) {
                return [
                    'status' => 'insufficient_data',
                    'reason' => "Data does not cover all 12 calendar months (missing month {$m}).",
                    'monthly_averages' => null,
                    'overall_average' => null,
                    'indices' => null
                ];
            }
        }

        $monthlyAvg = [];
        $sumOfMonthlyAvgs = 0;
        for ($m = 1; $m <= 12; $m++) {
            // Calculate Monthly Average
            $monthlyAvg[$m] = array_sum($byMonth[$m]) / count($byMonth[$m]);
            $sumOfMonthlyAvgs += $monthlyAvg[$m];
        }

        // Overall average = average of the 12 monthly averages
        $overall = $sumOfMonthlyAvgs / 12;

        if ($overall == 0) {
            return [
                'status' => 'insufficient_data',
                'reason' => 'Overall average is zero.',
                'monthly_averages' => null,
                'overall_average' => null,
                'indices' => null
            ];
        }

        $indices = [];
        for ($m = 1; $m <= 12; $m++) {
           // Seasonal Index = Monthly Average ÷ Overall Average
            $indices[$m] = $monthlyAvg[$m] / $overall;
        }

        return [
            'status' => 'ok',
            'reason' => '',
            'monthly_averages' => $monthlyAvg,
            'overall_average' => $overall,
            'indices' => $indices
        ];
    }
}