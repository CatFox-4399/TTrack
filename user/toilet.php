<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$currentUser = getCurrentUser();
$db = getDB();

// Validate toilet ID
$toiletId = (int)($_GET['id'] ?? 0);
if (!$toiletId) {
    header('Location: ' . BASE_URL . '/user/dashboard.php');
    exit;
}

// Security: ensure user is assigned to this toilet
if (!isUserAssignedToToilet($currentUser['id'], $toiletId)) {
    setFlash('error', 'You are not assigned to this toilet.');
    header('Location: ' . BASE_URL . '/user/dashboard.php');
    exit;
}

// Fetch toilet info
$stmt = $db->prepare("SELECT * FROM toilets WHERE id=? AND status='active'");
$stmt->execute([$toiletId]);
$toilet = $stmt->fetch();
if (!$toilet) {
    setFlash('error', 'Toilet not found or inactive.');
    header('Location: ' . BASE_URL . '/user/dashboard.php');
    exit;
}

// Get active session for this user + toilet
$activeSession = getActiveSession($currentUser['id'], $toiletId);

// ============================================================
// HANDLE POST ACTIONS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    // ---- CHECK IN ----
    if ($postAction === 'checkin') {
        // Prevent double check-in
        if ($activeSession) {
            setFlash('error', 'You already have an active check-in for this toilet. Please check out first.');
        } else {
            $comment = trim($_POST['checkin_comment'] ?? '');

            $ins = $db->prepare("INSERT INTO toilet_sessions (user_id, toilet_id, checkin_at, checkin_comment, status) VALUES (?, ?, NOW(), ?, 'open')");
            $ins->execute([$currentUser['id'], $toiletId, $comment]);
            $sessionId = (int)$db->lastInsertId();

            // Handle photo uploads
            if (!empty($_FILES['checkin_photos']['name'][0])) {
                $saved = uploadSessionPhotos($_FILES['checkin_photos'], $sessionId, 'checkin');
                savePhotosToDb($sessionId, 'checkin', $saved);
            }

            setFlash('success', 'Check-in recorded successfully! Remember to check out when done.');
        }
        header('Location: ' . BASE_URL . '/user/toilet.php?id=' . $toiletId);
        exit;
    }

    // ---- CHECK OUT ----
    if ($postAction === 'checkout') {
        // RULE: Must have an active check-in first
        if (!$activeSession) {
            setFlash('error', 'No active check-in found. You must check in before checking out.');
            header('Location: ' . BASE_URL . '/user/toilet.php?id=' . $toiletId);
            exit;
        }

        $comment   = trim($_POST['checkout_comment'] ?? '');
        $sessionId = (int)$activeSession['id'];

        // Update session to closed
        $upd = $db->prepare("UPDATE toilet_sessions SET checkout_at=NOW(), checkout_comment=?, status='closed' WHERE id=?");
        $upd->execute([$comment, $sessionId]);

        // Handle photo uploads
        if (!empty($_FILES['checkout_photos']['name'][0])) {
            $saved = uploadSessionPhotos($_FILES['checkout_photos'], $sessionId, 'checkout');
            savePhotosToDb($sessionId, 'checkout', $saved);
        }

        setFlash('success', 'Check-out completed! Session recorded successfully.');
        header('Location: ' . BASE_URL . '/user/toilet.php?id=' . $toiletId);
        exit;
    }
}

// Re-fetch active session after POST
$activeSession = getActiveSession($currentUser['id'], $toiletId);

// Photos for active session
$activeCheckinPhotos = $activeSession ? getSessionPhotos($activeSession['id'], 'checkin') : [];

// Toilet history (all closed sessions for this toilet — visible to all assigned users)
$history = getToiletHistory($toiletId, 30);

$pageTitle = e($toilet['name']) . ' — Check In/Out';
require_once __DIR__ . '/../includes/header.php';
?>

