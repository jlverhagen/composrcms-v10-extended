<?php
/** Read the independent approval record. A missing or malformed record fails closed. */
function extended_read_catalogue_high_water($path)
{
    require_once __DIR__ . '/extended_registry_trust.php';
    extended_registry_check_directory(dirname($path));
    extended_registry_check_existing_file($path);
    $bytes = @file_get_contents($path, false, null, 0, 262145);
    if ($bytes === false || strlen($bytes) > 262144) {
        throw new RuntimeException('Catalogue high-water record is unavailable');
    }
    $state = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($state) || ($state['version'] ?? null) !== 1 || !is_array($state['sources'] ?? null)) {
        throw new RuntimeException('Invalid catalogue high-water record');
    }
    foreach ($state['sources'] as $id => $entry) {
        if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/D', (string)$id)
            || !is_int($entry['issued_at'] ?? null) || $entry['issued_at'] < 1
            || !is_string($entry['sha256'] ?? null) || !preg_match('/^[a-f0-9]{64}$/D', $entry['sha256'])) {
            throw new RuntimeException('Invalid catalogue high-water entry');
        }
    }
    return $state;
}

/** Operate on exact manifest bytes after signature, source and freshness verification. */
function extended_assert_approved_catalogue($path, $source_id, $issued_at, $manifest_bytes)
{
    $state = extended_read_catalogue_high_water($path);
    $approved = $state['sources'][$source_id] ?? null;
    if ($approved === null || $approved['issued_at'] !== $issued_at
        || !hash_equals($approved['sha256'], hash('sha256', $manifest_bytes))) {
        throw new RuntimeException('Catalogue does not match independent approval');
    }
}

/** Used by the privileged offline operator, with pins from independent trusted configuration. */
function extended_approve_catalogue_high_water($path, $source_id, $manifest_bytes, $signature, $keys, $allowed_ids, $now = null)
{
    if (PHP_SAPI !== 'cli') throw new RuntimeException('Catalogue approval requires CLI');
    require_once __DIR__ . '/extended_addon_catalogue.php';
    require_once __DIR__ . '/extended_manifest_verify.php';
    require_once __DIR__ . '/extended_registry_trust.php';
    if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/D', $source_id)) {
        throw new RuntimeException('Invalid source identity');
    }
    $catalogue = extended_verify_signed_manifest($manifest_bytes, $signature, $keys, $allowed_ids);
    extended_validate_catalogue($catalogue, $source_id, $now);
    $candidate = array('issued_at' => $catalogue['issued_at'], 'sha256' => hash('sha256', $manifest_bytes));

    extended_registry_check_directory(dirname($path));
    $lock_path = $path . '.lock';
    extended_registry_check_existing_file($lock_path);
    $lock = @fopen($lock_path, 'c');
    if ($lock === false) throw new RuntimeException('Cannot open catalogue approval lock');
    try {
        if (!flock($lock, LOCK_EX)) throw new RuntimeException('Cannot lock catalogue approval record');
        $state = extended_read_catalogue_high_water($path);
        $previous = $state['sources'][$source_id] ?? null;
        if ($previous !== null && $candidate['issued_at'] <= $previous['issued_at']) {
            if ($candidate === $previous) return $candidate;
            throw new RuntimeException('Catalogue approval would roll back or replace an equal-time snapshot');
        }
        $state['sources'][$source_id] = $candidate;
        $bytes = json_encode($state, JSON_THROW_ON_ERROR);
        $temp = @tempnam(dirname($path), '.catalogue-approval-');
        if ($temp === false) throw new RuntimeException('Cannot stage approval record');
        try {
            $mode = fileperms($path) & 0777;
            if (dirname($temp) !== dirname($path) || !chgrp($temp, filegroup($path))
                || !chmod($temp, $mode) || file_put_contents($temp, $bytes) !== strlen($bytes)
                || !rename($temp, $path)) {
                throw new RuntimeException('Cannot publish catalogue approval record');
            }
        } finally {
            if (is_file($temp)) unlink($temp);
        }
        return $candidate;
    } finally {
        fclose($lock);
    }
}
