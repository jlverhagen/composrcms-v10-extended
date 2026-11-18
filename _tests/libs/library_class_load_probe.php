<?php
/**
 * CLI-only declaration loading from reviewed production-library directories.
 * Usage: php library_class_load_probe.php TRUSTED_SOURCE GROUP
 * Loading declarations does not construct objects or exercise library workflows.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$source = isset($argv[1]) ? realpath($argv[1]) : false;
$group = $argv[2] ?? '';
$groups = array(
    'less' => array('sources_custom/ILess'),
    'sabre' => array('sources_custom/sabredav/lib/Sabre', 'sources_custom/sabredav/vendor/sabre/vobject/lib/Sabre/VObject'),
    'swift' => array('sources_custom/swift_mailer/lib/classes'),
    'spout' => array('sources_custom/spout/src/Spout'),
    'getid3' => array('sources_custom/getid3'),
    'photobucket' => array('sources_custom/photobucket/PBAPI.php', 'sources_custom/photobucket/PBAPI', 'sources_custom/photobucket/OAuth'),
    'facebook' => array('sources_custom/facebook/facebook.php'),
);
if ($argc !== 3 || $source === false || !isset($groups[$group])) {
    fwrite(STDERR, "Usage: php library_class_load_probe.php TRUSTED_SOURCE GROUP\n");
    exit(2);
}
$files = array();
foreach ($groups[$group] as $relative) {
    $path = $source . '/' . $relative;
    if (is_file($path)) {
        $files[$path] = true;
    } elseif (is_dir($path)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $entry) {
            $name = $entry->getPathname();
            if ($entry->isFile() && substr($name, -4) === '.php'
                && !preg_match('#/(?:tests?|examples?|demos?)/#i', substr($name, strlen($source)))) {
                $files[$name] = true;
            }
        }
    } else {
        throw new RuntimeException('Missing library source: ' . $relative);
    }
}
ksort($files);
$symbols = array();
foreach (array_keys($files) as $file) {
    $tokens = token_get_all(file_get_contents($file));
    $namespace = '';
    $length = count($tokens);
    for ($i = 0; $i < $length; $i++) {
        $token = $tokens[$i];
        if (!is_array($token)) {
            continue;
        }
        if ($token[0] === T_NAMESPACE) {
            $namespace = '';
            for ($j = $i + 1; $j < $length; $j++) {
                if ($tokens[$j] === ';') {
                    break;
                }
                if ($tokens[$j] === '{') {
                    throw new RuntimeException('Bracketed namespace requires explicit review: ' . $file);
                }
                if (is_array($tokens[$j]) && in_array($tokens[$j][0], array(T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED), true)) {
                    $namespace .= $tokens[$j][1];
                }
            }
            $i = $j;
        } elseif (in_array($token[0], array(T_CLASS, T_INTERFACE, T_TRAIT), true)) {
            $j = $i + 1;
            while ($j < $length && is_array($tokens[$j]) && in_array($tokens[$j][0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true)) {
                $j++;
            }
            // Anonymous declarations and ::class constants have no following name.
            if ($j >= $length || !is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING) {
                continue;
            }
            $name = ($namespace === '' ? '' : $namespace . '\\') . $tokens[$j][1];
            $key = strtolower($name);
            if (isset($symbols[$key]) && $symbols[$key]['file'] !== $file) {
                throw new RuntimeException('Duplicate declaration requires explicit review: ' . $name);
            }
            $symbols[$key] = array('name' => $name, 'file' => $file, 'kind' => token_name($token[0]));
        }
    }
}
if ($symbols === array()) {
    throw new RuntimeException('No named declarations were found');
}
ksort($symbols);
$loader = function ($name) use ($symbols) {
    $key = strtolower($name);
    if (isset($symbols[$key])) {
        require_once $symbols[$key]['file'];
    }
};
spl_autoload_register($loader);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return true;
});
$loaded = array();
$failures = array();
try {
    if ($group === 'getid3') {
        // Modules deliberately refuse direct inclusion before this entry point.
        require_once $source . '/sources_custom/getid3/getid3.php';
    }
    foreach ($symbols as $symbol) {
        $name = $symbol['name'];
        try {
            $exists = ($symbol['kind'] === 'T_INTERFACE') ? interface_exists($name)
                : (($symbol['kind'] === 'T_TRAIT') ? trait_exists($name) : class_exists($name));
            if (!$exists) {
                throw new RuntimeException('Named declaration not available after autoload');
            }
            $reflection = new ReflectionClass($name);
            $loaded[] = array('name' => $name, 'kind' => $symbol['kind'], 'file' => substr($reflection->getFileName(), strlen($source) + 1));
        } catch (Throwable $error) {
            $failures[] = array('name' => $name, 'error' => get_class($error), 'message' => $error->getMessage());
        }
    }
} finally {
    restore_error_handler();
    spl_autoload_unregister($loader);
}
$inventory = array();
foreach (array_keys($files) as $file) {
    $inventory[substr($file, strlen($source) + 1)] = hash_file('sha256', $file);
}
echo json_encode(array(
    'php' => PHP_VERSION, 'group' => $group, 'php_files' => count($files),
    'discovered' => count($symbols), 'loaded' => count($loaded), 'failures' => $failures,
    'declarations' => $loaded, 'inventory' => $inventory,
    'scope' => 'Declaration loading/reflection only; no constructors or library workflows invoked by the probe'
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), "\n";
exit($failures === array() ? 0 : 1);
