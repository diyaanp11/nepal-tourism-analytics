<h1 class="page-title">My Reviews</h1>
<?php if (empty($reviews)): ?>
    <p style="color: #6c757d;">You have not written any reviews yet.</p>
<?php else: ?>
    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Destination</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $r): ?>
                <tr>
                    <td>
                        <a href="<?= url('tourist/destination/' . $r['destination_id']) ?>" style="font-weight: 500; color: #192b25; text-decoration: none;">
                            <?= e($r['dest_name']) ?>
                        </a>
                    </td>
                    <td style="color: #d5a45e;"><?= str_repeat('★', (int)$r['rating']) ?></td>
                    <td style="font-size: 13px; max-width: 300px;"><?= e($r['comment']) ?></td>
                    <td style="font-size: 12px; color: #6c757d;"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                    <td>
                        <a href="<?= url('tourist/destination/' . $r['destination_id']) ?>" class="btn btn-outline btn-sm">Edit</a>
                        <form method="post" action="<?= url('tourist/reviews/delete/' . $r['destination_id']) ?>" style="display:inline;">
                            <?= \App\Core\CSRF::field() ?>
                            <button type="submit" class="btn-link" style="font-size: 13px; color: #dc3545; margin-left: 0.5rem;" onclick="return confirm('Delete this review?');">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>