<?php
$flashes = [];
foreach (['error', 'success', 'info'] as $_type) {
    if (($_m = flash($_type)) !== null) { $flashes[$_type] = $_m; }
}
unset($_type, $_m);

$isAuth = \App\Core\Auth::check();
$role = \App\Core\Auth::role();
$roleName = $role ? ucfirst($role) : '';
$userName = \App\Core\Session::get('user_name');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? config('app.name')) ?> - <?= e(config('app.name')) ?></title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>

<?php if ($isAuth): ?>
    <nav class="navbar">
        <div class="d-flex align-items-center">
            <a class="navbar-brand" href="<?= url('/') ?>"><?= e(config('app.name')) ?></a>
            <span class="navbar-center">| <?= e($title ?? 'Dashboard') ?></span>
        </div>
        <div class="nav-actions">
            <span class="role-badge"><?= e($roleName) ?></span>
            <span style="color: #fff; font-size: 14px;"><?= e((string)$userName) ?></span>
            <button type="button" class="btn btn-primary btn-sm" id="logoutBtn">Log Out</button>
        </div>
    </nav>

    <div class="app-container">
        <?= \App\Core\View::render('partials.sidebar', ['role' => $role]) ?>

        <main class="main-content">
            <?php if (!empty($flashes)): ?>
                <div style="margin-bottom: 1rem;">
                    <?= \App\Core\View::render('partials.flash', ['flashes' => $flashes]) ?>
                </div>
            <?php endif; ?>
            
            <?= $content ?>
        </main>
    </div>

    <!-- Custom Logout Modal -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal-box">
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to log out?</p>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" id="logoutCancel">No</button>
                <form method="post" action="<?= url('logout') ?>" style="display:inline;">
                    <?= \App\Core\CSRF::field() ?>
                    <button type="submit" class="btn btn-primary">Yes, Log Out</button>
                </form>
            </div>
        </div>
    </div>

<?php else: ?>
    <nav class="navbar">
        <div class="d-flex align-items-center">
            <a class="navbar-brand" href="<?= url('/') ?>"><?= e(config('app.name')) ?></a>
        </div>
        <div class="nav-actions">
            <a class="btn btn-outline" href="<?= url('login') ?>" style="color: #fff; border-color: #fff;">Log In</a>
            <a class="btn btn-primary" href="<?= url('register') ?>">Sign Up</a>
        </div>
    </nav>

    <main class="flex-grow-1" style="display: flex; flex-direction: column;">
        <?php if (!empty($flashes)): ?>
            <div style="padding: 1rem;">
                <?= \App\Core\View::render('partials.flash', ['flashes' => $flashes]) ?>
            </div>
        <?php endif; ?>
        <?= $content ?>
    </main>
<?php endif; ?>

<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>