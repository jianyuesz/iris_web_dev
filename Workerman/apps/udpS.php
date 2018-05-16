<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/21
 * Time: 16:53
 */
header("Content-type:text/html; charset=gbk");
ini_set('date.timezone','Asia/Shanghai');
use Workerman\Worker;
use Workerman\Lib\Timer;
//require_once 'log.php';
require_once "bit.php";
require_once '../Autoloader.php';
require_once '../vendor/autoload.php';

$db_host = "localhost";   //数据库主机
$db_port = 3306;            //数据库端口
$db_user = "root";         //数据库用户名
$db_password = "admin";    //数据库密码
$db_name = "varis";        //数据库名称

define('HEARTBEAT_TIME',20);//心跳包间隔
$khz = array();//客户组
$count = 0;

$webConnection = null;//页面连接
$webRequestImei = "";//页面请求IMEI
$webTimeoutCount = 3;//页面请求超时时间，3秒

/****************************************************************************************/
function broadcast($message){
    global $khz;
    foreach($khz as $item){
        echo $item['i']  . ">>" . $message . "\n";
        $item['z'] = $message;
        $item['c']->send(trim($item['z']));
    }
}//广播到所有

function toType($type,$message){
    global $khz;
    foreach($khz as $item){
        if($item['y'] == $type){
            $item['z'] = $message;
            $item['c']->send(trim($item['z']));
            break;
        }
    }
}//广播到类型

function toImei($imei,$message){
    global $khz;
    foreach($khz as $item){
        if($item['i'] == $imei){
            $item['z'] = $message;
            $item['c']->send(trim($item['z']));
            break;
        }
    }
}//发送到IMEI

function cleanOffline(){
    global $khz,$db,$count,$webConnection,$webRequestImei,$webTimeoutCount;

    $count = $count + 1;
    $checkHeartbeat = false;
    if($count >= 2){
        $checkHeartbeat = true;
        $count = 0;

        if($webRequestImei != ""){
            $webTimeoutCount = $webTimeoutCount - 1;
            if($webTimeoutCount <= 0){
                $returnInfo = "timeout";
                $webConnection->send(trim($returnInfo));//一定要回复，不然POST那边要超时
                $webRequestImei = "";
                $webConnection = null;
                $webTimeoutCount = 3;
            }
        }
    }

    $nowTime = time();
    foreach($khz as $item){
        if($item['z'] != ""){
            $current = $item['c'];
            $current->send(trim($item['z']));//重发命令
        }
        if($checkHeartbeat){
            if($nowTime - $item['t'] > HEARTBEAT_TIME + 3){
                $devState = $item['s'];
                setBit($devState,0,0);//设置为不在线
                $imei = $item['i'];
                $result = $db->update('vr_devices')->cols(array('devState'=>$devState))->where("imei='$imei'")->query();//将状态改到数据库中
                unset($khz[$item['i']]);
            }//超时心跳时间3S，删除这一条
        }
    }
}//清理不在线的连接以及重发

function makeRandomStr($length,$isNum){
    if($isNum){
        $str = "0123456789ABCDEF";
        $strlen = 16;
        while($length > $strlen){
            $str .= $str;
            $strlen += 16;
        }
    }else{
        $str = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $strlen = 26;
        while($length > $strlen){
            $str .= $str;
            $strlen += 26;
        }
    }
    $str = str_shuffle($str);
    return substr($str,0,$length);
}//生成随机字符串

$udp_worker = new Worker("udp://0.0.0.0:8686");
$udp_worker->count = 1;
$udp_worker->name = 'UDP-S';

$udp_worker->onWorkerStart = function($worker){
    global $db,$db_host,$db_port,$db_user,$db_password,$db_name;
    echo "onWorkerStart={$worker->id}\n";
    echo "imei\t\t\ttime\t\tip&port\t\t\tstatus\n";

    $db = new Workerman\MySQL\Connection($db_host,$db_port,$db_user,$db_password,$db_name);//连接数据库

    Timer::add(0.5,function()use($worker){
        cleanOffline();
    });//定时器
};//开始

$udp_worker->onWorkerStop = function($worker){
    echo "onWorkerStop={$worker->id}\n";
};//停止

$udp_worker->onWorkerReload = function($worker){
    echo "onWorkerReload={$worker->id}\n";
};//重新加载

