<?php
/**
 * Server-side confirmation state for a local addon handoff.
 * The caller supplies an authenticated administrator ID and a dedicated private
 * directory outside the web root. The caller owns CSRF checks and authorization.
 */
function extended_create_addon_confirmation($directory, $admin_id, $archive_path, $source_id, $addon_id, $sha256)
{
    require_once __DIR__ . '/extended_local_addon_handoff.php';
    $directory = extended_addon_confirmation_directory($directory);
    $preview = extended_preview_local_addon_handoff($archive_path, $source_id, $addon_id, $sha256);
    $absolute_archive = realpath($archive_path);
    if ($absolute_archive === false) {
        throw new RuntimeException('Verified archive is unavailable');
    }
    $token = bin2hex(random_bytes(32));
    $record = array(
        'admin_id' => $admin_id,
        'expires_at' => time() + 600,
        'archive_path' => $absolute_archive,
        'preview' => $preview,
    );
    $bytes = json_encode($record, JSON_THROW_ON_ERROR);
    $temp = @tempnam($directory, '.confirmation-');
    if ($temp === false) {
        throw new RuntimeException('Could not create confirmation state');
    }
    try {
        if (dirname($temp) !== $directory || !chmod($temp, 0600) || file_put_contents($temp, $bytes) !== strlen($bytes)
            || !link($temp, $directory . '/' . $token)) {
            throw new RuntimeException('Could not publish confirmation state');
        }
    } finally {
        unlink($temp);
    }
    return array('token' => $token, 'selectable_paths' => $preview['selectable_paths']);
}

function extended_addon_confirmation_directory($directory)
{
    $path = realpath($directory);
    if ($path === false || !is_dir($path) || (fileperms($path) & 0077) !== 0) {
        throw new RuntimeException('Confirmation directory must be private');
    }
    return $path;
}

/**
 * Finish a confirmation once. A null selection cancels it.
 * A successful confirmation returns the checked archive, selection and identity.
 */
function extended_finish_addon_confirmation($directory, $admin_id, $token, $selected_paths)
{
    require_once __DIR__ . '/extended_local_addon_handoff.php';
    if (!is_string($token) || preg_match('/^[a-f0-9]{64}$/D', $token) !== 1) {
        throw new RuntimeException('Confirmation is unavailable');
    }
    $path = extended_addon_confirmation_directory($directory) . '/' . $token;
    $stream = @fopen($path, 'rb');
    if ($stream === false) {
        throw new RuntimeException('Confirmation is unavailable');
    }
    try {
        if (!flock($stream, LOCK_EX)) {
            throw new RuntimeException('Could not lock confirmation state');
        }
        // A concurrent request may have opened the file before it was consumed.
        clearstatcache(true, $path);
        $current = @lstat($path);
        $opened = fstat($stream);
        if ($current === false || $opened === false || $current['ino'] !== $opened['ino']
            || $current['dev'] !== $opened['dev'] || ($current['mode'] & 0170077) !== 0100000) {
            throw new RuntimeException('Confirmation is unavailable');
        }
        $record = json_decode(stream_get_contents($stream), true, 512, JSON_THROW_ON_ERROR);
        if ($record['admin_id'] !== $admin_id) {
            throw new RuntimeException('Confirmation is unavailable');
        }
        if ($record['expires_at'] <= time()) {
            unlink($path);
            throw new RuntimeException('Confirmation has expired');
        }
        $result = null;
        if ($selected_paths !== null) {
            $result = array(
                'archive_path' => $record['archive_path'],
                'source_id' => $record['preview']['source_id'],
                'addon_id' => $record['preview']['addon_id'],
                'sha256' => $record['preview']['sha256'],
                'selected_paths' => extended_confirm_local_addon_handoff(
                    $record['archive_path'], $record['preview'], $selected_paths
                ),
            );
        }
        if (!unlink($path)) {
            throw new RuntimeException('Could not consume confirmation state');
        }
        if ($record['expires_at'] <= time()) {
            throw new RuntimeException('Confirmation has expired');
        }
        return $result;
    } finally {
        fclose($stream);
    }
}
