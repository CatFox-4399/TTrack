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

$pageTitle = 'My Toilets';
require_once __DIR__ . '/../includes/header.php';
?>

<?php renderFlash(); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-home" style="color:var(--primary)"></i> My Assigned Toilets</h1>
        <p class="page-subtitle">Welcome, <?= e($currentUser['full_name']) ?>! Select a toilet to check in.</p>
    </div>
</div>

<?php if (empty($toilets)): ?>
    <div class="empty-state" style="margin-top:4rem">
        <div class="empty-state-icon"><i class="fas fa-toilet"></i></div>
        <h3>No Toilets Assigned</h3>
        <p>You have not been assigned to any toilet yet.<br>Please contact your administrator.</p>
    </div>
<?php else: ?>
    <div style="margin-bottom:1rem;color:var(--text-secondary);font-size:0.9rem">
        <i class="fas fa-info-circle" style="color:var(--primary)"></i>
        You have <?= count($toilets) ?> toilet<?= count($toilets)>1?'s':'' ?> assigned. Click a toilet to check in.
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
                        <span class="badge badge-open">● Active Check-In</span>
                        <span style="font-size:0.78rem;color:var(--text-muted);margin-left:0.5rem">
                            since <?= fdt($active['checkin_at'], 'h:i A') ?>
                        </span>
                    <?php else: ?>
                        <span class="badge badge-closed">Ready to Check In</span>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
