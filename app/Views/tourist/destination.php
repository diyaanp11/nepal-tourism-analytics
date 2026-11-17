<div class="card" style="display: flex; gap: 2rem; flex-wrap: wrap; border-radius: 12px; overflow: hidden; padding: 0;">
    <div style="flex: 1; min-width: 300px; position: relative;">
        <img src="<?= image_src($dest['image']) ?>" alt="<?= e($dest['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; min-height: 400px;" onerror="this.src='<?= asset('img/placeholder.svg') ?>'">
    </div>
    <div style="flex: 1; min-width: 300px; padding: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: start;">
            <h1 style="font-family: Georgia, serif; color: #192b25; margin-top: 0; font-size: 2.5rem;"><?= e($dest['name']) ?></h1>
            <form method="post" action="<?= url('tourist/favourites/toggle/' . $dest['id']) ?>">
                <?= \App\Core\CSRF::field() ?>
                <button type="submit" class="fav-btn <?= $isFavourite ? 'active' : '' ?>" style="background: #f8f9fa; padding: 12px; border: 1px solid #dee2e6; border-radius: 50%;">
                    <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="<?= $isFavourite ? '#dc3545' : 'none' ?>" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </button>
            </form>
        </div>
        
        <div class="dest-types" style="margin-bottom: 1.5rem;">
            <?php foreach ($dest['types'] as $t): ?>
                <span style="background: #fdfbf7; border: 1px solid #d5a45e; color: #192b25; border-radius: 20px; padding: 4px 12px; font-size: 13px; margin-right: 5px;"><?= e($t) ?></span>
            <?php endforeach; ?>
        </div>

        <!-- BIG VISUAL STARS FOR AVERAGE RATING -->
        <?php if ($avgRating !== null): ?>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 1.5rem; background: #fdfbf7; padding: 10px 15px; border-radius: 8px; border: 1px solid #dee2e6;">
                <span style="font-size: 24px; color: #d5a45e; letter-spacing: 2px;">
                    <?php echo str_repeat('★', (int)round($avgRating)) . str_repeat('☆', 5 - (int)round($avgRating)); ?>
                </span>
                <span style="font-size: 15px; font-weight: 600; color: #192b25;">
                    <?= number_format($avgRating, 1) ?> / 5.0 
                    <span style="color: #6c757d; font-weight: normal; font-size: 13px;">(<?= count($reviews) ?> reviews)</span>
                </span>
            </div>
        <?php endif; ?>

        <p style="font-size: 15px; line-height: 1.8; color: #495057; margin-bottom: 2rem;"><?= e($dest['description']) ?></p>

        <div style="font-size: 14px; background: #f8f9fa; padding: 1.5rem; border-radius: 8px;">
            <p style="margin-bottom: 0.5rem;"><strong>Cost:</strong> <?= number_format((float)$dest['avg_cost_npr']) ?> NPR</p>
            <p style="margin-bottom: 0.5rem;"><strong>Most visited months:</strong> <?= e($dest['best_months_str']) ?></p>
            <?php if ($dest['latitude'] && $dest['longitude']): ?>
                <p style="margin-bottom: 1rem;"><strong>Coordinates:</strong> <?= e((string)$dest['latitude']) ?>, <?= e((string)$dest['longitude']) ?></p>
                <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $dest['latitude'] ?>,<?= $dest['longitude'] ?>" target="_blank" class="btn btn-primary btn-sm">View on map and distance</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card" style="margin-top: 1.5rem; border-radius: 12px;">
    <h3 style="font-family: Georgia, serif; font-size: 1.5rem; margin-bottom: 1.5rem;">Reviews</h3>
    
    <?php if (empty($reviews)): ?>
        <p style="color: #6c757d; margin-bottom: 2rem;">No reviews yet. Be the first to review this place!</p>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1.5rem; margin-bottom: 2rem;">
            <?php foreach ($reviews as $r): ?>
                <div style="border-bottom: 1px solid #eee; padding-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <strong style="color: #192b25; font-size: 15px;"><?= e($r['user_name']) ?></strong>
                        <span style="color: #d5a45e; font-size: 16px; letter-spacing: 1px;">
                            <?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?>
                        </span>
                    </div>
                    <div style="font-size: 12px; color: #adb5bd; margin-bottom: 0.5rem;"><?= date('M j, Y', strtotime($r['created_at'])) ?></div>
                    <p style="margin: 0; font-size: 14px; color: #495057; line-height: 1.6;"><?= nl2br(e($r['comment'])) ?></p>
                    
                    <?php if ($r['user_id'] == \App\Core\Auth::id()): ?>
                        <form method="post" action="<?= url('tourist/reviews/delete/' . $dest['id']) ?>" style="margin-top: 0.5rem;">
                            <?= \App\Core\CSRF::field() ?>
                            <button type="submit" class="btn-link" style="font-size: 12px; color: #dc3545; text-decoration: none;" onclick="return confirm('Delete your review?');">Delete Review</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div style="background: #fdfbf7; padding: 1.5rem; border-radius: 8px; border: 1px solid #dee2e6;">
        <h4 style="margin-bottom: 1rem; font-family: Georgia, serif;"><?= $userReview ? 'Edit your review' : 'Write a review' ?></h4>
        <form method="post" action="<?= url('tourist/reviews/store/' . $dest['id']) ?>">
            <?= \App\Core\CSRF::field() ?>
            <div style="margin-bottom: 1rem;">
                <label class="form-label" style="font-size: 13px;">Rating</label>
                <select name="rating" class="form-control" style="width: 200px;" required>
                    <option value="">Select...</option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>" <?= ($userReview && $userReview['rating'] == $i) ? 'selected' : '' ?>><?= str_repeat('★', $i) ?> (<?= $i ?>)</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div style="margin-bottom: 1rem;">
                <label class="form-label" style="font-size: 13px;">Comment (optional, max 1000 chars)</label>
                <textarea name="comment" class="form-control" rows="3" maxlength="1000"><?= e($userReview['comment'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><?= $userReview ? 'Update Review' : 'Submit Review' ?></button>
        </form>
    </div>
</div>