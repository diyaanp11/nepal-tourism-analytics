<div class="card" style="display: flex; gap: 2rem; flex-wrap: wrap;">
    <div style="flex: 1; min-width: 300px;">
        <img src="<?= image_src($dest['image']) ?>" alt="<?= e($dest['name']) ?>" style="width: 100%; border-radius: 8px; object-fit: cover; max-height: 400px;" onerror="this.src='<?= asset('img/placeholder.svg') ?>'">
    </div>
    <div style="flex: 1; min-width: 300px;">
        <div style="display: flex; justify-content: space-between; align-items: start;">
            <h1 style="font-family: Georgia, serif; color: #192b25; margin-top: 0;"><?= e($dest['name']) ?></h1>
            <form method="post" action="<?= url('tourist/favourites/toggle/' . $dest['id']) ?>">
                <?= \App\Core\CSRF::field() ?>
                <button type="submit" class="fav-btn <?= $isFavourite ? 'active' : '' ?>" style="background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6;">
                    <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="<?= $isFavourite ? '#dc3545' : 'none' ?>" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </button>
            </form>
        </div>
        
        <div class="dest-types" style="margin-bottom: 1rem;">
            <?php foreach ($dest['types'] as $t): ?>
                <span style="padding: 4px 10px; font-size: 13px;"><?= e($t) ?></span>
            <?php endforeach; ?>
        </div>

        <p style="font-size: 15px; line-height: 1.6; color: #495057;"><?= e($dest['description']) ?></p>

        <div style="margin-top: 1.5rem; font-size: 14px;">
            <p><strong>Cost:</strong> <?= number_format((float)$dest['avg_cost_npr']) ?> NPR</p>
            <p><strong>Most visited months:</strong> <?= e($dest['best_months_str']) ?></p>
            <?php if ($dest['latitude'] && $dest['longitude']): ?>
                <p><strong>Coordinates:</strong> <?= e((string)$dest['latitude']) ?>, <?= e((string)$dest['longitude']) ?></p>
                <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $dest['latitude'] ?>,<?= $dest['longitude'] ?>" target="_blank" class="btn btn-primary btn-sm">View on map and distance</a>
            <?php endif; ?>
        </div>
        
        <?php if ($avgRating !== null): ?>
            <div style="margin-top: 1.5rem;">
                <strong>Rating:</strong> <?= number_format($avgRating, 1) ?>/5 (<?= count($reviews) ?> reviews)
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="margin-top: 1.5rem;">
    <h3>Reviews</h3>
    
    <?php if (empty($reviews)): ?>
        <p style="color: #6c757d;">No reviews yet. Be the first to review this place!</p>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem;">
            <?php foreach ($reviews as $r): ?>
                <div style="border-bottom: 1px solid #eee; padding-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <strong><?= e($r['user_name']) ?></strong>
                        <span style="color: #d5a45e; font-size: 14px;"><?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?></span>
                    </div>
                    <div style="font-size: 12px; color: #6c757d; margin-bottom: 0.5rem;"><?= date('M j, Y', strtotime($r['created_at'])) ?></div>
                    <p style="margin: 0; font-size: 14px;"><?= nl2br(e($r['comment'])) ?></p>
                    
                    <?php if ($r['user_id'] == \App\Core\Auth::id()): ?>
                        <form method="post" action="<?= url('tourist/reviews/delete/' . $dest['id']) ?>" style="margin-top: 0.5rem;">
                            <?= \App\Core\CSRF::field() ?>
                            <button type="submit" class="btn-link" style="font-size: 12px; color: #dc3545;" onclick="return confirm('Delete your review?');">Delete Review</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h4 style="margin-bottom: 1rem;"><?= $userReview ? 'Edit your review' : 'Write a review' ?></h4>
    <form method="post" action="<?= url('tourist/reviews/store/' . $dest['id']) ?>">
        <?= \App\Core\CSRF::field() ?>
        <div style="margin-bottom: 1rem;">
            <label class="form-label">Rating</label>
            <select name="rating" class="form-control" style="width: 150px;" required>
                <option value="">Select...</option>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>" <?= ($userReview && $userReview['rating'] == $i) ? 'selected' : '' ?>><?= $i ?> Star<?= $i > 1 ? 's' : '' ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div style="margin-bottom: 1rem;">
            <label class="form-label">Comment (optional, max 1000 chars)</label>
            <textarea name="comment" class="form-control" rows="3" maxlength="1000"><?= e($userReview['comment'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><?= $userReview ? 'Update Review' : 'Submit Review' ?></button>
    </form>
</div>