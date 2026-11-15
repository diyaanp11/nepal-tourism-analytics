<?php /** @var array $dest */ /** @var array $favourites */ ?>
<div class="dest-card">
    <div class="dest-img-wrap">
        <a href="<?= url('tourist/destination/' . $dest['id']) ?>">
            <img src="<?= image_src($dest['image']) ?>" alt="<?= e($dest['name']) ?>" onerror="this.src='<?= asset('img/placeholder.svg') ?>'">
        </a>
        <form method="post" action="<?= url('tourist/favourites/toggle/' . $dest['id']) ?>" class="fav-form">
            <?= \App\Core\CSRF::field() ?>
            <button type="submit" class="fav-btn <?= in_array($dest['id'], $favourites) ? 'active' : '' ?>" aria-label="Toggle favourite">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="<?= in_array($dest['id'], $favourites) ? '#dc3545' : 'none' ?>" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
            </button>
        </form>
    </div>
    <div class="dest-info">
        <h4><?= e($dest['name']) ?></h4>
        <div class="dest-types">
            <?php foreach (array_slice($dest['type_names'], 0, 3) as $tn): ?>
                <span><?= e($tn) ?></span>
            <?php endforeach; ?>
        </div>
        <a href="<?= url('tourist/destination/' . $dest['id']) ?>" class="btn btn-outline btn-sm" style="margin-top: auto; width: 100%;">See more</a>
    </div>
</div>