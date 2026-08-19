</main><!-- /.main-content -->

<footer class="site-footer">
    <div class="footer-inner">
        <span><?= APP_NAME ?> &mdash; <?= e(__('app_subtitle')) ?></span>
        <span class="footer-time" id="footerTime"></span>
    </div>
</footer>

<!-- Lightbox -->
<div class="lightbox-overlay" id="lightboxOverlay">
    <button class="lightbox-close" aria-label="Close photo view"><i class="fas fa-times"></i></button>
    <img id="lightboxImg" class="lightbox-img" src="" alt="Enlarged photo">
</div>

<script>
window.__i18n = {
    lang: <?= json_encode(getCurrentLang()) ?>,
    camera_modal_title: <?= json_encode(__('camera_modal_title')) ?>,
    camera_placeholder: <?= json_encode(__('camera_placeholder')) ?>,
    camera_permission_hint: <?= json_encode(__('camera_permission_hint')) ?>,
    camera_captured_label: <?= json_encode(__('camera_captured_label')) ?>,
    camera_use_photos: <?= json_encode(__('camera_use_photos')) ?>,
    flip_camera: <?= json_encode(__('flip_camera')) ?>,
    take_photo_shutter: <?= json_encode(__('take_photo_shutter')) ?>,
    camera_denied: <?= json_encode(__('profile_camera_denied')) ?>,
    ongoing: <?= json_encode(__('badge_ongoing')) ?>
};
</script>
<script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= filemtime(__DIR__ . '/../assets/js/app.js') ?>"></script>
</body>
</html>

