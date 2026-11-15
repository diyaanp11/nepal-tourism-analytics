<?php
declare(strict_types=1);

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) return;
    $rel = substr($class, strlen($prefix));
    $file = __DIR__ . '/../app/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require $file;
});

use App\Services\Analytics\DemandForecast;
use App\Services\Analytics\BestMonths;
use App\Services\Analytics\DestinationLifecycle;
use App\Services\Analytics\YoYGrowth;
use App\Services\Recommendation\EntropyWeightCalculator;

$passed = 0;
$failed = 0;

function assert_close($expected, $actual, float $tol, string $msg): void
{
    global $passed, $failed;
    if ($actual === null) {
        echo "FAIL: {$msg} (null received)\n";
        $failed++;
        return;
    }
    if (abs($expected - $actual) <= $tol) {
        echo "PASS: {$msg}\n";
        $passed++;
    } else {
        echo "FAIL: {$msg} (expected {$expected}, got {$actual})\n";
        $failed++;
    }
}

function assert_eq($expected, $actual, string $msg): void
{
    global $passed, $failed;
    if ($expected === $actual) {
        echo "PASS: {$msg}\n";
        $passed++;
    } else {
        echo "FAIL: {$msg} (expected " . json_encode($expected) . ", got " . json_encode($actual) . ")\n";
        $failed++;
    }
}

function makeNationalRows(int $year, int $total): array
{
    $perMonth = intdiv($total, 12);
    $rem = $total - $perMonth * 12;
    $rows = [];
    for ($m = 1; $m <= 12; $m++) {
        $rows[] = ['year' => $year, 'month' => $m, 'arrivals' => $perMonth + ($m == 12 ? $rem : 0)];
    }
    return $rows;
}

function makeDestRows(string $id, string $name, int $year, int $total): array
{
    $perMonth = intdiv($total, 12);
    $rem = $total - $perMonth * 12;
    $rows = [];
    for ($m = 1; $m <= 12; $m++) {
        $rows[] = ['dest_id' => $id, 'dest_name' => $name, 'year' => $year, 'month' => $m, 'arrivals' => $perMonth + ($m == 12 ? $rem : 0)];
    }
    return $rows;
}

echo "===== Forecast: Linear series Jan 2023 - Dec 2024 (t = 1..24) =====\n";
$rows = [];
$t = 0;
for ($y = 2023; $y <= 2024; $y++) {
    for ($m = 1; $m <= 12; $m++) {
        $t++;
        $rows[] = ['year' => $y, 'month' => $m, 'arrivals' => 100 + 10 * $t];
    }
}
$result = (new DemandForecast())->compute($rows);
assert_eq('ok', $result['status'], 'Test 1: status');
assert_close(10.0, $result['growth'], 0.001, 'Test 1: growth=10');
assert_close(100.0, $result['baseline'], 0.001, 'Test 1: baseline=100');
for ($m = 1; $m <= 12; $m++) {
    assert_close(1.0, $result['seasonal_indices'][$m], 0.001, "Test 1: index[{$m}]=1");
}
assert_close(350.0, $result['forecast'], 0.001, 'Test 1: forecast=350');

echo "\n===== Forecast: Same series missing Jun/Jul/Aug 2023 =====\n";
$rows = [];
$t = 0;
for ($y = 2023; $y <= 2024; $y++) {
    for ($m = 1; $m <= 12; $m++) {
        $t++;
        if ($y === 2023 && in_array($m, [6, 7, 8], true)) continue;
        $rows[] = ['year' => $y, 'month' => $m, 'arrivals' => 100 + 10 * $t];
    }
}
$result = (new DemandForecast())->compute($rows);
assert_eq('ok', $result['status'], 'Test 2: status');
assert_close(10.0, $result['growth'], 0.001, 'Test 2: growth=10');
assert_close(100.0, $result['baseline'], 0.001, 'Test 2: baseline=100');
for ($m = 1; $m <= 12; $m++) {
    assert_close(1.0, $result['seasonal_indices'][$m], 0.001, "Test 2: index[{$m}]=1");
}
assert_close(350.0, $result['forecast'], 0.001, 'Test 2: forecast=350');

echo "\n===== Best months: 2 years identical values =====\n";
$values = [50, 60, 120, 130, 70, 40, 30, 35, 80, 150, 140, 60];
$rows = [];
foreach ([2023, 2024] as $y) {
    for ($m = 1; $m <= 12; $m++) {
        $rows[] = ['year' => $y, 'month' => $m, 'arrivals' => $values[$m - 1]];
    }
}
$result = (new BestMonths())->pick($rows);
assert_eq('ok', $result['status'], 'Test 3: status');
assert_close(80.4167, $result['overall_average'], 0.001, 'Test 3: overall avg=80.4167');
assert_close(1.865, $result['indices'][10], 0.001, 'Test 3: Oct index=1.865');
assert_close(1.741, $result['indices'][11], 0.001, 'Test 3: Nov index=1.741');
assert_close(1.617, $result['indices'][4], 0.001, 'Test 3: Apr index=1.617');
assert_eq([10, 11, 4], $result['best_months'], 'Test 3: best months [10,11,4]');

echo "\n===== Best months: only Jan-Jun in two years =====\n";
$rows = [];
for ($y = 2023; $y <= 2024; $y++) {
    for ($m = 1; $m <= 6; $m++) {
        $rows[] = ['year' => $y, 'month' => $m, 'arrivals' => 100];
    }
}
$result = (new BestMonths())->pick($rows);
assert_eq('insufficient_data', $result['status'], 'Test 4: insufficient_data');

