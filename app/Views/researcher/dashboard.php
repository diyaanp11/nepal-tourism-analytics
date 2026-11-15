<div class="d-flex justify-content-between align-items-center" style="margin-bottom: 1.5rem;">
    <h1 class="page-title" style="margin: 0;">Researcher Analytics</h1>
    <div class="d-flex gap-2 flex-wrap">
        <?php 
            $qStr = http_build_query(array_filter($filters['current']));
            $qStr = $qStr ? '&' . $qStr : '';
        ?>
        <a href="<?= url('researcher/export?type=trend' . $qStr) ?>" class="btn btn-outline btn-sm">Export Trend</a>
        <a href="<?= url('researcher/export?type=nationality' . $qStr) ?>" class="btn btn-outline btn-sm">Export Nationality</a>
        <a href="<?= url('researcher/export?type=purpose' . $qStr) ?>" class="btn btn-outline btn-sm">Export Purpose</a>
        <a href="<?= url('researcher/export?type=lifecycle' . $qStr) ?>" class="btn btn-outline btn-sm">Export Lifecycle</a>
        <a href="<?= url('researcher/export?type=forecast' . $qStr) ?>" class="btn btn-outline btn-sm">Export Forecast</a>
        <a href="<?= url('researcher/export?type=heatmap' . $qStr) ?>" class="btn btn-outline btn-sm">Export Heatmap</a>
    </div>
</div>

