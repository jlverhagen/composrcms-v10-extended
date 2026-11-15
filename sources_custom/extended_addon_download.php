<?php
/** Local-only package preflight. Does not stage or install. */
function extended_verify_local_addon_package($path, $expected_sha256, $expected_id, $max_bytes = 52428800, $include_inventory = false)
{
    if (!is_file($path) || is_link($path) || !is_readable($path)) throw new RuntimeException('Package must be a regular local file');
    $size = filesize($path);
    if ($size === false || $size <= 0 || $size > $max_bytes || ($size % 512) !== 0) throw new RuntimeException('Invalid package size');
    if (!is_string($expected_sha256) || !preg_match('/^[a-f0-9]{64}$/D', $expected_sha256) ||
        !hash_equals($expected_sha256, hash_file('sha256', $path))) throw new RuntimeException('Package checksum mismatch');
    if (!is_string($expected_id) || !preg_match('/^[A-Za-z0-9_][A-Za-z0-9_.-]{0,127}$/D', $expected_id)) throw new RuntimeException('Invalid expected addon ID');
    // Strict TAR header scan. This is a preflight only; final Composr extraction must be separately audited.
    $fp = fopen($path, 'rb');
    if ($fp === false) throw new RuntimeException('Cannot open package');
    $count = 0; $found = false; $terminated = false; $names = array(); $files = array(); $directories = array(); $position = 0;
    $inventory = array();
    try {
        while ($position + 512 <= $size) {
            $header = fread($fp, 512);
            if (strlen($header) !== 512) throw new RuntimeException('Truncated TAR header');
            $position += 512;
            if ($header === str_repeat("\0", 512)) {
                // Require the standard two zero blocks and prohibit hidden
                // records after the end marker, even if another reader scans on.
                $remaining = $size - $position;
                if ($remaining < 512) throw new RuntimeException('Incomplete TAR end marker');
                while ($remaining > 0) {
                    $chunk = fread($fp, min(8192, $remaining));
                    if ($chunk === false || $chunk === '' || trim($chunk, "\0") !== '') {
                        throw new RuntimeException('Non-zero data after TAR end marker');
                    }
                    $remaining -= strlen($chunk);
                }
                $terminated = true;
                break;
            }
            $stored = trim(substr($header, 148, 8), " \0");
            if (!preg_match('/^[0-7]+$/D', $stored)) throw new RuntimeException('Invalid TAR checksum');
            $sumHeader = substr_replace($header, str_repeat(' ', 8), 148, 8);
            $sum = 0; for ($i=0; $i<512; $i++) $sum += ord($sumHeader[$i]);
            if ($sum !== octdec($stored)) throw new RuntimeException('TAR header checksum mismatch');
            $name = rtrim(substr($header, 0, 100), "\0");
            $prefix = rtrim(substr($header, 345, 155), "\0");
            if ($prefix !== '' && substr($header, 257, 5) !== 'ustar') throw new RuntimeException('Ambiguous TAR prefix');
            if ($prefix !== '') $name = $prefix . '/' . $name;
            $type = $header[156];
            if ($name === '' || str_contains($name, "\0") || str_contains($name, "\\") ||
                str_starts_with($name, '/') || preg_match('/^[A-Za-z]:/', $name) ||
                str_contains($name, '//') || preg_match('#(^|/)\.\.?(/|$)#', $name)) throw new RuntimeException('Unsafe TAR path');
            if ($type !== "0" && $type !== "\0" && $type !== "5") throw new RuntimeException('Unsupported TAR entry type');
            $isDirectory = ($type === '5');
            if ($isDirectory !== str_ends_with($name, '/')) throw new RuntimeException('Ambiguous TAR entry type');
            $canonical = rtrim($name, '/');
            if ($canonical === '' || isset($names[$canonical])) throw new RuntimeException('Duplicate TAR path');
            $parts = explode('/', $canonical);
            array_pop($parts);
            $parent = '';
            foreach ($parts as $part) {
                $parent = ($parent === '') ? $part : $parent . '/' . $part;
                if (isset($files[$parent])) throw new RuntimeException('TAR file is also a directory');
                $directories[$parent] = true;
            }
            if ($isDirectory) {
                if (isset($files[$canonical])) throw new RuntimeException('TAR file is also a directory');
                $directories[$canonical] = true;
            } else {
                if (isset($directories[$canonical])) throw new RuntimeException('TAR file is also a directory');
                $files[$canonical] = true;
            }
            $names[$canonical] = true;
            $sizeText = trim(substr($header, 124, 12), " \0");
            if (!preg_match('/^[0-7]+$/D', $sizeText)) throw new RuntimeException('Invalid TAR entry size');
            $entrySize = octdec($sizeText);
            $padded = (int)(ceil($entrySize / 512) * 512);
            if ($entrySize < 0 || $position + $padded > $size) throw new RuntimeException('Truncated TAR entry');
            if ($name === 'addon.inf' && ($type === '0' || $type === "\0")) {
                if ($entrySize === 0) throw new RuntimeException('Empty addon.inf');
                if ($entrySize > 65536) throw new RuntimeException('Oversized addon.inf');
                $inf = fread($fp, $entrySize);
                if (strlen($inf) !== $entrySize) throw new RuntimeException('Truncated addon.inf');
                // Reject ambiguous metadata before comparing it with the signed catalogue.
                // Composr parses the whole INI file, so a second name assignment must
                // not be allowed to override a matching first assignment.
                if (preg_match_all('/^[ \t]*name[ \t]*=/mi', $inf) !== 1 ||
                    !preg_match('/^[ \t]*name[ \t]*=[ \t]*(?:"'.preg_quote($expected_id, '/').'"|\''.preg_quote($expected_id, '/').'\'|'.preg_quote($expected_id, '/').')[ \t]*\r?$/mi', $inf)) {
                    throw new RuntimeException('addon.inf identity mismatch');
                }
                $found = true;
                fseek($fp, $padded - $entrySize, SEEK_CUR);
            } else fseek($fp, $padded, SEEK_CUR);
            if ($include_inventory) {
                $inventory[$position - 512] = array('path' => $name, 'size' => $entrySize);
            }
            $position += $padded;
            if (++$count > 10000) throw new RuntimeException('Too many TAR entries');
        }
    } finally { fclose($fp); }
    if (!$terminated) throw new RuntimeException('Missing TAR end marker');
    if (!$found) throw new RuntimeException('Missing addon.inf');
    $result = array('addon_id' => $expected_id, 'sha256' => $expected_sha256, 'entries' => $count, 'bytes' => $size);
    if ($include_inventory) {
        $result['inventory'] = $inventory;
    }
    return $result;
}

function extended_stage_verified_addon()
{
    throw new RuntimeException('Remote staging remains disabled until transport and Composr integration are audited');
}

/** Retrieve a package and verify locally; returns temporary path, NEVER installs. */
function extended_fetch_verified_addon($addon, $temporary_directory)
{
    if (!is_array($addon) || !isset($addon['download_url'], $addon['sha256'], $addon['id'])) {
        throw new RuntimeException('Incomplete addon entry');
    }
    require_once __DIR__ . '/extended_http_transport.php';
    $path = extended_transport_fetch($addon['download_url'], $temporary_directory, 52428800, true);
    try {
        $result = extended_verify_local_addon_package($path, $addon['sha256'], $addon['id']);
        return array('path' => $path, 'verification' => $result);
    } catch (Throwable $e) {
        @unlink($path);
        throw $e;
    }
}
