<h1 class="page-title">Import CSV Data</h1>

<?php if (!empty($importErrors)): ?>
    <div class="alert alert-danger">
        <strong>Import Errors (First 10):</strong>
        <ul style="margin: 0.5rem 0 0 1rem;">
            <?php foreach ($importErrors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($createdLookups)): ?>
    <div class="alert alert-success">
        <strong>Automatically Created Lookups:</strong>
        <ul style="margin: 0.5rem 0 0 1rem;">
            <?php foreach ($createdLookups as $lookup): ?>
                <li><?= e($lookup) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <h3>Upload Data File</h3>
    <form method="post" action="<?= url('admin/import/upload') ?>" enctype="multipart/form-data">
        <?= \App\Core\CSRF::field() ?>
        
        <div class="mb-3">
            <label class="form-label">Select Import Type</label>
            <select name="import_type" class="form-control" required>
                <option value="monthly_arrivals">National Monthly Arrivals (year, month, arrivals)</option>
                <option value="arrivals_by_nationality">Arrivals by Nationality (year, country, arrivals)</option>
                <option value="arrivals_by_purpose">Arrivals by Purpose (year, purpose, arrivals)</option>
                <option value="destination_demand">Destination Demand (destination_slug, destination_name, region_name, year, month, arrivals)</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">CSV File</label>
            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
        </div>

        <button type="submit" class="btn btn-primary">Start Import</button>
    </form>
</div>

<div class="card">
    <h3>Recent Imports</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Filename</th>
                <th>Status</th>
                <th>Accepted</th>
                <th>Failed</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($history as $h): ?>
            <tr>
                <td><?= e($h['created_at']) ?></td>
                <td><?= e($h['filename']) ?></td>
                <td>
                    <span style="background: <?= $h['status'] === 'success' ? '#d1e7dd' : '#f8d7da' ?>; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                        <?= ucfirst($h['status']) ?>
                    </span>
                </td>
                <td><?= $h['rows_accepted'] ?></td>
                <td><?= $h['rows_rejected'] ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($history)): ?>
                <tr><td colspan="5" class="text-center" style="color: #6c757d;">No imports yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>