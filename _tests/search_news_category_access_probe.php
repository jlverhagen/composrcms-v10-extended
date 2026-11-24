<?php
// Standalone probe: php _tests/search_news_category_access_probe.php
// This checks the query predicate helper; it is not CMS web acceptance.
require_once dirname(__DIR__) . '/sources/database_search.php';

function expect_news_where($label, $actual, $expected)
{
    if ($actual !== $expected) {
        fwrite(STDERR, $label . ': unexpected predicate: ' . $actual . "\n");
        exit(1);
    }
}

expect_news_where('single grant', search_news_category_access_where('validated=1', array('1' => true)), '(validated=1) AND r.news_category IN (1)');
expect_news_where('multiple grants and OR grouping', search_news_category_access_where('a=1 OR b=1', array('1' => true, '10' => true)), '(a=1 OR b=1) AND r.news_category IN (1,10)');
expect_news_where('no grants', search_news_category_access_where('', array()), '1=0');
expect_news_where('zero category', search_news_category_access_where('', array('0' => true)), 'r.news_category IN (0)');
expect_news_where('unexpected category leaves old path intact', search_news_category_access_where('validated=1', array('1) OR 1=1 --' => true)), 'validated=1');
expect_news_where('noncanonical ID leaves old path intact', search_news_category_access_where('', array('01' => true)), '');
if (class_exists('SQLite3')) {
    $db = new SQLite3(':memory:');
    $db->exec('CREATE TABLE news (id INTEGER, news_category INTEGER, validated INTEGER, title TEXT)');
    $db->exec("INSERT INTO news VALUES (16,10,1,'audit restricted'),(13,1,1,'audit public'),(14,1,0,'audit draft')");
    $where = search_news_category_access_where("validated=1 AND title LIKE '%audit%'", array('1' => true));
    $rows = $db->query('SELECT r.id FROM news r WHERE ' . $where . ' ORDER BY id DESC LIMIT 1');
    $first = $rows->fetchArray(SQLITE3_ASSOC);
    if ($first === false || $first['id'] !== 13 || $rows->fetchArray(SQLITE3_ASSOC) !== false) {
        fwrite(STDERR, "accessible row was not returned before the limit\n");
        exit(1);
    }
    $none = search_news_category_access_where('validated=1', array());
    if ((int)$db->querySingle('SELECT COUNT(*) FROM news r WHERE ' . $none) !== 0) {
        fwrite(STDERR, "no-access predicate returned rows\n");
        exit(1);
    }
    echo "in-memory SQL probe passed (SQLite, not CMS acceptance)\n";
} else {
    echo "in-memory SQL probe skipped: SQLite3 extension unavailable\n";
}
echo "news category predicate probe passed\n";
