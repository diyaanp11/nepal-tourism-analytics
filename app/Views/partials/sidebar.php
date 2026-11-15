<?php /** @var string $role */ 
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
        <a class="sidebar-link <?= isActive('tourist/dashboard', $currentUri) ?>" href="<?= url('tourist/dashboard') ?>">Dashboard</a>
        <a class="sidebar-link <?= isActive('tourist/favourites', $currentUri) ?>" href="<?= url('tourist/favourites') ?>">Favourites</a>
        <a class="sidebar-link <?= isActive('tourist/reviews', $currentUri) ?>" href="<?= url('tourist/reviews') ?>">My Reviews</a>
    <?php endif; ?>
</aside>