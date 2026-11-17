<h1 class="page-title" style="font-family: Georgia, serif;">My Reviews</h1>
<?php if (empty($reviews)): ?>
    <div class="card" style="text-align: center; padding: 3rem;">
        <p style="color: #6c757d; font-size: 16px;">You have not written any reviews yet.</p>
        <a href="<?= url('tourist/dashboard') ?>" class="btn btn-primary" style="margin-top: 1rem;">Discover Places</a>
    </div>
<?php else: ?>
    <div class="card" style="border-radius: 12px; padding: 0; overflow: hidden;">
        <table class="table" style="margin: 0;">
            <thead style="background: #f8f9fa;">
                <tr>
                    <th style="padding: 15px;">Destination</th>
                    <th style="padding: 15px;">Rating</th>
                    <th style="padding: 15px;">Comment</th>
                    <th style="padding: 15px;">Date</th>
                    <th style="padding: 15px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $r): ?>
                <tr>
                    <td style="padding: 15px;">
                        <a href="<?= url('tourist/destination/' . $r['destination_id']) ?>" style="font-weight: 600; color: #192b25; text-decoration: none;">
                            <?= e($r['dest_name']) ?>
                        </a>
                    </td>
                    <td style="padding: 15px; color: #d5a45e; font-size: 16px; letter-spacing: 1px;">
                        <?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?>
                    </td>
                    <td style="padding: 15px; font-size: 13px; color: #495057; max-width: 300px;"><?= e($r['comment']) ?></td>
                    <td style="padding: 15px; font-size: 12px; color: #6c757d;"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                    <td style="padding: 15px;">
                        <a href="<?= url('tourist/destination/' . $r['destination_id']) ?>" class="btn btn-outline btn-sm">Edit</a>
                        <form method="post" action="<?= url('tourist/reviews/delete/' . $r['destination_id']) ?>" style="display:inline;">
                            <?= \App\Core\CSRF::field() ?>
                            <button type="submit" class="btn-link" style="font-size: 13px; color: #dc3545; margin-left: 0.5rem; text-decoration: none;" onclick="return confirm('Delete this review?');">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>