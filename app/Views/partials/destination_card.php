<?php /** @var array $dest */ /** @var array $favourites */ ?>
<div class="dest-card" style="transition: transform 0.2s; cursor: pointer;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
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
    <div class="dest-info" style="background: #fff;">
        <h4 style="font-family: Georgia, serif; font-size: 16px; margin-bottom: 0.5rem;"><?= e($dest['name']) ?></h4>
        <div class="dest-types" style="margin-bottom: 0.5rem;">
            <?php foreach (array_slice($dest['type_names'], 0, 3) as $tn): ?>
                <span style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 2px 8px; font-size: 11px; color: #6c757d;"><?= e($tn) ?></span>
            <?php endforeach; ?>
        </div>
        
        <!-- STARS SHOW HERE -->
        <div style="font-size: 14px; color: #d5a45e; margin-bottom: 1rem; display: flex; align-items: center; gap: 5px;">
            <?php if ($dest['avg_rating'] !== null): ?>
                <span style="letter-spacing: 2px;"><?= str_repeat('★', (int)round($dest['avg_rating'])) . str_repeat('☆', 5 - (int)round($dest['avg_rating'])) ?></span>
                <span style="color: #6c757d; font-size: 11px; font-family: sans-serif;">(<?= $dest['review_count'] ?>)</span>
            <?php else: ?>
                <span style="color: #adb5bd; font-size: 11px;">No reviews yet</span>
            <?php endif; ?>
        </div>

        <a href="<?= url('tourist/destination/' . $dest['id']) ?>" class="btn btn-outline btn-sm" style="margin-top: auto; width: 100%;">See more</a>
    </div>
</div>