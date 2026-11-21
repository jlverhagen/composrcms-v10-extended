<?php
/** CLI-only Linux C UTF-8 ASCII date-case regression. No CMS bootstrap. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$source = isset($argv[1]) ? realpath($argv[1]) : false;
if ($argc !== 3 || !$source || PHP_OS_FAMILY !== 'Linux' || !function_exists('nl_langinfo')) {
    fwrite(STDERR, "Usage: php date_ascii_case_probe.php SOURCE LOCALES_JSON\n"); exit(2);
}
require $source . '/sources/temporal.php';
$locales = json_decode(file_get_contents($argv[2]), true, 512, JSON_THROW_ON_ERROR);
$formats = array('%^a', '%^A', '%^b', '%^B', '%^h', '%^p', '%P', '%^P', '%^Z');
$timezones = array('UTC', 'America/Chicago', 'Europe/Paris', 'Asia/Kathmandu', 'Australia/Lord_Howe');
$timestamps = array(-315619200, 0, 951782400, 2147483648, 4107542400, 1772951400, 1772955000, 1793511000, 1793514600);
$saved_time = setlocale(LC_TIME, 0); $saved_ctype = setlocale(LC_CTYPE, 0);
$saved_zone = date_default_timezone_get(); $saved_reporting = error_reporting(-1);
$GLOBALS['LOCALE_FILTER_CACHE'] = array();
$checks = 0; $accepted = 0; $fallback = 0; $tested = array(); $ctypes = array();
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) { throw new ErrorException($message, 0, $severity, $file, $line); }
    return true;
});
try {
    foreach (array('C.utf8', 'C.UTF-8', 'C.UTF8') as $ctype) {
        if (setlocale(LC_CTYPE, $ctype) === false) { continue; }
        $ctypes[] = $ctype;
        foreach ($locales as $locale) {
            if (setlocale(LC_TIME, $locale) === false) { continue; }
            $tested[$locale] = true;
            foreach ($timezones as $timezone) {
                date_default_timezone_set($timezone);
                foreach ($timestamps as $timestamp) {
                    foreach ($formats as $format) {
                        $checks++;
                        $old = error_reporting(E_ALL & ~E_DEPRECATED);
                        try { $reference = strftime($format, $timestamp); }
                        finally { error_reporting($old); }
                        if ($reference === false) { $reference = ''; } // libc may produce empty AM/PM text.
                        $raw = _cms_strftime_linux($format, $timestamp);
                        if (preg_match('#[^\x00-\x7f]#', $reference)) {
                            if ($raw !== null) { throw new RuntimeException('Non-ASCII case must retain fallback'); }
                            $fallback++;
                        } else {
                            if ($raw !== $reference || cms_strftime($format, $timestamp) !== trim($reference)) {
                                throw new RuntimeException(json_encode(array($ctype, $locale, $timezone, $timestamp, $format, $raw, $reference)));
                            }
                            $accepted++;
                        }
                    }
                }
            }
        }
    }
    if (!$ctypes || !$accepted || !$fallback) { throw new RuntimeException('Required positive/fallback coverage absent'); }
    setlocale(LC_TIME, 'C'); date_default_timezone_set('UTC');
    if (_cms_strftime_linux('%%P %%^B %P %^B', 0) !== '%P %^B am JANUARY') {
        throw new RuntimeException('Percent escape behavior changed');
    }
    if (setlocale(LC_CTYPE, 'tr_TR.utf8') !== false && _cms_strftime_linux('%^B', 0) !== null) {
        throw new RuntimeException('Unverified locale must retain fallback');
    }
} finally {
    restore_error_handler(); error_reporting($saved_reporting);
    setlocale(LC_TIME, $saved_time); setlocale(LC_CTYPE, $saved_ctype); date_default_timezone_set($saved_zone);
}
echo json_encode(array('php' => PHP_VERSION, 'checks' => $checks, 'native_matches' => $accepted,
    'non_ascii_fallbacks' => $fallback, 'ctype_aliases' => $ctypes, 'time_locales' => count($tested),
    'timezones' => $timezones, 'timestamps' => $timestamps), JSON_THROW_ON_ERROR), "\n";
