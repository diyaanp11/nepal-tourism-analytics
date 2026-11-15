<h1 class="page-title">Destinations</h1>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Destination Name</th>
                <th>Region</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($destinations as $d): ?>
            <tr>
                <td style="font-weight: 500;"><?= e($d['name']) ?></td>
                <td><?= e($d['region_name'] ?? '—') ?></td>
                <td>
                    <?php if ($d['status'] === 'active'): ?>
                        <span style="background: #d1e7dd; color: #0f5132; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Active</span>
                    <?php elseif ($d['status'] === 'pending'): ?>
                        <span style="background: #fff3cd; color: #664d03; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Pending</span>
                    <?php else: ?>
                        <span style="background: #f8d7da; color: #842029; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?= url('admin/destinations/edit/' . $d['id']) ?>" class="btn btn-outline btn-sm">Edit</a>
                    
                    <form method="post" action="<?= url('admin/destinations/toggle/' . $d['id']) ?>" style="display:inline;">
                        <?= \App\Core\CSRF::field() ?>
                        <?php if ($d['status'] === 'active'): ?>
                            <button type="submit" class="btn btn-outline-danger btn-sm">Deactivate</button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-primary btn-sm">Activate</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($destinations)): ?>
                <tr><td colspan="4" class="text-center" style="color: #6c757d;">No destinations found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>