<?php renderFlash(); ?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem">
            <a href="<?= BASE_URL ?>/user/dashboard.php" class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> My Toilets
            </a>
        </div>
        <h1>
            <span style="font-size:1.4rem">🚽</span>
            <?= e($toilet['name']) ?>
        </h1>
        <?php if ($toilet['location']): ?>
            <p class="page-subtitle"><i class="fas fa-location-dot" style="color:var(--primary)"></i> <?= e($toilet['location']) ?></p>
        <?php endif; ?>
    </div>
    <div>
        <?php if ($activeSession): ?>
            <span class="badge badge-open" style="font-size:0.85rem;padding:0.5rem 1rem">
                ● Active Check-In
            </span>
        <?php else: ?>
            <span class="badge badge-closed" style="font-size:0.85rem;padding:0.5rem 1rem">
                Ready
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     SESSION PANEL
     ============================================================ -->
<?php if ($activeSession): ?>
<!-- ---- ACTIVE SESSION: Show check-out form ---- -->
<div class="session-panel" style="border-color:rgba(245,158,11,0.3)">
    <div class="session-panel-header">
        <div class="session-panel-title" style="color:var(--warning)">
            <i class="fas fa-hourglass-half"></i> Active Session — <?= e($toilet['name']) ?>
        </div>
        <span style="font-size:0.85rem;color:var(--text-secondary)" data-checkin-time="<?= e($activeSession['checkin_at']) ?>">
            calculating…
        </span>
    </div>
    <div class="session-panel-body">
        <!-- Active Session Info -->
        <div class="session-info-grid">
            <div class="session-info-item">
                <div class="session-info-label"><i class="fas fa-right-to-bracket"></i> Checked In At</div>
                <div class="session-info-value"><?= fdt($activeSession['checkin_at'], 'd M Y, h:i:s A') ?></div>
            </div>
            <?php if ($activeSession['checkin_comment']): ?>
            <div class="session-info-item">
                <div class="session-info-label"><i class="fas fa-comment"></i> Check-In Note</div>
                <div class="session-info-value"><?= e($activeSession['checkin_comment']) ?></div>
            </div>
            <?php endif; ?>
            <div class="session-info-item">
                <div class="session-info-label"><i class="fas fa-camera"></i> Before Photos</div>
                <div class="session-info-value"><?= count($activeCheckinPhotos) ?> uploaded</div>
            </div>
        </div>

        <?php if (!empty($activeCheckinPhotos)): ?>
        <div style="margin-bottom:1.25rem">
            <div class="section-label checkin" style="margin-bottom:0.5rem"><i class="fas fa-images"></i> Before Photos</div>
            <div class="photo-gallery">
                <?php foreach ($activeCheckinPhotos as $p): ?>
                    <a href="<?= BASE_URL ?>/uploads/sessions/<?= e($p['file_path']) ?>" title="<?= e($p['original_name'] ?? 'Before Photo') ?>">
                        <img src="<?= BASE_URL ?>/uploads/sessions/<?= e($p['file_path']) ?>" alt="Check-in photo" loading="lazy">
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="divider"></div>

        <!-- Check-Out Form -->
        <div class="section-label checkout" style="font-size:0.9rem;margin-bottom:1.25rem">
            <i class="fas fa-right-from-bracket"></i> Check Out — Upload After Photos
        </div>

        <form method="POST" enctype="multipart/form-data" id="checkoutForm" novalidate>
            <input type="hidden" name="action" value="checkout">

            <div class="form-group">
                <label class="form-label" for="checkout_photos">
                    <i class="fas fa-camera"></i> After Cleaning Photos
                </label>
                <div class="photo-upload-area" data-preview="checkoutPreview">
                    <input type="file" id="checkout_photos" name="checkout_photos[]" style="display:none;" multiple>
                    <div class="upload-icon"><i class="fas fa-camera"></i></div>
                    <div class="upload-text">Take Photo (Live Camera)</div>
                    <div class="upload-hint">Live camera capture only &bull; Tap anywhere to launch camera</div>
                    <div style="margin-top:0.75rem;">
                        <span class="open-camera-btn" style="display:inline-flex;margin-bottom:0;">
                            <i class="fas fa-camera-retro"></i> Open Live Camera
                        </span>
                    </div>
                </div>
                <div id="checkoutPreview" class="photo-preview-grid"></div>
            </div>

            <div class="form-group">
                <label class="form-label" for="checkout_comment">
                    <i class="fas fa-comment"></i> Comment / Remarks (Optional)
                </label>
                <textarea id="checkout_comment" name="checkout_comment" class="form-control"
                          rows="3" placeholder="e.g. Floor cleaned, rubbish removed, toilet flushed…"></textarea>
            </div>

            <button type="submit" class="btn btn-success btn-lg"
                    onclick="return confirm('Confirm check-out for <?= e(addslashes($toilet['name'])) ?>?')">
                <i class="fas fa-right-from-bracket"></i> Submit Check-Out
            </button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ---- NO ACTIVE SESSION: Show check-in form ---- -->
