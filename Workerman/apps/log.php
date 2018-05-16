<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/9
 * Time: 12:21
 */
header("Content-type: text/html; charset=utf-8");
ini_set('date.timezone','Asia/Shanghai');
define("LOG_TYPE_INFO",1);
define("LOG_TYPE_WARNING",2);
define("LOG_TYPE_ERROR",3);
function logcat($tag,$info){
    $file = "logs/" . date('Y-m-d') . ".log";//以日期为文件名
    switch($tag){
        case LOG_TYPE_INFO:$type = "INFO";break;
        case LOG_TYPE_WARNING:$type = "WARNING";break;
        case LOG_TYPE_ERROR:$type = "ERROR";break;
        default:return 0;
    }
    $content = date('H:i:s') . ',' .$type . ',' . $info . "\r\n";
    return file_put_contents($file,$content,FILE_APPEND);
}