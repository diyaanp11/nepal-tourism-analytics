<?php /** @var array<string, string> $flashes */ ?>
<?php foreach ($flashes as $type => $msg): ?>
    <?php
    $cls = match ($type) {
        'error'   => 'danger',
        'success' => 'success',
        'info'    => 'info',
        default   => 'secondary',
    };
    ?>
    <div class="alert alert-<?= $cls ?>" role="alert">
        <?= e($msg) ?>
        <button type="button" class="btn-close" data-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endforeach; ?>