<div class="session-panel" style="border-color:rgba(0,212,170,0.2)">
    <div class="session-panel-header">
        <div class="session-panel-title" style="color:var(--primary)">
            <i class="fas fa-right-to-bracket"></i> Check In — <?= e($toilet['name']) ?>
        </div>
        <span style="font-size:0.82rem;color:var(--text-secondary)">
            <i class="fas fa-clock"></i> <?= date('d M Y, h:i A') ?>
        </span>
    </div>
    <div class="session-panel-body">
        <div class="alert alert-info" style="margin-bottom:1.25rem">
            <i class="fas fa-info-circle"></i>
            Take photos of the toilet <strong>before</strong> cleaning. The check-in time will be recorded automatically.
        </div>

        <form method="POST" enctype="multipart/form-data" id="checkinForm" novalidate>
            <input type="hidden" name="action" value="checkin">

            <div class="form-group">
                <label class="form-label" for="checkin_photos">
                    <i class="fas fa-camera"></i> Before Photos (Current Condition)
                </label>
                <div class="photo-upload-area" data-preview="checkinPreview">
                    <input type="file" id="checkin_photos" name="checkin_photos[]" style="display:none;" multiple>
                    <div class="upload-icon"><i class="fas fa-camera"></i></div>
                    <div class="upload-text">Take Photo (Live Camera)</div>
                    <div class="upload-hint">Live camera capture only &bull; Tap anywhere to launch camera</div>
                    <div style="margin-top:0.75rem;">
                        <span class="open-camera-btn" style="display:inline-flex;margin-bottom:0;">
                            <i class="fas fa-camera-retro"></i> Open Live Camera
                        </span>
                    </div>
                </div>
                <div id="checkinPreview" class="photo-preview-grid"></div>
            </div>

            <div class="form-group">
                <label class="form-label" for="checkin_comment">
                    <i class="fas fa-comment"></i> Comment / Observations (Optional)
                </label>
                <textarea id="checkin_comment" name="checkin_comment" class="form-control"
                          rows="3" placeholder="e.g. Floor wet, rubbish bin full, no soap…"></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-lg"
                    onclick="return confirm('Confirm check-in for <?= e(addslashes($toilet['name'])) ?>?')">
                <i class="fas fa-right-to-bracket"></i> Submit Check-In
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================
     TOILET HISTORY (Shared — all assigned users can see)
     ============================================================ -->
