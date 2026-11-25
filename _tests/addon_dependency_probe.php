<?php
/**
 * Isolated CLI probe for addon dependency discovery.
 * Usage: php addon_dependency_probe.php CORE_SOURCE valid|malformed
 * Stubbed dependencies; not a CMS install or uninstall acceptance test.
 */
if (PHP_SAPI !== 'cli' || $argc !== 3 || !in_array($argv[2], array('valid', 'malformed'), true)) {
    fwrite(STDERR, "Usage: php addon_dependency_probe.php CORE_SOURCE valid|malformed\n");
    exit(2);
}
$source = realpath($argv[1]);
if ($source === false) { fwrite(STDERR, "Invalid source\n"); exit(2); }
$mode = $argv[2];
$fixture = sys_get_temp_dir() . '/composr-dependency-probe-' . bin2hex(random_bytes(8));
if (!mkdir($fixture . '/sources_custom/hooks/systems/addon_registry', 0700, true)) throw new RuntimeException('Unable to create fixture');
$hookdir = $fixture . '/sources_custom/hooks/systems/addon_registry';
$names = ($mode === 'valid') ? array('empty', 'requires', 'missing', 'unrelated') : array('malformed');
foreach ($names as $name) file_put_contents($hookdir . '/' . $name . '.php', '');
function get_file_base() { return $GLOBALS['fixture']; }
function filter_naughty_harsh($name) { return $name; }
function find_all_hooks($type, $system) { return array_fill_keys($GLOBALS['names'], 'sources_custom'); }
function collapse_1d_complexity($key, $rows) { return array_column($rows, $key); }
function extract_module_functions($path, $names) {
    $name = basename($path, '.php');
    if ($name === 'missing') return array(null);
    $fixtures = array(
        'empty' => array(),
        'requires' => array('requires' => array('target')),
        'unrelated' => array('requires' => array('other')),
        'malformed' => array('incompatible' => array()),
    );
    return array(array(function () use ($fixtures, $name) { return $fixtures[$name]; }, array()));
}
class DependencyProbeDB {
    function query_select($table, $fields, $where, $order) {
        return ($where['addon_name_dependant_upon'] === 'target') ? array(array('addon_name' => 'database_user')) : array();
    }
}
$GLOBALS['SITE_DB'] = new DependencyProbeDB();
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});
try {
    require_once $source . '/sources/addons2.php';
    if ($mode === 'valid') {
        $found = find_addon_dependencies_on('target');
        sort($found);
        if ($found !== array('database_user', 'requires')) throw new RuntimeException('Unexpected target dependencies: ' . json_encode($found));
        $other = find_addon_dependencies_on('other');
        if ($other !== array('unrelated')) throw new RuntimeException('Unexpected unrelated dependencies: ' . json_encode($other));
        if (find_addon_dependencies_on('absent') !== array()) throw new RuntimeException('Unexpected absent dependency');
        echo "valid empty, missing, registry and database dependency cases passed\n";
    } else {
        try {
            find_addon_dependencies_on('target');
            throw new RuntimeException('Malformed nonempty dependency data was silently accepted');
        } catch (ErrorException $e) {
            if (strpos($e->getMessage(), 'requires') === false) throw $e;
            echo "malformed nonempty dependency data raised a diagnostic\n";
        }
    }
} finally {
    restore_error_handler();
    foreach ($names as $name) unlink($hookdir . '/' . $name . '.php');
    rmdir($hookdir);
    rmdir(dirname($hookdir));
    rmdir(dirname(dirname($hookdir)));
    rmdir(dirname(dirname(dirname($hookdir))));
    rmdir($fixture);
}
