<h1 class="page-title">Dashboard Overview</h1>

<div class="stats-grid">
    <div class="card stat-card">
        <h3 class="stat-number"><?= $stats['total_destinations'] ?></h3>
        <p class="stat-label">Active Destinations</p>
    </div>
    <div class="card stat-card" style="border-top-color: #dc3545;">
        <h3 class="stat-number"><?= $stats['pending_destinations'] ?></h3>
        <p class="stat-label">Pending Approval</p>
    </div>
    <div class="card stat-card" style="border-top-color: #0dcaf0;">
        <h3 class="stat-number"><?= $stats['total_imports'] ?></h3>
        <p class="stat-label">CSV Imports</p>
    </div>
    <div class="card stat-card" style="border-top-color: #198754;">
        <h3 class="stat-number"><?= $stats['total_users'] ?></h3>
        <p class="stat-label">Registered Users</p>
    </div>
</div>