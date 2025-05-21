<?php
// Common footer file to be included in all pages
// Ensures consistent footer structure across the site
?>
</div> <!-- End of .page or .container -->

<!-- Common JavaScript -->
<!-- Always load Tabler.js for consistent theming across the application -->
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

<!-- Include jQuery only if required -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Include common JavaScript functions -->
<script src="<?= BASE_URL ?>includes/js/general.js"></script>

<!-- Additional scripts specific to the page can be included after this file -->
</body>

</html>