<div class="card no-print" style="margin-bottom: 1.5rem; padding: 1rem;">
    <form method="GET" action="<?= url('researcher/dashboard') ?>" class="d-flex flex-wrap gap-2 align-items-end">
        <div>
            <label class="form-label" style="font-size: 12px;">Year</label>
            <select name="year" class="form-control" style="width: 120px;">
                <option value="">All Years</option>
                <?php foreach ($filters['years'] as $y): ?>
                    <option value="<?= $y ?>" <?= $filters['current']['year'] == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size: 12px;">Region</label>
            <select name="region" class="form-control" style="width: 150px;">
                <option value="">All Regions</option>
                <?php foreach ($filters['regions'] as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= $filters['current']['region'] == $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size: 12px;">Nationality</label>
            <select name="nationality" class="form-control" style="width: 150px;">
                <option value="">All Countries</option>
                <?php foreach ($filters['countries'] as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filters['current']['nationality'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size: 12px;">Purpose</label>
            <select name="purpose" class="form-control" style="width: 150px;">
                <option value="">All Purposes</option>
                <?php foreach ($filters['purposes'] as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $filters['current']['purpose'] == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" style="font-size: 12px;">Destination</label>
            <select name="destination" class="form-control" style="width: 150px;">
                <option value="">All Destinations</option>
                <?php foreach ($filters['destinations'] as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $filters['current']['destination'] == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="<?= url('researcher/dashboard') ?>" class="btn btn-outline" style="margin-left: 0.5rem;">Clear</a>
        </div>
    </form>
    <div style="font-size: 12px; color: #6c757d; margin-top: 0.5rem;">
        <strong>Note:</strong> Year affects Trend, Nationality, and Purpose. Region/Destination affects Heatmap, Forecast, and Lifecycle dots.
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- 1. Arrival Trend -->
    <div class="card">
        <h3>National Arrival Trend</h3>
        <div style="height: 250px;">
            <?php if (!empty($emptyReasons['trend'])): ?>
                <p class="text-center" style="padding-top: 100px; color: #6c757d;"><?= e($emptyReasons['trend']) ?></p>
            <?php else: ?>
                <canvas id="trendChart"></canvas>
            <?php endif; ?>
        </div>
        <p style="color: #495057; font-size: 13px; margin-top: 1rem; font-weight: 500;">
            <?= e($descriptions['trend']) ?>
        </p>
    </div>

    <!-- 2. Nationality Mix -->
    <div class="card">
        <h3>Top Nationalities (<?= $latestYear ?>)</h3>
        <div style="height: 250px;">
            <?php if (!empty($emptyReasons['nationality'])): ?>
                <p class="text-center" style="padding-top: 100px; color: #6c757d;"><?= e($emptyReasons['nationality']) ?></p>
            <?php else: ?>
                <canvas id="nationalityChart"></canvas>
            <?php endif; ?>
        </div>
        <p style="color: #495057; font-size: 13px; margin-top: 1rem; font-weight: 500;">
            <?= e($descriptions['nationality']) ?>
        </p>
    </div>

    <!-- 3. Purpose Mix -->
    <div class="card">
        <h3>Purpose of Visit (<?= $latestYear ?>)</h3>
        <div style="height: 250px;">
            <?php if (!empty($emptyReasons['purpose'])): ?>
                <p class="text-center" style="padding-top: 100px; color: #6c757d;"><?= e($emptyReasons['purpose']) ?></p>
            <?php else: ?>
                <canvas id="purposeChart"></canvas>
            <?php endif; ?>
        </div>
        <p style="color: #495057; font-size: 13px; margin-top: 1rem; font-weight: 500;">
            <?= e($descriptions['purpose']) ?>
        </p>
    </div>

    <!-- 4. Lifecycle -->
    <div class="card">
        <h3>Destination Lifecycle</h3>
        <div style="height: 250px;">
            <?php if (!empty($emptyReasons['lifecycle'])): ?>
                <p class="text-center" style="padding-top: 100px; color: #6c757d;"><?= e($emptyReasons['lifecycle']) ?></p>
            <?php else: ?>
                <canvas id="lifecycleChart"></canvas>
            <?php endif; ?>
        </div>
        <p style="color: #495057; font-size: 13px; margin-top: 1rem; font-weight: 500;">
            <?= e($descriptions['lifecycle']) ?>
        </p>
    </div>
</div>

<!-- 5. Demand Forecast -->
<div class="card" style="margin-top: 1.5rem;">
    <h3>Forecast: Next Month</h3>
    <div style="height: 250px;">
        <?php if (!empty($emptyReasons['forecast'])): ?>
            <p class="text-center" style="padding-top: 100px; color: #6c757d;"><?= e($emptyReasons['forecast']) ?></p>
        <?php else: ?>
            <canvas id="forecastChart"></canvas>
        <?php endif; ?>
    </div>
    <p style="color: #495057; font-size: 13px; margin-top: 1rem; font-weight: 500;">
        <?= e($descriptions['forecast']) ?>
    </p>
</div>

<!-- 6. Heatmap -->
<div class="card" style="margin-top: 1.5rem;">
    <h3>Seasonal Arrival Heatmap</h3>
    <p style="color: #6c757d; font-size: 13px; margin-bottom: 1rem;">
        Colors represent the Seasonal Index. 
        <span style="display:inline-block;width:12px;height:12px;background:#d5a45e;border-radius:2px;margin-left:10px;"></span> <strong>Peak (≥1.2)</strong> 
        <span style="display:inline-block;width:12px;height:12px;background:#fdfbf7;border:1px solid #dee2e6;border-radius:2px;margin-left:10px;"></span> <strong>Shoulder (0.8-1.2)</strong> 
        <span style="display:inline-block;width:12px;height:12px;background:#e9ecef;border-radius:2px;margin-left:10px;"></span> <strong>Low (<0.8)</strong>
    </p>
    <div style="overflow-x: auto;">
        <table class="table" style="text-align: center;">
            <thead>
                <tr>
                    <th style="text-align: left;">Destination</th>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <th><?= date('M', mktime(0, 0, 0, $m, 1)) ?></th>
                    <?php endfor; ?>
                    <th style="text-align: left; min-width: 200px;">Insight</th>
                </tr>
            </thead>
            <tbody id="heatmapBody">
                <!-- Populated by JS securely -->
            </tbody>
        </table>
    </div>
</div>

<script>
    window.dashboardData = {
        trend: <?= json_encode($trendData, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,
        nationality: <?= json_encode($nationalityData, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,
        purpose: <?= json_encode($purposeData, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,
        lifecycle: <?= json_encode($lifecycleData, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,
        lifecycleCutoffs: <?= json_encode($lifecycleCutoffs ?? null, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,
        forecast: <?= json_encode($forecastData, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,
        heatmap: <?= json_encode($heatmapData, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>
    };
</script>
<script src="<?= asset('vendor/chartjs/chart.umd.min.js') ?>"></script>
<script src="<?= asset('js/researcher.js') ?>"></script>