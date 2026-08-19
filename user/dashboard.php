<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$currentUser = getCurrentUser();

// Get assigned toilets
$toilets = getAssignedToilets($currentUser['id']);

// If only one toilet assigned, redirect directly to it
if (count($toilets) === 1) {
    header('Location: ' . BASE_URL . '/user/toilet.php?id=' . $toilets[0]['id']);
    exit;
}

$pageTitle = __('nav_my_toilets');
require_once __DIR__ . '/../includes/header.php';
?>

<?php renderFlash(); ?>

<div class="page-header">
    <div class="page-header-left" style="display:flex;align-items:center;gap:1.1rem;">
        <a href="<?= BASE_URL ?>/user/profile.php" title="<?= e(__('nav_profile_picture')) ?>" style="text-decoration:none;display:inline-block;flex-shrink:0;">
            <?= renderUserAvatar($currentUser, 'user-avatar-lg', 'width:52px;height:52px;font-size:1.3rem;box-shadow:0 0 12px rgba(0,212,170,0.25);border:2px solid var(--border);') ?>
        </a>
        <div>
            <h1><i class="fas fa-home" style="color:var(--primary)"></i> <?= e(__('user_dash_title')) ?></h1>
            <p class="page-subtitle"><?= __('user_dash_welcome', e($currentUser['full_name'])) ?></p>
        </div>
    </div>
    <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/user/profile.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-user-gear"></i> <?= e(__('nav_profile_picture')) ?>
        </a>
    </div>
</div>

<?php if (empty($toilets)): ?>
    <div class="empty-state" style="margin-top:4rem">
        <div class="empty-state-icon"><i class="fas fa-toilet"></i></div>
        <h3><?= e(__('user_dash_empty_title')) ?></h3>
        <p><?= __('user_dash_empty_desc') ?></p>
    </div>
<?php else: ?>
    <div style="margin-bottom:1rem;color:var(--text-secondary);font-size:0.9rem">
        <i class="fas fa-info-circle" style="color:var(--primary)"></i>
        <?= __('user_dash_assigned_count', count($toilets)) ?>
    </div>
    <div class="toilet-grid">
        <?php foreach ($toilets as $toilet): ?>
            <?php
                // Get active session for this toilet for this user
                $active = getActiveSession($currentUser['id'], $toilet['id']);
            ?>
            <a href="<?= BASE_URL ?>/user/toilet.php?id=<?= $toilet['id'] ?>" class="toilet-card">
                <div class="toilet-card-icon">🚽</div>
                <div class="toilet-card-name"><?= e($toilet['name']) ?></div>
                <?php if ($toilet['location']): ?>
                    <div class="toilet-card-location">
                        <i class="fas fa-location-dot"></i> <?= e($toilet['location']) ?>
                    </div>
                <?php endif; ?>
                <?php if ($toilet['description']): ?>
                    <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:0.75rem">
                        <?= e(substr($toilet['description'],0,80)) ?><?= strlen($toilet['description'])>80?'…':'' ?>
                    </div>
                <?php endif; ?>
                <div class="toilet-card-status">
                    <?php if ($active): ?>
                        <span class="badge badge-open">● <?= e(__('badge_active_checkin')) ?></span>
                        <span style="font-size:0.78rem;color:var(--text-muted);margin-left:0.5rem">
                            <?= __('user_dash_since', fdt($active['checkin_at'], 'h:i A')) ?>
                        </span>
                    <?php else: ?>
                        <span class="badge badge-closed"><?= e(__('badge_ready')) ?></span>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

