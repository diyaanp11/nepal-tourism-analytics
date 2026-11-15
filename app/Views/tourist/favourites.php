<h1 class="page-title">My Favourites</h1>
<?php if (empty($favourites)): ?>
    <p style="color: #6c757d;">You have not saved any destinations yet.</p>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem;">
        <?php 
            $favMap = array_column($favourites, 'id');
            foreach ($favourites as $d): 
                echo \App\Core\View::render('partials.destination_card', ['dest' => $d, 'favourites' => $favMap]);
            endforeach; 
        ?>
    </div>
<?php endif; ?>