<div style="margin-top:2.5rem">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
        <h2 style="font-size:1.15rem;font-weight:700;display:flex;align-items:center;gap:0.5rem">
            <i class="fas fa-clock-rotate-left" style="color:var(--primary)"></i>
            Toilet History
        </h2>
        <span class="td-muted" style="font-size:0.82rem"><?= count($history) ?> record<?= count($history)!=1?'s':'' ?></span>
    </div>

    <?php if (empty($history)): ?>
        <div class="empty-state" style="padding:3rem">
            <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
            <h3>No History Yet</h3>
            <p>Completed sessions will appear here.</p>
        </div>
    <?php else: ?>
        <div class="history-timeline">
            <?php foreach ($history as $sess): ?>
                <?php
                    $ciPhotos = getSessionPhotos($sess['id'], 'checkin');
                    $coPhotos = getSessionPhotos($sess['id'], 'checkout');
                ?>
                <div class="history-item">
                    <!-- Header -->
                    <div class="history-item-header">
                        <div>
                            <div class="history-date">
                                <i class="fas fa-calendar-day"></i>
                                <?= fdt($sess['checkin_at'], 'd M Y') ?>
                            </div>
                            <div class="history-meta">
                                <i class="fas fa-user"></i> <?= e($sess['full_name']) ?>
                                <span style="color:var(--border)">|</span>
                                <i class="fas fa-camera"></i>
                                <?= count($ciPhotos) + count($coPhotos) ?> photos
                                <span style="color:var(--border)">|</span>
                                Duration: <?= timeDiff($sess['checkin_at'], $sess['checkout_at']) ?>
                            </div>
                        </div>
                        <span class="expand-icon" style="color:var(--text-muted);font-size:0.75rem">▼</span>
                    </div>

                    <!-- Body -->
                    <div class="history-body">
                        <div style="display:grid;grid-template-columns:1fr 1px 1fr;gap:1.5rem;align-items:start">

                            <!-- Check-In -->
                            <div>
                                <div class="section-label checkin">
                                    <i class="fas fa-right-to-bracket"></i> Check In
                                </div>
                                <div class="section-time"><i class="fas fa-clock"></i> <?= fdt($sess['checkin_at'], 'h:i:s A') ?></div>
                                <div class="section-comment">
                                    <?= $sess['checkin_comment'] ? '"'.e($sess['checkin_comment']).'"' : '<em style="opacity:0.5">No comment</em>' ?>
                                </div>
                                <div class="section-label" style="color:var(--text-muted);margin-top:0.75rem;margin-bottom:0.4rem;font-size:0.72rem">
                                    BEFORE PHOTOS (<?= count($ciPhotos) ?>)
                                </div>
                                <?php if (!empty($ciPhotos)): ?>
                                    <div class="photo-gallery">
                                        <?php foreach ($ciPhotos as $p): ?>
                                            <a href="<?= BASE_URL ?>/uploads/sessions/<?= e($p['file_path']) ?>" title="<?= e($p['original_name'] ?? 'Before Photo') ?>">
                                                <img src="<?= BASE_URL ?>/uploads/sessions/<?= e($p['file_path']) ?>" alt="Before" loading="lazy">
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p style="color:var(--text-muted);font-size:0.8rem">None</p>
                                <?php endif; ?>
                            </div>

                            <!-- Divider -->
                            <div style="background:var(--border);align-self:stretch"></div>

                            <!-- Check-Out -->
                            <div>
                                <div class="section-label checkout">
                                    <i class="fas fa-right-from-bracket"></i> Check Out
                                </div>
                                <div class="section-time"><i class="fas fa-clock"></i> <?= fdt($sess['checkout_at'], 'h:i:s A') ?></div>
                                <div class="section-comment">
                                    <?= $sess['checkout_comment'] ? '"'.e($sess['checkout_comment']).'"' : '<em style="opacity:0.5">No comment</em>' ?>
                                </div>
                                <div class="section-label" style="color:var(--text-muted);margin-top:0.75rem;margin-bottom:0.4rem;font-size:0.72rem">
                                    AFTER PHOTOS (<?= count($coPhotos) ?>)
                                </div>
                                <?php if (!empty($coPhotos)): ?>
                                    <div class="photo-gallery">
                                        <?php foreach ($coPhotos as $p): ?>
                                            <a href="<?= BASE_URL ?>/uploads/sessions/<?= e($p['file_path']) ?>" title="<?= e($p['original_name'] ?? 'After Photo') ?>">
                                                <img src="<?= BASE_URL ?>/uploads/sessions/<?= e($p['file_path']) ?>" alt="After" loading="lazy">
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p style="color:var(--text-muted);font-size:0.8rem">None</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
