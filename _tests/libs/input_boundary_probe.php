<?php
/**
 * CLI-only byte, substring and legacy-password compatibility regressions.
 * Usage: php [-n] input_boundary_probe.php TRUSTED_SOURCE characters|password|callbacks [--baseline]
 * Baseline mode records old warnings/deprecations for output comparison.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$source = isset($argv[1]) ? realpath($argv[1]) : false;
$mode = $argv[2] ?? '';
$baseline = ($argv[3] ?? '') === '--baseline';
if ($source === false || !in_array($mode, array('characters', 'password', 'callbacks'), true)
    || $argc < 3 || $argc > 4 || ($argc === 4 && !$baseline)) {
    fwrite(STDERR, "Usage: php [-n] input_boundary_probe.php SOURCE characters|password|callbacks [--baseline]\n");
    exit(2);
}
$checks = 0;
$diagnostics = array();
$observations = array();
$charset = 'utf-8';
function get_charset() { return $GLOBALS['charset']; }
function get_value($name) { return null; }
function boundary_expect($condition, $message)
{
    $GLOBALS['checks']++;
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
function boundary_record($context, $value)
{
    hash_update($context, pack('N', strlen($value)) . $value);
}
set_error_handler(function ($severity, $message, $file, $line) use ($baseline, &$diagnostics) {
    if (!(error_reporting() & $severity)) {
        return true;
    }
    if ($baseline && in_array($severity, array(E_WARNING, E_DEPRECATED), true)) {
        $diagnostics[$message] = ($diagnostics[$message] ?? 0) + 1;
        return true;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
try {
    if ($mode === 'characters') {
        require_once $source . '/sources/global3.php';
        require_once $source . '/sources/character_sets.php';
        $hash = hash_init('sha256');
        foreach (array_merge(range(-1024, 127), array(PHP_INT_MIN, PHP_INT_MAX, 128, 255, 256, 0x20AC, 0x1F600)) as $value) {
            $result = unichr($value);
            $expected = ($value <= 127) ? chr($value & 255) : '#&' . strval($value) . ';';
            boundary_expect($result === $expected, 'Legacy unichr output: ' . $value);
            boundary_record($hash, $result);
        }
        boundary_expect(unichrm(array('', '65')) === 'A' && unichrm_hex(array('', '41')) === 'A', 'Character replacement callbacks');
        $observations['unichr_sha256'] = hash_final($hash);
        $hash = hash_init('sha256');
        $cases = 0;
        foreach (range(0, 255) as $first) {
            $result = foxy_utf8_to_nce(chr($first));
            boundary_record($hash, $result);
            $cases++;
            foreach (range(0, 255) as $second) {
                boundary_record($hash, foxy_utf8_to_nce(chr($first) . chr($second)));
                $cases++;
            }
        }
        mt_srand(1729);
        for ($case = 0; $case < 2048; $case++) {
            $input = '';
            for ($i = 0, $length = mt_rand(3, 12); $i < $length; $i++) {
                $input .= chr(mt_rand(0, 255));
            }
            boundary_record($hash, foxy_utf8_to_nce($input));
            $cases++;
        }
        $named = array('', 'plain', "\xC3\xA9", "\xE2\x82\xAC", "\xF0\x9F\x98\x80", "\xE2\x82", "\xF0\x9F", "\xF0\x9F\x92", "\xE2\x82A", "\xFF", "\0");
        $named_outputs = array();
        foreach ($named as $input) {
            $output = foxy_utf8_to_nce($input);
            boundary_record($hash, $output);
            $named_outputs[bin2hex($input)] = bin2hex($output);
            $cases++;
        }
        $observations['font_text'] = array('cases' => $cases, 'sha256' => hash_final($hash), 'named_hex' => $named_outputs);
        $charset = 'iso-8859-1';
        $latin = '';
        foreach (range(0, 255) as $byte) {
            $latin .= chr($byte);
        }
        $observations['latin1_font_text_sha256'] = hash('sha256', foxy_utf8_to_nce($latin));
        $charset = 'utf-8';
        boundary_expect(utf8tohtml("A\xC3\xA9\xE2\x82\xAC\xF0\x9F\x98\x80") === 'A&#233;&#8364;&#128512;', 'Existing strict Unicode converter retains valid output');
        boundary_expect(utf8tohtml("\xFF") === '&#65533;', 'Existing strict Unicode converter retains its malformed-byte policy');
        $hash = hash_init('sha256');
        $substring_cases = 0;
        $inputs = array('', 'abc', "A\xC3\xA9B", "A\xE2\x82\xACB", "A\xF0\x9F\x98\x80B", "\0\xFF\0");
        foreach (range(0, 255) as $byte) {
            $inputs[] = 'A' . chr($byte) . 'B';
        }
        foreach ($inputs as $input) {
            foreach (array(-5, -2, -1, 0, 1, 2, 3, 4, 8) as $start) {
                foreach (array(null, -3, -1, 0, 1, 2, 4) as $amount) {
                    $result = cms_mb_substr($input, $start, $amount, true);
                    boundary_record($hash, $result);
                    $substring_cases++;
                }
            }
        }
        boundary_expect(cms_mb_substr('abc', 0, 0, true) === '', 'Zero-length substring remains empty');
        boundary_expect(cms_mb_substr('abc', 99, 1, true) === '', 'Past-end substring remains empty');
        $observations['substring'] = array('cases' => $substring_cases, 'sha256' => hash_final($hash));
    } elseif ($mode === 'password') {
        require_once $source . '/sources/hooks/systems/cns_auth/wordpress.php';
        $salt_input = pack('C*', ...range(0, 15));
        $salts = array();
        foreach (range(4, 31) as $cost) {
            $hasher = new PasswordHash($cost, false);
            $salt = $hasher->gensalt_blowfish($salt_input);
            boundary_expect(strlen($salt) === 29, 'Bcrypt salt length: ' . $cost);
            boundary_expect(substr($salt, 0, 7) === '$2a$' . str_pad(strval($cost), 2, '0', STR_PAD_LEFT) . '$', 'Bcrypt cost digits: ' . $cost);
            $salts[$cost] = $salt;
        }
        $portable = new PasswordHash(8, true);
        $setting = $portable->gensalt_private('123456');
        $portable_hash = $portable->crypt_private('fixture-password', $setting);
        boundary_expect(strlen($portable_hash) === 34, 'Portable hash shape');
        boundary_expect($portable->CheckPassword('fixture-password', $portable_hash), 'Existing portable password verifies');
        boundary_expect(!$portable->CheckPassword('wrong-password', $portable_hash), 'Wrong portable password is rejected');
        boundary_expect(CRYPT_BLOWFISH === 1, 'Bcrypt backend available for deterministic verification fixture');
        $bcrypt = new PasswordHash(4, false);
        $bcrypt_hash = crypt('fixture-password', $bcrypt->gensalt_blowfish($salt_input));
        boundary_expect(strlen($bcrypt_hash) === 60, 'Bcrypt hash shape');
        boundary_expect($bcrypt->CheckPassword('fixture-password', $bcrypt_hash), 'Existing bcrypt password verifies');
        boundary_expect(!$bcrypt->CheckPassword('wrong-password', $bcrypt_hash), 'Wrong bcrypt password is rejected');
        $observations = array('salts' => $salts, 'portable_setting' => $setting, 'portable_fixture_hash' => $portable_hash, 'bcrypt_fixture_hash' => $bcrypt_hash);
    } else {
        require_once $source . '/sources_custom/ILess/DebugInfo.php';
        $escaped = array();
        foreach (array('/fixtures/file.less', 'C:\theme\file.less', 'relative/path(1).less', '', 'file name.less', 'https://example.invalid/a.less') as $filename) {
            $value = ILess_DebugInfo::escapeFilenameForMediaQuery($filename);
            boundary_expect(is_string($value), 'Debug filename callback returns text');
            $escaped[$filename] = $value;
        }
        $debug = new ILess_DebugInfo('/fixtures/file.less', 7);
        $comment = $debug->getAsComment();
        boundary_expect($comment === "/* line 7, /fixtures/file.less */\n", 'Debug comment format');
        $media = $debug->getAsMediaQuery();
        boundary_expect(strpos($media, '\000037') !== false, 'Debug media-query line number');
        ILess_DebugInfo::setMediaQueryFormat('%file% @ %line%');
        $custom = $debug->getAsMediaQuery();
        boundary_expect(substr($custom, -4) === ' @ 7', 'Custom media-query format');
        class BoundaryDebugInfoChild extends ILess_DebugInfo
        {
            protected static function replaceCallback($match) { return 'override'; }
        }
        boundary_expect(BoundaryDebugInfoChild::escapeFilenameForMediaQuery('a.b') === ILess_DebugInfo::escapeFilenameForMediaQuery('a.b'), 'Callback retains declaring-class binding');
        $observations = array('escaped' => $escaped, 'comment' => $comment, 'media' => $media, 'custom' => $custom);
    }
} finally {
    restore_error_handler();
}
ksort($diagnostics);
echo json_encode(array(
    'php' => PHP_VERSION, 'mode' => $mode, 'checks' => $checks,
    'capabilities' => array('mbstring' => function_exists('mb_substr'), 'iconv' => function_exists('iconv_substr')),
    'observations' => $observations, 'diagnostics' => $diagnostics,
), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), "\n";
