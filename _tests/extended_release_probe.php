<?php
/** Isolated CLI test of Extended release selection; no network or CMS startup. */
if (PHP_SAPI !== 'cli' || $argc !== 2 || !is_file($argv[1] . '/sources/version2.php')) exit(2);
function require_code($name) {}
function extended_distribution_version() { return $GLOBALS['current']; }
function cache_and_carry($name, $args, $ttl) { $GLOBALS['requested'] = $args[0]; return array($GLOBALS['payload']); }
set_error_handler(function ($severity, $message, $file, $line) { if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line); });
require $argv[1] . '/sources/version2.php';
$SITE_INFO = array();
function release_fixture($tag, $prerelease = false, $draft = false, $assets = true) {
    $version = ltrim($tag, 'v');
    return array('tag_name' => $tag, 'prerelease' => $prerelease, 'draft' => $draft,
        'assets' => $assets ? array(array('state' => 'uploaded', 'name' => 'composr_upgrader-' . $version . '.cms'), array('state' => 'uploaded', 'name' => 'SHA256SUMS')) : array());
}
function expect_release($label, $current, $data, $expected) {
    $GLOBALS['current'] = $current;
    $GLOBALS['payload'] = is_string($data) ? $data : json_encode($data, JSON_THROW_ON_ERROR);
    $result = get_extended_release_update();
    if ($expected === null || $expected === '') {
        if ($result !== ($expected === null ? null : array())) throw new RuntimeException($label);
    } else {
        if (($result['version'] ?? null) !== $expected) throw new RuntimeException($label);
        if (strpos($result['upgrader_url'], '/composr_upgrader-' . $expected . '.cms') === false) throw new RuntimeException($label . ' URL');
    }
    echo $label . " passed\n";
}
expect_release('stable ignores prerelease and drafts', '2026.11.1', array(release_fixture('2026.12.1-alpha.1', true), release_fixture('2027.01.1', false, true), release_fixture('2026.12.1')), '2026.12.1');
expect_release('prerelease ordering', '2026.11.1-alpha.23', array(release_fixture('v2026.11.1-alpha.24', true), release_fixture('2026.11.1-alpha.25', true), release_fixture('2026.11.1-alpha.26', true, false, false)), '2026.11.1-alpha.25');
expect_release('stable newer than prerelease', '2026.11.1-alpha.23', array(release_fixture('2026.11.1'), release_fixture('2026.11.1-rc.1', true)), '2026.11.1');
expect_release('no newer release', '2026.11.1', array(release_fixture('2026.11.1'), release_fixture('2026.10.9')), '');
expect_release('invalid JSON', '2026.11.1', '{broken', null);
expect_release('unexpected object', '2026.11.1', '{"message":"failed"}', null);
expect_release('malformed records ignored', '2026.11.1', array(null, 1, array('tag_name' => array()), array('draft' => false, 'tag_name' => '2027.01.1', 'assets' => array(null, 1))), '');
$SITE_INFO['extended_release_repository'] = '../wrong';
expect_release('invalid repository rejected', '2026.11.1', array(), null);
