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

$pageTitle = __('admin_dash_title');
require_once __DIR__ . '/../includes/header.php';
?>

<?php renderFlash(); ?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-gauge-high" style="color:var(--primary)"></i> <?= e(__('admin_dash_title')) ?></h1>
        <p class="page-subtitle"><?= date('l, d F Y') ?> &mdash; <?= e(__('college_name')) ?></p>
    </div>
    <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/admin/users.php?action=add" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> <?= e(__('add_new_user')) ?>
        </a>
        <a href="<?= BASE_URL ?>/admin/toilets.php?action=add" class="btn btn-accent">
            <i class="fas fa-plus"></i> <?= e(__('add_new_toilet')) ?>
        </a>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalUsers ?></div>
            <div class="stat-label"><?= e(__('stat_total_users')) ?></div>
        </div>
    </div>
    <div class="stat-card accent">
        <div class="stat-icon"><i class="fas fa-toilet"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalToilets ?></div>
            <div class="stat-label"><?= e(__('stat_total_toilets')) ?></div>
        </div>
    </div>
    <div class="stat-card warning">
        <div class="stat-icon"><i class="fas fa-door-open"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $activeSessions ?></div>
            <div class="stat-label"><?= e(__('stat_active_checkins')) ?></div>
        </div>
    </div>
    <div class="stat-card success">
        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $todayCheckins ?></div>
            <div class="stat-label"><?= e(__('stat_today_checkins')) ?></div>
        </div>
    </div>
    <div class="stat-card info">
        <div class="stat-icon"><i class="fas fa-door-closed"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $todayCheckouts ?></div>
            <div class="stat-label"><?= e(__('stat_today_checkouts')) ?></div>
        </div>
    </div>
</div>

<!-- Two-column layout -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:2rem;">

    <!-- Active Sessions -->
    <div class="card" style="grid-column:<?= count($activeSess) > 0 ? '1' : 'span 2' ?>">
        <div class="card-title">
            <i class="fas fa-circle-dot" style="color:var(--warning)"></i>
            <?= e(__('live_active_sessions')) ?>
            <?php if ($activeSessions > 0): ?>
                <span class="badge badge-open" style="margin-left:auto"><?= $activeSessions ?> <?= strtolower(__('status_active')) ?></span>
            <?php endif; ?>
        </div>
        <?php if (empty($activeSess)): ?>
            <div class="empty-state" style="padding:2rem">
                <div class="empty-state-icon"><i class="fas fa-moon"></i></div>
                <h3><?= e(__('all_clear_title')) ?></h3>
                <p><?= e(__('all_clear_desc')) ?></p>
            </div>
        <?php else: ?>
            <div class="table-wrapper" style="border:none">
                <table>
                    <thead><tr>
                        <th><?= e(__('th_toilet')) ?></th>
                        <th><?= e(__('th_user')) ?></th>
                        <th><?= e(__('th_since')) ?></th>
                        <th><?= e(__('th_elapsed')) ?></th>
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
        <div class="card-title"><i class="fas fa-bolt"></i> <?= e(__('quick_actions')) ?></div>
        <div style="display:flex; flex-direction:column; gap:0.75rem;">
            <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline btn-full">
                <i class="fas fa-users"></i> <?= e(__('manage_users')) ?>
            </a>
            <a href="<?= BASE_URL ?>/admin/toilets.php" class="btn btn-outline btn-full">
                <i class="fas fa-toilet"></i> <?= e(__('manage_toilets')) ?>
            </a>
            <a href="<?= BASE_URL ?>/admin/history.php" class="btn btn-outline btn-full">
                <i class="fas fa-clock-rotate-left"></i> <?= e(__('view_full_history')) ?>
            </a>
            <a href="<?= BASE_URL ?>/admin/users.php?action=add" class="btn btn-primary btn-full">
                <i class="fas fa-user-plus"></i> <?= e(__('add_new_user')) ?>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Recent Sessions Table -->
<div class="card">
    <div class="card-title">
        <i class="fas fa-clock-rotate-left"></i> <?= e(__('recent_sessions')) ?>
        <a href="<?= BASE_URL ?>/admin/history.php" class="btn btn-outline btn-sm" style="margin-left:auto">
            <?= e(__('action_view_all')) ?> <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    <?php if (empty($recentSessions)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
            <h3><?= e(__('no_sessions_title')) ?></h3>
            <p><?= e(__('no_sessions_desc')) ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrapper" style="border:none">
            <table>
                <thead><tr>
                    <th><?= e(__('th_toilet')) ?></th>
                    <th><?= e(__('th_user')) ?></th>
                    <th><?= e(__('th_checkin')) ?></th>
                    <th><?= e(__('th_checkout')) ?></th>
                    <th><?= e(__('th_duration')) ?></th>
                    <th><?= e(__('th_status')) ?></th>
                    <th><?= e(__('th_actions')) ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($recentSessions as $s): ?>
                    <tr>
                        <td><strong><?= e($s['toilet_name']) ?></strong></td>
                        <td class="td-muted"><?= e($s['full_name']) ?></td>
                        <td class="td-muted"><?= fdt($s['checkin_at'], 'd M, h:i A') ?></td>
                        <td class="td-muted"><?= $s['checkout_at'] ? fdt($s['checkout_at'], 'd M, h:i A') : '—' ?></td>
                        <td class="td-muted">
                            <?= $s['checkout_at'] ? timeDiff($s['checkin_at'], $s['checkout_at']) : '<span class="text-warning">' . e(__('badge_ongoing')) . '</span>' ?>
                        </td>
                        <td>
                            <span class="badge badge-<?= $s['status'] ?>">
                                <?= $s['status'] === 'open' ? '● ' . e(__('status_open')) : '✓ ' . e(__('status_closed')) ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/admin/history.php?session_id=<?= $s['id'] ?>"
                               class="btn btn-outline btn-sm btn-icon" title="<?= e(__('action_view_details')) ?>">
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

