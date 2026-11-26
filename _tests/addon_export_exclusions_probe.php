<?php
if ($argc !== 3 || PHP_SAPI !== 'cli') exit(2);
define('I_UNDERSTAND_SQL_INJECTION', 1); define('I_UNDERSTAND_XSS', 2); define('I_UNDERSTAND_PATH_INJECTION', 4);
function i_solemnly_declare($x) {} function restrictify() {} function safe_ini_set($a,$b) {}
function get_screen_title($a,$b) { return new class { function evaluate_echo() {} }; }
function get_custom_file_base() { return $GLOBALS['probe_root']; }
function get_file_base() { return sys_get_temp_dir(); }
function escape_html($s) { return $s; } function require_code($s) {}
function php_function_allowed($s) { return false; } function disable_php_memory_limit() {}
function get_param_string($name,$default=null) { return $name === 'only' && $GLOBALS['probe_only'] !== 'ALL' ? $GLOBALS['probe_only'] : $default; }
function get_param_integer($name,$default=null) { return $default; }
function find_all_hooks($a,$b) { return array('fixture'=>'sources_custom','excel_support'=>'sources_custom','composr_homesite_support_credits'=>'sources_custom'); }
function read_addon_info($name) {
    if ($name !== 'fixture') throw new RuntimeException('Excluded hook executed');
    return array('files'=>array(),'version'=>10,'name'=>'fixture','incompatibilities'=>array(),'dependencies'=>array(),'author'=>'a','organisation'=>'o','category'=>'c','copyright_attribution'=>array(),'licence'=>'l','description'=>'d');
}
function get_version_branch($v) { return '10.x'; }
function attach_message($x,$y) {} function comcode_to_tempcode($x) { return $x; }
function create_addon(...$args) { echo 'EXPORTED_FIXTURE'; }
function generate_addon_description($x) { return ''; }
$GLOBALS['probe_root']=sys_get_temp_dir().'/composr-export-probe-'.bin2hex(random_bytes(8));
$GLOBALS['probe_only']=$argv[2]; ob_start(); require $argv[1]; $out=ob_get_clean();
$expected=$argv[2]==='ALL' || $argv[2]==='fixture';
if ((strpos($out,'EXPORTED_FIXTURE')!==false)!==$expected) throw new RuntimeException('Unexpected export selection');
echo 'CORE_EXPORT_PASS ', $argv[2], "\n";
