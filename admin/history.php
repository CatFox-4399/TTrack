<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$db = getDB();

// ── Handle DELETE actions ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Delete a single session
    if ($_POST['action'] === 'delete_session' && !empty($_POST['session_id'])) {
        $sid = (int)$_POST['session_id'];
        // Delete associated photos from DB (files left on disk — admin can clean manually)
        $db->prepare('DELETE FROM session_photos WHERE session_id = ?')->execute([$sid]);
        $db->prepare('DELETE FROM toilet_sessions WHERE id = ?')->execute([$sid]);
        setFlash('success', __('flash_history_deleted'));
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    // Delete ALL currently-filtered sessions
    if ($_POST['action'] === 'delete_all_filtered' && !empty($_POST['session_ids'])) {
        $ids = array_map('intval', explode(',', $_POST['session_ids']));
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare("DELETE FROM session_photos WHERE session_id IN ($placeholders)")->execute($ids);
            $db->prepare("DELETE FROM toilet_sessions WHERE id IN ($placeholders)")->execute($ids);
            setFlash('success', __('flash_all_history_deleted', count($ids)));
        }
        // Redirect back without session_id focus filter
        $qs = http_build_query(array_filter([
            'toilet_id' => $_GET['toilet_id'] ?? '',
            'user_id'   => $_GET['user_id']   ?? '',
            'status'    => $_GET['status']    ?? '',
            'date'      => $_GET['date']      ?? '',
        ]));
        header('Location: ' . BASE_URL . '/admin/history.php' . ($qs ? '?' . $qs : ''));
        exit;
    }
}

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

$pageTitle = __('history_title');
require_once __DIR__ . '/../includes/header.php';
?>

<?php renderFlash(); ?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-clock-rotate-left" style="color:var(--primary)"></i> <?= e(__('history_title')) ?></h1>
        <p class="page-subtitle"><?= e(__('history_subtitle')) ?></p>
    </div>
</div>

