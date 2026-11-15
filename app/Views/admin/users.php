<h1 class="page-title">Users</h1>

<div class="card">
    <h3>Create New Admin/Researcher</h3>
    <form method="post" action="<?= url('admin/users/store') ?>" class="d-flex gap-3 align-items-center" style="flex-wrap: wrap;">
        <?= \App\Core\CSRF::field() ?>
        <div style="flex: 1; min-width: 150px;">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div style="flex: 1; min-width: 150px;">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div style="flex: 1; min-width: 150px;">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" minlength="8" required>
        </div>
        <div style="width: 150px;">
            <label class="form-label">Role</label>
            <select name="role" class="form-control" required>
                <option value="researcher">Researcher</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top: 1.5rem;">Create</button>
    </form>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td style="font-weight: 500;"><?= e($u['name']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td style="text-transform: capitalize;"><?= e($u['role_name']) ?></td>
                <td>
                    <span style="background: <?= $u['is_active'] ? '#d1e7dd' : '#f8d7da' ?>; color: <?= $u['is_active'] ? '#0f5132' : '#842029' ?>; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                        <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </td>
                <td>
                    <?php if ($u['id'] !== \App\Core\Auth::id()): ?>
                    <form method="post" action="<?= url('admin/users/toggle/' . $u['id']) ?>" style="display:inline;">
                        <?= \App\Core\CSRF::field() ?>
                        <button type="submit" class="btn btn-outline btn-sm">
                            <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>