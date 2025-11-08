</main>
<footer class="text-center py-4 mt-4">
    <div class="container">
        <small>&copy; <?= date('Y') ?> Smart Shared Goals</small>
    </div>
</footer>
<script src="assets/js/app.js"></script>
<script src="assets/js/ui.js"></script>
<?php
// Output page-specific scripts using an absolute path derived from SCRIPT_NAME to avoid relative path issues
if (!empty($page_scripts) && is_array($page_scripts)) {
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    foreach ($page_scripts as $src) {
        $srcOut = $src;
        // if src is a relative path (doesn't start with http or '/'), prefix with base
        if (!preg_match('#^(https?:)?//#', $src) && strpos($src, '/') !== 0) {
            $srcOut = $base . '/' . ltrim($src, '/');
        }
        echo '<script src="' . htmlspecialchars($srcOut, ENT_QUOTES) . '"></script>' . PHP_EOL;
    }
    // small diagnostic to confirm page scripts were printed
    echo "<script>console.info('page scripts included', " . json_encode($page_scripts) . " );</script>" . PHP_EOL;
}
?>
</body>

</html>