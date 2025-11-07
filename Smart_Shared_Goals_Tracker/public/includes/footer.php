</main>
<footer class="text-center py-4 mt-4">
    <div class="container">
        <small>&copy; <?= date('Y') ?> Smart Shared Goals</small>
    </div>
</footer>
<script src="assets/js/app.js"></script>
<script src="assets/js/ui.js"></script>
<?php if (!empty($page_scripts) && is_array($page_scripts)) {
    foreach ($page_scripts as $src) {
        echo '<script src="' . htmlspecialchars($src, ENT_QUOTES) . '"></script>' . PHP_EOL;
    }
} ?>
</body>

</html>