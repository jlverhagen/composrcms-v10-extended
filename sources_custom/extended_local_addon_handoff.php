<?php
/**
 * Read-only prototype for checking a verified local TAR against Composr's stock reader.
 * Callers supply a protected archive and metadata from an already verified catalogue.
 * The preview belongs in trusted server-side state, separate from form data.
 */
function extended_preview_local_addon_handoff($archive_path, $source_id, $addon_id, $sha256)
{
    require_once __DIR__ . '/extended_addon_download.php';
    $package = extended_verify_local_addon_package($archive_path, $sha256, $addon_id, 52428800, true);

    require_code('tar');
    $tar = tar_open($archive_path, 'rb');
    try {
        $stock_inventory = array();
        foreach (tar_get_directory($tar) as $offset => $entry) {
            $stock_inventory[$offset] = array('path' => $entry['path'], 'size' => $entry['size']);
        }
        if ($stock_inventory !== $package['inventory']) {
            throw new RuntimeException('Stock TAR inventory differs from Extended preflight');
        }
        $metadata = tar_get_file($tar, 'addon.inf');
        if ($metadata === null || strlen($metadata['data']) !== $metadata['size']) {
            throw new RuntimeException('Stock TAR reader could not read addon.inf');
        }
        $info = better_parse_ini_file(null, $metadata['data']);
        if (($info['name'] ?? null) !== $addon_id) {
            throw new RuntimeException('Stock addon identity differs from the verified catalogue');
        }
    } finally {
        tar_close($tar);
    }

    $paths = array();
    foreach ($stock_inventory as $entry) {
        $path = $entry['path'];
        if (!str_ends_with($path, '/') && !in_array($path, array('addon.inf', 'mod.inf', 'addon_install_code.php'), true)) {
            $paths[] = $path;
        }
    }
    return array(
        'source_id' => $source_id,
        'addon_id' => $addon_id,
        'sha256' => $sha256,
        'inventory' => $stock_inventory,
        'selectable_paths' => $paths,
    );
}

/**
 * Recheck the trusted preview and return the exact subset requested in the final form.
 * The caller still owns authentication, token expiry, staging and all installation.
 */
function extended_confirm_local_addon_handoff($archive_path, $preview, $selected_paths)
{
    $current = extended_preview_local_addon_handoff(
        $archive_path, $preview['source_id'], $preview['addon_id'], $preview['sha256']
    );
    if ($current !== $preview) {
        throw new RuntimeException('Archive no longer matches the reviewed preview');
    }
    if (!is_array($selected_paths) || !array_is_list($selected_paths) || $selected_paths === array()) {
        throw new RuntimeException('Select at least one reviewed addon file');
    }
    $seen = array();
    foreach ($selected_paths as $path) {
        if (!is_string($path) || !in_array($path, $current['selectable_paths'], true) || isset($seen[$path])) {
            throw new RuntimeException('Unreviewed or duplicate addon file selection');
        }
        $seen[$path] = true;
    }
    // Stock Composr uses non-strict in_array for file selection.
    foreach ($current['inventory'] as $entry) {
        $path = $entry['path'];
        if (is_numeric($path) && !isset($seen[$path]) && in_array($path, $selected_paths)) {
            throw new RuntimeException('Stock installer would select an additional path');
        }
    }
    return $selected_paths;
}
