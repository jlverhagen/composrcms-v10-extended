<?php
/**
 * Offline snapshot stager. NO network calls, no automatic updates, no installation.
 * Creates immutable, content-addressed verified snapshots under a protected directory.
 * The caller must separately authorize and configure the browser to use returned paths.
 */
function extended_stage_signed_catalogue_snapshot($manifest_path, $signature_path, $trusted_keys, $allowed_key_ids, $source_id, $root, $now = null)
{
    require_once __DIR__ . '/extended_addon_catalogue.php';
    require_once __DIR__ . '/extended_registry_trust.php';
    if (!is_string($source_id) || !preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/D', $source_id)) {
        throw new RuntimeException('Invalid source identity');
    }
    if (!is_string($root) || $root === '' || !str_starts_with($root, '/') || !is_dir($root) || is_link($root)) {
        throw new RuntimeException('Snapshot root must be an existing absolute protected directory');
    }
    extended_registry_check_directory($root);
    foreach (array($manifest_path, $signature_path) as $path) {
        if (!is_string($path) || !is_file($path) || is_link($path)) throw new RuntimeException('Invalid snapshot input');
        extended_registry_check_existing_file($path);
    }
    $manifest_bytes = file_get_contents($manifest_path, false, null, 0, 2097153);
    $signature_bytes = file_get_contents($signature_path, false, null, 0, 4097);
    if ($manifest_bytes === false || $signature_bytes === false || strlen($manifest_bytes) > 2097152 || strlen($signature_bytes) > 4096) {
        throw new RuntimeException('Cannot read bounded snapshot input');
    }
    require_once __DIR__ . '/extended_manifest_verify.php';
    $catalogue = extended_verify_signed_manifest($manifest_bytes, $signature_bytes, $trusted_keys, $allowed_key_ids);
    extended_validate_catalogue($catalogue, $source_id, $now);
    $digest = hash('sha256', $manifest_bytes);
    $snapshot_dir = $root . '/' . $source_id . '-' . $digest;
    $lock_path = $root . '/snapshots.lock';
    extended_registry_check_existing_file($lock_path);
    $lock = @fopen($lock_path, 'c');
    if ($lock === false) throw new RuntimeException('Cannot open snapshot store lock');
    try {
        if (!flock($lock, LOCK_EX)) throw new RuntimeException('Cannot lock snapshot store');
        extended_registry_check_existing_file($lock_path);
        $mp = $snapshot_dir . '/catalogue.json';
        $sp = $snapshot_dir . '/catalogue.sig.json';
        if (!file_exists($snapshot_dir) && !is_link($snapshot_dir)) {
            $pending = $root . '/.snapshot-' . bin2hex(random_bytes(16));
            if (!mkdir($pending, 0700)) throw new RuntimeException('Cannot create pending snapshot');
            try {
                extended_registry_atomic_write($pending . '/catalogue.json', $manifest_bytes);
                extended_registry_atomic_write($pending . '/catalogue.sig.json', $signature_bytes);
                extended_verify_local_catalogue($pending . '/catalogue.json', $pending . '/catalogue.sig.json',
                    $trusted_keys, $allowed_key_ids, $source_id, $now);
                if (!rename($pending, $snapshot_dir)) throw new RuntimeException('Cannot publish verified snapshot');
            } finally {
                if (is_dir($pending)) {
                    @unlink($pending . '/catalogue.json');
                    @unlink($pending . '/catalogue.sig.json');
                    @rmdir($pending);
                }
            }
        }
        extended_registry_check_directory($snapshot_dir);
        if (!is_file($mp) || !is_file($sp)) throw new RuntimeException('Incomplete snapshot');
        extended_registry_check_existing_file($mp);
        extended_registry_check_existing_file($sp);
        $stored_manifest = file_get_contents($mp, false, null, 0, 2097153);
        $stored_signature = file_get_contents($sp, false, null, 0, 4097);
        if ($stored_manifest !== $manifest_bytes || $stored_signature !== $signature_bytes) {
            throw new RuntimeException('Snapshot conflict');
        }
        // Re-verify committed files before returning their paths.
        extended_verify_local_catalogue($mp, $sp, $trusted_keys, $allowed_key_ids, $source_id, $now);
        return array('source_id' => $source_id, 'digest' => $digest, 'manifest_path' => $mp, 'signature_path' => $sp);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
