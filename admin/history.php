<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$db = getDB();

// Filters
$filterToilet = (int)($_GET['toilet_id'] ?? 0);
$filterUser   = (int)($_GET['user_id'] ?? 0);
$filterStatus = $_GET['status'] ?? '';
$filterDate   = $_GET['date'] ?? '';
$sessionFocus = (int)($_GET['session_id'] ?? 0);

// Build WHERE clause
$where  = [];
$params = [];

if ($filterToilet) { $where[] = 'ts.toilet_id = ?'; $params[] = $filterToilet; }
if ($filterUser)   { $where[] = 'ts.user_id = ?';   $params[] = $filterUser; }
if ($filterStatus) { $where[] = 'ts.status = ?';     $params[] = $filterStatus; }
if ($filterDate)   { $where[] = 'DATE(ts.checkin_at) = ?'; $params[] = $filterDate; }
if ($sessionFocus) { $where[] = 'ts.id = ?'; $params[] = $sessionFocus; }

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sessions = $db->prepare("
    SELECT ts.*, u.full_name, u.username, t.name AS toilet_name, t.location AS toilet_location
    FROM toilet_sessions ts
    JOIN users u ON u.id = ts.user_id
    JOIN toilets t ON t.id = ts.toilet_id
    {$whereSQL}
    ORDER BY ts.checkin_at DESC
    LIMIT 200
");
$sessions->execute($params);
$sessions = $sessions->fetchAll();

$allToilets = getAllToilets();
$allUsers   = getAllUsers('user');

$pageTitle = 'Session History';
require_once __DIR__ . '/../includes/header.php';
?>

<?php renderFlash(); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-clock-rotate-left" style="color:var(--primary)"></i> Session History</h1>
        <p class="page-subtitle">Complete check-in / check-out records with photo evidence.</p>
    </div>
</div>

<!-- Filters -->
<form method="GET" action="" class="filter-bar" style="margin-bottom:1.5rem">
    <select name="toilet_id" class="form-control auto-submit-select" style="max-width:200px">
        <option value="">All Toilets</option>
        <?php foreach ($allToilets as $t): ?>
            <option value="<?= $t['id'] ?>" <?= $filterToilet == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="user_id" class="form-control auto-submit-select" style="max-width:200px">
        <option value="">All Users</option>
        <?php foreach ($allUsers as $u): ?>
            <option value="<?= $u['id'] ?>" <?= $filterUser == $u['id'] ? 'selected' : '' ?>><?= e($u['full_name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status" class="form-control auto-submit-select" style="max-width:150px">
        <option value="">All Status</option>
        <option value="open"   <?= $filterStatus === 'open'   ? 'selected' : '' ?>>Open</option>
        <option value="closed" <?= $filterStatus === 'closed' ? 'selected' : '' ?>>Closed</option>
    </select>
    <input type="date" name="date" class="form-control" style="max-width:180px"
           value="<?= e($filterDate) ?>" title="Filter by check-in date">
    <button type="submit" class="btn btn-outline"><i class="fas fa-filter"></i> Filter</button>
    <?php if ($filterToilet || $filterUser || $filterStatus || $filterDate || $sessionFocus): ?>
        <a href="<?= BASE_URL ?>/admin/history.php" class="btn btn-outline">
            <i class="fas fa-times"></i> Clear
        </a>
    <?php endif; ?>
    <span class="td-muted" style="font-size:0.82rem;margin-left:auto">
        <?= count($sessions) ?> record<?= count($sessions) != 1 ? 's' : '' ?> found
    </span>
</form>

<!-- History Timeline -->
<?php if (empty($sessions)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
        <h3>No Records Found</h3>
        <p>No session records match the selected filters.</p>
    </div>
<?php else: ?>
<div class="history-timeline">
    <?php foreach ($sessions as $sess): ?>
        <?php
            $ciPhotos = getSessionPhotos($sess['id'], 'checkin');
            $coPhotos = getSessionPhotos($sess['id'], 'checkout');
            $isOpen   = $sess['status'] === 'open';
            $isFocus  = $sessionFocus && $sessionFocus == $sess['id'];
        ?>
        <div class="history-item <?= $isFocus ? 'expanded' : '' ?>">
            <!-- Header -->
            <div class="history-item-header">
                <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
                    <div>
                        <div class="history-date">
                            <i class="fas fa-toilet"></i>
                            <?= e($sess['toilet_name']) ?>
                            <?php if ($sess['toilet_location']): ?>
                                <span style="color:var(--text-muted);font-weight:400;font-size:0.8rem"> — <?= e($sess['toilet_location']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="history-meta">
                            <i class="fas fa-user"></i> <?= e($sess['full_name']) ?>
                            <span style="color:var(--border)">|</span>
                            <i class="fas fa-calendar"></i> <?= fdt($sess['checkin_at'], 'd M Y') ?>
                            <span style="color:var(--border)">|</span>
                            <i class="fas fa-camera"></i> <?= count($ciPhotos) + count($coPhotos) ?> photo<?= (count($ciPhotos)+count($coPhotos))!=1?'s':'' ?>
                        </div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:0.75rem">
                    <span class="badge badge-<?= $sess['status'] ?>">
                        <?= $isOpen ? '● Open' : '✓ Closed' ?>
                    </span>
                    <?php if (!$isOpen): ?>
                        <span class="td-muted" style="font-size:0.8rem">
                            <?= timeDiff($sess['checkin_at'], $sess['checkout_at']) ?>
                        </span>
                    <?php endif; ?>
                    <span class="expand-icon" style="color:var(--text-muted);font-size:0.75rem"><?= $isFocus ? '▲' : '▼' ?></span>
                </div>
            </div>

            <!-- Body (expandable) -->
            <div class="history-body">
                <div style="display:grid;grid-template-columns:1fr<?= $isOpen ? '' : ' 1px 1fr' ?>;gap:1.5rem;align-items:start">

                    <!-- Check-In -->
                    <div class="checkin-section">
                        <div class="section-label checkin">
                            <i class="fas fa-right-to-bracket"></i> Check In
                        </div>
                        <div class="section-time">
                            <i class="fas fa-clock"></i> <?= fdt($sess['checkin_at'], 'd M Y, h:i:s A') ?>
                        </div>
                        <?php if ($sess['checkin_comment']): ?>
                            <div class="section-comment">"<?= e($sess['checkin_comment']) ?>"</div>
                        <?php else: ?>
                            <div class="section-comment" style="opacity:0.5">No comment</div>
                        <?php endif; ?>

                        <div class="section-label" style="color:var(--text-muted);margin-top:0.75rem;margin-bottom:0.4rem;font-size:0.75rem">
                            <i class="fas fa-images"></i> Before Photos (<?= count($ciPhotos) ?>)
                        </div>
                        <?php if (!empty($ciPhotos)): ?>
                            <div class="photo-gallery">
                                <?php foreach ($ciPhotos as $p): ?>
                                    <a href="<?= BASE_URL ?>/uploads/sessions/<?= e($p['file_path']) ?>"
                                       title="<?= e($p['original_name'] ?? 'Before Photo') ?>">
                                        <img src="<?= BASE_URL ?>/uploads/sessions/<?= e($p['file_path']) ?>"
                                             alt="Check-in photo" loading="lazy">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color:var(--text-muted);font-size:0.8rem">No photos uploaded</p>
                        <?php endif; ?>
                    </div>

                    <?php if (!$isOpen): ?>
                    <!-- Divider -->
                    <div style="background:var(--border);width:1px;align-self:stretch"></div>

                    <!-- Check-Out -->
                    <div class="checkout-section">
                        <div class="section-label checkout">
                            <i class="fas fa-right-from-bracket"></i> Check Out
                        </div>
                        <div class="section-time">
                            <i class="fas fa-clock"></i> <?= fdt($sess['checkout_at'], 'd M Y, h:i:s A') ?>
                        </div>
                        <?php if ($sess['checkout_comment']): ?>
                            <div class="section-comment">"<?= e($sess['checkout_comment']) ?>"</div>
                        <?php else: ?>
                            <div class="section-comment" style="opacity:0.5">No comment</div>
                        <?php endif; ?>

                        <div class="section-label" style="color:var(--text-muted);margin-top:0.75rem;margin-bottom:0.4rem;font-size:0.75rem">
                            <i class="fas fa-images"></i> After Photos (<?= count($coPhotos) ?>)
                        </div>
                        <?php if (!empty($coPhotos)): ?>
                            <div class="photo-gallery">
                                <?php foreach ($coPhotos as $p): ?>
                                    <a href="<?= BASE_URL ?>/uploads/sessions/<?= e($p['file_path']) ?>"
                                       title="<?= e($p['original_name'] ?? 'After Photo') ?>">
                                        <img src="<?= BASE_URL ?>/uploads/sessions/<?= e($p['file_path']) ?>"
                                             alt="Check-out photo" loading="lazy">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color:var(--text-muted);font-size:0.8rem">No photos uploaded</p>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                        <!-- Open session note -->
                        <div style="grid-column:span 1;display:flex;align-items:center;padding:1rem;background:var(--warning-bg);border:1px solid rgba(245,158,11,0.3);border-radius:var(--radius-md)">
                            <div>
                                <div style="color:var(--warning);font-weight:700;margin-bottom:0.25rem">
                                    <i class="fas fa-hourglass-half"></i> Session In Progress
                                </div>
                                <div style="color:var(--text-secondary);font-size:0.82rem">
                                    User has checked in but not yet checked out.<br>
                                    Duration: <span data-checkin-time="<?= e($sess['checkin_at']) ?>">calculating…</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
