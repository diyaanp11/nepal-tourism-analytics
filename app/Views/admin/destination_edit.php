<h1 class="page-title">Edit: <?= e($dest['name']) ?></h1>

<div class="card">
    <form method="post" action="<?= url('admin/destinations/update/' . $dest['id']) ?>" enctype="multipart/form-data">
        <?= \App\Core\CSRF::field() ?>
        
        <div class="mb-3">
            <label class="form-label">Destination Name</label>
            <input type="text" class="form-control" value="<?= e($dest['name']) ?>" disabled>
        </div>

        <div class="mb-3">
            <label class="form-label">Region</label>
            <?php if ($dest['region_id']): ?>
                <input type="text" class="form-control" value="<?= e($dest['region_name'] ?? '') ?>" disabled>
                <div class="form-text">Region cannot be changed once assigned.</div>
            <?php else: ?>
                <select name="region_id" class="form-control" style="margin-bottom: 0.5rem;">
                    <option value="">-- Select Existing Region --</option>
                    <?php foreach ($regions as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="new_region" class="form-control" placeholder="Or type a new region name...">
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label class="form-label">Average Cost (NPR)</label>
            <input type="number" step="0.01" name="avg_cost_npr" class="form-control" value="<?= e((string)$dest['avg_cost_npr']) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"><?= e($dest['description'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Image</label>
            <?php 
            $imgSrc = $dest['image'] ?? '';
            if ($imgSrc && !preg_match('/^https?:\/\//', $imgSrc)) {
                $imgSrc = url($imgSrc);
            }
            ?>
            <div style="margin-bottom: 1rem;">
                <img src="<?= e($imgSrc ?: asset('img/placeholder.svg')) ?>" alt="Preview" style="max-width: 200px; max-height: 150px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;" onerror="this.src='<?= asset('img/placeholder.svg') ?>'">
            </div>
            <input type="hidden" name="existing_image" value="<?= e($dest['image'] ?? '') ?>">
            
            <?php if (!empty($dest['image'])): ?>
                <div style="margin-bottom: 0.5rem;">
                    <label><input type="checkbox" name="remove_image" value="1"> Remove current image</label>
                </div>
            <?php endif; ?>

            <div style="margin-bottom: 0.5rem;">
                <label><input type="radio" name="image_type" value="upload" checked onclick="document.getElementById('img_upload').style.display='block'; document.getElementById('img_url').style.display='none';"> Upload File</label>
                <label style="margin-left: 1rem;"><input type="radio" name="image_type" value="url" onclick="document.getElementById('img_upload').style.display='none'; document.getElementById('img_url').style.display='block';"> Paste URL</label>
            </div>
            
            <div id="img_upload">
                <input type="file" name="image_file" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                <div class="form-text">Max 2MB. JPG, PNG, or WebP only.</div>
            </div>
            <div id="img_url" style="display: none;">
                <input type="url" name="image_url" class="form-control" placeholder="https://example.com/image.jpg">
            </div>
        </div>

        <div class="d-flex gap-3 mb-3">
            <div style="flex: 1;">
                <label class="form-label">Latitude (Optional)</label>
                <input type="text" name="latitude" class="form-control" value="<?= e((string)$dest['latitude']) ?>">
            </div>
            <div style="flex: 1;">
                <label class="form-label">Longitude (Optional)</label>
                <input type="text" name="longitude" class="form-control" value="<?= e((string)$dest['longitude']) ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Destination Types</label>
            <div class="d-flex gap-3 flex-wrap">
                <?php foreach ($types as $t): ?>
                    <label style="display: flex; align-items: center; gap: 5px;">
                        <input type="checkbox" name="types[]" value="<?= $t['id'] ?>" <?= in_array($t['id'], $destTypes) ? 'checked' : '' ?>>
                        <?= e($t['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Most Visited Months</label>
            <input type="text" class="form-control" value="<?= e($bestMonths) ?>" disabled>
            <div class="form-text">Calculated automatically from visitor data.</div>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="<?= url('admin/destinations') ?>" class="btn btn-outline">Cancel</a>
    </form>
</div>