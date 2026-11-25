<?php
// Standalone integration probe with SQLite and stubbed CMS dependencies.
// Run with PHP CLI and SQLite3 enabled; not a substitute for web or MySQL testing.
if (!class_exists('SQLite3')) { fwrite(STDERR, "SQLite3 extension required\n"); exit(77); }
require getenv('SEARCH_SOURCE') ?: dirname(__DIR__) . '/sources/database_search.php';
define('MAXIMUM_RESULT_COUNT_POINT',1000);
function multi_lang_content(){return false;}
function get_db_type(){return 'mysql';}
function get_member(){return 7;}
function _get_where_clause_groups($m){return 'group_id=1';}
function db_string_equal_to($a,$b){return $a."='".str_replace("'","''",$b)."'";}
function db_cast($v,$t){return "CAST($v AS $t)";}
function can_arbitrary_groupby(){return false;}
function db_has_subqueries($x){return true;}
function get_param_integer($x,$d=0){return $d;}
function cms_profile_start_for($x){}
function cms_profile_end_for($x,$y=null){}
function require_code($module){}
function is_under_radar($s){return false;}
function db_has_full_text($x){return false;}
function db_encode_like($s){return str_replace("'","''",$s);}
function list_to_map($key,$rows){$r=array();foreach($rows as $row){$r[$row[$key]]=$row;}return $r;}
class Forum {public $admin=false;function is_super_admin($m){return $this->admin;}}
class FakeStatic {function apply_sql_limit_clause(&$sql,$limit){$sql.=' LIMIT '.(int)$limit;}}
class FakeDB {
    public $db,$dedupe_mode=false,$connection_read=null,$queries=array(),$static_ob,$fail_keyword_count=false;
    function __construct(){
        $this->static_ob=new FakeStatic();
        $this->db=new SQLite3(':memory:');
        foreach(array(
            'CREATE TABLE cms_news (id INTEGER, news_category INTEGER, validated INTEGER, title TEXT, date_and_time INTEGER)',
            'CREATE TABLE cms_seo_meta_keywords (meta_for_id TEXT, meta_for_type TEXT, meta_keyword TEXT)',
            'CREATE TABLE cms_group_category_access (group_id INTEGER, module_the_name TEXT, category_name TEXT)',
            'CREATE TABLE cms_member_category_access (member_id INTEGER, active_until INTEGER, module_the_name TEXT, category_name TEXT)',
            "INSERT INTO cms_news VALUES (16,10,1,'audit restricted',20),(13,1,1,'audit public',10),(14,1,0,'audit draft',5)",
            "INSERT INTO cms_seo_meta_keywords VALUES ('16','news','audit'),('13','news','audit'),('14','news','audit')",
            "INSERT INTO cms_group_category_access VALUES (1,'news','1')"
        ) as $sql){if(!$this->db->exec($sql))throw new Exception($this->db->lastErrorMsg());}
    }
    function get_table_prefix(){return 'cms_';}
    function query_select($table,$fields,$where){return array();}
    function query($sql,$limit=null,$start=null,$fail_ok=false,$quiet=false){
        $this->queries[]=$sql;
        if(strpos($sql,'SET SESSION')===0)return array();
        if($limit!==null)$sql.=' LIMIT '.(int)$limit;
        $rows=$this->db->query($sql);if($rows===false)throw new Exception($this->db->lastErrorMsg().' SQL: '.$sql);
        $out=array();while($r=$rows->fetchArray(SQLITE3_ASSOC))$out[]=$r;return $out;
    }
    function query_value_if_there($sql,$fail_ok=false){
        if (preg_match('/\\bunion\\b/i', $sql)) throw new Exception('Count query contains UNION');
        if ($this->fail_keyword_count && strpos($sql, 'search_keyword_only_ids') !== false) return null;
        if (substr($sql,0,1)==='(' && substr($sql,-1)===')') $sql=substr($sql,1,-1);
        $r=$this->query($sql);return reset($r[0]);
    }
}
$GLOBALS['SITE_DB']=new FakeDB();
$GLOBALS['FORUM_DRIVER']=new Forum();
$GLOBALS['IS_ACTUALLY_ADMIN']=false;
$GLOBALS['TOTAL_SEARCH_RESULTS']=0;
$order='r.date_and_time';
$rows=get_search_rows('news','id','audit',true,'AND',false,'DESC',1,0,false,'news r',array('r.title'=>'SHORT_TRANS'),'r.validated=1','', $order,'r.*',array(),'news','news_category');
if(count($rows)!==1 || (int)reset($rows)['id']!==13){fwrite(STDERR,'Guest result mismatch: '.json_encode($rows)."\n");exit(1);}
echo 'Guest mixed-access first page returns 13; count='.$GLOBALS['TOTAL_SEARCH_RESULTS']."\n";
$GLOBALS['SITE_DB']->db->exec("INSERT INTO cms_news VALUES (12,1,1,'audit public second',9)");
$GLOBALS['SITE_DB']->db->exec("INSERT INTO cms_seo_meta_keywords VALUES ('12','news','audit')");
foreach(array(0,1) as $offset){
    $GLOBALS['TOTAL_SEARCH_RESULTS']=0;
    $order='r.date_and_time';
    $rows=get_search_rows('news','id','audit',true,'AND',false,'DESC',1,$offset,false,'news r',array('r.title'=>'SHORT_TRANS'),'r.validated=1','', $order,'r.*',array(),'news','news_category');
    if($GLOBALS['TOTAL_SEARCH_RESULTS']!==2){throw new Exception('Overlapping count wrong at offset '.$offset.': '.$GLOBALS['TOTAL_SEARCH_RESULTS']);}
}
echo "Two visible overlapping matches count as two on both pages\n";
$GLOBALS['SITE_DB']->db->exec("INSERT INTO cms_news VALUES (11,1,1,'keyword-only item',8),(10,1,1,'audit content only',7)");
$GLOBALS['SITE_DB']->db->exec("INSERT INTO cms_seo_meta_keywords VALUES ('11','news','audit'),('13','news','audit')");
$GLOBALS['TOTAL_SEARCH_RESULTS']=0;
$order='r.date_and_time';
$rows=get_search_rows('news','id','audit',true,'AND',false,'DESC',1,0,false,'news r',array('r.title'=>'SHORT_TRANS'),'r.validated=1','', $order,'r.*',array(),'news','news_category');
if($GLOBALS['TOTAL_SEARCH_RESULTS']!==4){throw new Exception('Partial overlap or duplicate keyword count failed: '.$GLOBALS['TOTAL_SEARCH_RESULTS']);}
echo "Keyword-only, content-only and duplicate keywords count distinctly\n";
$GLOBALS['SITE_DB']->fail_keyword_count=true;
$GLOBALS['TOTAL_SEARCH_RESULTS']=0;
$order='r.date_and_time';
$rows=get_search_rows('news','id','audit',true,'AND',false,'DESC',1,0,false,'news r',array('r.title'=>'SHORT_TRANS'),'r.validated=1','', $order,'r.*',array(),'news','news_category');
if (count($rows)!==1 || (int)reset($rows)['id']!==13 || $GLOBALS['TOTAL_SEARCH_RESULTS']<4) throw new Exception('Failed count did not fall back');
echo "Failed supplemental count falls back and retains visible rows\n";
$GLOBALS['SITE_DB']->fail_keyword_count=false;
$GLOBALS['SITE_DB']->db->exec("DELETE FROM cms_group_category_access");
$GLOBALS['TOTAL_SEARCH_RESULTS']=0;
$order='r.date_and_time';
$rows=get_search_rows('news','id','audit',true,'AND',false,'DESC',1,0,false,'news r',array('r.title'=>'SHORT_TRANS'),'r.validated=1','', $order,'r.*',array(),'news','news_category');
if(count($rows)!==0 || $GLOBALS['TOTAL_SEARCH_RESULTS']!==0){throw new Exception('Empty grants failed');}
echo "Empty grants return no results\n";
$GLOBALS['SITE_DB']->db->exec("INSERT INTO cms_member_category_access VALUES (7,4102444800,'news','10')");
$order='r.date_and_time';
$rows=get_search_rows('news','id','audit',true,'AND',true,'DESC',1,0,false,'news r',array('r.title'=>'SHORT_TRANS'),'r.validated=1','', $order,'r.*',array(),'news','news_category');
if(count($rows)!==1 || (int)reset($rows)['id']!==16){throw new Exception('Member metadata grant failed');}
echo "Member grant returns category 10 for metadata-only search\n";
$GLOBALS['FORUM_DRIVER']->admin=true;
$order='r.date_and_time';
$rows=get_search_rows('news','id','audit',true,'AND',false,'DESC',1,0,true,'news r',array('r.title'=>'SHORT_TRANS'),'r.validated=1','', $order,'r.*',array(),'news','news_category');
if(count($rows)!==1 || (int)reset($rows)['id']!==16){throw new Exception('Admin title-only search failed');}
echo "Admin title-only search is not restricted\n";
$GLOBALS['FORUM_DRIVER']->admin=false;
$GLOBALS['SITE_DB']->db->exec('DELETE FROM cms_news');
$GLOBALS['SITE_DB']->db->exec('DELETE FROM cms_seo_meta_keywords');
$GLOBALS['SITE_DB']->db->exec('DELETE FROM cms_member_category_access');
$GLOBALS['SITE_DB']->db->exec("INSERT INTO cms_group_category_access VALUES (1,'news','1')");
$GLOBALS['SITE_DB']->db->exec('BEGIN');
for ($i=1; $i<=2100; $i++) {
    $GLOBALS['SITE_DB']->db->exec("INSERT INTO cms_news VALUES ($i,1,1,'audit fixture',$i)");
    $GLOBALS['SITE_DB']->db->exec("INSERT INTO cms_seo_meta_keywords VALUES ('$i','news','audit')");
}
$GLOBALS['SITE_DB']->db->exec('COMMIT');
$GLOBALS['TOTAL_SEARCH_RESULTS']=0;
$order='r.date_and_time';
$rows=get_search_rows('news','id','audit',true,'AND',false,'DESC',1,0,false,'news r',array('r.title'=>'SHORT_TRANS'),'r.validated=1','', $order,'r.*',array(),'news','news_category');
if ($GLOBALS['TOTAL_SEARCH_RESULTS']!==2000) throw new Exception('Capped count changed: '.$GLOBALS['TOTAL_SEARCH_RESULTS']);
echo "Capped count is approximate: 2000 reported for 2100 matching records\n";
