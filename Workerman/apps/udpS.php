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

$db_host = "localhost";   //���ݿ�����
$db_port = 3306;            //���ݿ�˿�
$db_user = "root";         //���ݿ��û���
$db_password = "admin";    //���ݿ�����
$db_name = "varis";        //���ݿ�����

define('HEARTBEAT_TIME',20);//���������
$khz = array();//�ͻ���
$count = 0;

$webConnection = null;//ҳ������
$webRequestImei = "";//ҳ������IMEI
$webTimeoutCount = 3;//ҳ������ʱʱ�䣬3��

/****************************************************************************************/
function broadcast($message){
    global $khz;
    foreach($khz as $item){
        echo $item['i']  . ">>" . $message . "\n";
        $item['z'] = $message;
        $item['c']->send(trim($item['z']));
    }
}//�㲥������

function toType($type,$message){
    global $khz;
    foreach($khz as $item){
        if($item['y'] == $type){
            $item['z'] = $message;
            $item['c']->send(trim($item['z']));
            break;
        }
    }
}//�㲥������

function toImei($imei,$message){
    global $khz;
    foreach($khz as $item){
        if($item['i'] == $imei){
            $item['z'] = $message;
            $item['c']->send(trim($item['z']));
            break;
        }
    }
}//���͵�IMEI

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
                $webConnection->send(trim($returnInfo));//һ��Ҫ�ظ�����ȻPOST�Ǳ�Ҫ��ʱ
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
            $current->send(trim($item['z']));//�ط�����
        }
        if($checkHeartbeat){
            if($nowTime - $item['t'] > HEARTBEAT_TIME + 3){
                $devState = $item['s'];
                setBit($devState,0,0);//����Ϊ������
                $imei = $item['i'];
                $result = $db->update('vr_devices')->cols(array('devState'=>$devState))->where("imei='$imei'")->query();//��״̬�ĵ����ݿ���
                unset($khz[$item['i']]);
            }//��ʱ����ʱ��3S��ɾ����һ��
        }
    }
}//�������ߵ������Լ��ط�

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
}//��������ַ���

$udp_worker = new Worker("udp://0.0.0.0:8686");
$udp_worker->count = 1;
$udp_worker->name = 'UDP-S';

$udp_worker->onWorkerStart = function($worker){
    global $db,$db_host,$db_port,$db_user,$db_password,$db_name;
    echo "onWorkerStart={$worker->id}\n";
    echo "imei\t\t\ttime\t\tip&port\t\t\tstatus\n";

    $db = new Workerman\MySQL\Connection($db_host,$db_port,$db_user,$db_password,$db_name);//�������ݿ�

    Timer::add(0.5,function()use($worker){
        cleanOffline();
    });//��ʱ��
};//��ʼ

$udp_worker->onWorkerStop = function($worker){
    echo "onWorkerStop={$worker->id}\n";
};//ֹͣ

$udp_worker->onWorkerReload = function($worker){
    echo "onWorkerReload={$worker->id}\n";
};//���¼���

