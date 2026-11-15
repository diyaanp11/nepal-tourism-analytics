<?php
declare(strict_types=1);

namespace App\Services\Analytics;

final class YoYGrowth
{
    public function compute($current, $previous): array
    {
        if ($previous === null || (float)$previous == 0) {
            return [
                'status' => 'insufficient_data',
                'reason' => 'Previous period value is zero or missing.',
                'growth' => null
            ];
        }
        //formula
        $growth = (((float)$current - (float)$previous) / (float)$previous) * 100;

        return [
            'status' => 'ok',
            'reason' => '',
            'growth' => $growth
        ];
    }
}