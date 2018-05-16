<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/26
 * Time: 10:02
 */

namespace app\varis\controller;
use think\Controller;

class Index extends Controller
{
    public function index(){
        return view();
    }

    public function device(){
        return view();
    }

    public function setNewApkVersion(){
        $file = request()->file('file');
        if(empty($file))$this->error("请选择要上传的文件");
        else{
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads',false);
            $packageName = input('packageName');//应用包名
            $versionName = input('versionName');//版本代码
            $versionCode = input('versionCode');//版本号
            $updataInfo = input('updataInfo');//更新详情
            $downloadUrl = '/uploads/' . $info->getInfo("name");//下载地址

            if($packageName != null && $versionCode != null && $versionName != null && $updataInfo != null && $downloadUrl != null){
                $old_versionCode = db('apkupdata')->where('packageName',$packageName)->value('versionCode');
                if($old_versionCode == null){
                    //插入
                    db('apkupdata')->insert([
                        'packageName'=>$packageName,
                        'versionName'=>$versionName,
                        'versionCode'=>$versionCode,
                        'updataInfo'=>$updataInfo,
                        'downloadUrl'=>$downloadUrl,
                    ]);
                    $this->success("新增更新成功");
                }elseif($old_versionCode < $versionCode){
                    //更新
                    db('apkupdata')->where('packageName',$packageName)->update([
                        'versionName'=>$versionName,
                        'versionCode'=>$versionCode,
                        'updataInfo'=>$updataInfo,
                        'downloadUrl'=>$downloadUrl,
                    ]);
                    $this->success("修改更新成功");
                }else{
                    //旧的版本号大于等于现行版本号，不能更新
                    $this->error("提交的版本不如旧版本新");
                }
            }else $this->error("提交失败");
        }
    }

    public function addInfoToSys(){
        $infoName = input('infoName');//内容名称
        $infoType = input('infoType');//内容类型
        $infoHref = input('infoHref');//内容地址

        if($infoName != null && $infoType != null && $infoHref != null){
            $old_infoHref = db('infos')->where('infoName',$infoName)->value('infoHref');
            if($old_infoHref != null)$this->error('内容名称重复,资源是 ' . $old_infoHref);
            $old_infoName = db('infos')->where('infoHref',$infoHref)->value('infoName');
            if($old_infoName != null)$this->error('此资源已经添加过,名称是 ' . $old_infoName);

            do{
                $infoID = makeRandomStr(10,1);
            }while(db('infos')->where('infoID',$infoID)->value('infoName') != null);

            db('infos')->insert([
                'infoID'=>$infoID,
                'infoName'=>$infoName,
                'infoType'=>$infoType,
                'infoHref'=>$infoHref
            ]);

            $this->success("添加资源成功，ID是 " . $infoID);
        }else $this->error("所有字段必填");
    }

    public function regeditUser(){
        $userName = input('userName');//用户名称
        $userPwd = input('userPwd');//用户密码
        $userEmail = input('userEmail');//用户邮箱

        if($userName != null && $userPwd != null && $userEmail != null){
            $old_userID = db('user')->where('userName',$userName)->value('userID');
            if($old_userID != null)$this->error('用户名称重复');
            $old_userID = db('user')->where('userEmail',$userEmail)->value('userID');
            if($old_userID != null)$this->error('此邮箱已经注册');

            do{
                $userID = makeRandomStr(10,1);
            }while(db('user')->where('userID',$userID)->value('userName') != null);

            db('user')->insert([
                'userID'=>$userID,
                'userName'=>$userName,
                'userPwd'=>$userPwd,
                'userEmail'=>$userEmail
            ]);

            $this->success("注册成功，ID是 " . $userID);
        }else $this->error("所有字段必填");
    }

    public function bindDevice(){
        $userName = input('userName');//用户名称
        $userPwd = input('userPwd');//用户密码
        $devID = input('devID');//设备标识

        if($userName != null && $userPwd != null && $devID != null){
            $old_userPwd = db('user')->where('userName',$userName)->value('userPwd');
            if($old_userPwd != $userPwd)$this->error('用户登录失败');

            $userID = db('user')->where('userName',$userName)->value('userID');
            $imei = db('devices')->where('devID',$devID)->value('imei');
            if($imei != null){
                db('devices')->where('devID',$devID)->update([
                    'devName'=>$userName,
                    'userName'=>$userName,
                    'userPwd'=>$userPwd,
                    'site'=>'www',
                    'note'=>'www',
                    'devID'=>'',
                    'devUserID'=>$userID
                ]);

                $userDevices_s = explode(",",db('user')->where('userID',$userID)->value('userDevices'));
                array_push($userDevices_s,$imei);
                $userDevices =implode(",",$userDevices_s);
                db('user')->where('userID',$userID)->update(['userDevices'=>$userDevices]);

                $this->success('绑定成功，设备IMEI为 ' . $imei);
            }else $this->error('没有找到设备');
        }else $this->error("所有字段必填");
    }

