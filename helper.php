<?php
require_once(dirname(__FILE__) . '/variables.php');

function admin_url($action, $params=array()) {
    $str = "admin/admin.php?action=$action&loc=".urlencode(full_uri());
    foreach($params as $key => $val) {
        $str .= "&$key=$val";
    }
    return $str;
}
function file_url($dir, $filename, $url_encode=false) {
    $base_dir = BASE_DIR;
    if ($url_encode) {
        $filename = pathinfo($filename, PATHINFO_DIRNAME) . '/' 
            . rawurlencode(basename($filename));
        //$filename = rawurlencode($filename);
    }
    if (strlen($dir) > 0) {
        return $base_dir . $dir . '/' . $filename;
    } else {
        return $base_dir . $filename;
    }   
}
function file_path($dir, $filename) {
    $base = pathinfo(__FILE__, PATHINFO_DIRNAME) . '/../';
    if (strlen($dir) > 0) {
        return $base . $dir . '/' . $filename;
    } else {
        return $base . $filename;
    }
}
function full_uri(){
    return "http://".$_SERVER["HTTP_HOST"].$_SERVER['REQUEST_URI'];
}
function setUrlVar($url, $var, $val) {
    //print_r($url);
    $par = parse_url($url);
    parse_str($par['query'], $vars);
    $vars[$var] = $val;
    return 'http://'.$par['host'].$par['path'].'?'.http_build_query($vars); 
}

/* taken from 
 * http://www.php.net/manual/en/function.http-build-query.php#52789
 */
if (!function_exists('http_build_query')) {
    function http_build_query($formdata, $numeric_prefix = "") {
       $arr = array();
       foreach ($formdata as $key => $val)
         $arr[] = urlencode($numeric_prefix.$key)."=".urlencode($val);
       return implode($arr, "&");
    }
}

