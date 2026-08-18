</main><!-- /.main-content -->

<footer class="site-footer">
    <div class="footer-inner">
        <span><?= APP_NAME ?> &mdash; <?= APP_SUBTITLE ?></span>
        <span class="footer-time" id="footerTime"></span>
    </div>
</footer>

<!-- Lightbox -->
<div class="lightbox-overlay" id="lightboxOverlay">
    <button class="lightbox-close" aria-label="Close photo view"><i class="fas fa-times"></i></button>
    <img id="lightboxImg" class="lightbox-img" src="" alt="Enlarged photo">
</div>

<script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= filemtime(__DIR__ . '/../assets/js/app.js') ?>"></script>
</body>
</html>
