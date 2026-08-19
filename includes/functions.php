<?php
// ============================================================
// Shared Utility Functions
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Sanitize a string for output.
 */
if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Format a MySQL datetime for display.
 */
function fdt($datetime, $format = 'd M Y, h:i A') {
    if (!$datetime) return '—';
    return date($format, strtotime($datetime));
}

/**
 * Get the time difference as a human-readable string (e.g. "17 minutes").
 */
function timeDiff($start, $end) {
    $diff = strtotime($end) - strtotime($start);
    if ($diff < 60)   return $diff . ' sec';
    if ($diff < 3600) return round($diff / 60) . ' min';
    return round($diff / 3600, 1) . ' hr';
}

/**
 * Get toilets assigned to a user.
 */
function getAssignedToilets($userId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT t.* FROM toilets t
        INNER JOIN user_toilets ut ON ut.toilet_id = t.id
        WHERE ut.user_id = ? AND t.status = 'active'
        ORDER BY t.name ASC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Check if a user is assigned to a specific toilet.
 */
function isUserAssignedToToilet($userId, $toiletId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM user_toilets WHERE user_id = ? AND toilet_id = ?");
    $stmt->execute([$userId, $toiletId]);
    return (bool)$stmt->fetch();
}

/**
 * Get the active (open) session for a user + toilet, or null.
 */
function getActiveSession($userId, $toiletId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM toilet_sessions
        WHERE user_id = ? AND toilet_id = ? AND status = 'open'
        ORDER BY checkin_at DESC LIMIT 1
    ");
    $stmt->execute([$userId, $toiletId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Get all photos for a session by type (checkin/checkout).
 */
function getSessionPhotos($sessionId, $type) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM session_photos
        WHERE session_id = ? AND photo_type = ?
        ORDER BY uploaded_at ASC
    ");
    $stmt->execute([$sessionId, $type]);
    return $stmt->fetchAll();
}

/**
 * Get all closed (historical) sessions for a toilet, newest first.
 */
function getToiletHistory($toiletId, $limit = 50) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT ts.*, u.full_name, u.username,
               t.name AS toilet_name
        FROM toilet_sessions ts
        INNER JOIN users u ON u.id = ts.user_id
        INNER JOIN toilets t ON t.id = ts.toilet_id
        WHERE ts.toilet_id = ? AND ts.status = 'closed'
        ORDER BY ts.checkin_at DESC
        LIMIT ?
    ");
    $stmt->execute([$toiletId, $limit]);
    return $stmt->fetchAll();
}

/**
 * Upload multiple photos for a session.
 * Returns an array of successfully saved file paths.
 */
function uploadSessionPhotos($files, $sessionId, $type) {
    $saved = [];
    $dir = UPLOAD_DIR . $sessionId . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // $files should be a re-indexed array from $_FILES multi-upload
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        if ($files['size'][$i] > MAX_FILE_SIZE) continue;
        if (!in_array($files['type'][$i], ALLOWED_TYPES, true)) continue;

        $ext      = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        $filename = $type . '_' . uniqid() . '.' . $ext;
        $destPath = $dir . $filename;

        if (move_uploaded_file($files['tmp_name'][$i], $destPath)) {
            $saved[] = [
                'path'          => $sessionId . '/' . $filename,
                'original_name' => $files['name'][$i],
            ];
        }
    }
    return $saved;
}

/**
 * Save photo records to DB after upload.
 */
function savePhotosToDb($sessionId, $type, $savedFiles) {
    if (empty($savedFiles)) return;
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO session_photos (session_id, photo_type, file_path, original_name)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($savedFiles as $f) {
        $stmt->execute([$sessionId, $type, $f['path'], $f['original_name']]);
    }
}

/**
 * Get all users (optionally filtered by role).
 */
function getAllUsers($role = '') {
    $db = getDB();
    if ($role) {
        $stmt = $db->prepare("SELECT * FROM users WHERE role = ? ORDER BY full_name ASC");
        $stmt->execute([$role]);
    } else {
        $stmt = $db->query("SELECT * FROM users ORDER BY role DESC, full_name ASC");
    }
    return $stmt->fetchAll();
}

/**
 * Get all toilets.
 */
function getAllToilets() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM toilets ORDER BY name ASC");
    return $stmt->fetchAll();
}

