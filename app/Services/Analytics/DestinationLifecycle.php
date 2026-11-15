<?php
declare(strict_types=1);

namespace App\Services\Analytics;

final class DestinationLifecycle
{
    public function classify(array $nationalMonthlyData, array $destinationMonthlyData): array
    {
        $nationalByYear = [];
        foreach ($nationalMonthlyData as $r) {
            $nationalByYear[(int)$r['year']][(int)$r['month']] = (float)$r['arrivals'];
        }

        $completeYears = [];
        foreach ($nationalByYear as $y => $months) {
            if (count($months) === 12) $completeYears[] = $y;
        }

        if (empty($completeYears)) {
            return ['status' => 'insufficient_data', 'reason' => 'No year has complete 12-month national data.'];
        }

        rsort($completeYears);
        $currentYear = $completeYears[0];
        $previousYear = $currentYear - 1;

        if (!in_array($previousYear, $completeYears, true)) {
            return ['status' => 'insufficient_data', 'reason' => "Previous year ({$previousYear}) lacks complete national data."];
        }

        $nationalCurrent = array_sum($nationalByYear[$currentYear]);
        $nationalPrev = array_sum($nationalByYear[$previousYear]);

        if ($nationalPrev <= 0) {
            return ['status' => 'insufficient_data', 'reason' => 'National previous year total is zero.'];
        }

        $nationalGrowth = (($nationalCurrent - $nationalPrev) / $nationalPrev) * 100;

        $destData = [];
        foreach ($destinationMonthlyData as $r) {
            $id = $r['dest_id'];
            $destData[$id]['name'] = $r['dest_name'];
            $destData[$id]['years'][(int)$r['year']][(int)$r['month']] = (float)$r['arrivals'];
        }

        $complete = [];
        foreach ($destData as $id => $info) {
            if (!isset($info['years'][$currentYear]) || count($info['years'][$currentYear]) < 12) continue;
            if (!isset($info['years'][$previousYear]) || count($info['years'][$previousYear]) < 12) continue;

            $current = array_sum($info['years'][$currentYear]);
            $previous = array_sum($info['years'][$previousYear]);

            if ($previous <= 0) continue;
            // Growth Formula
            $growth = (($current - $previous) / $previous) * 100;
            // Market Share Formula
            $share = $nationalCurrent > 0 ? ($current / $nationalCurrent) * 100 : 0;

            $complete[$id] = [
                'id' => $id,
                'name' => $info['name'],
                'current' => $current,
                'previous' => $previous,
                'growth' => $growth,
                'share' => $share
            ];
        }

        if (empty($complete)) {
            return ['status' => 'insufficient_data', 'reason' => 'No destination has complete 12-month data in both years.'];
        }
        // Cutoffs
        $growthCutoff = $nationalGrowth;
        $shareCutoff = array_sum(array_column($complete, 'share')) / count($complete);

        $classified = [];
        foreach ($complete as $c) {
            $isHighGrowth = $c['growth'] >= $growthCutoff;
            $isHighShare = $c['share'] >= $shareCutoff;

            if ($isHighGrowth && $isHighShare) $stage = 'Star';
            elseif ($isHighGrowth && !$isHighShare) $stage = 'Question Mark';
            elseif (!$isHighGrowth && $isHighShare) $stage = 'Cash Cow';
            else $stage = 'Dog';

            $classified[] = [
                'id' => $c['id'],
                'name' => $c['name'],
                'growth' => round($c['growth'], 4),
                'share' => round($c['share'], 4),
                'stage' => $stage
            ];
        }

        return [
            'status' => 'ok',
            'reason' => '',
            'current_year' => $currentYear,
            'previous_year' => $previousYear,
            'national_growth' => round($nationalGrowth, 4),
            'growth_cutoff' => round($growthCutoff, 4),
            'share_cutoff' => round($shareCutoff, 4),
            'classified' => $classified
        ];
    }
}