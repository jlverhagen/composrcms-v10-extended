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
        $saved_ctype = setlocale(LC_CTYPE, 0);
        setlocale(LC_CTYPE, 'C');
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
            $formats = array('%c', '%x', '%X', '%r', '%C', '%k', '%a', '%A', '%b', '%B', '%d', '%D', '%e', '%F', '%g', '%G', '%h', '%H', '%I', '%j', '%l', '%m', '%M', '%n', '%o', '%p', '%R', '%S', '%t', '%T', '%u', '%U', '%V', '%w', '%W', '%y', '%Y', '%z', '%Z', '%%', '%e%o %B %Y', '%a %e%o %B %Y', '%l:%M %p', '%Y-%m-%d %H:%M:%S %z', '%%Y %%B %Y');
            foreach (array('C', 'd', 'e', 'H', 'I', 'j', 'k', 'l', 'm', 'M', 'S', 'u', 'U', 'V', 'w', 'W', 'y', 'Y') as $directive) {
                foreach (array('-', '_', '0', '3', '03', '_3', '-3') as $modifier) {
                    $formats[] = '%' . $modifier . $directive;
                }
            }
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
                $locale_formats = $formats;
                foreach (array('a' => array(ABDAY_1, ABDAY_2, ABDAY_3, ABDAY_4, ABDAY_5, ABDAY_6, ABDAY_7), 'A' => array(DAY_1, DAY_2, DAY_3, DAY_4, DAY_5, DAY_6, DAY_7), 'b' => array(ABMON_1, ABMON_2, ABMON_3, ABMON_4, ABMON_5, ABMON_6, ABMON_7, ABMON_8, ABMON_9, ABMON_10, ABMON_11, ABMON_12), 'B' => array(MON_1, MON_2, MON_3, MON_4, MON_5, MON_6, MON_7, MON_8, MON_9, MON_10, MON_11, MON_12), 'p' => array(AM_STR, PM_STR)) as $directive => $items) {
                    $ascii = true;
                    foreach ($items as $item) {
                        if (preg_match('#[^\x00-\x7f]#', nl_langinfo($item))) {
                            $ascii = false;
                        }
                    }
                    if ($ascii) {
                        $locale_formats[] = '%^' . $directive;
                        if ($directive === 'p') {
                            $locale_formats[] = '%P';
                            $locale_formats[] = '%^P';
                        }
                        if ($directive === 'b') {
                            $locale_formats[] = '%^h';
                        }
                    }
                }
                $locale_formats[] = '%^Z';
                foreach (array('E' => array(ERA, 'cCxXyY'), 'O' => array(ALT_DIGITS, 'deHImMSuUVwWy')) as $modifier => $settings) {
                    foreach (str_split($settings[1]) as $directive) {
                        $format = '%' . $modifier . $directive;
                        if (nl_langinfo($settings[0]) === '') {
                            $locale_formats[] = $format;
                        } else {
                            $this->assertTrue(_cms_strftime_linux($format, 0) === null, 'Locale-specific modifier must keep the fallback: ' . $locale . ' / ' . $format);
                        }
                    }
                }
                foreach (array('UTC', 'America/Chicago', 'Europe/Paris', 'Asia/Kathmandu', 'Australia/Lord_Howe') as $timezone) {
                    date_default_timezone_set($timezone);
                    foreach ($timestamps as $timestamp) {
                        foreach ($locale_formats as $format) {
                            // Suppress only the known deprecation in the reference implementation.
                            $legacy_format = str_replace(array('%e', '%l', '%o'), array('%-d', '%-I', date('S', $timestamp)), $format);
                            $expected = trim(@strftime($legacy_format, $timestamp));
                            $raw = _cms_strftime_linux($format, $timestamp);
                            $this->assertTrue($raw !== null, 'Expected native replacement for ' . $locale . ' / ' . $format);
                            $actual = cms_strftime($format, $timestamp);
                            $this->assertTrue($actual === $expected, $locale . ' / ' . $timezone . ' / ' . $timestamp . ' / ' . $format . ': ' . var_export(array($expected, $actual), true));
                        }
                    }
                }
            }
            $this->assertTrue(_cms_strftime_linux('%Q', 0) === null, 'Unknown directives must use the fallback');
            $this->assertTrue(_cms_strftime_linux('%_^B', 0) === null, 'Combined case modifiers must keep the fallback');
            $this->assertTrue(_cms_strftime_linux('%c', 0, 9) === null, 'Composite recursion must be bounded');
            $this->assertTrue(_cms_strftime_linux('%%c', 0) === '%c', 'Escaped composite must stay literal');
            date_default_timezone_set('UTC');
            setlocale(LC_TIME, 'C');
            $this->assertTrue(cms_strftime('%%P %%^B %P %^B', 0) === '%P %^B am JANUARY', 'Escaped case directives stay literal');
            if (setlocale(LC_CTYPE, 'C.utf8', 'C.UTF-8') !== false) {
                $this->assertTrue(_cms_strftime_linux('%^B', 0) === 'JANUARY', 'C UTF-8 locale retains ASCII month casing');
                setlocale(LC_CTYPE, 'C');
            }
            $this->assertTrue(cms_strftime('%%o %%e %%l', 0) === '%o %e %l', 'Escaped directives must stay literal');
            $GLOBALS['LOCALE_FILTER_CACHE'] = array(array('January', 'Custom month'));
            $this->assertTrue(cms_strftime('%B %Y', 0) === 'Custom month 1970', 'Language-pack substitutions must still apply');
        } finally {
            restore_error_handler();
            setlocale(LC_TIME, $saved_locale);
            setlocale(LC_CTYPE, $saved_ctype);
            date_default_timezone_set($saved_timezone);
            $GLOBALS['LOCALE_FILTER_CACHE'] = $saved_filters;
        }
    }
}
