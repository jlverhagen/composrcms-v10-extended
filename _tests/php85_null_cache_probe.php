<?php
/** CLI-only regression for PHP 8.5 null array-key deprecations. */
if (PHP_SAPI !== 'cli' || $argc !== 3 || !in_array($argv[2], array('theme', 'forum'), true)) exit(2);
set_error_handler(function ($severity, $message, $file, $line) { if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line); });
if ($argv[2] === 'forum') {
    require $argv[1] . '/sources/forum_stub.php';
    class ProbeForum extends Forum_driver_base {
        public $calls = 0;
        public function _get_usergroup_list($a, $b, $c, $d, $member, $e) { ++$this->calls; return array('member' => $member); }
    }
    $GLOBALS['USERGROUP_LIST_CACHE'] = null;
    $f = new ProbeForum();
    if ($f->get_usergroup_list()['member'] !== null || $f->get_usergroup_list()['member'] !== null || $f->calls !== 1) throw new RuntimeException('Null member cache mismatch');
    if ($f->get_usergroup_list(false, false, false, null, 7)['member'] !== 7 || $f->get_usergroup_list(false, false, false, null, 7)['member'] !== 7 || $f->calls !== 2) throw new RuntimeException('Explicit member cache mismatch');
    echo "forum cache passed\n";
    exit;
}
function get_custom_file_base() { return sys_get_temp_dir() . '/composr-audit-no-such-directory'; }
function get_file_base() { return get_custom_file_base(); }
function user_lang() { return 'EN'; }
function fallback_lang() { return 'EN'; }
function url_is_local($url) { return true; }
function support_smart_decaching() { return true; }
class ProbeThemeDriver { function get_theme() { return 'custom'; } }
class ProbeThemeDb {
    public $deleted = 0;
    function query_select_value_if_there($a, $b, $c) { return null; }
    function query_delete($a, $b, $c, $d) { ++$this->deleted; }
}
require $argv[1] . '/sources/themes.php';
init__themes();
$GLOBALS['DEV_MODE'] = false;
$GLOBALS['SEMI_DEV_MODE'] = true;
$GLOBALS['USER_LANG_CACHED'] = 'EN';
$GLOBALS['SITE_INFO'] = array();
$GLOBALS['SMART_CACHE'] = null;
$GLOBALS['FORUM_DRIVER'] = new ProbeThemeDriver();
$GLOBALS['SITE_DB'] = new ProbeThemeDb();
$GLOBALS['THEME_IMAGES_CACHE'][THEME_IMAGE_PLACE_SITE]['audit_missing'] = 'themes/custom/images/nonexistent.png';
$result = find_theme_image('audit_missing', true, true);
if ($result !== '' || $GLOBALS['SITE_DB']->deleted !== 1) throw new RuntimeException('Missing image fallback mismatch');
echo "theme fallback passed\n";
