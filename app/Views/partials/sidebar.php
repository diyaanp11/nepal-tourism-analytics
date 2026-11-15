<?php 
/** @var string $role */ 
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
function isActive(string $path, string $currentUri): string {
    return strpos($currentUri, $path) !== false ? 'active' : '';
}
?>
<aside class="sidebar">
    <?php if ($role === 'admin'): ?>
        <a class="sidebar-link <?= isActive('admin/dashboard', $currentUri) ?>" href="<?= url('admin/dashboard') ?>">Dashboard</a>
        <a class="sidebar-link <?= isActive('admin/import', $currentUri) ?>" href="<?= url('admin/import') ?>">Import CSV Data</a>
        <a class="sidebar-link <?= isActive('admin/destinations', $currentUri) ?>" href="<?= url('admin/destinations') ?>">Destinations</a>
        <a class="sidebar-link <?= isActive('admin/users', $currentUri) ?>" href="<?= url('admin/users') ?>">Users</a>
        <a class="sidebar-link <?= isActive('admin/logs', $currentUri) ?>" href="<?= url('admin/logs') ?>">System Logs</a>
    <?php elseif ($role === 'researcher'): ?>
        <a class="sidebar-link <?= isActive('researcher/dashboard', $currentUri) ?>" href="<?= url('researcher/dashboard') ?>">Analytics Dashboard</a>
    <?php elseif ($role === 'tourist'): ?>
        <a class="sidebar-link <?= (strpos($currentUri, 'tourist/dashboard') !== false && !str_contains($currentUri, 'rec=')) ? 'active' : '' ?>" href="<?= url('tourist/dashboard') ?>">Dashboard</a>
        <a class="sidebar-link <?= isActive('tourist/favourites', $currentUri) ?>" href="<?= url('tourist/favourites') ?>">Favourites</a>
        <a class="sidebar-link <?= isActive('tourist/reviews', $currentUri) ?>" href="<?= url('tourist/reviews') ?>">My Reviews</a>
        
        <div style="padding: 1rem; border-top: 1px solid #dee2e6; margin-top: 1rem;">
            <h4 style="font-size: 14px; margin-bottom: 0.5rem; color: #192b25;">Find my places</h4>
            <?php 
                $repo = new \App\Services\Recommendation\RecommendationRepository();
                $sideData = $repo->getSidebarData(\App\Core\Auth::id());
                
                $budgetVal = (float)($sideData['profile']['budget_npr'] ?? 0);
                if ($budgetVal <= 0) $budgetVal = $sideData['max_cost'];
            ?>
            <form method="post" action="<?= url('tourist/preferences') ?>">
                <?= \App\Core\CSRF::field() ?>
                
                <div style="margin-bottom: 1rem;">
                    <label class="form-label" style="font-size: 12px; margin-bottom: 0.2rem;">Interests</label>
                    <?php foreach ($sideData['types'] as $t): ?>
                        <label style="display: block; font-size: 12px; margin-bottom: 0.2rem;">
                            <input type="checkbox" name="interests[]" value="<?= $t['id'] ?>" <?= in_array((int)$t['id'], $sideData['selected_interests']) ? 'checked' : '' ?>>
                            <?= e($t['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label class="form-label" style="font-size: 12px; margin-bottom: 0.2rem;">Max Budget (NPR)</label>
                    <input type="range" name="budget" min="0" max="<?= $sideData['max_cost'] ?>" value="<?= $budgetVal ?>" 
                           style="width: 100%;" oninput="this.nextElementSibling.value = this.value">
                    <output style="font-size: 12px; color: #495057; display: block; text-align: right;"><?= $budgetVal ?></output>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label class="form-label" style="font-size: 12px; margin-bottom: 0.2rem;">Travel Month</label>
                    <select name="travel_month" class="form-control" style="font-size: 12px; padding: 4px;">
                        <option value="">Any Month</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $sideData['profile']['travel_month'] == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-sm w-100" style="margin-bottom: 0.5rem;">Show recommendations</button>
                <a href="<?= url('tourist/dashboard') ?>" class="btn btn-outline btn-sm w-100">Clear</a>
            </form>
        </div>
    <?php endif; ?>
</aside>