<?php
/**
 * Offline signed registry acceptance with monotonic sequence and durable verified cache.
 * This is a standalone prototype. Caller must protect state_dir outside the web root.
 * No network access, no automatic trust-on-first-use, and no installer integration.
 */
function extended_registry_accept($manifest_bytes, $signature_json, $trusted_keys, $allowed_key_ids, $state_dir, $now = null)
{
    require_once __DIR__ . '/extended_manifest_verify.php';
    if (!is_string($state_dir) || $state_dir === '' || !is_dir($state_dir) || is_link($state_dir)) {
        throw new RuntimeException('State directory must exist and be protected');
    }
    extended_registry_check_directory($state_dir);
    if ($now === null) $now = time();
    if (!is_int($now) || $now < 0) throw new RuntimeException('Invalid clock');
    $manifest = extended_verify_signed_manifest($manifest_bytes, $signature_json, $trusted_keys, $allowed_key_ids);
    extended_validate_registry_manifest($manifest, $now);
    $lock_path = $state_dir . '/registry.lock';
    extended_registry_check_existing_file($lock_path);
    $lock = fopen($lock_path, 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) throw new RuntimeException('Cannot lock registry state');
    try {
        extended_registry_check_existing_file($lock_path);
        $state_path = $state_dir . '/registry-state.json';
        extended_registry_check_existing_file($state_path);
        $previous = null;
        if (file_exists($state_path)) {
            $raw = file_get_contents($state_path);
            $previous = json_decode($raw, true);
            if (!is_array($previous) || !isset($previous['sequence'], $previous['sha256']) || !is_int($previous['sequence']) || !is_string($previous['sha256'])) throw new RuntimeException('Corrupt registry state: fail closed');
        }
        $digest = hash('sha256', $manifest_bytes);
        if ($previous !== null) {
            if ($manifest['sequence'] < $previous['sequence']) throw new RuntimeException('Registry rollback rejected');
            if ($manifest['sequence'] === $previous['sequence'] && !hash_equals($previous['sha256'], $digest)) throw new RuntimeException('Registry equivocation rejected');
        }
        // Cache is written before the state pointer. Failure cannot advance the sequence.
        $cache_path = $state_dir . '/registry-' . $digest . '.json';
        extended_registry_check_existing_file($cache_path);
        extended_registry_atomic_write($cache_path, $manifest_bytes);
        $state = json_encode(array('sequence' => $manifest['sequence'], 'sha256' => $digest, 'expires_at' => $manifest['expires_at']), JSON_THROW_ON_ERROR);
        extended_registry_atomic_write($state_path, $state);
        return $manifest;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
function extended_registry_atomic_write($path, $bytes)
{
    $dir = dirname($path);
    extended_registry_check_directory($dir);
    extended_registry_check_existing_file($path);
    $temp = tempnam($dir, '.registry-');
    if ($temp === false) throw new RuntimeException('Cannot create temp file');
    try {
        if (file_put_contents($temp, $bytes, LOCK_EX) !== strlen($bytes)) throw new RuntimeException('Incomplete registry write');
        chmod($temp, 0600);
        extended_registry_check_existing_file($path);
        if (!rename($temp, $path)) throw new RuntimeException('Cannot commit registry state');
    } finally {
        if (file_exists($temp)) unlink($temp);
    }
}

/** Refuse symlinks, hardlinked state, and world/group writable directories. */
function extended_registry_check_directory($dir)
{
    clearstatcache(true, $dir);
    if (!is_dir($dir) || is_link($dir)) throw new RuntimeException('Unsafe registry state directory');
    $st = @lstat($dir);
    if ($st === false || (($st['mode'] & 0022) !== 0)) throw new RuntimeException('Registry state directory must not be group/world writable');
}
function extended_registry_check_existing_file($path)
{
    clearstatcache(true, $path);
    if (!file_exists($path) && !is_link($path)) return;
    $st = @lstat($path);
    if ($st === false || !is_file($path) || is_link($path) || $st['nlink'] !== 1 || (($st['mode'] & 0022) !== 0)) {
        throw new RuntimeException('Unsafe registry state file');
    }
}

/** Validate registry metadata after detached signature verification. */
function extended_validate_registry_manifest(array $manifest, int $now, bool $allow_expired = false): void
{
    foreach (array('registry_id', 'sequence', 'issued_at', 'expires_at', 'sources') as $field) {
        if (!array_key_exists($field, $manifest)) throw new RuntimeException('Missing registry field: ' . $field);
    }
    if ($manifest['registry_id'] !== 'composrcms-v10-extended') throw new RuntimeException('Wrong registry identity');
    foreach (array('sequence', 'issued_at', 'expires_at') as $field) {
        if (!is_int($manifest[$field]) || $manifest[$field] < 0) throw new RuntimeException('Invalid ' . $field);
    }
    if ($manifest['sequence'] < 1 || $manifest['issued_at'] > $now + 300 || (!$allow_expired && $manifest['expires_at'] <= $now) || $manifest['expires_at'] <= $manifest['issued_at'] || $manifest['expires_at'] - $manifest['issued_at'] > 30 * 86400) {
        throw new RuntimeException('Invalid registry freshness');
    }
    if (!is_array($manifest['sources']) || count($manifest['sources']) > 100 || !array_is_list($manifest['sources'])) throw new RuntimeException('Invalid sources');
    $seen = array();
    foreach ($manifest['sources'] as $source) {
        if (!is_array($source) || !isset($source['id'], $source['manifest_url'], $source['enabled'], $source['signing_key_ids']) || !is_string($source['id']) || !preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/D', $source['id']) || !is_string($source['manifest_url']) || !preg_match('~^https://[^/]+/~i', $source['manifest_url']) || !is_bool($source['enabled']) || !is_array($source['signing_key_ids']) || !array_is_list($source['signing_key_ids'])) throw new RuntimeException('Invalid source record');
        require_once __DIR__ . '/extended_http_transport.php';
        extended_transport_validate_url($source['manifest_url']);
        if (isset($seen[$source['id']])) throw new RuntimeException('Duplicate source');
        $seen[$source['id']] = true;
        foreach ($source['signing_key_ids'] as $id) if (!is_string($id) || strlen($id) > 128) throw new RuntimeException('Invalid source key ID');
    }
}
