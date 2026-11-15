<?php
declare(strict_types=1);

namespace App\Services\Analytics;

final class BestMonths
{
    public function pick(array $rows, int $topN = 3): array
    {
        $si = new SeasonalIndex();
        $result = $si->compute($rows);
        
        if ($result['status'] !== 'ok') {
            return [
                'status' => $result['status'],
                'reason' => $result['reason'],
                'best_months' => [],
                'indices' => $result['indices'],
                'overall_average' => $result['overall_average']
            ];
        }

        $indices = $result['indices'];
        arsort($indices); // Sort descending by index value, keep keys
        
        $top = array_slice(array_keys($indices), 0, $topN);

        return [
            'status' => 'ok',
            'reason' => '',
            'best_months' => $top,
            'indices' => $result['indices'],
            'overall_average' => $result['overall_average']
        ];
    }
}