<?php
namespace app\wechat\controller;
use think\Controller;
class Base extends Controller
{
    public $AppID = 'wx9ccd3a644d9fad91';
    public $AppSecret = '145a4d08c482fa4e5fc30b35fbbb14ea';

    //post数据
    public function postBycurl($url,$data)
    {
        $ch = curl_init();//初始化cur
        curl_setopt($ch, CURLOPT_URL,$url);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    //get数据
    public function getBycurl($url,$data)
    {
        $parmas = '?';
        foreach($data as $k=>$v)
        {
            $parmas .= $k.'='.$v.'&';
        }
        $parmas = substr($parmas,0,strlen($parmas)-1);
        $url = $url.$parmas;
        $ch = curl_init();//初始化cur
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch,CURLOPT_HEADER,0);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    //随机字符
    public function sendStr($num)
    {
        $seed = '0123456789';
        $string = '';
        for($i = 0;$i<$num;$i++)
        {
            $ber = rand(0,9);
            $string .= substr($seed,$ber,1);
        }
        return $string;
    }

    //发送短信
    public function sendMessForparnter($phone,$mess)
    {
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
        if($output == '00')
        {
            return 0;
        }
        else{
            return $output;
        }
    }

    //获得openid
    public function getOpenid($code)
    {
        $data['appid'] = $this->AppID;
        $data['secret'] = $this->AppSecret;
        $data['js_code'] = $code;
//        $data['grant_type'] = 'authorization_code';
        $url = 'https://api.weixin.qq.com/sns/jscode2session';
        $res = $this->postBycurl($url,$data);
        $array = json_decode($res,TRUE );
        return $array['openid'];
    }

    //获得微信绑定账号
    public function getAccWithwechat($openid)
    {
        $account = db('wechat_account')->where('wechat_openid="'.$openid.'"')->find();
        return $account;
    }


    //随机字符串
    public function randStr($num)
    {
        $chars = 'qwertyuiopasdfghjklmnbvcxzQWERTYUIOPLKJHGFDSAZXCVBNM0123456789';
        $rands = '';
        for($i=0;$i<$num;$i++)
        {
            $seed = rand(0,61);
            $rands .= substr($chars,$seed,1);
        }
        return $rands;
    }
}
