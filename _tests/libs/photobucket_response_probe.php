<?php
/**
 * CLI-only response strategy regression without credentials or HTTP requests.
 * Usage: php photobucket_response_probe.php TRUSTED_SOURCE
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$source = isset($argv[1]) ? realpath($argv[1]) : false;
if ($argc !== 2 || $source === false || !is_file($source . '/sources_custom/photobucket/PBAPI.php')) {
    fwrite(STDERR, "Usage: php photobucket_response_probe.php TRUSTED_SOURCE\n");
    exit(2);
}
require_once $source . '/sources_custom/photobucket/PBAPI.php';
$checks = 0;
function response_expect($condition, $message)
{
    global $checks;
    $checks++;
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return true;
});
try {
    require_once $source . '/sources_custom/photobucket/PBAPI/Response/simplexml.php';
    require_once $source . '/sources_custom/photobucket/PBAPI/Response/json.php';
    require_once $source . '/sources_custom/photobucket/PBAPI/Response/phpserialize.php';
    $parser = new PBAPI_Response_simplexml();
    $xml = '<response><status>OK</status><content><item id="1">hello</item></content></response>';
    $full = $parser->parse($xml);
    response_expect($full instanceof SimpleXMLElement && $full->getName() === 'response', 'XML response shape');
    response_expect((string) $full->content->item === 'hello' && (string) $full->content->item['id'] === '1', 'XML content and attributes');
    $content = $parser->parse($xml, true);
    response_expect($content instanceof SimpleXMLElement && $content->getName() === 'content', 'Content-only XML shape');
    response_expect((string) $content->item === 'hello' && $parser->getFormat() === 'xml', 'XML content and format');
    $caught = null;
    try {
        $parser->parse('<response><status>ERROR</status><message>fixture failure</message><code>42</code></response>');
    } catch (PBAPI_Exception_Response $error) {
        $caught = $error;
    }
    response_expect($caught !== null && $caught->getMessage() === 'fixture failure' && $caught->getCode() === 42, 'XML failure retains message and integer code');
    $method = new ReflectionMethod(PBAPI_Response_simplexml::class, 'detectException');
    response_expect($method->invoke($parser, array('status' => 'OK')) === null, 'Override accepts the parent array contract');
    $caught = null;
    try {
        $method->invoke($parser, array('status' => 'ERROR', 'message' => 'array failure', 'code' => 43));
    } catch (PBAPI_Exception_Response $error) {
        $caught = $error;
    }
    response_expect($caught !== null && $caught->getMessage() === 'array failure' && $caught->getCode() === 43, 'Array branch delegates error detection to the parent');
    $rejected = false;
    try {
        $method->invoke($parser, new stdClass());
    } catch (TypeError $error) {
        $rejected = true;
    }
    response_expect($rejected, 'Unsupported response types remain rejected');
    $success = array('status' => 'OK', 'content' => array('name' => 'hello'));
    $failure = array('status' => 'ERROR', 'message' => 'strategy failure', 'code' => 44);
    foreach (array('json' => new PBAPI_Response_json(), 'phpserialize' => new PBAPI_Response_phpserialize()) as $format => $strategy) {
        $encoded = ($format === 'json') ? json_encode($success) : serialize($success);
        response_expect($strategy->parse($encoded) === $success, 'Full array response: ' . $format);
        response_expect($strategy->parse($encoded, true) === $success['content'], 'Content-only response: ' . $format);
        response_expect($strategy->getFormat() === $format, 'Strategy format: ' . $format);
        $caught = null;
        try {
            $strategy->parse(($format === 'json') ? json_encode($failure) : serialize($failure));
        } catch (PBAPI_Exception_Response $error) {
            $caught = $error;
        }
        response_expect($caught !== null && $caught->getMessage() === 'strategy failure' && $caught->getCode() === 44, 'Array strategy error: ' . $format);
    }
} finally {
    restore_error_handler();
}
echo json_encode(array('php' => PHP_VERSION, 'checks' => $checks, 'result' => 'response strategies passed'), JSON_THROW_ON_ERROR), "\n";
