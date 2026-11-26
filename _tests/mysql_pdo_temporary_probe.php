<?php
/** CLI-only PDO driver probe using disposable session-local database tables. */
if (PHP_SAPI !== 'cli' || $argc !== 3 || !is_file($argv[1] . '/sources/database/mysql_pdo.php') || !is_file($argv[2])) exit(2);
function require_code($name) { if ($name === 'database/shared/mysql') require_once $GLOBALS['probe_source'] . '/sources/database/shared/mysql.php'; }
function get_charset() { return 'utf-8'; }
function get_forum_type() { return 'cns'; }
function fix_bad_unicode($string) { return $string; }
set_error_handler(function ($severity, $message, $file, $line) { if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line); });
require $argv[2];
$GLOBALS['probe_source'] = $argv[1];
$GLOBALS['IN_MINIKERNEL_VERSION'] = false;
require $argv[1] . '/sources/database/mysql_pdo.php';
$driver = new Database_Static_mysql_pdo();
$db = $driver->db_get_connection(false, $SITE_INFO['db_site'], $SITE_INFO['db_site_host'], $SITE_INFO['db_site_user'], $SITE_INFO['db_site_password']);
if (!$db instanceof PDO) throw new RuntimeException('PDO connection unavailable');
if ($db !== $driver->db_get_connection(false, $SITE_INFO['db_site'], $SITE_INFO['db_site_host'], $SITE_INFO['db_site_user'], $SITE_INFO['db_site_password'])) throw new RuntimeException('Connection cache mismatch');
foreach (array('MyISAM', 'InnoDB') as $engine) {
    $db->exec('CREATE TEMPORARY TABLE composr_audit_pdo (id INT PRIMARY KEY, body VARCHAR(100) NOT NULL) ENGINE=' . $engine . ' DEFAULT CHARSET=utf8mb4');
    try {
        $stmt = $db->prepare('INSERT INTO composr_audit_pdo (id, body) VALUES (?, ?)');
        $stmt->execute(array(1, 'café Ω'));
        if ($db->query('SELECT body FROM composr_audit_pdo WHERE id=1')->fetchColumn() !== 'café Ω') throw new RuntimeException('Unicode mismatch');
        $failed = false;
        try { $stmt->execute(array(1, 'duplicate')); } catch (PDOException $e) { $failed = true; }
        if (!$failed || (int)$db->query('SELECT COUNT(*) FROM composr_audit_pdo')->fetchColumn() !== 1) throw new RuntimeException('Duplicate handling');
        if ($db->exec("UPDATE composr_audit_pdo SET body='updated' WHERE id=1") !== 1) throw new RuntimeException('Update count');
        if ($db->query('SELECT body FROM composr_audit_pdo WHERE id=1')->fetchColumn() !== 'updated') throw new RuntimeException('Update mismatch');
        if ($db->exec('DELETE FROM composr_audit_pdo WHERE id=1') !== 1 || (int)$db->query('SELECT COUNT(*) FROM composr_audit_pdo')->fetchColumn() !== 0) throw new RuntimeException('Delete mismatch');
        echo $engine . " temporary CRUD and duplicate rejection passed\n";
        $text = "café Ω O'Reilly";
        $escaped = $driver->db_escape_string($text);
        $driver->db_query("INSERT INTO composr_audit_pdo (id, body) VALUES (1, '" . $escaped . "')", $db);
        $driver->db_query("INSERT INTO composr_audit_pdo (id, body) VALUES (2, 'second')", $db);
        $rows = $driver->db_query('SELECT id, body FROM composr_audit_pdo ORDER BY id', $db, 1, 0);
        if (count($rows) !== 1 || $rows[0]['id'] !== 1 || $rows[0]['body'] !== $text) throw new RuntimeException('Wrapper read/escape/limit mismatch');
        $failed = $driver->db_query("INSERT INTO composr_audit_pdo (id, body) VALUES (1, 'duplicate')", $db, null, null, true);
        $rows = $driver->db_query('SELECT id, body FROM composr_audit_pdo ORDER BY id', $db);
        if ($failed !== null || count($rows) !== 2 || $rows[0]['body'] !== $text) throw new RuntimeException('Wrapper duplicate handling');
        if ($driver->db_query("UPDATE composr_audit_pdo SET body='changed' WHERE id=2", $db, null, null, false, true) !== 1) throw new RuntimeException('Wrapper update count');
        $driver->db_query('DELETE FROM composr_audit_pdo WHERE id=1', $db);
        $rows = $driver->db_query('SELECT id, body FROM composr_audit_pdo ORDER BY id', $db);
        if (count($rows) !== 1 || $rows[0]['id'] !== 2 || $rows[0]['body'] !== 'changed') throw new RuntimeException('Wrapper update/delete mismatch');
        echo $engine . " driver wrapper CRUD, limit, escaping and duplicate rejection passed\n";
    } finally { $db->exec('DROP TEMPORARY TABLE IF EXISTS composr_audit_pdo'); }
}