    public function buy(){
        $userName = input('userName');//用户名称
        $userPwd = input('userPwd');//用户密码
        $infoID = input('infoID');//内容

        if($userName != null && $userPwd != null && $infoID != null){
            $old_userPwd = db('user')->where('userName',$userName)->value('userPwd');
            if($old_userPwd != $userPwd)$this->error('用户登录失败');

            $userID = db('user')->where('userName',$userName)->value('userID');
            $infoName = db('infos')->where('infoID',$infoID)->value('infoName');
            if($infoName != null){

                $userInfos_s = explode(",",db('user')->where('userID',$userID)->value('userInfos'));
                if(array_search($infoID,$userInfos_s))$this->error("已经购买过这个内容了");
                array_push($userInfos_s,$infoID);
                $userInfos = implode(",",$userInfos_s);
                db('user')->where('userID',$userID)->update(['userInfos'=>$userInfos]);

                $this->success('购买成功');
            }else $this->error('没有找到该内容');
        }else $this->error("所有字段必填");
    }

    public function infoToDev(){
        $userName = input('userName');//用户名称
        $userPwd = input('userPwd');//用户密码
        $imei = input('imei');//设备IMEI
        $infoID = input('infoID');//内容

        if($userName != null && $imei != null && $userPwd != null && $infoID != null){
            /*
             * 1.检查登录
             * 2.检查是否有此设备
             * 3.检查是否有此内容
             * 4.检查设备中是否有此内容
             * 5.将内容添加到设备
             */
            $old_userPwd = db('user')->where('userName',$userName)->value('userPwd');
            if($old_userPwd != $userPwd)$this->error('用户登录失败');

            $userDevices_s = explode(",",db('user')->where('userName',$userName)->value('userDevices'));
            if(!array_search($imei,$userDevices_s))$this->error("没有绑定过这个设备");

            $userInfos_s = explode(",",db('user')->where('userName',$userName)->value('userInfos'));
            if(!array_search($infoID,$userInfos_s))$this->error("没有购买过这个内容");

            foreach($userDevices_s as $imei_s){
                $devInfos_s = explode(",",db('devices')->where('imei',$imei_s)->value('devInfos'));
                if(array_search($infoID,$devInfos_s))$this->error($infoID . " 在设备编号为 " . $imei_s . "中已经下载好，不能另外下载");

                $devInInfos_s = explode(",",db('devices')->where('imei',$imei_s)->value('devInInfos'));
                if(array_search($infoID,$devInInfos_s))$this->error($infoID . " 在设备编号为 " . $imei_s . "中准备下载，不能另外下载");

                $devOutInfos_s = explode(",",db('devices')->where('imei',$imei_s)->value('devOutInfos'));
                if(array_search($infoID,$devOutInfos_s))$this->error($infoID . " 在设备编号为 " . $imei_s . "中还没有删除，不能另外下载");
            }

            $devInInfos_s = explode(",",db('devices')->where('imei',$imei)->value('devInInfos'));
            array_push($devInInfos_s,$infoID);
            db('devices')->where('imei',$imei)->update(['devInInfos'=>implode(",",$devInInfos_s)]);
            $this->success($infoID . " 将下载到 " . $imei);
        }else $this->error("所有字段必填");
    }

    public function deleteTest(){
        $userInfos = db('user')->where('userID','7210563498')->value('userInfos');
        $userInfos_s = explode(",",$userInfos);
        array_push($userInfos_s,'sdfsdf');
        array_push($userInfos_s,'sdfsd3f');
        array_push($userInfos_s,'sdfsdff');
        array_push($userInfos_s,'sdfsdewf');
        array_push($userInfos_s,'sdfsdfef');
        $delarr = ['2064359781',"sdfsd3f"];
        $rel = array_diff($userInfos_s,$delarr);
        $this->success(implode(",",$rel));
    }

    public function crctest(){
        $str1 = 0x30;
        $str2 = 0x31;
        return hex2bin($str1);
    }
}