<?php
namespace app\admin\controller;
use think\Controller;
class BaseController extends Controller
{
/***************** 公共函数 *****************/ 

/**
***	公共判断
**/ 

	public function publicResouce($re){

	}

/**
***	公共提示页面=>成功提示
**/

	public function successHtml()
	{
		return view();
	}

	//
	public function _initialize(){

	}

	//发送短信
    public function sendMess($phone,$user,$money,$remain)
    {
        $mess = '尊敬的%s用户，您的专属Lahor+消费卡于%s，消费%s元，剩余%s元，欢迎下次光临';
        $mess = sprintf($mess ,$user,date('Y-m-d H:i:s'),$money,$remain);
        $url = "http://114.255.71.158:8061";
        $data = [
            'username'=>'hgkj',
            'password'=>md5( 'Hgkj0116'),
            'phone'=>$phone,
            'epid'=>123591,
            'message'=>iconv('UTF-8','GBK//IGNORE',$mess),
        ];
        $ch = curl_init() ;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch) ;
        curl_close($ch) ;
        return $output;
    }


    //发送商家短信
    public function sendMessForparnter($phone,$user,$shop)
    {
        $mess = '尊敬的%s先生/女士，您的商铺%s于%s已成功加入蓝划商业联盟。如有需要请拨打客服热线：028-67803333。';
        $mess = sprintf($mess ,$user,$shop,date('Y-m-d H:i:s'));
        $url = "http://114.255.71.158:8061";
        $data = [
            'username'=>'hgkj',
            'password'=>md5( 'Hgkj0116'),
            'phone'=>$phone,
            'epid'=>123591,
            'message'=>iconv('UTF-8','GBK//IGNORE',$mess),
        ];
        $ch = curl_init() ;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch) ;
        curl_close($ch) ;
        return $output;
    }


    //发送会员短信
    public function sendMessForUser($phone,$user)
    {
        $mess = '尊敬的先生/女士，恭喜您于%s核准成为小西街99号店收益会员。如有需要请拨打客服热线：0839-2297001。';
        //$mess = '尊敬的%s先生/女士，恭喜您于%s已成为蓝划商业联盟的会员。如有需要请拨打客服热线：028-67803333。';
        $mess = sprintf($mess ,date('Y-m-d H:i:s'));
        $url = "http://114.255.71.158:8061";
        $data = [
            'username'=>'hgkj',
            'password'=>md5( 'Hgkj0116'),
            'phone'=>$phone,
            'epid'=>123591,
            'message'=>iconv('UTF-8','GBK//IGNORE',$mess),
        ];
        $ch = curl_init() ;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch) ;
        curl_close($ch) ;
        return $output;
    }


    //发送短信
    public function test($phone)
    {
        $url = 'http://106.ihuyi.com/webservice/sms.php?method=Submit';
        $data = [
            'account'=>'C11767717',
            'password'=>'b653a6c6b6087fa9284a00769b2a19f6',
            'mobile'=>$phone,
            'content'=>'您的验证码是：12345。请不要把验证码泄露给其他人。'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    //  会员类型区域
    public function membertype()
    {
        $membertype = db("member_type")
                    ->where("type_status", 1)
                    ->select();

        return $membertype;
    }

    //  会员标准
    public function memberstand()
    {
        $memberstand = db("standard")
                     ->where("standard_status",1)
                     ->select();

        return $memberstand;
    }

    //  会员等级 
    public function membergrade()
    {
        $membergrade = db("member_grade")
                     ->where("grade_status", 1)
                     ->select();

        return $membergrade;
    }









    
}