<?php
// Common footer file to be included in all pages
// Ensures consistent footer structure across the site
?>
</div> <!-- End of .page or .container -->

<!-- Common JavaScript -->
<?php if (strpos($pageTitle, 'Dashboard') !== false || strpos($pageTitle, 'Proxy Manager') !== false): ?>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
<?php else: ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php endif; ?>

<!-- Include jQuery only if required -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Include common JavaScript functions -->
<script src="<?= defined('BASE_URL') ? BASE_URL : '/' ?>includes/general.js"></script>

<!-- Additional scripts specific to the page can be included after this file -->
</body>

</html>