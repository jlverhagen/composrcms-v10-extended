<?php
/** Standalone regression for nullable web-standards parent tags. */
if (PHP_SAPI !== 'cli' || $argc !== 2) {
    exit(2);
}

require $argv[1];
init__webstandards();
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return true;
});

foreach (array(array(null, true), array('body', true), array('absent', false)) as $case) {
    $GLOBALS['PARENT_TAG'] = $case[0];
    $GLOBALS['TEXT_NO_BLOCK'] = array('' => true, 'body' => true);
    $GLOBALS['BLOCK_CONSTRAIN'] = true;
    $GLOBALS['XML_CONSTRAIN'] = false;
    $GLOBALS['WELL_FORMED_ONLY'] = true;
    $GLOBALS['POS'] = 0;
    $GLOBALS['LINENO'] = 0;
    $GLOBALS['LINESTART'] = 0;
    $GLOBALS['OUT'] = 'loose text<br />';
    $GLOBALS['LEN'] = strlen($GLOBALS['OUT']);
    $GLOBALS['WEBSTANDARDS_CHECKER_OFF'] = null;
    $GLOBALS['TAG_STACK'] = array();
    $GLOBALS['TAG_RANGES'] = array();
    $GLOBALS['VALUE_RANGES'] = array();

    $result = _get_next_tag();
    $found = false;
    foreach ($result[1] as $error) {
        if ($error[0] === 'XHTML_TEXT_NO_BLOCK') $found = true;
    }
    if ($found !== $case[1]) throw new RuntimeException('Incorrect loose-text validation');
}
restore_error_handler();
echo "webstandards null-parent probe passed\n";