$udp_worker->onMessage = function($connection,$data){
    global $khz,$db,$webConnection,$webRequestImei,$webTimeoutCount;
    $cmd = substr($data,0,1);
    switch($cmd){
        case "t"://����������������ʱ�䣬��ȡ�����״̬
            $imei = "";
            foreach($khz as $item){
                if(in_array($connection->getRemoteAddress(),$item)){
                    $imei = $item['i'];//ͨ����ַ���ҵ�IMEI
                    break;
                }
            }
            if($imei != ""){
                //���������е�����ʱ�䲢�ظ�
                $khz[$imei]['t'] = time();//��������ʱ��
                $khz[$imei]['z'] = "zt" . $khz[$imei]['s'];
                $connection->send(trim($khz[$imei]['z']));//�ظ�״̬��IP
            }else{
                echo $connection->getRemoteAddress() . ">>tr\n";
                $connection->send("tr");//�ظ�Ҫ��ע��
            }
            break;
        case "r"://ע�������IMEI���豸���ͽ���ע��
            if(stripos($data,",")){
                $imei = substr($data,1,stripos($data,",") - 1);
                $type = substr($data,stripos($data,",") + 1);
                $info = $db->select('imei,devID,devState')->from('vr_devices')->where("imei='$imei'")->row();
                if($info['imei'] == null){
                    //����ΨһdevID
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
                    $connection->send(trim("tj" . $devID));//Ҫ�󼤻�
                }else{
                    if($info['devID'] != null)$connection->send(trim("tj" . $info['devID']));//Ҫ�󼤻�
                    else{
                        //������Ѽ����ͬ���ݿ�����һ��ע�ᵽ��̬�б�����ttҪ�����ͨѶ����
                        $dl = array();
                        $dl['i'] = $imei;//IMEI
                        $dl['y'] = $type;//device type
                        $dl['t'] = time();//ʱ��
                        $dl['c'] = $connection;//����
                        $dl['a'] = $connection->getRemoteAddress();//��ַ
                        $dl['s'] = $info['devState'];//״̬
                        //  7        6   5   4         3       2       1       0
                        //  Ĭ��Ϊ1  nc  nc  ����     ���  ��/����    ����    ����״̬
                        $dl['z'] = "tt" . HEARTBEAT_TIME;//������Ϣ������������֮�󣬰�������룬�豸��Ҫ�ڳ�ʱʱ���ڷ�����
                        setBit($dl['s'],0,1);//����Ϊ����
                        setBit($dl['s'],7,1);//ͷ��1
                        $result = $db->update('vr_devices')->cols(array('devState'=>$dl['s']))->where("imei='$imei'")->query();//��״̬�ĵ����ݿ���
                        $khz[$imei] = $dl;//���뵽��̬�б�
                        echo $khz[$imei]['i'] . ">>tt\n";
                        $connection->send(trim($khz[$imei]['z']));
                    }
                }
            }else $connection->send("tr");
            break;
		case "z"://�������������AIR202�����豸
            $info = substr($data,1,stripos($data,",") - 1);
            $dat = substr($data,stripos($data,",") + 1);

            $imei = "";
            foreach($khz as $item){
                if(in_array($connection->getRemoteAddress(),$item)){
                    $imei = $item['i'];//ͨ����ַ���ҵ�IMEI
                    break;
                }
            }

            if($imei != ""){//���������е�����ʱ�����շ�������
                $khz[$imei]['t'] = time();//��������ʱ��
                if($info == $khz[$imei]['z'] || trim($khz[$imei]['z']) == ""){
                    $khz[$imei]['z'] = "";
                    echo ".";
                    if($webRequestImei == $imei && $webRequestImei != ""){
                        $returnInfo = $dat;
                        $webConnection->send(trim($returnInfo));//һ��Ҫ�ظ�����ȻPOST�Ǳ�Ҫ��ʱ
                        $webRequestImei = "";
                        $webConnection = null;
                        $webTimeoutCount = 3;
                    }
                }
            }
			break;
        case 'p'://����post������,��Ҫ��֤IP
            if($connection->getRemoteIp() == "127.0.0.1"){
                $postCmd = substr($data,1,stripos($data,",") - 1);
                $postVal = substr($data,stripos($data,",") + 1);

                $webConnection = $connection;//�ݴ�ҳ������
                $webRequestImei = $postVal;//�ݴ�ҳ���������IMEI

                switch($postCmd){
                    case "updata":
                        //����б����Ƿ������IMEI
                        if(array_key_exists($postVal,$khz)){
                            $devState = $db->select('devState')->from('vr_devices')->where("imei='$postVal'")->single();//�����ݿ��ж�ȡ���IMEI
                            if($devState != null)$khz[$postVal]['s'] = $devState;
                            $khz[$postVal]['z'] = "zt" . $khz[$postVal]['s'];
                            $current = $khz[$postVal]['c'];
                            $current->send(trim($khz[$postVal]['z']));//��״̬����IMEI
                        }
                        break;//���IMEI�и���
                    default:break;
                }
            }
            break;
        default:break;
    }
};//�յ���Ϣ

Worker::runAll();