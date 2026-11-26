<?php
// Run independently with a reviewed copy of sources_custom/nested_csv.php.
$root = sys_get_temp_dir() . '/composr-nested-csv-' . bin2hex(random_bytes(8));
if (!mkdir($root) || !mkdir($root . '/private_data')) {
    throw new RuntimeException('Cannot create test directory');
}
$GLOBALS['nested_probe_root'] = $root;
function get_forum_type() { return 'none'; }
function get_custom_file_base() { return $GLOBALS['nested_probe_root']; }
function get_file_base() { return $GLOBALS['nested_probe_root']; }
function warn_exit($message) { throw new RuntimeException($message); }
set_error_handler(function ($severity, $message) { throw new ErrorException($message, 0, $severity); });
try {
    file_put_contents($root . '/private_data/audit.csv', "country,state,code\nUSA,Wisconsin,01\nUSA,Illinois,1\nUSA,Wisconsin,01\nCanada,Ontario,2\n");
    require dirname(__DIR__) . '/sources_custom/nested_csv.php';
    $values = get_csv_data_values('audit.csv', 'country', 'USA', 'state');
    $rows = get_csv_data_values('audit.csv', 'country', 'USA');
    if ($values !== array(0 => 'Wisconsin', 1 => 'Illinois') || count($rows) !== 2 || $rows[0]['code'] !== '01' || $rows[1]['code'] !== '1') {
        throw new RuntimeException('Unexpected CSV results');
    }
    echo "PASS nested CSV scalar and distinct rows\n";
} finally {
    unlink($root . '/private_data/audit.csv');
    rmdir($root . '/private_data');
    rmdir($root);
}
