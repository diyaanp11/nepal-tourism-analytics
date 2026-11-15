<?php
declare(strict_types=1);

namespace App\Services\Recommendation;

final class MultiFactorRecommender implements RecommendationEngineInterface
{
    private array $interestTypeNames = [];

    public function recommend(array $destinations, array $profile, int $limit = 5): array
    {
        if (empty($destinations)) {
            return ['status' => 'insufficient_data', 'reason' => 'No destinations.', 'recommendations' => []];
        }
        if (count($destinations) < 2) {
            return ['status' => 'insufficient_data', 'reason' => 'Not enough destinations yet to personalise.', 'recommendations' => []];
        }

        $this->interestTypeNames = [];
        foreach ($destinations as $d) {
            foreach ($d['types'] as $idx => $tid) {
                if (!isset($this->interestTypeNames[$tid])) {
                    $this->interestTypeNames[$tid] = $d['type_names'][$idx] ?? 'Unknown';
                }
            }
        }

        $raw = $this->computeRawScores($destinations, $profile);
        if (empty($raw)) {
            return ['status' => 'insufficient_data', 'reason' => 'Could not score any destination.', 'recommendations' => []];
        }

        $calc = new EntropyWeightCalculator();
        $weightsResult = $calc->compute($raw);
        if ($weightsResult['status'] !== 'ok') {
            return array_merge($weightsResult, ['recommendations' => []]);
        }

        $scored = $calc->applyWeights($raw, $weightsResult['weights']);
        usort($scored, fn($a, $b) => $b['final_score'] <=> $a['final_score']);

        $byId = [];
        foreach ($destinations as $d) {
            $byId[$d['id']] = $d;
        }

        $recs = [];
        foreach (array_slice($scored, 0, $limit) as $s) {
            if (!isset($byId[$s['id']])) continue;
            
            $dest = $byId[$s['id']];
            $reasons = $this->generateReasons($s, $dest, $weightsResult['weights'], $profile);

            $recs[] = [
                'destination' => $dest,
                'final_score' => $s['final_score'],
                'match_percentage' => (int)round($s['final_score'] * 100),
                'scores' => [
                    'interest' => $s['interest'],
                    'season' => $s['season'],
                    'budget' => $s['budget'],
                    'popularity' => $s['popularity'],
                    'rating' => $s['rating']
                ],
                'reasons' => $reasons
            ];
        }

        return [
            'status' => 'ok',
            'reason' => '',
            'weights' => $weightsResult['weights'],
            'recommendations' => $recs
        ];
    }

    private function computeRawScores(array $destinations, array $profile): array
    {
        $maxPop = 1;
        foreach ($destinations as $d) {
            if ($d['popularity_arrivals'] > $maxPop) $maxPop = $d['popularity_arrivals'];
        }

        $touristInterests = $profile['interests'] ?? [];
        $touristBudget = (float)($profile['budget'] ?? 0);
        $touristMonth = (int)($profile['travel_month'] ?? 0);

        $raw = [];
        foreach ($destinations as $d) {
            $matchCount = count(array_intersect($touristInterests, $d['types']));
            $interest = count($touristInterests) > 0 ? ($matchCount / count($touristInterests)) : 0;

            $season = in_array($touristMonth, $d['best_months'], true) ? 1.0 : 0.0;

            $cost = (float)$d['cost'];
            if ($cost <= $touristBudget) {
                $budget = 1.0;
            } else {
                $budget = max(0, 1 - (($cost - $touristBudget) / max(1, $touristBudget)));
            }

            $popularity = $d['popularity_arrivals'] / $maxPop;
            $rating = $d['avg_rating'] !== null ? ($d['avg_rating'] / 5) : 0.5;

            $raw[] = [
                'id' => $d['id'],
                'interest' => $interest,
                'season' => $season,
                'budget' => $budget,
                'popularity' => $popularity,
                'rating' => $rating
            ];
        }
        return $raw;
    }

    private function generateReasons(array $scores, array $dest, array $weights, array $profile): array
    {
        $candidates = [];
        
        if ($scores['interest'] >= 0.5) {
            $matched = array_intersect($profile['interests'] ?? [], $dest['types']);
            $names = array_map(fn($id) => $this->interestTypeNames[$id] ?? 'Unknown', $matched);
            $candidates['interest'] = [
                'score' => $scores['interest'],
                'weight_score' => $weights['interest'] * $scores['interest'],
                'text' => "Matches your interest in " . implode(', ', $names)
            ];
        }
        
        if ($scores['season'] >= 0.5) {
            $m = (int)($profile['travel_month'] ?? 0);
            $monthName = $m > 0 ? date('F', mktime(0, 0, 0, $m, 1)) : 'your travel month';
            $candidates['season'] = [
                'score' => $scores['season'],
                'weight_score' => $weights['season'] * $scores['season'],
                'text' => "{$monthName} is one of its most visited months"
            ];
        }

        if ($scores['budget'] >= 0.5) {
            $text = $scores['budget'] >= 1.0 ? "Fits your budget" : "A little above your budget";
            $candidates['budget'] = [
                'score' => $scores['budget'],
                'weight_score' => $weights['budget'] * $scores['budget'],
                'text' => $text
            ];
        }

        if ($scores['popularity'] >= 0.5) {
            $candidates['popularity'] = [
                'score' => $scores['popularity'],
                'weight_score' => $weights['popularity'] * $scores['popularity'],
                'text' => "Popular with visitors"
            ];
        }

        if ($dest['avg_rating'] !== null && $scores['rating'] >= 0.5) {
            $ratingStr = number_format((float)$dest['avg_rating'], 1);
            $candidates['rating'] = [
                'score' => $scores['rating'],
                'weight_score' => $weights['rating'] * $scores['rating'],
                'text' => "Rated {$ratingStr}/5 by travellers"
            ];
        }

        uksort($candidates, function($a, $b) use ($candidates) {
            $diff = $candidates[$b]['weight_score'] <=> $candidates[$a]['weight_score'];
            if ($diff !== 0) return $diff;
            $order = ['interest' => 0, 'season' => 1, 'budget' => 2, 'popularity' => 3, 'rating' => 4];
            return $order[$a] <=> $order[$b];
        });

        $reasons = [];
        foreach (array_slice(array_keys($candidates), 0, 2) as $key) {
            $reasons[] = $candidates[$key]['text'];
        }
        return $reasons;
    }
}