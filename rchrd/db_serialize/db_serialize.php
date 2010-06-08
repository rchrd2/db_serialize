<?php
/**
 * This is an experimental method of rapidly creating a CMS. It is not scalable
 * but can work for very small scale sites that need rapid development and 
 * minimal sql such as a simple portfolio site.
 * 
 * Objects are serialized and stored as TEXT in a mysql database.
 * 
 * @author Richard Caceres <rcaceres@ucla.edu>
 * @copyright Richard Caceres, 2010
 * @version 1.1
 * @license MIT
 */ 

///////////////////////////////////////////////////////////////////////////////
/**
 * global variables
 */ 
$db = null;

require_once('variables.php');
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'dbname');
// define('DB_USER', 'root');
// define('DB_PSWD', '');

///////////////////////////////////////////////////////////////////////////////

/**
 * use this to connect to the database
 */
function connect_db() {
    global $db;
    if($db == null) {
        $db = mysql_connect(DB_HOST, DB_USER, DB_PSWD) 
                or die ('Could not connect to DB host');
        mysql_select_db(DB_NAME) or die ('Could not connect to DB');
    }
}
/**
 * use this to connect close the database
 */
function close_db() {
    global $db;
    if ($db !== null) {
        mysql_close($db);
    }
    $db = null;
}
/**
 * use this to insert a new object into the table
 * 
 * @param String $table - the name of the table
 * @param Object $obj - any object to be put in the table
 */
function insert_object($table, $obj) {
    // we have to get the number of rows to generate an id
    //$result = mysql_query("SELECT * FROM `$table`");
    ///$id = mysql_num_rows($result);
    //$obj->uid = $id + 1;
    //serialize the object
    $table = addslashes($table);
    $objstr = base64_encode(serialize($obj));
    $insert_query = "INSERT INTO `$table` (`id`, `obj`) 
            VALUES ('', '".$objstr."');";
    $insert_result = mysql_query($insert_query);
    $obj->uid = mysql_insert_id();
    update_object($table, $obj);
}
/**
 * use this to update an object. make sure it has uid defined
 * 
 * @param String $table - the name of the table
 * @param Object $obj - any object to be put in the table
 */
function update_object($table, $obj) {
    $table = addslashes($table);
    if (!isset($obj->uid)) return false;
    $objstr = base64_encode(serialize($obj));    
    $update_query = "UPDATE `$table` SET `obj` = '".$objstr."' 
            WHERE `id` = {$obj->uid} LIMIT 1;";
    $update_result = mysql_query($update_query);
}
/**
 * use this to delete an object. make sure it has uid defined
 * 
 * @param String $table - the name of the table
 * @param int $uid - any object to be put in the table
 */
function delete_object($table, $uid) {
    $uid = addslashes($uid);
    $sql = "DELETE FROM `$table` WHERE `id` = $uid LIMIT 1;";
    $result = mysql_query($sql);
}
/**
 * use this to retreive an object
 * 
 * @param String $table - the name of the table
 * @param Int $id
 */
function get_object($table, $id=1) {
    $qtable = addslashes($table);
    $qid = addslashes($id);
    $sql = "SELECT * FROM `$qtable` WHERE id = $qid LIMIT 1;";
    $select_result = mysql_query($sql);
    if ($select_result == false) {
        return false;
    }
    $row = mysql_fetch_assoc($select_result);
    if ($row == false) {
        return false;
    } else {
        $obj = unserialize(base64_decode($row['obj']));
        //var_dump($obj);die();
        return $obj;
    }
}
/**
 * use this to get all objects from a table
 * 
 * @param String $table
 */
function get_objects($table) {
    $table = addslashes($table);
    $sql = "SELECT * FROM `$table`";
    $select_result = mysql_query($sql);
    $objs = array();
    while ($row = mysql_fetch_assoc($select_result)) {
        $objs[] = unserialize(base64_decode($row['obj']));
    }
    return $objs;
}
/**
 * use this helper function to create a new table
 * 
 * @param String $table
 */
function create_table($table) {
    $sql = "CREATE TABLE IF NOT EXISTS `$table` (`id` int(11) NOT NULL 
        auto_increment, `obj` text character set utf8 collate utf8_bin 
        NOT NULL, PRIMARY KEY  (`id`)) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $result = mysql_query($sql);
}
///////////////////////////////////////////////////////////////////////////////
/** 
 * helper function for sorting array of objects based off key
 * 
 * modified from http://www.php.net/manual/en/function.sort.php#87032
 * 
 * @param Array $data -- the array 
 * @param String $key -- the key
 * @param Bool $case_i -- case insensitive?
 */

function sort_objects(&$data, $key, $case_i = true) {
    for ($i = count($data) - 1; $i >= 0; $i--) {
        $swapped = false;
        for ($j = 0; $j < $i; $j++) {
            if ($case_i == true) {
                $cp1 = strtolower($data[$j]->$key);
                $cp2 = strtolower($data[$j + 1]->$key);
            } else {
                $cp1 = $data[$j]->$key;
                $cp2 = $data[$j + 1]->$key;
            }
            if ($cp1 > $cp2) { 
                $tmp = $data[$j];
                $data[$j] = $data[$j + 1];
                $data[$j + 1] = $tmp;
                $swapped = true;
            }
        }
        if (!$swapped) return;
    }
}

/** 
 * helper function filtering objects
 * 
 * modified from http://www.php.net/manual/en/function.sort.php#87032
 * 
 * @param Array $data -- the array 
 * @param String $key -- the key
 * @param $val
 */

function filter_objects(&$data, $key, $value) {
    $newdata = array();
    foreach($data as $d) {
        if (isset($d->$key) && $d->$key == $value) {
            $newdata[] = $d;
        }
    }
    $data = $newdata;
}

/**
 * This 'cures' magic quotes problems
 * 
 * @see http://us.php.net/manual/en/function.get-magic-quotes-gpc.php
 */ 
function stripslashes_deep($value) {
    $value = is_array($value) ?
                array_map('stripslashes_deep', $value) :
                stripslashes($value);
    return $value;
} 
if( (function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc()) 
        || ini_get('magic_quotes_sybase')) {
    foreach($_GET as $k => $v) $_GET[$k] = stripslashes_deep($v);
    foreach($_POST as $k => $v) $_POST[$k] = stripslashes_deep($v);
    foreach($_COOKIE as $k => $v) $_COOKIE[$k] = stripslashes_deep($v);
    foreach($_REQUEST as $k => $v) $_REQUEST[$k] = stripslashes_deep($v);
    foreach($_FILES as $k => $v) $_FILES[$k] = stripslashes_deep($v);   
}

?>