/**
 * Get toilet IDs assigned to a user.
 */
function getUserToiletIds($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT toilet_id FROM user_toilets WHERE user_id = ?");
    $stmt->execute([$userId]);
    return array_column($stmt->fetchAll(), 'toilet_id');
}

/**
 * Render a flash alert HTML block (call once at top of page body).
 */
function renderFlash() {
    $flash = getFlash();
    if (!$flash) return;
    $icons = [
        'success' => '✅',
        'error'   => '❌',
        'warning' => '⚠️',
        'info'    => 'ℹ️',
    ];
    $icon = $icons[$flash['type']] ?? 'ℹ️';
    echo '<div class="alert alert-' . e($flash['type']) . ' auto-dismiss">'
       . $icon . ' ' . e($flash['message'])
       . '</div>';
}

/**
 * Get the full URL to a user's avatar, or null if none is set.
 */
function getUserAvatarUrl($user) {
    if (empty($user)) return null;
    $filename = is_array($user) ? ($user['profile_picture'] ?? null) : null;
    if (!$filename && is_numeric($user)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $stmt->execute([(int)$user]);
        $row = $stmt->fetch();
        $filename = $row['profile_picture'] ?? null;
    }
    if ($filename && file_exists(AVATAR_DIR . $filename)) {
        return AVATAR_URL . $filename . '?v=' . filemtime(AVATAR_DIR . $filename);
    }
    return null;
}

/**
 * Render user avatar markup (either image or initial letter).
 */
function renderUserAvatar($user, $extraClass = '', $style = '') {
    $url = getUserAvatarUrl($user);
    $name = is_array($user) ? ($user['full_name'] ?? $user['username'] ?? 'U') : 'U';
    $initial = strtoupper(substr(trim($name), 0, 1)) ?: 'U';

    $classAttr = 'user-avatar' . ($extraClass ? ' ' . e($extraClass) : '');
    $styleAttr = $style ? ' style="' . e($style) . '"' : '';

    if ($url) {
        return '<img src="' . e($url) . '" class="' . $classAttr . ' user-avatar-img" alt="' . e($name) . '"' . $styleAttr . '>';
    }
    return '<span class="' . $classAttr . '"' . $styleAttr . '>' . e($initial) . '</span>';
}

/**
 * Upload and set a new profile picture for a user.
 */
function uploadUserAvatar($file, $userId) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error code: ' . $file['error']];
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Image size must be 5MB or less.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mimeType, $allowed, true)) {
        return ['success' => false, 'error' => 'Invalid file format. Please upload JPG, PNG, WEBP, or GIF.'];
    }

    if (!is_dir(AVATAR_DIR)) {
        mkdir(AVATAR_DIR, 0755, true);
    }

    $ext = match ($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        default      => 'jpg'
    };

    $db = getDB();
    // Fetch and remove existing avatar file if any
    $stmt = $db->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $old = $stmt->fetchColumn();
    if ($old && file_exists(AVATAR_DIR . $old)) {
        @unlink(AVATAR_DIR . $old);
    }

    $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
    $destination = AVATAR_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Failed to save uploaded image.'];
    }

    $stmt = $db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $stmt->execute([$filename, $userId]);

    if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$userId) {
        $_SESSION['profile_picture'] = $filename;
    }

    return ['success' => true, 'filename' => $filename];
}

/**
 * Delete a user's custom profile picture.
 */
function deleteUserAvatar($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $old = $stmt->fetchColumn();
    if ($old && file_exists(AVATAR_DIR . $old)) {
        @unlink(AVATAR_DIR . $old);
    }

    $stmt = $db->prepare("UPDATE users SET profile_picture = NULL WHERE id = ?");
    $stmt->execute([$userId]);

    if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$userId) {
        $_SESSION['profile_picture'] = null;
    }

    return ['success' => true];
}
