<?php
// Simple proxy validation script
// - Fetches a proxy via ProxyManager
// - Calls a test endpoint through the proxy
// - Prints results or clear errors at each step

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/ProxyManager.php';

    echo "Step 1: Initialize ProxyManager...\n";
    $proxyManager = new ProxyManager();

    // Dummy target for selection logic; replace if your logic depends on domain
    $targetForSelection = 'https://example.com';
    $usedProxies = [];

    echo "Step 2: Fetching a proxy using getLeastUsedProxy for target: {$targetForSelection}\n";
    $proxy = $proxyManager->getLeastUsedProxy($targetForSelection, $usedProxies);

    if (!$proxy || empty($proxy['ip']) || empty($proxy['port'])) {
        throw new Exception('No valid proxy returned by ProxyManager.');
    }

    $proxyIp = trim($proxy['ip']);
    $proxyPort = trim($proxy['port']);
    $proxyTypeStr = strtolower($proxy['type'] ?? 'http');
    $proxyKey = $proxyIp . ':' . $proxyPort;

    echo "Step 3: Proxy selected -> {$proxyKey} (Type: " . strtoupper($proxyTypeStr) . ")\n";

    // Choose a developer-friendly test endpoint that returns caller IP
    // httpbin returns JSON like: { "origin": "x.x.x.x" }
    $testUrl = 'https://httpbin.org/ip';
    echo "Step 4: Preparing cURL request to {$testUrl} via proxy...\n";

    $ch = curl_init();

    // Map string type to CURL proxy type
    switch ($proxyTypeStr) {
        case 'https':
            $proxyType = CURLPROXY_HTTPS;
            break;
        case 'socks4':
            $proxyType = CURLPROXY_SOCKS4;
            break;
        case 'socks5':
            $proxyType = CURLPROXY_SOCKS5;
            break;
        default:
            $proxyType = CURLPROXY_HTTP;
            break;
    }

    $timeout = (int)(getenv('PROXY_TEST_TIMEOUT') ?: 20);
    $connectTimeout = (int)(getenv('PROXY_TEST_CONNECT_TIMEOUT') ?: 10);

    $opts = [
        CURLOPT_URL => $testUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => $connectTimeout,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_PROXY => $proxyKey,
        CURLOPT_PROXYTYPE => $proxyType,
        CURLOPT_ENCODING => 'gzip, deflate',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: ProxyTest/1.0'
        ],
        CURLOPT_VERBOSE => true,
        CURLOPT_STDERR => fopen('php://stderr', 'w'),
    ];

    // Proxy auth if provided
    if (!empty($proxy['username']) && !empty($proxy['password'])) {
        $opts[CURLOPT_PROXYUSERPWD] = $proxy['username'] . ':' . $proxy['password'];
    }

    curl_setopt_array($ch, $opts);

    echo "Step 5: Executing request... (Timeout: {$timeout}s, ConnectTimeout: {$connectTimeout}s)\n";
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo "ERROR: cURL failed.\n";
        echo "Proxy: {$proxyKey} (" . strtoupper($proxyTypeStr) . ")\n";
        echo "cURL Error: {$curlError}\n";
        if (isset($proxy['id'])) {
            echo "Note: Proxy ID = {$proxy['id']}\n";
            // Mirror production flow: record failed attempt
            try {
                $proxyManager->recordFailedAttempt($proxy['id']);
            } catch (Throwable $e) { /* ignore */
            }
        }
        exit(1);
    }

    if ($httpCode !== 200) {
        echo "ERROR: Non-200 HTTP status received: {$httpCode}\n";
        echo "Proxy: {$proxyKey} (" . strtoupper($proxyTypeStr) . ")\n";
        echo "Response: \n{$response}\n";
        if (isset($proxy['id'])) {
            // Mirror production flow: record failed attempt on HTTP errors too
            try {
                $proxyManager->recordFailedAttempt($proxy['id']);
            } catch (Throwable $e) { /* ignore */
            }
        }
        exit(1);
    }

    $data = json_decode($response, true);
    $origin = $data['origin'] ?? 'unknown';

    // Success: reset failed attempts like production verifier
    if (isset($proxy['id'])) {
        try {
            $proxyManager->resetFailedAttempts($proxy['id']);
        } catch (Throwable $e) { /* ignore */
        }
    }

    echo "Step 6: Success! Response from test endpoint:\n";
    echo "- Proxy used: {$proxyKey} (" . strtoupper($proxyTypeStr) . ")\n";
    echo "- Origin reported by server (your outbound IP): {$origin}\n";
    echo "- Raw response: {$response}\n";

    echo "\nTest completed.\n";
} catch (Throwable $e) {
    echo 'FATAL: ' . $e->getMessage() . "\n";
    exit(1);
}
