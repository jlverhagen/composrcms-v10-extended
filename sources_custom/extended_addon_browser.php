<?php
/**
 * ComposrCMS v10 Extended - read-only, offline, signed catalogue aggregator.
 * This intentionally has NO network calls, file writes, or installation APIs.
 * The caller supplies a trusted, administrator-controlled configuration file.
 */
function extended_browser_config_from_file($path)
{
    if (!is_string($path) || $path === '' || !is_file($path) || is_link($path)) {
        throw new RuntimeException('Browser configuration unavailable');
    }
    if (filesize($path) > 262144) throw new RuntimeException('Oversized browser configuration');
    $data = json_decode(file_get_contents($path), true);
    if (!is_array($data) || ($data['schema_version'] ?? null) !== 1 || !isset($data['sources']) ||
        !is_array($data['sources']) || !array_is_list($data['sources']) || count($data['sources']) > 100) {
        throw new RuntimeException('Invalid browser configuration');
    }
    return $data;
}

function extended_browser_load($config, $now = null)
{
    if (!is_array($config) || ($config['schema_version'] ?? null) !== 1 ||
        !isset($config['sources']) || !is_array($config['sources']) || !array_is_list($config['sources'])) {
        throw new RuntimeException('Invalid browser configuration');
    }
    if (count($config['sources']) > 100) throw new RuntimeException('Too many sources');
    $result = array('sources' => array(), 'addons' => array());
    $seen = array();
    foreach ($config['sources'] as $source) {
        if (!is_array($source) || !isset($source['id'], $source['enabled']) ||
            !is_string($source['id']) || !preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/D', $source['id']) ||
            !is_bool($source['enabled']) || isset($seen[$source['id']])) {
            throw new RuntimeException('Invalid or duplicate source configuration');
        }
        $id = $source['id'];
        $seen[$id] = true;
        if (!$source['enabled']) {
            $result['sources'][] = array('id' => $id, 'status' => 'disabled', 'count' => 0);
            continue;
        }
        try {
            foreach (array('manifest_path','signature_path','trusted_keys','allowed_key_ids') as $key) {
                if (!isset($source[$key])) throw new RuntimeException('Incomplete source configuration');
            }
            if (!is_string($source['manifest_path']) || !is_string($source['signature_path']) ||
                !str_starts_with($source['manifest_path'], '/') || !str_starts_with($source['signature_path'], '/') ||
                is_link($source['manifest_path']) || is_link($source['signature_path']) ||
                !is_array($source['trusted_keys']) || !is_array($source['allowed_key_ids'])) {
                throw new RuntimeException('Invalid local manifest configuration');
            }
            require_once __DIR__ . '/extended_addon_catalogue.php';
            $record = defined('EXTENDED_CATALOGUE_HIGH_WATER_PATH') ? EXTENDED_CATALOGUE_HIGH_WATER_PATH : null;
            $manifest = $source['manifest_path'];
            $signature = $source['signature_path'];
            if ($record !== null) {
                require_once __DIR__ . '/extended_catalogue_high_water.php';
                $approved = extended_read_catalogue_high_water($record)['sources'][$id] ?? null;
                if ($approved === null) throw new RuntimeException('Source has no independent approval');
                $snapshot = dirname($record) . '/' . $id . '-' . $approved['sha256'];
                if (file_exists($snapshot) || is_link($snapshot)) {
                    extended_registry_check_directory($snapshot);
                    $manifest = $snapshot . '/catalogue.json';
                    $signature = $snapshot . '/catalogue.sig.json';
                    extended_registry_check_existing_file($manifest);
                    extended_registry_check_existing_file($signature);
                }
            }
            $catalogue = extended_verify_local_catalogue(
                $manifest, $signature, $source['trusted_keys'], $source['allowed_key_ids'], $id, $now, $record
            );
            $count = 0;
            foreach ($catalogue['addons'] as $addon) {
                // Source identity is part of the key: same addon name from two sources stays separate.
                $key = $id . ':' . $addon['id'];
                $result['addons'][$key] = array(
                    'key' => $key, 'source_id' => $id, 'id' => $addon['id'],
                    'name' => $addon['name'], 'version' => $addon['version'],
                    'license' => $addon['license'], 'cms_minimum' => $addon['cms_compatibility']['minimum'],
                    'php_minimum' => $addon['php_compatibility']['minimum'],
                    'sha256' => $addon['sha256']
                );
                $count++;
            }
            $result['sources'][] = array('id' => $id, 'status' => 'verified', 'count' => $count);
        } catch (Throwable $e) {
            // Fail this source closed; do not display any unsigned/expired entries from it.
            $result['sources'][] = array('id' => $id, 'status' => 'unavailable', 'count' => 0);
        }
    }
    ksort($result['addons'], SORT_STRING);
    return $result;
}

/** Escape all externally supplied strings, including signed but untrusted publisher metadata. */
function extended_browser_escape($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function extended_browser_render_html($result, $local_install = false)
{
    $html = '<div class="extended-addon-browser">';
    $html .= $local_install
        ? '<p><strong>Signed addon catalogue</strong> - review an operator-staged local archive below.</p>'
        : '<p><strong>Read-only signed addon catalogue</strong> - downloads and installation are disabled.</p>';
    $html .= '<h2>Repository status</h2><ul>';
    foreach ($result['sources'] as $source) {
        $html .= '<li>' . extended_browser_escape($source['id']) . ': ' . extended_browser_escape($source['status']) .
            ' (' . intval($source['count']) . ' addons)</li>';
    }
    $html .= '</ul><h2>Available addons</h2>';
    if (count($result['addons']) === 0) return $html . '<p>No verified addons are available.</p></div>';
    $html .= '<table class="wide_table"><thead><tr><th scope="col">Addon</th><th scope="col">Source</th><th scope="col">Version</th><th scope="col">License</th><th scope="col">Minimum CMS</th><th scope="col">Minimum PHP</th></tr></thead><tbody>';
    foreach ($result['addons'] as $addon) {
        $html .= '<tr><td>' . extended_browser_escape($addon['name']) . ' <small>(' . extended_browser_escape($addon['id']) . ')</small></td>' .
            '<td>' . extended_browser_escape($addon['source_id']) . '</td><td>' . extended_browser_escape($addon['version']) .
            '</td><td>' . extended_browser_escape($addon['license']) . '</td><td>' . extended_browser_escape($addon['cms_minimum']) .
            '</td><td>' . extended_browser_escape($addon['php_minimum']) . '</td></tr>';
    }
    return $html . '</tbody></table></div>';
}
