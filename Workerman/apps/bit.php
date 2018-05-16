<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/1
 * Time: 17:08
 */
function setBit(&$num,$add,$val){
    if($val)$num = $num | (1 << $add);//ÖÃ1
    else $num = $num & ~(1 << $add);//ÖÃ0
}//ÖÃÎ»

function getBit($num,$add){
    return $num >> $add & 1;
}//È¡Î»