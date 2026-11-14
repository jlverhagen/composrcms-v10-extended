<?php
/**
 * Experimental fail-closed HTTPS transport for signed catalogues and addon packages.
 * No redirects, no proxy, no credentials, no private IPs, no install side effects.
 * DNS answers are checked then a selected address is pinned via CURLOPT_RESOLVE.
 * Requires PHP cURL, and must be audited against the actual hosting environment.
 */
function extended_transport_is_public_ip($ip)
{
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return false;
    }
    $packed = inet_pton($ip);
    if (strlen($packed) === 16) {
        // Restrict to global-unicast space. Translation and tunnel prefixes may
        // encode an IPv4 destination and cannot be validated as ordinary IPv6.
        if ((ord($packed[0]) & 0xe0) !== 0x20) return false;
        if (substr($packed, 0, 2) === "\x20\x02") return false; // 6to4
        if (substr($packed, 0, 2) === "\x20\x01" && (ord($packed[2]) & 0xfe) === 0) return false;
        if (substr($packed, 0, 2) === "\x3f\xff" && (ord($packed[2]) & 0xf0) === 0) return false;
        return true;
    }
    $value = unpack('N', $packed)[1];
    $blocked = array(
        array(0x64400000, 0xffc00000), // Carrier-grade NAT: 100.64.0.0/10
        array(0xc0000000, 0xffffff00), // IETF protocol assignments: 192.0.0.0/24
        array(0xc0000200, 0xffffff00), // Documentation: 192.0.2.0/24
        array(0xc0586300, 0xffffff00), // Deprecated 6to4 relay: 192.88.99.0/24
        array(0xc6120000, 0xfffe0000), // Benchmarking: 198.18.0.0/15
        array(0xc6336400, 0xffffff00), // Documentation: 198.51.100.0/24
        array(0xcb007100, 0xffffff00), // Documentation: 203.0.113.0/24
        array(0xe0000000, 0xe0000000), // Multicast and reserved: 224.0.0.0/3
    );
    foreach ($blocked as $range) {
        if (($value & $range[1]) === $range[0]) {
            return false;
        }
    }
    return true;
}

function extended_transport_validate_url($url)
{
    if (!is_string($url) || strlen($url) > 2048 || preg_match('/[\x00-\x20\x7f]/', $url)) {
        throw new RuntimeException('Invalid URL');
    }
    $parts = parse_url($url);
    if (!is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https' ||
        !isset($parts['host']) || isset($parts['user']) || isset($parts['pass']) ||
        isset($parts['fragment']) || (isset($parts['port']) && $parts['port'] !== 443)) {
        throw new RuntimeException('Only standard HTTPS URLs without credentials are allowed');
    }
    $host = strtolower($parts['host']);
    if (strlen($host) > 253 || !preg_match('/^(?=.{1,253}$)[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?$/D', $host) ||
        strpos($host, '..') !== false || !str_contains($host, '.') ||
        filter_var($host, FILTER_VALIDATE_IP) ||
        preg_match('/^[0-9.]+$/D', $host) || str_ends_with($host, '.local') ||
        str_ends_with($host, '.internal') || str_ends_with($host, '.localhost')) {
        throw new RuntimeException('Invalid public DNS hostname');
    }
    foreach (explode('.', $host) as $label) {
        if ($label === '' || strlen($label) > 63 || str_starts_with($label, '-') || str_ends_with($label, '-')) {
            throw new RuntimeException('Invalid DNS label');
        }
    }
    if (!isset($parts['path']) || $parts['path'] === '' || $parts['path'][0] !== '/') throw new RuntimeException('URL must contain an absolute path');
    return $host;
}

function extended_transport_resolve_public($host)
{
    if (!function_exists('dns_get_record')) throw new RuntimeException('DNS resolver unavailable');
    $records = dns_get_record($host, DNS_A | DNS_AAAA);
    if ($records === false || count($records) === 0) throw new RuntimeException('No DNS addresses');
    $addresses = array();
    foreach ($records as $record) {
        $ip = $record['ip'] ?? ($record['ipv6'] ?? null);
        if (!is_string($ip) || !extended_transport_is_public_ip($ip)) {
            throw new RuntimeException('DNS returned non-public or invalid address');
        }
        $addresses[$ip] = true;
    }
    if (!$addresses) throw new RuntimeException('No public DNS addresses');
    return array_keys($addresses);
}

/** Download to a new temporary file in $directory. Caller owns cleanup. */
function extended_transport_fetch($url, $directory, $max_bytes = 2097152)
{
    if (!extension_loaded('curl')) throw new RuntimeException('PHP cURL is required');
    if (!is_int($max_bytes) || $max_bytes < 1 || $max_bytes > 52428800) throw new RuntimeException('Invalid byte limit');
    $host = extended_transport_validate_url($url);
    $addresses = extended_transport_resolve_public($host);
    if (!is_dir($directory) || !is_writable($directory)) throw new RuntimeException('Invalid temporary directory');
    $path = tempnam($directory, 'extended-fetch-');
    if ($path === false) throw new RuntimeException('Cannot allocate temporary file');
    $file = fopen($path, 'wb');
    if ($file === false) { @unlink($path); throw new RuntimeException('Cannot open temporary file'); }
    $curl = curl_init();
    if ($curl === false) { fclose($file); @unlink($path); throw new RuntimeException('Cannot initialize cURL'); }
    $bytes = 0;
    $limit_exceeded = false;
    try {
        $pinned = $addresses[0];
        $pin = str_contains($pinned, ':') ? '[' . $pinned . ']' : $pinned;
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROXY => '',
            CURLOPT_NOPROXY => '*',
            CURLOPT_HTTPHEADER => array('Accept: application/json, application/octet-stream', 'Accept-Encoding: identity'),
            CURLOPT_USERAGENT => 'ComposrCMS-v10-Extended-development/0.4',
            CURLOPT_RESOLVE => array($host . ':443:' . $pin),
            CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use ($file, $max_bytes, &$bytes, &$limit_exceeded) {
                $n = strlen($chunk);
                if ($bytes + $n > $max_bytes) { $limit_exceeded = true; return 0; }
                $written = fwrite($file, $chunk);
                if ($written === false) return 0;
                $bytes += $written;
                return $written;
            },
        );
        if (defined('CURLOPT_PROTOCOLS_STR')) $options[CURLOPT_PROTOCOLS_STR] = 'https';
        else $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
        if (defined('CURLOPT_REDIR_PROTOCOLS_STR')) $options[CURLOPT_REDIR_PROTOCOLS_STR] = 'https';
        // Never initiate a request if TLS, proxy, protocol, or DNS pinning
        // options could not all be applied.
        if (!curl_setopt_array($curl, $options)) {
            throw new RuntimeException('Cannot configure secure HTTPS transport');
        }
        $ok = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $remote_ip = curl_getinfo($curl, CURLINFO_PRIMARY_IP);
        if ($limit_exceeded) throw new RuntimeException('Download exceeds byte limit');
        if ($ok !== true || $status !== 200 || $bytes === 0) throw new RuntimeException('HTTPS retrieval failed or returned non-200 status');
        if ($remote_ip !== $pinned) throw new RuntimeException('Remote IP did not match DNS pin');
        if (!fflush($file)) throw new RuntimeException('Cannot flush temporary download');
        return $path;
    } catch (Throwable $e) {
        fclose($file);
        @unlink($path);
        throw $e;
    } finally {
        curl_close($curl);
        if (is_resource($file)) fclose($file);
    }
}