echo "\n===== Lifecycle: complete data =====\n";
$national = array_merge(
    makeNationalRows(2024, 1000000),
    makeNationalRows(2025, 1100000)
);
$dests = array_merge(
    makeDestRows('A', 'A', 2024, 100000), makeDestRows('A', 'A', 2025, 140000),
    makeDestRows('B', 'B', 2024, 60000),  makeDestRows('B', 'B', 2025, 63000),
    makeDestRows('C', 'C', 2024, 90000),  makeDestRows('C', 'C', 2025, 85500),
    makeDestRows('D', 'D', 2024, 20000),  makeDestRows('D', 'D', 2025, 30000)
);
$result = (new DestinationLifecycle())->classify($national, $dests);
assert_eq('ok', $result['status'], 'Test 5: status');
assert_close(10.0, $result['growth_cutoff'], 0.001, 'Test 5: growth_cutoff=10');
assert_close(7.2386, $result['share_cutoff'], 0.001, 'Test 5: share_cutoff=7.2386');

$byId = [];
foreach ($result['classified'] as $c) $byId[$c['id']] = $c;
assert_close(12.727, $byId['A']['share'], 0.001, 'Test 5: A share=12.727');
assert_close(5.727, $byId['B']['share'], 0.001, 'Test 5: B share=5.727');
assert_close(7.773, $byId['C']['share'], 0.001, 'Test 5: C share=7.773');
assert_close(2.727, $byId['D']['share'], 0.001, 'Test 5: D share=2.727');
assert_eq('Star', $byId['A']['stage'], 'Test 5: A=Star');
assert_eq('Dog', $byId['B']['stage'], 'Test 5: B=Dog');
assert_eq('Cash Cow', $byId['C']['stage'], 'Test 5: C=Cash Cow');
assert_eq('Question Mark', $byId['D']['stage'], 'Test 5: D=Question Mark');

echo "\n===== Lifecycle: no earlier year =====\n";
$national = makeNationalRows(2024, 1000000);
$result = (new DestinationLifecycle())->classify($national, []);
assert_eq('insufficient_data', $result['status'], 'Test 6: insufficient_data');

echo "\n===== Entropy weights =====\n";
$rawScores = [
    ['id' => 'D1', 'interest' => 1.0, 'season' => 1.0, 'budget' => 1.0, 'popularity' => 1.0, 'rating' => 0.9],
    ['id' => 'D2', 'interest' => 0.5, 'season' => 1.0, 'budget' => 0.6, 'popularity' => 0.6, 'rating' => 0.8],
    ['id' => 'D3', 'interest' => 0.0, 'season' => 0.0, 'budget' => 1.0, 'popularity' => 0.3, 'rating' => 0.5],
    ['id' => 'D4', 'interest' => 0.0, 'season' => 0.0, 'budget' => 0.2, 'popularity' => 0.1, 'rating' => 0.7],
];
$calc = new EntropyWeightCalculator();
$result = $calc->compute($rawScores);
assert_eq('ok', $result['status'], 'Test 7: status');
assert_close(0.4591, $result['entropies']['interest'], 0.001, 'Test 7: entropy interest=0.4591');
assert_close(0.5000, $result['entropies']['season'], 0.001, 'Test 7: entropy season=0.5');
assert_close(0.9046, $result['entropies']['budget'], 0.001, 'Test 7: entropy budget=0.9046');
assert_close(0.8239, $result['entropies']['popularity'], 0.001, 'Test 7: entropy popularity=0.8239');
assert_close(0.9843, $result['entropies']['rating'], 0.001, 'Test 7: entropy rating=0.9843');
assert_close(0.4073, $result['weights']['interest'], 0.001, 'Test 7: weight interest=0.4073');
assert_close(0.3765, $result['weights']['season'], 0.001, 'Test 7: weight season=0.3765');
assert_close(0.0718, $result['weights']['budget'], 0.001, 'Test 7: weight budget=0.0718');
assert_close(0.1326, $result['weights']['popularity'], 0.001, 'Test 7: weight popularity=0.1326');
assert_close(0.0118, $result['weights']['rating'], 0.001, 'Test 7: weight rating=0.0118');
assert_close(1.0, array_sum($result['weights']), 0.001, 'Test 7: weights sum to 1');

$scored = $calc->applyWeights($rawScores, $result['weights']);
$byId = [];
foreach ($scored as $s) $byId[$s['id']] = $s;
assert_close(0.9988, $byId['D1']['final_score'], 0.001, 'Test 7: score D1=0.9988');
assert_close(0.7122, $byId['D2']['final_score'], 0.001, 'Test 7: score D2=0.7122');
assert_close(0.1175, $byId['D3']['final_score'], 0.001, 'Test 7: score D3=0.1175');
assert_close(0.0359, $byId['D4']['final_score'], 0.001, 'Test 7: score D4=0.0359');

echo "\n===== YoY Growth =====\n";
$yoy = new YoYGrowth();
$result = $yoy->compute(140000, 100000);
assert_eq('ok', $result['status'], 'Test 8: status');
assert_close(40.0, $result['growth'], 0.001, 'Test 8: growth=40.0');

echo "\n=========================\n";
echo "PASSED: {$passed}\n";
echo "FAILED: {$failed}\n";
echo "=========================\n";
exit($failed > 0 ? 1 : 0);