<?php
/** CLI-only mysqli-driver probe. Uses only connection-local temporary tables. */
if (PHP_SAPI !== 'cli' || ($argc < 4 || $argc > 5) || (isset($argv[4]) && !in_array($argv[4], array('MyISAM', 'InnoDB'), true)) || !in_array($argv[3], array('basic', 'nullable_bit'), true)) exit(2);
function require_code($name) { if ($name === 'database/shared/mysql') require_once $GLOBALS['probe_source'] . '/sources/database/shared/mysql.php'; }
function get_charset() { return 'utf-8'; }
function get_forum_type() { return 'cns'; }
function fix_bad_unicode($s) { return $s; }
set_error_handler(function ($severity, $message, $file, $line) { if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line); });
require $argv[2];
$GLOBALS['probe_source'] = $argv[1];
$GLOBALS['IN_MINIKERNEL_VERSION'] = false;
require $argv[1] . '/sources/database/mysqli.php';
$driver = new Database_Static_mysqli();
$connection = $driver->db_get_connection(false, $SITE_INFO['db_site'], $SITE_INFO['db_site_host'], $SITE_INFO['db_site_user'], $SITE_INFO['db_site_password']);
foreach (isset($argv[4]) ? array($argv[4]) : array('MyISAM', 'InnoDB') as $engine) {
    if (!mysqli_query($connection[0], 'CREATE TEMPORARY TABLE composr_audit_mysqli (id INT PRIMARY KEY, body VARCHAR(100), flag BIT(1) NULL) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8mb4')) throw new RuntimeException('Temporary table creation failed');
    try {
        if ($argv[3] === 'nullable_bit') {
            $driver->db_query("INSERT INTO composr_audit_mysqli (id, body, flag) VALUES (1, 'nullbit', NULL), (2, 'zero', b'0'), (3, 'one', b'1')", $connection);
            $rows = $driver->db_query('SELECT id, flag FROM composr_audit_mysqli ORDER BY id', $connection);
            if (count($rows) !== 3 || $rows[0]['flag'] !== null || $rows[1]['flag'] !== 0 || $rows[2]['flag'] !== 1) throw new RuntimeException('Nullable bit values mismatch');
            echo $engine . " nullable bit passed\n";
        } else {
            $text = "café Ω O'Reilly";
            $escaped = $driver->db_escape_string($text);
            $driver->db_query("INSERT INTO composr_audit_mysqli (id, body) VALUES (1, '" . $escaped . "'), (2, 'second')", $connection);
            $rows = $driver->db_query('SELECT id, body FROM composr_audit_mysqli ORDER BY id', $connection, 1, 0);
            if (count($rows) !== 1 || $rows[0]['id'] !== 1 || $rows[0]['body'] !== $text) throw new RuntimeException('Read/escape/limit mismatch');
            $failed = $driver->db_query("INSERT INTO composr_audit_mysqli (id, body) VALUES (1, 'duplicate')", $connection, null, null, true);
            if ($failed !== null || count($driver->db_query('SELECT id FROM composr_audit_mysqli', $connection)) !== 2) throw new RuntimeException('Duplicate handling');
            if ($driver->db_query("UPDATE composr_audit_mysqli SET body='changed' WHERE id=2", $connection, null, null, false, true) !== 1) throw new RuntimeException('Update mismatch');
            $driver->db_query('DELETE FROM composr_audit_mysqli WHERE id=1', $connection);
            $rows = $driver->db_query('SELECT id, body FROM composr_audit_mysqli ORDER BY id', $connection);
            if (count($rows) !== 1 || $rows[0]['id'] !== 2 || $rows[0]['body'] !== 'changed') throw new RuntimeException('Delete mismatch');
            echo $engine . " basic wrappers passed\n";
        }
    } finally { mysqli_query($connection[0], 'DROP TEMPORARY TABLE IF EXISTS composr_audit_mysqli'); }
}
