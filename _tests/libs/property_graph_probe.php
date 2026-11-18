<?php
/**
 * CLI-only property/inheritance regressions against a trusted source checkout.
 * Usage: php property_graph_probe.php SOURCE MODE [--baseline]
 * The baseline switch records deprecated diagnostics for before/after comparison.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$source = isset($argv[1]) ? realpath($argv[1]) : false;
$mode = $argv[2] ?? '';
$baseline = ($argv[3] ?? '') === '--baseline';
$modes = array('json-core', 'json-facebook', 'diff', 'less', 'computed-guards', 'trait');
if ($source === false || !is_dir($source . '/sources') || !in_array($mode, $modes, true)
    || $argc < 3 || $argc > 4 || ($argc === 4 && !$baseline)) {
    fwrite(STDERR, "Usage: php property_graph_probe.php SOURCE MODE [--baseline]\n");
    exit(2);
}
$checks = 0;
$diagnostics = array();
$observations = array();
function graph_expect($condition, $message)
{
    global $checks;
    $checks++;
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
set_error_handler(function ($severity, $message, $file, $line) use ($baseline, &$diagnostics) {
    if (!(error_reporting() & $severity)) {
        return true;
    }
    if ($baseline && $severity === E_DEPRECATED) {
        $diagnostics[] = $message;
        return true;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$loader = function ($class) use ($source) {
    $path = null;
    if (strpos($class, 'ILess_') === 0) {
        $path = $source . '/sources_custom/' . str_replace('_', '/', $class) . '.php';
    } elseif (strpos($class, 'Sabre\\') === 0) {
        $path = $source . '/sources_custom/sabredav/lib/' . str_replace('\\', '/', $class) . '.php';
    }
    if ($path !== null && is_file($path)) {
        require_once $path;
    }
};
spl_autoload_register($loader);
try {
    if ($mode === 'json-core' || $mode === 'json-facebook') {
        require_once $source . (($mode === 'json-core') ? '/sources/json_inner.php' : '/sources_custom/facebook/jsonwrapper/JSON/JSON.php');
        $json = new Services_JSON();
        graph_expect($json->use === 0, 'Default JSON flags');
        $data = array('text' => "quote \" and newline\n", 'unicode' => 'é', 'values' => array(null, true, false, 0, 1.5));
        $encoded = $json->encode($data);
        graph_expect(json_decode($encoded, true) === $data, 'JSON encoding retains structure and values');
        $decoded = $json->decode('{"value":1,"empty":null}');
        graph_expect($decoded instanceof stdClass && $decoded->value === 1 && $decoded->empty === null, 'Default JSON object decoding');
        $loose = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
        graph_expect($loose->decode('{"value":1}') === array('value' => 1), 'Constructor JSON flags');
        $json->use = SERVICES_JSON_LOOSE_TYPE;
        graph_expect($json->decode('{"changed":true}') === array('changed' => true), 'Public JSON flag updates');
        if (!$baseline) {
            graph_expect((new ReflectionProperty(Services_JSON::class, 'use'))->isPublic(), 'JSON flags explicitly declared');
        }
        $observations = array('encoded' => $encoded, 'decoded' => $decoded, 'loose' => $loose->decode('{"value":1}'));
    } elseif ($mode === 'diff') {
        require_once $source . '/sources/diff/Diff.php';
        require_once $source . '/sources/diff/Diff/Engine/native.php';
        require_once $source . '/sources/diff/Diff/ThreeWay.php';
        $same = new Text_Diff_ThreeWay_Op(array('old'), array('new'), array('new'));
        graph_expect($same->merged() === array('new') && !$same->isConflict(), 'Equal branches merge');
        $same->final1[] = 'reference';
        graph_expect($same->merged() === array('new', 'reference'), 'Merged cache retains its reference');
        $left = new Text_Diff_ThreeWay_Op(array('old'), array('left'), array('old'));
        graph_expect($left->merged() === array('left'), 'One-sided change');
        $conflict = new Text_Diff_ThreeWay_Op(array('old'), array('left'), array('right'));
        graph_expect($conflict->merged() === false && $conflict->isConflict(), 'Conflicting branches');
        $copy = new Text_Diff_ThreeWay_Op_copy(array('copy'));
        $copy->final1[] = 'shared';
        graph_expect($copy->orig === array('copy', 'shared') && $copy->final2 === $copy->orig, 'Copy operation retains shared references');
        graph_expect($copy->merged() === $copy->orig && !$copy->isConflict(), 'Copy remains conflict-free');
        $builder = new Text_Diff_ThreeWay_BlockBuilder();
        graph_expect($builder->isEmpty() && $builder->finish() === false, 'Empty block builder');
        $builder->input(array('old'));
        $builder->out1(array('left'));
        $builder->out2(array('old'));
        graph_expect(!$builder->isEmpty(), 'Builder accepts input');
        $block = $builder->finish();
        graph_expect($block->merged() === array('left') && $builder->isEmpty(), 'Builder output and reset');
        $clean = new Text_Diff_ThreeWay(array('a', 'b'), array('a', 'B'), array('a', 'b'));
        $merged = $clean->mergedOutput();
        graph_expect($merged === array('a', 'B') && $clean->_conflictingBlocks === 0, 'Complete non-conflicting merge');
        $conflicting = new Text_Diff_ThreeWay(array('a', 'b'), array('a', 'LEFT'), array('a', 'RIGHT'));
        $marked = $conflicting->mergedOutput('ours', 'theirs');
        graph_expect($marked === array('a', '<<<<<<< ours', 'LEFT', '=======', 'RIGHT', '>>>>>>> theirs'), 'Complete conflict markers');
        graph_expect($conflicting->_conflictingBlocks === 1, 'Conflict counter');
        $observations = array('copy' => $copy->orig, 'cached' => $same->merged(), 'block' => $block->merged(), 'merged' => $merged, 'conflict' => $marked);
    } elseif ($mode === 'less') {
        $info = new ILess_FileInfo(array('filename' => 'input.less', 'pluginTag' => 'custom', 'pluginNull' => null));
        graph_expect($info->filename === 'input.less' && $info->pluginTag === 'custom', 'Known and plugin file metadata');
        graph_expect(property_exists($info, 'pluginNull') && !isset($info->pluginNull), 'Null plugin metadata');
        $reference = &$info->pluginTag;
        $reference = 'updated';
        graph_expect($info->pluginTag === 'updated', 'Public plugin metadata references');
        $copy = clone $info;
        graph_expect(get_object_vars($copy) === get_object_vars($info), 'Metadata clone');
        $restored = unserialize(serialize($info), array('allowed_classes' => array('ILess_FileInfo')));
        graph_expect(get_object_vars($restored) === get_object_vars($info), 'Metadata serialization');
        $arguments = new ILess_Visitor_Arguments(array('visitDeeper' => false, 'pluginValue' => array(1, 2), 'nullable' => null));
        graph_expect($arguments->visitDeeper === false && $arguments->pluginValue === array(1, 2), 'Visitor known and plugin arguments');
        graph_expect(array_key_exists('nullable', get_object_vars($arguments)), 'Visitor null metadata');
        $argument_copy = unserialize(serialize($arguments), array('allowed_classes' => array('ILess_Visitor_Arguments')));
        graph_expect(get_object_vars($argument_copy) === get_object_vars($arguments), 'Visitor serialization');
        if (!$baseline) {
            foreach (array(ILess_FileInfo::class, ILess_Visitor_Arguments::class) as $class) {
                graph_expect(count((new ReflectionClass($class))->getAttributes(AllowDynamicProperties::class)) === 1, 'Only intentional property bags opt in');
            }
        }
        $parser = new ILess_Parser();
        $parser->parseString('@width: 2px; .sample { width: @width; }');
        $css = $parser->getCSS();
        graph_expect(strpos($css, '.sample') !== false && strpos($css, '2px') !== false, 'Actual LESS compilation');
        $observations = array('info' => get_object_vars($info), 'arguments' => get_object_vars($arguments), 'css' => $css);
    } elseif ($mode === 'computed-guards') {
        require_once $source . '/sources/diff/Diff/Renderer.php';
        $renderer = new Text_Diff_Renderer(array('leading_context_lines' => 7, 'unknown_setting' => 9));
        graph_expect($renderer->_leading_context_lines === 7 && !property_exists($renderer, '_unknown_setting'), 'Diff renderer guards computed writes');
        $environment = new ILess_Environment(array('compress' => true));
        graph_expect($environment->compress === true, 'LESS known option');
        $invalid = false;
        try {
            new ILess_Environment(array('unknown_setting' => true));
        } catch (InvalidArgumentException $error) {
            $invalid = true;
        }
        graph_expect($invalid, 'LESS rejects unknown options');
        $client = new Sabre\DAV\Client(array('baseUri' => 'https://example.invalid/', 'unknown_setting' => true));
        graph_expect(!property_exists($client, 'unknown_setting'), 'WebDAV client ignores unlisted settings');
        foreach (array('baseUri', 'userName', 'password', 'proxy') as $name) {
            graph_expect((new ReflectionClass(Sabre\DAV\Client::class))->hasProperty($name), 'WebDAV setting is declared: ' . $name);
        }
        require_once $source . '/sources_custom/getid3/getid3.php';
        $id3 = (new ReflectionClass('getID3'))->newInstanceWithoutConstructor();
        graph_expect($id3->setOption(array('option_tag_id3v2' => false, 'unknown_setting' => true)), 'getID3 accepts option array');
        graph_expect($id3->option_tag_id3v2 === false && !property_exists($id3, 'unknown_setting'), 'getID3 guards computed writes');
        require_once $source . '/sources_custom/openid.php';
        $_SERVER['HTTP_HOST'] = 'example.invalid';
        $_SERVER['REQUEST_URI'] = '/login';
        unset($_SERVER['HTTPS']);
        $openid = new LightOpenID();
        $openid->identity = 'example.invalid';
        graph_expect($openid->identity === 'http://example.invalid/', 'OpenID setter uses declared identity state');
        $openid->realm = ' http://example.invalid/ ';
        graph_expect($openid->realm === 'http://example.invalid/', 'OpenID realm uses declared state');
        $observations = array('renderer' => $renderer->getParams(), 'compress' => $environment->compress, 'id3' => $id3->option_tag_id3v2, 'identity' => $openid->identity);
    } elseif ($mode === 'trait') {
        require_once $source . '/sources_custom/spout/tests/Spout/TestUsingResource.php';
        $fixture = new class {
            use \Box\Spout\TestUsingResource;
            public function generated($name) { return $this->getGeneratedResourcePath($name); }
        };
        graph_expect($fixture->generated('example.CSV') === 'tests/resources/generated/csv/example.CSV', 'Trait uses its own declared path state');
        foreach (array('resourcesPath', 'generatedResourcesPath', 'generatedUnwritableResourcesPath') as $name) {
            graph_expect((new ReflectionProperty($fixture, $name))->isPrivate(), 'Trait property is declared: ' . $name);
        }
        $observations = array('generated' => $fixture->generated('example.CSV'));
    }
} finally {
    spl_autoload_unregister($loader);
    restore_error_handler();
}
echo json_encode(array('php' => PHP_VERSION, 'mode' => $mode, 'checks' => $checks, 'observations' => $observations, 'diagnostics' => $diagnostics), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), "\n";
