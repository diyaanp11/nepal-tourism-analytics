<h1 class="page-title" style="font-family: Georgia, serif;">My Favourites</h1>
<?php if (empty($favourites)): ?>
    <div class="card" style="text-align: center; padding: 3rem;">
        <p style="color: #6c757d; font-size: 16px;">You have not saved any destinations yet.</p>
        <a href="<?= url('tourist/dashboard') ?>" class="btn btn-primary" style="margin-top: 1rem;">Discover Places</a>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.5rem;">
        <?php 
            $favMap = array_column($favourites, 'id');
            foreach ($favourites as $d): 
                echo \App\Core\View::render('partials.destination_card', ['dest' => $d, 'favourites' => $favMap]);
            endforeach; 
        ?>
    </div>
<?php endif; ?>