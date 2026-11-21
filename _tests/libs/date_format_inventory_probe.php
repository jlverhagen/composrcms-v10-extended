<?php
/** CLI-only shipped date-format comparison against Linux libc. No CMS bootstrap. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$source = isset($argv[1]) ? realpath($argv[1]) : false;
if ($argc !== 3 || !$source || PHP_OS_FAMILY !== 'Linux' || !function_exists('nl_langinfo')) {
    fwrite(STDERR, "Usage: php date_format_inventory_probe.php SOURCE LOCALES_JSON\n"); exit(2);
}
$locales = json_decode(file_get_contents($argv[2]), true, 512, JSON_THROW_ON_ERROR);
require $source . '/sources/temporal.php';
$formats = array('literal:%a' => '%a', 'literal:%b' => '%b', 'literal:%B %Y' => '%B %Y');
$calendar_keys = array('calendar_minute', 'calendar_minute_ampm_known', 'calendar_date', 'calendar_date_verbose',
    'calendar_date_range_single', 'calendar_date_range_single_long', 'calendar_day_of_month',
    'calendar_day_of_month_verbose', 'calendar_month_in_year', 'calendar_month_in_year_verbose');
foreach (array('global', 'calendar') as $file) {
    $section = '';
    foreach (file($source . '/lang/EN/' . $file . '.ini', FILE_IGNORE_NEW_LINES) as $line) {
        if (preg_match('/^\[([^]]+)\]$/', $line, $match)) { $section = $match[1]; continue; }
        if ($section !== 'strings' || strpos($line, '=') === false) { continue; }
        list($key, $value) = explode('=', $line, 2);
        if (($file === 'global' && strpos($key, 'date_') === 0) || in_array($key, $calendar_keys, true)) {
            $formats[$file . ':' . $key] = $value;
        }
    }
}
$timezones = array('UTC', 'America/Chicago', 'Europe/Paris', 'Asia/Kathmandu', 'Australia/Lord_Howe');
$timestamps = array(-315619200, 0, 951782400, 2147483648, 4107542400, 1772951400, 1772955000, 1793511000, 1793514600);
$saved_time = setlocale(LC_TIME, 0); $saved_ctype = setlocale(LC_CTYPE, 0);
$saved_zone = date_default_timezone_get(); $saved_reporting = error_reporting(-1);
$GLOBALS['LOCALE_FILTER_CACHE'] = array();
$checks = 0; $tested = array(); $skipped = array(); $unsupported = array(); $mismatches = array();
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) { throw new ErrorException($message, 0, $severity, $file, $line); }
    return true;
});
try {
    setlocale(LC_CTYPE, 'C');
    foreach ($locales as $locale) {
        if (setlocale(LC_TIME, $locale) === false) { $skipped[] = $locale; continue; }
        $tested[] = $locale;
        foreach ($timezones as $timezone) {
            date_default_timezone_set($timezone);
            foreach ($timestamps as $timestamp) {
                foreach ($formats as $key => $format) {
                    $checks++;
                    $native = _cms_strftime_linux($format, $timestamp);
                    if ($native === null) { $unsupported[] = array($locale, $timezone, $timestamp, $key); continue; }
                    $public = cms_strftime($format, $timestamp);
                    $reference_format = str_replace(array('%e', '%l', '%o'), array('%-d', '%-I', date('S', $timestamp)), $format);
                    // Only the reference implementation may emit its known deprecation.
                    $old = error_reporting(E_ALL & ~E_DEPRECATED);
                    try { $legacy = strftime($reference_format, $timestamp); }
                    finally { error_reporting($old); }
                    $legacy = ($legacy === false) ? '' : trim($legacy);
                    if ($public !== $legacy || trim($native) !== $public) {
                        $mismatches[] = array('locale' => $locale, 'timezone' => $timezone, 'timestamp' => $timestamp,
                            'key' => $key, 'native_hex' => bin2hex($native), 'public_hex' => bin2hex($public), 'legacy_hex' => bin2hex($legacy));
                    }
                }
            }
        }
    }
} finally {
    restore_error_handler(); error_reporting($saved_reporting);
    setlocale(LC_TIME, $saved_time); setlocale(LC_CTYPE, $saved_ctype); date_default_timezone_set($saved_zone);
}
echo json_encode(array('php' => PHP_VERSION, 'formats' => $formats, 'checks' => $checks,
    'locales_tested' => $tested, 'locales_skipped' => $skipped, 'timezones' => $timezones,
    'timestamps' => $timestamps, 'unsupported' => $unsupported, 'mismatches' => $mismatches), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), "\n";
exit(($unsupported || $mismatches) ? 1 : 0);
