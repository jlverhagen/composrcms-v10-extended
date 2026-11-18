<?php
/**
 * CLI-only ownership evidence from trusted addon hooks and installer inventory.
 * Usage: php addon_scope_inventory.php TRUSTED_CORE_CHECKOUT FINDINGS_JSON
 * Registry hooks are executable PHP; use only a reviewed source checkout.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$source = isset($argv[1]) ? realpath($argv[1]) : false;
$findings_path = $argv[2] ?? '';
if ($argc !== 3 || $source === false || !is_file($source . '/data/files.bin') || !is_file($findings_path)) {
    fwrite(STDERR, "Usage: php addon_scope_inventory.php TRUSTED_CORE_CHECKOUT FINDINGS_JSON\n");
    exit(2);
}
$findings = json_decode(file_get_contents($findings_path), true, 512, JSON_THROW_ON_ERROR);
$installer = unserialize(file_get_contents($source . '/data/files.bin'), array('allowed_classes' => false));
if (!is_array($installer) || !isset($findings['findings'])) {
    throw new RuntimeException('Invalid inventory or findings input');
}
$owners = array();
$addons = array();
$errors = array();
$counts = array('bundled' => 0, 'optional' => 0);
foreach (array('bundled' => 'sources', 'optional' => 'sources_custom') as $kind => $directory) {
    foreach (glob($source . '/' . $directory . '/hooks/systems/addon_registry/*.php') as $registry) {
        $id = basename($registry, '.php');
        try {
            require_once $registry;
            $class = 'Hook_addon_registry_' . $id;
            $hook = new $class();
            $files = $hook->get_file_list();
            if (!is_array($files)) {
                throw new RuntimeException('Registry file list is not an array');
            }
            $owner = $kind . ':' . $id;
            $counts[$kind]++;
            $addons[$owner] = array('registry' => substr($registry, strlen($source) + 1), 'file_count' => count($files));
            foreach ($files as $file) {
                if (!is_string($file)) {
                    throw new RuntimeException('Registry file path is not a string');
                }
                $owners[$file][] = $owner;
            }
        } catch (Throwable $error) {
            $errors[$kind . ':' . $id] = $error->getMessage();
        }
    }
}
$groups = array();
$relevant_owners = array();
foreach ($findings['findings'] as $id => $entries) {
    $paths = array_unique(array_column($entries, 'file'));
    sort($paths);
    foreach ($paths as $path) {
        $file_owners = array_values(array_unique($owners[$path] ?? array()));
        sort($file_owners);
        $groups[$id][] = array('path' => $path, 'owners' => $file_owners, 'installer_inventory' => isset($installer[$path]), 'exists' => is_file($source . '/' . $path));
        foreach ($file_owners as $owner) {
            $relevant_owners[$owner] = $addons[$owner];
        }
    }
}
ksort($groups);
ksort($relevant_owners);
$tracker = array('php_files' => 0, 'installer_php_files' => 0, 'owners' => array(), 'unowned_php' => array());
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source . '/tracker', FilesystemIterator::SKIP_DOTS)) as $file) {
    if (!$file->isFile() || substr($file->getFilename(), -4) !== '.php') {
        continue;
    }
    $path = substr($file->getPathname(), strlen($source) + 1);
    $tracker['php_files']++;
    if (isset($installer[$path])) {
        $tracker['installer_php_files']++;
    }
    if (!isset($owners[$path])) {
        $tracker['unowned_php'][] = $path;
    }
    foreach ($owners[$path] ?? array() as $owner) {
        $tracker['owners'][$owner] = ($tracker['owners'][$owner] ?? 0) + 1;
    }
}
sort($tracker['unowned_php']);
ksort($tracker['owners']);
$report = array(
    'registry_counts' => $counts,
    'registry_errors' => $errors,
    'installer_inventory_entries' => count($installer),
    'installer_inventory_sha256' => hash_file('sha256', $source . '/data/files.bin'),
    'groups' => $groups,
    'relevant_addons' => $relevant_owners,
    'tracker' => $tracker,
    'limitation' => 'Registry ownership and installer membership are evidence of packaging scope, not a compatibility or maintenance approval.'
);
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
exit($errors === array() ? 0 : 1);
