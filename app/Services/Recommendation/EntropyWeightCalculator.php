<?php
declare(strict_types=1);

namespace App\Services\Recommendation;

final class EntropyWeightCalculator
{
    private const CRITERIA = ['interest', 'season', 'budget', 'popularity', 'rating'];

    public function compute(array $rawScores): array
    {
        $n = count($rawScores);
        if ($n < 2) {
            return [
                'status' => 'insufficient_data',
                'reason' => 'Need at least 2 destinations for entropy weighting.',
                'entropies' => null,
                'divergences' => null,
                'weights' => null
            ];
        }

        $entropies = [];
        $divergences = [];

        foreach (self::CRITERIA as $c) {
            $values = array_column($rawScores, $c);
            $sum = array_sum($values);

            if ($sum == 0) {
                $entropy = 1.0;
            } else {
                $entropy = 0.0;
                foreach ($values as $v) {
                    // Step 6 & 7: Proportion and Entropy
                    $p = $v / $sum;
                    if ($p > 0) {
                        $entropy += $p * log($p); // Σ(Proportion × ln(Proportion))
                    }
                }
                $entropy = -$entropy / log($n); // Multiply by -1/ln(n)
            }

            $entropies[$c] = $entropy;
            // Step 8: Divergence
            $divergences[$c] = 1 - $entropy;
        }

        $sumDiv = array_sum($divergences);
        $weights = [];
        foreach (self::CRITERIA as $c) {
            // Step 9: Weight
            $weights[$c] = $sumDiv > 0 ? ($divergences[$c] / $sumDiv) : (1 / count(self::CRITERIA));
        }

        return [
            'status' => 'ok',
            'reason' => '',
            'entropies' => $entropies,
            'divergences' => $divergences,
            'weights' => $weights
        ];
    }

    public function applyWeights(array $rawScores, array $weights): array
    {
        $result = [];
        foreach ($rawScores as $r) {
            $score = 0;
            // Step 10: Final Score
            foreach (self::CRITERIA as $c) {
                $score += $weights[$c] * ($r[$c] ?? 0);
            }
            $r['final_score'] = $score;
            $result[] = $r;
        }
        return $result;
    }
}