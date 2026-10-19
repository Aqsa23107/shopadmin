<?php
/**
 * Global Footer Component
 */
?>
</main>
<footer style="padding:1.25rem 2rem;border-top:1px solid var(--border-color);display:flex;align-items:center;justify-content:space-between;font-size:0.8rem;color:var(--text-muted);background:var(--bg-card);margin-top:auto;">
    <div>
        &copy; <?= date('Y') ?> <strong><?= APP_NAME ?></strong> &bull; shop_db on localhost
    </div>
    <div style="display:flex;gap:1rem;">
        <span>Core PHP &bull; MySQL &bull; Modern UI</span>
        <span>v<?= APP_VERSION ?></span>
    </div>
</footer>
</div> <!-- /main-wrapper -->
</div> <!-- /app-wrapper -->

<!-- Toast Notification Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Custom Scripts -->
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<?php if (isset($extraScripts)): ?>
    <?= $extraScripts ?>
<?php endif; ?>
</body>
</html>
