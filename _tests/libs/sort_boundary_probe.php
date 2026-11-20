<?php
/** CLI-only nullable sorting checks. Usage: php sort_boundary_probe.php SOURCE [--baseline] */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$source = isset($argv[1]) ? realpath($argv[1]) : false;
$baseline = ($argv[2] ?? '') === '--baseline';
if (!$source || $argc < 2 || $argc > 3 || ($argc === 3 && !$baseline)) { exit(2); }
$checks = 0;
$diagnostics = array();
$observations = array();
$DEV_MODE = true;
set_error_handler(function ($severity, $message, $file, $line) use ($baseline, &$diagnostics) {
    if (!(error_reporting() & $severity)) { return true; }
    if ($baseline && $severity === E_DEPRECATED) {
        $diagnostics[$message] = ($diagnostics[$message] ?? 0) + 1;
        return true;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
function sort_expect($condition, $message)
{
    $GLOBALS['checks']++;
    if (!$condition) { throw new RuntimeException($message); }
}
class SortFixtureText
{
    private $value;
    public function __construct($value) { $this->value = $value; }
    public function evaluate() { return $this->value; }
}
try {
    require_once $source . '/sources/global3.php';
    $scalars = array(null, '', 'a', 'A', 'aa', '10', '2', '0', 0, 2, false, 1.5);
    $M_SORT_KEY = 'name';
    foreach ($scalars as $a) {
        foreach ($scalars as $b) {
            $value = _strlen_sort(array('id' => 1, 'name' => $a), array('id' => 2, 'name' => $b));
            sort_expect(is_int($value), 'Length comparator result remains integer');
            $observations['length'][] = $value;
        }
    }
    $values = array_merge($scalars, array(new SortFixtureText(null), new SortFixtureText(''), new SortFixtureText('a')));
    foreach (array(array('name'), array('!name'), array('group', 'name'), array('group', '!name')) as $keys) {
        $M_SORT_KEY = $keys;
        foreach ($values as $a) {
            foreach ($values as $b) {
                // Native object-vs-number comparisons are outside the string callback contract.
                if (($keys[0] === 'name' || $keys[0] === '!name') && is_object($b) && !is_string($a) && !is_object($a)) {
                    continue;
                }
                $value = _multi_sort(array('name' => $a, 'group' => 'same'), array('name' => $b, 'group' => 'same'));
                sort_expect(is_int($value), 'Map comparator result remains integer');
                $observations['comparisons'][] = $value;
            }
        }
    }
    $fixture = array(
        array('id' => 1, 'name' => 'b', 'group' => 'x'),
        array('id' => 2, 'name' => null, 'group' => 'x'),
        array('id' => 3, 'name' => '', 'group' => 'x'),
        array('id' => 4, 'name' => 'A', 'group' => 'y'),
        array('id' => 5, 'name' => '10', 'group' => 'y'),
        array('id' => 6, 'name' => '2', 'group' => 'y')
    );
    foreach (array(false, true) as $stable) {
        foreach (array('name', '!name', 'group,name', 'group,!name') as $key) {
            foreach (array($fixture, array_combine(array('a', 'b', 'c', 'd', 'e', 'f'), $fixture), array($fixture[0]), array()) as $input) {
                $rows = $input;
                sort_maps_by($rows, $key, $stable);
                sort_expect(count($rows) === count($input), 'Sort retains row count');
                $observations['sorted'][] = array('keys' => array_keys($rows), 'ids' => array_column($rows, 'id'));
            }
        }
    }
    $M_SORT_KEY = 'name';
    sort_expect(_strlen_sort(array('name' => null), array('name' => 'long')) === -1, 'Null length retains empty-text ordering');
    $M_SORT_KEY = array('name');
    sort_expect(_multi_sort(array('name' => 'a'), array('name' => null)) > 0, 'Null comparison retains empty-text ordering');
} finally { restore_error_handler(); }
echo json_encode(array('php' => PHP_VERSION, 'checks' => $checks, 'observations' => $observations, 'diagnostics' => $diagnostics), JSON_THROW_ON_ERROR), "\n";