$udp_worker->onMessage = function($connection,$data){
    global $khz,$db,$webConnection,$webRequestImei,$webTimeoutCount;
    $cmd = substr($data,0,1);
    switch($cmd){
        case "t"://心跳包，更新在线时间，获取命令和状态
            $imei = "";
            foreach($khz as $item){
                if(in_array($connection->getRemoteAddress(),$item)){
                    $imei = $item['i'];//通过地址查找到IMEI
                    break;
                }
            }
            if($imei != ""){
                //更新数组中的在线时间并回复
                $khz[$imei]['t'] = time();//更新在线时间
                $khz[$imei]['z'] = "zt" . $khz[$imei]['s'];
                $connection->send(trim($khz[$imei]['z']));//回复状态和IP
            }else{
                echo $connection->getRemoteAddress() . ">>tr\n";
                $connection->send("tr");//回复要求注册
            }
            break;
        case "r"://注册包，用IMEI和设备类型进行注册
            if(stripos($data,",")){
                $imei = substr($data,1,stripos($data,",") - 1);
                $type = substr($data,stripos($data,",") + 1);
                $info = $db->select('imei,devID,devState')->from('vr_devices')->where("imei='$imei'")->row();
                if($info['imei'] == null){
                    //生成唯一devID
                    while(true){
                        $devID = makeRandomStr(4,false);
                        $info = $db->select("imei")->from('vr_devices')->where("devID='$devID'")->row();
                        if(!$info)break;
                    }
                    $result = $db->insert('vr_devices')->cols(array(
                        'imei'=>$imei,
                        'devType'=>$type,
                        'devID'=>$devID,
                        'regDate'=>time()
                    ))->query();
                    $connection->send(trim("tj" . $devID));//要求激活
                }else{
                    if($info['devID'] != null)$connection->send(trim("tj" . $info['devID']));//要求激活
                    else{
                        //结果是已激活，和同数据库数据一起注册到动态列表，返回tt要求进入通讯流程
                        $dl = array();
                        $dl['i'] = $imei;//IMEI
                        $dl['y'] = $type;//device type
                        $dl['t'] = time();//时间
                        $dl['c'] = $connection;//连接
                        $dl['a'] = $connection->getRemoteAddress();//地址
                        $dl['s'] = $info['devState'];//状态
                        //  7        6   5   4         3       2       1       0
                        //  默认为1  nc  nc  更新     审核  启/禁用    开关    在线状态
                        $dl['z'] = "tt" . HEARTBEAT_TIME;//反馈信息（发送了命令之后，把命令存入，设备需要在超时时间内反馈）
                        setBit($dl['s'],0,1);//设置为在线
                        setBit($dl['s'],7,1);//头置1
                        $result = $db->update('vr_devices')->cols(array('devState'=>$dl['s']))->where("imei='$imei'")->query();//将状态改到数据库中
                        $khz[$imei] = $dl;//加入到动态列表
                        echo $khz[$imei]['i'] . ">>tt\n";
                        $connection->send(trim($khz[$imei]['z']));
                    }
                }
            }else $connection->send("tr");
            break;
		case "z"://反馈包，针对于AIR202单种设备
            $info = substr($data,1,stripos($data,",") - 1);
            $dat = substr($data,stripos($data,",") + 1);

            $imei = "";
            foreach($khz as $item){
                if(in_array($connection->getRemoteAddress(),$item)){
                    $imei = $item['i'];//通过地址查找到IMEI
                    break;
                }
            }

            if($imei != ""){//更新数组中的在线时间和清空反馈设置
                $khz[$imei]['t'] = time();//更新在线时间
                if($info == $khz[$imei]['z'] || trim($khz[$imei]['z']) == ""){
                    $khz[$imei]['z'] = "";
                    echo ".";
                    if($webRequestImei == $imei && $webRequestImei != ""){
                        $returnInfo = $dat;
                        $webConnection->send(trim($returnInfo));//一定要回复，不然POST那边要超时
                        $webRequestImei = "";
                        $webConnection = null;
                        $webTimeoutCount = 3;
                    }
                }
            }
			break;
        case 'p'://来自post短连接,需要验证IP
            if($connection->getRemoteIp() == "127.0.0.1"){
                $postCmd = substr($data,1,stripos($data,",") - 1);
                $postVal = substr($data,stripos($data,",") + 1);

                $webConnection = $connection;//暂存页面连接
                $webRequestImei = $postVal;//暂存页面请求相关IMEI

                switch($postCmd){
                    case "updata":
                        //检查列表中是否有这个IMEI
                        if(array_key_exists($postVal,$khz)){
                            $devState = $db->select('devState')->from('vr_devices')->where("imei='$postVal'")->single();//从数据库中读取这个IMEI
                            if($devState != null)$khz[$postVal]['s'] = $devState;
                            $khz[$postVal]['z'] = "zt" . $khz[$postVal]['s'];
                            $current = $khz[$postVal]['c'];
                            $current->send(trim($khz[$postVal]['z']));//将状态发往IMEI
                        }
                        break;//这个IMEI有更新
                    default:break;
                }
            }
            break;
        default:break;
    }
};//收到消息

Worker::runAll();