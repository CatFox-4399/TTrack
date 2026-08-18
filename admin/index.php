<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$db = getDB();

// Stats
$totalUsers    = $db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalToilets  = $db->query("SELECT COUNT(*) FROM toilets")->fetchColumn();
$activeSessions= $db->query("SELECT COUNT(*) FROM toilet_sessions WHERE status='open'")->fetchColumn();
$todayCheckins = $db->query("SELECT COUNT(*) FROM toilet_sessions WHERE DATE(checkin_at) = CURDATE()")->fetchColumn();
$todayCheckouts= $db->query("SELECT COUNT(*) FROM toilet_sessions WHERE DATE(checkout_at) = CURDATE()")->fetchColumn();

// Recent sessions (latest 8)
$recentSessions = $db->query("
    SELECT ts.*, u.full_name, u.username, t.name AS toilet_name
    FROM toilet_sessions ts
    JOIN users u ON u.id = ts.user_id
    JOIN toilets t ON t.id = ts.toilet_id
    ORDER BY ts.checkin_at DESC
    LIMIT 8
")->fetchAll();

// Active sessions
$activeSess = $db->query("
    SELECT ts.*, u.full_name, t.name AS toilet_name
    FROM toilet_sessions ts
    JOIN users u ON u.id = ts.user_id
    JOIN toilets t ON t.id = ts.toilet_id
    WHERE ts.status = 'open'
    ORDER BY ts.checkin_at DESC
")->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<?php renderFlash(); ?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-gauge-high" style="color:var(--primary)"></i> Admin Dashboard</h1>
        <p class="page-subtitle"><?= date('l, d F Y') ?> &mdash; <?= COLLEGE_NAME ?></p>
    </div>
    <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/admin/users.php?action=add" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Add User
        </a>
        <a href="<?= BASE_URL ?>/admin/toilets.php?action=add" class="btn btn-accent">
            <i class="fas fa-plus"></i> Add Toilet
        </a>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalUsers ?></div>
            <div class="stat-label">Total Users</div>
        </div>
    </div>
    <div class="stat-card accent">
        <div class="stat-icon"><i class="fas fa-toilet"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalToilets ?></div>
            <div class="stat-label">Total Toilets</div>
        </div>
    </div>
    <div class="stat-card warning">
        <div class="stat-icon"><i class="fas fa-door-open"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $activeSessions ?></div>
            <div class="stat-label">Active Check-Ins</div>
        </div>
    </div>
    <div class="stat-card success">
        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $todayCheckins ?></div>
            <div class="stat-label">Today's Check-Ins</div>
        </div>
    </div>
    <div class="stat-card info">
        <div class="stat-icon"><i class="fas fa-door-closed"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $todayCheckouts ?></div>
            <div class="stat-label">Today's Check-Outs</div>
        </div>
    </div>
</div>

<!-- Two-column layout -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:2rem;">

    <!-- Active Sessions -->
    <div class="card" style="grid-column:<?= count($activeSess) > 0 ? '1' : 'span 2' ?>">
        <div class="card-title">
            <i class="fas fa-circle-dot" style="color:var(--warning)"></i>
            Live Active Sessions
            <?php if ($activeSessions > 0): ?>
                <span class="badge badge-open" style="margin-left:auto"><?= $activeSessions ?> active</span>
            <?php endif; ?>
        </div>
        <?php if (empty($activeSess)): ?>
            <div class="empty-state" style="padding:2rem">
                <div class="empty-state-icon"><i class="fas fa-moon"></i></div>
                <h3>All Clear</h3>
                <p>No active check-ins at the moment.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper" style="border:none">
                <table>
                    <thead><tr>
                        <th>Toilet</th><th>User</th><th>Since</th><th>Elapsed</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($activeSess as $s): ?>
                        <tr>
                            <td><strong><?= e($s['toilet_name']) ?></strong></td>
                            <td class="td-muted"><?= e($s['full_name']) ?></td>
                            <td class="td-muted"><?= fdt($s['checkin_at'], 'h:i A') ?></td>
                            <td>
                                <span class="text-warning" data-checkin-time="<?= e($s['checkin_at']) ?>">
                                    —
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Links -->
    <?php if (count($activeSess) > 0): ?>
    <div class="card">
        <div class="card-title"><i class="fas fa-bolt"></i> Quick Actions</div>
        <div style="display:flex; flex-direction:column; gap:0.75rem;">
            <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline btn-full">
                <i class="fas fa-users"></i> Manage Users
            </a>
            <a href="<?= BASE_URL ?>/admin/toilets.php" class="btn btn-outline btn-full">
                <i class="fas fa-toilet"></i> Manage Toilets
            </a>
            <a href="<?= BASE_URL ?>/admin/history.php" class="btn btn-outline btn-full">
                <i class="fas fa-clock-rotate-left"></i> View Full History
            </a>
            <a href="<?= BASE_URL ?>/admin/users.php?action=add" class="btn btn-primary btn-full">
                <i class="fas fa-user-plus"></i> Add New User
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Recent Sessions Table -->
<div class="card">
    <div class="card-title">
        <i class="fas fa-clock-rotate-left"></i> Recent Sessions
        <a href="<?= BASE_URL ?>/admin/history.php" class="btn btn-outline btn-sm" style="margin-left:auto">
            View All <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    <?php if (empty($recentSessions)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
            <h3>No Sessions Yet</h3>
            <p>Check-in records will appear here once users start their sessions.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper" style="border:none">
            <table>
                <thead><tr>
                    <th>Toilet</th>
                    <th>User</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Duration</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($recentSessions as $s): ?>
                    <tr>
                        <td><strong><?= e($s['toilet_name']) ?></strong></td>
                        <td class="td-muted"><?= e($s['full_name']) ?></td>
                        <td class="td-muted"><?= fdt($s['checkin_at'], 'd M, h:i A') ?></td>
                        <td class="td-muted"><?= $s['checkout_at'] ? fdt($s['checkout_at'], 'd M, h:i A') : '—' ?></td>
                        <td class="td-muted">
                            <?= $s['checkout_at'] ? timeDiff($s['checkin_at'], $s['checkout_at']) : '<span class="text-warning">Ongoing</span>' ?>
                        </td>
                        <td>
                            <span class="badge badge-<?= $s['status'] ?>">
                                <?= $s['status'] === 'open' ? '● Open' : '✓ Closed' ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/admin/history.php?session_id=<?= $s['id'] ?>"
                               class="btn btn-outline btn-sm btn-icon" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
