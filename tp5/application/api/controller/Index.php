<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/26
 * Time: 10:19
 */

namespace app\api\controller;
use think\Db;
use think\Request;
use think\Controller;

class Index extends Controller
{
    public function index(){
        return "api functional test";
    }

    public function devAct(){
        $devID = input('post.devID');
        $devName = input('post.devName');
        $userName = input('post.userName');
        $userPwd = input('post.userPwd');
        $site = input('post.site');
        $note = input('post.note');

        $code = 0;
        $message = '';
        $udpmsg = '';
        if($devID == null || $devName == null || $userName == null || $userPwd == null || $site == null || $note == null)$message = "所有参数必填";
        else{
            if(!db('devices')->where('devID',$devID)->find())$message = "没有激活条件";
            elseif(db('devices')->where('devName',$devName)->find())$message = "设备名称重复";
            elseif(db('devices')->where('userName',$userName)->find())$message = "用户名称重复";
            else{
                $imei = db('devices')->where('devID',$devID)->value('imei');
                db('devices')->where('devID',$devID)->update([
                    'devName'=>$devName,
                    'userName'=>$userName,
                    'userPwd'=>$userPwd,
                    'site'=>$site,
                    'note'=>$note,
                    'devID'=>'',
                ]);
                $code = 1;
                $message = "操作成功";
                $udpmsg = udpPostSend("updata," . $imei);//通知UDP服务器，此IMEI有更新
            }
        }
        return ['code'=>$code, 'msg'=>$message,'udpmsg'=>$udpmsg];
    }//设备激活

    public function devSta(){
        $imei = input('post.imei');
        $status = input('post.status');

        $code = 0;
        $udpmsg = '';

        if($imei == null)$message = "请输入IMEI";
        else{
            $devState = db('devices')->where('imei',$imei)->value('devState');
            $type = db('devices')->where('imei',$imei)->value('devType');
            switch($status){
                case 0:break;
                case 1:if(!getBit($devState,3))setBit($devState,3,1);break;//通过审核 1
                case 2:if(getBit($devState,3))setBit($devState,3,0);break;//不通过审核 0
                case 3:if(getBit($devState,3) && getBit($devState,2))setBit($devState,2,0);break;//启用设备 0	/启用自动上锁 //如果审核不通过，无法启用禁止设备
                case 4:if(getBit($devState,3) && (!getBit($devState,2)))setBit($devState,2,1);break;//禁用设备 1	/禁用自动上锁     //如果审核不通过，无法启用禁止设备
                case 5:if(getBit($devState,3) && (!getBit($devState,2)) && (!getBit($devState,1)))setBit($devState,1,1);break;//解锁设备 1 //如果审核不通过 或 设备被禁用，无法锁定解锁设备
                case 6:if(getBit($devState,3) && (!getBit($devState,2)) && getBit($devState,1))setBit($devState,1,0);break;//锁定设备 0 //如果审核不通过 或 设备被禁用，无法锁定解锁设备
                case 7:if(!getBit($devState,0))setBit($devState,0,1);break;//在线 1
                case 8:if(getBit($devState,0))setBit($devState,0,0);break;//离线 0
                case 9:if(!getBit($devState,4))setBit($devState,4,1);break;//内容有更新
                default:$state = -1;break;
            }
            db('devices')->where('imei',$imei)->update(['devState'=>$devState]);
            $udpmsg = udpPostSend("updata," . $imei);//通知UDP服务器，此IMEI有更新
            $code = 1;
            $message = $devState;
        }
        return ['code'=>$code, 'msg'=>$message,'udpmsg'=>$udpmsg];
    }//修改设备状态

    public function devGetAll(){
        $paging = input('post.paging');//分页数
        $page = (int)input('post.page');//请求页

        $dat = db('devices')->select();//获取记录集
        $count = count($dat);//记录总数
        $pageCount = ceil($count / $paging);//总页数

        if($page>$pageCount)$page = $pageCount;//如果请求页大于总页数，那就请求最后一页
        $getCount = $page * $paging;//请求的最大指针
        $startPot = $getCount - $paging;//请求开始的指针
        if($getCount > $count)$getCount = $count;//如果请求最大指针大于总记录数，那就请求到最大指针
        $getCount = $getCount - $startPot;//实际请求数

        $infoarr = array();
        for($i=0;$i<$getCount;$i++){
            $j = $dat[$i + $startPot];
            $info = [
                'imei'=>$j['imei'],
                'devType'=>$j['devType'],
                'devName'=>$j['devName'],
                'userName'=>$j['userName'],
                'userPwd'=>$j['userPwd'],
                'site'=>$j['site'],
                'note'=>$j['note'],
                'devState'=>$j['devState'],
                'devID'=>$j['devID'],
            ];
            array_push($infoarr,$info);
        }
        return ['count'=>$count,'pageCount'=>$pageCount,'pagePot'=>$page,'dat'=>$infoarr];//总计录数，总页数，当前页，数据
    }//取所有记录

    public function videoGetIns(){
        $imei = input('post.imei');
        if($imei != null){
            $devState = db('devices')->where('imei',$imei)->value('devState');//从数据库中获取状态
            if(getBit($devState,4))setBit($devState,4,0);//更新状态置0
            db('devices')->where('imei',$imei)->update(['devState'=>$devState]);//更新回数据库中
            $udpmsg = udpPostSend("updata," . $imei);//通知UDP服务器，此IMEI有更新
        }
        return db('devices')->where('imei',$imei)->find();
    }//返回要下载\删除的列表

    public function getInfoOnID(){
        $imei = input('post.imei');
        $infoID = input('post.infoID');//内容ID
        $isValid = false;

        $devInfos_s = explode(",",db('devices')->where('imei',$imei)->value('devInfos'));
        if(array_search($infoID,$devInfos_s))$isValid = true;

        $devInInfos_s = explode(",",db('devices')->where('imei',$imei)->value('devInInfos'));
        if(array_search($infoID,$devInInfos_s))$isValid = true;

        $devOutInfos_s = explode(",",db('devices')->where('imei',$imei)->value('devOutInfos'));
        if(array_search($infoID,$devOutInfos_s))$isValid = true;

        if($isValid) return db('infos')->where('infoID',$infoID)->find();
        return null;
    }//用infoID获取详细内容

    public function catUserInfo(){
        $userName = input('post.userName');
        $userPwd = input('post.userPwd');

        $code = 0;
        $msg = '';
        if($userPwd == db('user')->where('userName',$userName)->value('userPwd')){
            $code = 1;
            $msg = [
                'userID'=>db('user')->where('userName',$userName)->value('userID'),
                'userEmail'=>db('user')->where('userName',$userName)->value('userEmail'),
                'userDevices'=>db('user')->where('userName',$userName)->value('userDevices'),
                'userInfos'=>db('user')->where('userName',$userName)->value('userInfos')
            ];
        }
        return ['code'=>$code, 'msg'=>$msg];
    }//查看用户信息

    public function catDevInfo(){
        $devices = input('post.devices');
        $code = 0;
        $msg = '';

        return ['code'=>$code,'msg'=>$msg];
    }//查看设备信息（未完成）
}