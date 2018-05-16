<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/16
 * Time: 10:37
 */

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
}//Éú³ÉËæ»ú×Ö·û´®