<div class="container" style="padding-top: 1rem;">
    <form method="GET" action="<?= url('tourist/dashboard') ?>" class="search-bar">
        <input type="text" name="q" class="form-control" placeholder="Search by destination name or type..." value="<?= e($search) ?>" style="border-radius: 30px; padding: 12px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
        <button type="submit" class="btn btn-primary" style="border-radius: 30px; padding: 12px 25px;">Search</button>
        <?php if ($search): ?>
            <a href="<?= url('tourist/dashboard') ?>" class="btn btn-outline" style="border-radius: 30px; padding: 12px 25px;">Clear</a>
        <?php endif; ?>
    </form>

    <?php if ($search && empty($searchResults)): ?>
        <h3 style="color: #6c757d; text-align: center; margin-top: 3rem;">No places found for "<?= e($search) ?>"</h3>
    <?php endif; ?>

    <?php if ($search && !empty($searchResults)): ?>
        <div class="netflix-row">
            <h3 style="font-family: Georgia, serif; font-size: 1.5rem; margin-bottom: 1.5rem;">Results for "<?= e($search) ?>"</h3>
            <div class="netflix-scroll">
                <?php foreach ($searchResults as $d): ?>
                    <?= \App\Core\View::render('partials.destination_card', ['dest' => $d, 'favourites' => $favourites]) ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if (!empty($similar)): ?>
        <div class="netflix-row">
            <h3 style="font-family: Georgia, serif; font-size: 1.5rem; margin-bottom: 1.5rem;">You may be interested in</h3>
            <div class="netflix-scroll">
                <?php foreach ($similar as $d): ?>
                    <?= \App\Core\View::render('partials.destination_card', ['dest' => $d, 'favourites' => $favourites]) ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($showRec && !empty($recommendations)): ?>
        <div class="netflix-row">
            <h3 style="font-family: Georgia, serif; font-size: 1.5rem; margin-bottom: 1.5rem;">Recommended for you</h3>
            
            <?php if (!empty($weights)): ?>
            <details class="weights-details" style="background: #fdfbf7; border: 1px solid #dee2e6;">
                <summary style="color: #192b25;">How were these chosen?</summary>
                <p style="margin-top: 0.5rem; color: #6c757d; font-size: 13px;">The importance of each factor is calculated from the data, not chosen by us.</p>
                <ul style="margin: 0.5rem 0 0 1rem; font-size: 13px; color: #495057;">
                    <?php foreach ($weights as $k => $w): ?>
                        <li><?= ucfirst($k) ?>: <?= round($w * 100, 1) ?>%</li>
                    <?php endforeach; ?>
                </ul>
            </details>
            <?php endif; ?>

            <div class="netflix-scroll">
                <?php foreach ($recommendations as $rec): ?>
                    <div class="dest-card" style="transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                        <div class="dest-img-wrap">
                            <a href="<?= url('tourist/destination/' . $rec['destination']['id']) ?>">
                                <img src="<?= image_src($rec['destination']['image']) ?>" alt="<?= e($rec['destination']['name']) ?>" onerror="this.src='<?= asset('img/placeholder.svg') ?>'">
                            </a>
                            <div style="position: absolute; top: 10px; left: 10px; background: #198754; color: #fff; padding: 4px 10px; border-radius: 20px; font-weight: bold; font-size: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                <?= $rec['match_percentage'] ?>% Match
                            </div>
                            <form method="post" action="<?= url('tourist/favourites/toggle/' . $rec['destination']['id']) ?>" class="fav-form">
                                <?= \App\Core\CSRF::field() ?>
                                <button type="submit" class="fav-btn <?= in_array($rec['destination']['id'], $favourites) ? 'active' : '' ?>">
                                    <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="<?= in_array($rec['destination']['id'], $favourites) ? '#dc3545' : 'none' ?>" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                                </button>
                            </form>
                        </div>
                        <div class="dest-info" style="background: #fff;">
                            <h4 style="font-family: Georgia, serif; font-size: 16px; margin-bottom: 0.5rem;"><?= e($rec['destination']['name']) ?></h4>
                            
                            <!-- Reasons list -->
                            <ul class="reasons" style="color: #198754; font-size: 12px; margin-bottom: 1rem; padding-left: 1rem;">
                                <?php foreach ($rec['reasons'] as $reason): ?>
                                    <li><?= e($reason) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <div style="font-size: 14px; color: #d5a45e; margin-bottom: 1rem; display: flex; align-items: center; gap: 5px;">
                                <?php if ($rec['destination']['avg_rating'] !== null): ?>
                                    <span style="letter-spacing: 2px;"><?= str_repeat('★', (int)round($rec['destination']['avg_rating'])) . str_repeat('☆', 5 - (int)round($rec['destination']['avg_rating'])) ?></span>
                                    <span style="color: #6c757d; font-size: 11px;">(<?= $rec['destination']['review_count'] ?>)</span>
                                <?php else: ?>
                                    <span style="color: #adb5bd; font-size: 11px;">No reviews yet</span>
                                <?php endif; ?>
                            </div>

                            <a href="<?= url('tourist/destination/' . $rec['destination']['id']) ?>" class="btn btn-outline btn-sm" style="margin-top: auto; width: 100%;">See more</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!empty($becauseYouLike)): ?>
        <div class="netflix-row">
            <h3 style="font-family: Georgia, serif; font-size: 1.5rem; margin-bottom: 1.5rem;">Because you like <?= e($likeTypeName) ?></h3>
            <div class="netflix-scroll">
                <?php foreach ($becauseYouLike as $d): ?>
                    <?= \App\Core\View::render('partials.destination_card', ['dest' => $d, 'favourites' => $favourites]) ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($mustKnow) && empty($search)): ?>
        <div class="netflix-row">
            <h3 style="font-family: Georgia, serif; font-size: 1.5rem; margin-bottom: 1.5rem;">You must know</h3>
            <div class="netflix-scroll">
                <?php foreach ($mustKnow as $d): ?>
                    <?= \App\Core\View::render('partials.destination_card', ['dest' => $d, 'favourites' => $favourites]) ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($topPlaces)): ?>
        <div class="netflix-row">
            <h3 style="font-family: Georgia, serif; font-size: 1.5rem; margin-bottom: 1.5rem;">Top places</h3>
            <?php if (!$hasArrivalsData): ?>
                <p style="font-size: 13px; color: #6c757d; margin-bottom: 1rem;">Ranking will appear once visitor data is imported.</p>
            <?php endif; ?>
            <div class="netflix-scroll" id="top-places-container">
                <?php foreach ($topPlaces as $d): ?>
                    <?= \App\Core\View::render('partials.destination_card', ['dest' => $d, 'favourites' => $favourites]) ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="netflix-row" id="new-places-row" 
     data-url="<?= url('tourist/new-destinations') ?>" 
     data-initial-since="<?= date('Y-m-d H:i:s') ?>"
     style="display: none;">
    <h3 style="font-family: Georgia, serif; font-size: 1.5rem; margin-bottom: 1.5rem;">Newly added</h3>
    <div class="netflix-scroll" id="new-places-container"></div>
</div>