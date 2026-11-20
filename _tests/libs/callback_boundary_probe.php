<?php
/** CLI-only callback checks. Usage: php callback_boundary_probe.php SOURCE [--baseline] */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$source = isset($argv[1]) ? realpath($argv[1]) : false;
$baseline = ($argv[2] ?? '') === '--baseline';
if (!$source || $argc < 2 || $argc > 3 || ($argc === 3 && !$baseline)) { exit(2); }
$checks = 0;
$observations = array();
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) { return true; }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
spl_autoload_register(function ($class) use ($source) {
    if (strpos($class, 'ILess_') === 0) {
        require_once $source . '/sources_custom/' . str_replace('_', '/', $class) . '.php';
    } elseif (strpos($class, 'Sabre\\') === 0) {
        require_once $source . '/sources_custom/sabredav/lib/' . str_replace('\\', '/', $class) . '.php';
    }
});
function callback_expect($condition, $message)
{
    $GLOBALS['checks']++;
    if (!$condition) { throw new RuntimeException($message); }
}
function callback_fixture($registry, $value) { return array(get_class($registry), $value); }
class CallbackFixture
{
    public static function staticCall($registry, $value) { return callback_fixture($registry, $value); }
    public function objectCall($registry, $value) { return callback_fixture($registry, $value); }
    public function __invoke($registry, $value) { return callback_fixture($registry, $value); }
}
try {
    $registry = new ILess_FunctionRegistry();
    $object = new CallbackFixture();
    $callbacks = array('callback_fixture', array(CallbackFixture::class, 'staticCall'), array($object, 'objectCall'), $object,
        function ($registry, $value) { return callback_fixture($registry, $value); });
    foreach ($callbacks as $i => $callback) {
        callback_expect($registry->addFunction('Fixture' . $i, $callback, 'Alias' . $i) === $registry, 'Registration remains fluent');
        $result = $registry->call('ALIAS' . $i, array('fixture'));
        callback_expect($result === array('ILess_FunctionRegistry', 'fixture'), 'Case-insensitive alias dispatch with registry argument');
        $observations['registry'][] = $result;
    }
    callback_expect(abs(floatval($registry->call('pi')->value) - pi()) < 0.000000000001, 'Builtin object-method callback');
    foreach (array('missing_callback_fixture', array(CallbackFixture::class, 'objectCall'), null) as $invalid) {
        $caught = null;
        try { $registry->addFunction('bad', $invalid); } catch (InvalidArgumentException $e) { $caught = $e; }
        callback_expect($caught instanceof InvalidArgumentException, 'Invalid callback rejected with intended exception');
    }
    $caught = null;
    try { $registry->addAlias('absent_alias', 'absent_function'); } catch (Throwable $e) { $caught = $e; }
    callback_expect($caught instanceof ($baseline ? ArgumentCountError::class : InvalidArgumentException::class), 'Missing alias target exception');
    if (!$baseline) {
        callback_expect($caught->getMessage() === 'Invalid alias "absent_alias" for "absent_function" given. The "absent_function" does not exist.', 'Complete alias error message');
    }
    $alias_failure = array('class' => get_class($caught), 'message' => $caught->getMessage());
    $info = new ILess_FileInfo();
    $importer = new ILess_Importer_Callback(
        function ($path, $context) use ($info) { callback_expect($context === $info, 'Import context identity'); return 'import:' . $path; },
        function ($path, $context) use ($info) { callback_expect($context === $info, 'Modification context identity'); return 123; }
    );
    callback_expect($importer->import('fixture.less', $info) === 'import:fixture.less', 'Importer callback return');
    callback_expect($importer->getLastModified('fixture.less', $info) === 123, 'Modification callback return');
    $caught = null;
    try { new ILess_Importer_Callback(null, 'missing_callback_fixture'); } catch (InvalidArgumentException $e) { $caught = $e; }
    callback_expect($caught instanceof InvalidArgumentException, 'Invalid importer callback rejected');
    $dom = new DOMDocument();
    $dom->loadXML('<d:root xmlns:d="DAV:"><d:prop><d:resourcetype><d:collection/></d:resourcetype><d:displayname>Fixture</d:displayname><d:empty/></d:prop></d:root>');
    $properties = Sabre\DAV\XMLUtil::parseProperties($dom->documentElement, array('{DAV:}resourcetype' => Sabre\DAV\Property\ResourceType::class));
    callback_expect($properties['{DAV:}resourcetype'] instanceof Sabre\DAV\Property\ResourceType, 'Mapped static WebDAV callback type');
    callback_expect($properties['{DAV:}resourcetype']->is('{DAV:}collection'), 'Mapped WebDAV property contents');
    callback_expect($properties['{DAV:}displayname'] === 'Fixture' && $properties['{DAV:}empty'] === '', 'Unmapped and empty properties preserved');
    $observations['webdav'] = array('collection' => $properties['{DAV:}resourcetype']->is('{DAV:}collection'), 'name' => $properties['{DAV:}displayname'], 'empty' => $properties['{DAV:}empty']);
} finally { restore_error_handler(); }
echo json_encode(array('php' => PHP_VERSION, 'checks' => $checks, 'observations' => $observations, 'alias_failure' => $alias_failure), JSON_THROW_ON_ERROR), "\n";
