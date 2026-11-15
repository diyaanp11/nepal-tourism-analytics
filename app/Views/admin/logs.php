<h1 class="page-title">System Logs</h1>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Time</th>
                <th>User</th>
                <th>Action</th>
                <th>Details</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
            <tr style="font-size: 13px;">
                <td style="color: #6c757d;"><?= e($log['created_at']) ?></td>
                <td><?= e($log['user_name'] ?? 'System') ?></td>
                <td style="font-weight: 600;"><?= e($log['action']) ?></td>
                <td style="color: #495057;"><?= e($log['details'] ?? '') ?></td>
                <td style="color: #6c757d;"><?= e($log['ip_address'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
                <tr><td colspan="5" class="text-center" style="color: #6c757d;">No logs found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>