<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/28
 * Time: 10:07
 */

function setBit(&$num,$add,$val){
    if($val)$num = $num | (1 << $add);//置1
    else $num = $num & ~(1 << $add);//置0
}//置位

function getBit($num,$add){
    return $num >> $add & 1;
}//取位

function udpPostSend($sendMsg = '',$ip = '127.0.0.1',$port = '8686'){
    $handle = stream_socket_client("udp://{$ip}:{$port}",$errno,$errstr,1);
    if(!$handle)die("ERROR:{$errno} - {$errstr}\n");
    fwrite($handle,'p' . $sendMsg);
    $result = fread($handle,1024);
    fclose($handle);
    return $result;
}//发送UDP命令

function makeRandomStr($length,$type){
    switch($type){
        case 1:
            $str = "0123456789";
            $strlen = 10;
            while($length > $strlen){
                $str .= $str;
                $strlen += 10;
            }
            break;
        case 2:
            $str = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $strlen = 26;
            while($length > $strlen){
                $str .= $str;
                $strlen += 26;
            }
            break;
        default:
            $str = "ABCDEFGHJKLMNPQRSTUVWXYZ3456789";
            $strlen = 31;
            while($length > $strlen){
                $str .= $str;
                $strlen += 31;
            }
            break;
    }
    $str = str_shuffle($str);
    return substr($str,0,$length);
}//生成随机字符串