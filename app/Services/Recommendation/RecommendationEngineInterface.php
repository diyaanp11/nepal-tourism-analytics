<?php
declare(strict_types=1);

namespace App\Services\Recommendation;

interface RecommendationEngineInterface
{
    public function recommend(array $destinations, array $profile, int $limit = 5): array;
}