<?php
/**
 * Regression checks for the locale-aware, non-deprecated date formatter.
 *
 * @license http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @package testing_platform
 */
class temporal_format_compatibility_test_set extends cms_test_case
{
    public function testLinuxDateFormatting()
    {
        if (PHP_OS_FAMILY !== 'Linux' || !function_exists('nl_langinfo')) {
            return; // Other platforms still use the legacy fallback.
        }
        require_code('temporal');
        $saved_locale = setlocale(LC_TIME, 0);
        $saved_timezone = date_default_timezone_get();
        $saved_filters = $GLOBALS['LOCALE_FILTER_CACHE'];
        $GLOBALS['LOCALE_FILTER_CACHE'] = array();
        set_error_handler(function ($severity, $message, $file, $line) {
            if (error_reporting() & $severity) {
                throw new ErrorException($message, 0, $severity, $file, $line);
            }
            return true;
        });
        try {
            $formats = array('%a', '%A', '%b', '%B', '%d', '%D', '%e', '%F', '%g', '%G', '%h', '%H', '%I', '%j', '%l', '%m', '%M', '%n', '%o', '%p', '%R', '%S', '%t', '%T', '%u', '%U', '%V', '%w', '%W', '%y', '%Y', '%z', '%Z', '%%', '%e%o %B %Y', '%a %e%o %B %Y', '%l:%M %p', '%Y-%m-%d %H:%M:%S %z', '%%Y %%B %Y');
            $timestamps = array(-315619200, 0, 951782400, 2147483648, 4107542400, 1772951400, 1772955000, 1793511000, 1793514600);
            for ($year = 2015; $year <= 2027; $year++) {
                foreach (array('01-01', '01-04', '12-28', '12-31') as $day) {
                    $timestamps[] = strtotime($year . '-' . $day . ' 12:34:56 UTC');
                }
            }
            foreach (array('C', 'en_US.utf8', 'fr_FR.utf8', 'de_DE.utf8', 'tr_TR.utf8', 'ja_JP.utf8', 'ar_EG.utf8') as $locale) {
                if (setlocale(LC_TIME, $locale) === false) {
                    continue;
                }
                foreach (array('UTC', 'America/Chicago', 'Europe/Paris', 'Asia/Kathmandu', 'Australia/Lord_Howe') as $timezone) {
                    date_default_timezone_set($timezone);
                    foreach ($timestamps as $timestamp) {
                        foreach ($formats as $format) {
                            // Suppress only the known deprecation in the reference implementation.
                            $legacy_format = str_replace(array('%e', '%l', '%o'), array('%-d', '%-I', date('S', $timestamp)), $format);
                            $expected = trim(@strftime($legacy_format, $timestamp));
                            $actual = cms_strftime($format, $timestamp);
                            $this->assertTrue($actual === $expected, $locale . ' / ' . $timezone . ' / ' . $timestamp . ' / ' . $format . ': ' . var_export(array($expected, $actual), true));
                        }
                    }
                }
            }
            date_default_timezone_set('UTC');
            setlocale(LC_TIME, 'C');
            $this->assertTrue(cms_strftime('%%o %%e %%l', 0) === '%o %e %l', 'Escaped directives must stay literal');
            $GLOBALS['LOCALE_FILTER_CACHE'] = array(array('January', 'Custom month'));
            $this->assertTrue(cms_strftime('%B %Y', 0) === 'Custom month 1970', 'Language-pack substitutions must still apply');
        } finally {
            restore_error_handler();
            setlocale(LC_TIME, $saved_locale);
            date_default_timezone_set($saved_timezone);
            $GLOBALS['LOCALE_FILTER_CACHE'] = $saved_filters;
        }
    }
}
