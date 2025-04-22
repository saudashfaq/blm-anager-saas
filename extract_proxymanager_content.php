<?php
// Extract the content between the HTML boilerplate from proxymanager.php
$file = file_get_contents('proxymanager.php.bak');

// Extract PHP code at the top
preg_match('/^<\?php.*?\?>/s', $file, $phpMatches);
$phpCode = $phpMatches[0] ?? '';

// Add pageTitle and bodyClass variables
$phpCode = str_replace('<?php', "<?php\n\$pageTitle = 'Proxy Manager';\n\$bodyClass = 'theme-light';", $phpCode);

// Extract content between <body> and </body>
preg_match('/<body.*?>(.*)<\/body>/s', $file, $bodyMatches);
$bodyContent = $bodyMatches[1] ?? '';

// Create new content with header and footer includes
$newContent = $phpCode . "\n\n";
$newContent .= "// Include header\ninclude_once __DIR__ . '/includes/header.php';\n?>\n\n";
$newContent .= $bodyContent . "\n\n";
$newContent .= "<!-- Include proxy manager specific JavaScript -->\n";
$newContent .= "<script src=\"<?= defined('BASE_URL') ? BASE_URL : '/' ?>includes/js/proxymanager.js\"></script>\n\n";
$newContent .= "<?php include_once __DIR__ . '/includes/footer.php'; ?>";

// Write to new file
file_put_contents('new_proxymanager.php', $newContent);
echo "Content extracted and saved to new_proxymanager.php\n";