<!-- Filters -->
<form method="GET" action="" class="filter-bar" style="margin-bottom:1.5rem">
    <select name="toilet_id" class="form-control auto-submit-select" style="max-width:200px">
        <option value=""><?= e(__('filter_all_toilets')) ?></option>
        <?php foreach ($allToilets as $t): ?>
            <option value="<?= $t['id'] ?>" <?= $filterToilet == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="user_id" class="form-control auto-submit-select" style="max-width:200px">
        <option value=""><?= e(__('filter_all_students')) ?></option>
        <?php foreach ($allUsers as $u): ?>
            <option value="<?= $u['id'] ?>" <?= $filterUser == $u['id'] ? 'selected' : '' ?>><?= e($u['full_name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status" class="form-control auto-submit-select" style="max-width:150px">
        <option value=""><?= e(__('filter_all_status')) ?></option>
        <option value="open"   <?= $filterStatus === 'open'   ? 'selected' : '' ?>><?= e(__('status_open')) ?></option>
        <option value="closed" <?= $filterStatus === 'closed' ? 'selected' : '' ?>><?= e(__('status_closed')) ?></option>
    </select>
    <input type="date" name="date" class="form-control" style="max-width:180px"
           value="<?= e($filterDate) ?>" title="<?= e(__('filter_by_date')) ?>">
    <button type="submit" class="btn btn-outline"><i class="fas fa-filter"></i> <?= e(__('action_filter')) ?></button>
    <?php if ($filterToilet || $filterUser || $filterStatus || $filterDate || $sessionFocus): ?>
        <a href="<?= BASE_URL ?>/admin/history.php" class="btn btn-outline">
            <i class="fas fa-times"></i> <?= e(__('action_clear')) ?>
        </a>
    <?php endif; ?>
    <span class="td-muted" style="font-size:0.82rem;margin-left:auto">
        <?= e(__('records_found', count($sessions))) ?>
    </span>
</form>

<?php if (!empty($sessions)): ?>
<?php $allSessionIds = implode(',', array_column($sessions, 'id')); ?>
<div style="margin-bottom:1rem;display:flex;justify-content:flex-end">
    <form method="POST" action="" id="deleteAllForm">
        <input type="hidden" name="action" value="delete_all_filtered">
        <input type="hidden" name="session_ids" value="<?= e($allSessionIds) ?>">
        <button type="submit" class="btn btn-danger btn-sm"
            data-confirm="<?= e(__('confirm_delete_all_filtered', count($sessions))) ?>">
            <i class="fas fa-trash-can"></i> <?= e(__('delete_all_visible', count($sessions))) ?>
        </button>
    </form>
</div>
<?php endif; ?>

<!-- History Timeline -->
<?php if (empty($sessions)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
        <h3><?= e(__('no_history_found')) ?></h3>
        <p><?= e(__('no_history_desc')) ?></p>
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
                            <i class="fas fa-camera"></i> <?= e(__('photos_count', count($ciPhotos) + count($coPhotos))) ?>
                        </div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:0.75rem">
                    <span class="badge badge-<?= $sess['status'] ?>">
                        <?= $isOpen ? '● ' . e(__('status_open')) : '✓ ' . e(__('status_closed')) ?>
                    </span>
                    <?php if (!$isOpen): ?>
                        <span class="td-muted" style="font-size:0.8rem">
                            <?= timeDiff($sess['checkin_at'], $sess['checkout_at']) ?>
                        </span>
                    <?php endif; ?>
                    <span class="expand-icon" style="color:var(--text-muted);font-size:0.75rem"><?= $isFocus ? '▲' : '▼' ?></span>
                    <!-- Delete button -->
                    <form method="POST" action="" style="margin:0" onclick="event.stopPropagation()">
                        <input type="hidden" name="action" value="delete_session">
                        <input type="hidden" name="session_id" value="<?= $sess['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm btn-icon"
                            title="<?= e(__('action_delete')) ?>"
                            data-confirm="<?= e(__('confirm_delete_session', $sess['full_name'])) ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Body (expandable) -->
            <div class="history-body">
                <div style="display:grid;grid-template-columns:1fr<?= $isOpen ? '' : ' 1px 1fr' ?>;gap:1.5rem;align-items:start">

                    <!-- Check-In -->
                    <div class="checkin-section">
                        <div class="section-label checkin">
                            <i class="fas fa-right-to-bracket"></i> <?= e(__('check_in_title')) ?>
                        </div>
                        <div class="section-time">
                            <i class="fas fa-clock"></i> <?= fdt($sess['checkin_at'], 'd M Y, h:i:s A') ?>
                        </div>
                        <?php if ($sess['checkin_comment']): ?>
                            <div class="section-comment">"<?= e($sess['checkin_comment']) ?>"</div>
                        <?php else: ?>
                            <div class="section-comment" style="opacity:0.5"><?= e(__('no_comment')) ?></div>
                        <?php endif; ?>

                        <div class="section-label" style="color:var(--text-muted);margin-top:0.75rem;margin-bottom:0.4rem;font-size:0.75rem">
                            <i class="fas fa-images"></i> <?= e(__('before_photos_count', count($ciPhotos))) ?>
                        </div>
                        <?php if (!empty($ciPhotos)): ?>
                            <div class="photo-gallery">
                                <?php foreach ($ciPhotos as $p): ?>
                                    <a href="<?= BASE_URL ?>/uploads/sessions/<?= e($p['file_path']) ?>"
                                       title="<?= e($p['original_name'] ?? __('before_photos')) ?>">
                                        <img src="<?= BASE_URL ?>/uploads/sessions/<?= e($p['file_path']) ?>"
                                             alt="<?= e(__('before_photos')) ?>" loading="lazy">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color:var(--text-muted);font-size:0.8rem"><?= e(__('no_photos_uploaded')) ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if (!$isOpen): ?>
                    <!-- Divider -->
                    <div style="background:var(--border);width:1px;align-self:stretch"></div>

                    <!-- Check-Out -->
                    <div class="checkout-section">
                        <div class="section-label checkout">
                            <i class="fas fa-right-from-bracket"></i> <?= e(__('check_out_title')) ?>
                        </div>
                        <div class="section-time">
                            <i class="fas fa-clock"></i> <?= fdt($sess['checkout_at'], 'd M Y, h:i:s A') ?>
                        </div>
                        <?php if ($sess['checkout_comment']): ?>
                            <div class="section-comment">"<?= e($sess['checkout_comment']) ?>"</div>
                        <?php else: ?>
                            <div class="section-comment" style="opacity:0.5"><?= e(__('no_comment')) ?></div>
                        <?php endif; ?>

                        <div class="section-label" style="color:var(--text-muted);margin-top:0.75rem;margin-bottom:0.4rem;font-size:0.75rem">
                            <i class="fas fa-images"></i> <?= e(__('after_photos_count', count($coPhotos))) ?>
                        </div>
                        <?php if (!empty($coPhotos)): ?>
                            <div class="photo-gallery">
                                <?php foreach ($coPhotos as $p): ?>
                                    <a href="<?= BASE_URL ?>/uploads/sessions/<?= e($p['file_path']) ?>"
                                       title="<?= e($p['original_name'] ?? __('after_photos')) ?>">
                                        <img src="<?= BASE_URL ?>/uploads/sessions/<?= e($p['file_path']) ?>"
                                             alt="<?= e(__('after_photos')) ?>" loading="lazy">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color:var(--text-muted);font-size:0.8rem"><?= e(__('no_photos_uploaded')) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                        <!-- Open session note -->
                        <div style="grid-column:span 1;display:flex;align-items:center;padding:1rem;background:var(--warning-bg);border:1px solid rgba(245,158,11,0.3);border-radius:var(--radius-md)">
                            <div>
                                <div style="color:var(--warning);font-weight:700;margin-bottom:0.25rem">
                                    <i class="fas fa-hourglass-half"></i> <?= e(__('session_in_progress')) ?>
                                </div>
                                <div style="color:var(--text-secondary);font-size:0.82rem">
                                    <?= e(__('session_in_progress_hint')) ?><br>
                                    <?= e(__('th_duration')) ?>: <span data-checkin-time="<?= e($sess['checkin_at']) ?>">calculating…</span>
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

