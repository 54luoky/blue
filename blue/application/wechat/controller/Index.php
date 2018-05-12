<?php
namespace app\wechat\controller;
use app\wechat\controller\base;
use think\Session;

class Index extends Base
{
    public function index()
    {

    }

    //存储openid，用户信息
    public function storeMess()
    {
        $code = input('post.code');
        $openid = $this->getOpenid($code);
        \think\Cache::set('openid',$openid);var_dump($openid);exit;
        $token = db('wechat_account')->find($openid);
        if($token)
        {
            \think\Cache::set('user',$token);
            print json_encode(['verify'=>0]);
            exit;
        }
    }

    //是否绑定账户
    public function checkBind()
    {
        $code = input('post.code');
        $data['appid'] = $this->AppID;
        $data['secret'] = $this->AppSecret;
        $data['js_code'] = $code;
        $data['grant_type'] = 'authorization_code';

        $url = 'https://api.weixin.qq.com/sns/jscode2session';
        $res = $this->postBycurl($url,$data);
        $array = json_decode($res,TRUE );
        //file_put_contents('./luo.txt',var_export($res,true));
        \think\Cache::set('openid',$array['openid']);
        if($array['openid'])
        {
            $token = db('wechat_account')->find($array['openid']);
            if($token)
            {
                \think\Cache::set('user',$token);
                print json_encode(['verify'=>0]);
                exit;
            }
            else
            {
                print json_encode(['verify'=>2]);
                exit;
            }
        }
        else
        {
            print json_encode(['verify'=>1]);
            exit;
        }
    }

    //发送身份验证短信
    public function verifyMess()
    {
        $phone = input('post.phone');
        $verNum = $this->sendStr(4);
        $message = '您的身份验证码为：'.$verNum;
        $res = $this->sendMessForparnter($phone,$message);
        if($res == 0)
        {
            $code = [
                'number'=>$verNum,
                'expire'=>time()
            ];
            \think\Cache::set('verifyCode',$code);
            print json_encode(['verify'=>true]);
        }
    }

    //验证身份
    public function verifyAction()
    {
        $code_user = input('post.codeO');
        $openid = $this->getOpenid($code_user);

        $status = '';
        $phone = input('post.phone');//用户电话
        $code = input('post.code');//用户code
        $status = input('post.status');//用户0，商家1
        $userInfo = json_decode( input('post.userInfo'),true);//用户信息
        //获得与销毁验证码
        $verify = \think\Cache::get('verifyCode');
        \think\Cache::rm('verifyCode');

        //判断有验证码
        if(!$verify)
        {
            print json_encode(['status'=>1,'mess'=>'没有验证码']);
            exit;
        }

        //验证码是否正确
        if($verify['number'] != $code)
        {
            print json_encode(['status'=>2,'mess'=>'验证码错误']);
            exit;
        }

        //判断是否过期
        if( (time()-$verify['expire']) < 5*60 )
        {
            switch ($status)
            {
                case 0:
                        $location = db('member')->where('mobile_phone='.$phone)->find();
                        break;
                case 1:
                        $location = db('shops')->where('phone='.$phone)->find();
                        break;
                default :
                    print json_encode(['status'=>3,'mess'=>'状态错误']);
                    exit;
            }

            //验证身份
            if($location)
            {
                //判断有没有验证过
                $isVered = db('wechat_account')->find(['openid'=>$openid]);
                if($isVered)
                {
                    print json_encode(['status'=>6,'mess'=>'不能重复验证']);
                    exit;
                }

                $data = [];
                $data = [
                    'wechat_openid'=>$openid,
                    'wechat_type'=>$status,
                    'uid'=>($status == 0)? $location['mid'] : $location['id'],
                    'nickName'=>$userInfo['nickName'],
                    'country'=>$userInfo['country'],
                    'city'=>$userInfo['city'],
                    'province'=>$userInfo['province'],
                    'gender'=>$userInfo['gender'],
                    'avatarUrl'=>$userInfo['avatarUrl'],
                ];
                $result = db('wechat_account')->insert($data);

                //验证成功
                if($result)
                {
                    print json_encode(['status'=>0]);
                    exit;
                }
            }
            else{
                print json_encode(['status'=>4,'mess'=>'没有该账号']);
                exit;
            }
        }
        else
        {
            \think\Cache::rm('verifyCode');
            print json_encode(['status'=>5,'mess'=>'验证码过期']);
            exit;
        }
    }


    //消费
    public function expense()
    {
        $mid = input('post.mid');//商家id
        $money = input('post.money');//消费金额
        $qrurl = input('post.qrurl');//支付使用的二维码
        $user = \think\Cache::get('user');
        $openid = $user['wechat_openid'];
        if(!$openid)
        {
            //openid获取失败
            print json_encode(['status'=>6,'mess'=>'openid获取失败']);
            exit;
        }

        $consumer = db('wechat_account')->find(['wechat_openid'=>$openid]);
        if($consumer)
        {
            //查找会员
            if($consumer['wechat_type'] == 0)
            {
                $shop = db('shops')->where('id='.$mid)->find();//商家
                $person = db('member')->where('mid='.$consumer['uid'])->find();//会员
                $acount = db('consumer')->where('cid='.$person['member_cid'])->find();//消费者账号

                //是否是消费者
                if($acount['cons_sid'] != $person['mid'])
                {
                    print json_encode(['status'=>5,'mess'=>'不是消费者']);
                    exit;
                }

                //判断是否有足够余额
                if($acount['cons_balance'] - $money < 0)
                {
                    if($acount['cons_balancs'] - $money < 0)
                    {
                        print json_encode(['status'=>4,'mess'=>'余额不足']);
                        exit;
                    }
                    //可提现消费
                    else
                    {
                        $con_type = 2;
                    }
                }
                //不可提现消费
                else
                {
                    $con_type = 1;
                }


                //事务
                \think\Db::startTrans();
//                try{
                    if($con_type == 1){
                        $con_mon = $acount['cons_balance'] - $money;
                        $res = db('consumer')->where('cid='.$acount['cid'])->update(['cons_balance'=> $con_mon ]);
                        $blan = $con_mon+$acount['cons_balancs'];
                    }
                    if($con_type == 2)
                    {
                        $con_mon = $acount['cons_balancs'] - $money;
                        db('consumer')->where('cid='.$acount['cid'])->update(['cons_balancs'=> $con_mon ]);
                        $blan = $con_mon+$acount['cons_balance'];
                    }
                    db('consume')->insert(['uid'=>$acount['cid'],'tip'=>$money,'time'=> date('Y-m-d h:i:s',time()) ,'mid'=>$mid,'con_type'=>$con_type,'qrurl'=>$qrurl]);//消费记录

                    //发送短信
                    $con_time = date('Y-m-d h:i:s',time());
                    $messTomember = '您在商场'.$shop['name'].'，于'.$con_time.'消费金额'.$money.'元，余额为'.$blan;
                    $messToshop = '手机号码为'.$person['mobile_phone'].'于'.$con_time.',在您的商店消费'.$money.'元';

                    //发送给商家
//                    $comTr = $this->sendMessForparnter($acount['cons_phone'],$messTomember);
//                    if($comTr == 0 )
//                    {
//                        $comDp = $this->sendMessForparnter($shop['phone'],$messToshop);
//                        if($comDp == 0 )
//                        {
                            //操作完成
                            \think\Db::commit();
                            print json_encode(['status'=>0]);
                            exit;
//                        }
//                        else
//                        {
//                            throw new \Exception("402");
//                        }
//                    }else{
//                        throw new \Exception("401");
//                    }

//                }  catch (\Exception $e)
//                {
//                    \think\Db::rollback();
//                    print json_encode(['status'=>6,'mess'=>'数据存储失败']);
//                    exit;
//                }

            }

            //商家
            elseif ($consumer['wechat_type'] == 1)
            {
                print json_encode(['status'=>2,'mess'=>'不是会员']);
                exit;
            }
        }
        else{
            print json_encode(['status'=>1,'没有绑定']);
            exit;
        }
    }


    //消费列表
    public function conLists()
    {

        $account = \think\Cache::get('user');

        if(!$account)
        {
            print json_encode(['status'=>false]);exit;
        }
        //是不是消费者
//        $conmer = db('consumer')->where('cons_sid='.$account['uid'])->find();var_dump($openid);exit;
//        if(!$conmer)
//        {
//            print json_encode(['status'=>1]);
//            exit;
//        }

        //消费列表
        if($account['wechat_type'] == 0)
        {
            $coner = db('member')->where('mid='.$account['uid'])->find();
            $list = db('consume')
                ->field('e.*,p.stroe as sh_name,p.id as pid,r.cons_name as co_name')
                ->alias('e')
                ->join('blue_consumer r','r.cid = e.uid','left')
                ->join('blue_shops p','p.id = e.mid','left')
                ->where('uid='.$coner['member_cid'])
                ->limit(10)
                ->order('id','desc')
                ->select();
            foreach($list as &$v)
            {
                $shop = db('wechat_account')
                    ->where('uid ='.$v['pid'].' and wechat_type=1')
                    ->find();
                if($shop)$v['wechat'] = $shop;
                $v['time'] = substr($v['time'],0,11);
            }
            $data = [
                'status'=>0,
                'type'=>0,
                'list'=>$list
            ];
            print json_encode($data);exit;
        }
        else
        {
            $list = db('consume')
                ->alias('e')
                ->field('e.*,p.name as sh_name,r.cons_name as co_name,r.cons_sid')
                ->join('blue_consumer r','r.cid = e.uid','left')
                ->join('blue_shops p','p.id = e.mid','left')
                ->where('mid='.$account['uid'])
                ->order('id','desc')
                ->limit(10)
                ->select();
            foreach($list as &$v)
            {
                $user = db('wechat_account')
                    ->where('uid ='.$v['cons_sid'].' and wechat_type=0')
                    ->find();
                if($user)$v['wechat'] = $user;
                $v['time'] = substr($v['time'],0,11);
            }
            $data = [
                'status'=>0,
                'type'=>1,
                'list'=>$list
            ];
            print json_encode($data);exit;
        }
    }


    //根据时间查找消费列表
    public function getListBytime()
    {
        $account = \think\Cache::get('user');
        if(!$account)
        {
            print json_encode(['status'=>false]);exit;
        }

        $time = input('post.time');
        $stime = $time.'-00 00:00:00';
        $date = explode('-',$time);
        $year = (int)$date[0];
        $month= (int)$date[1];
        if($month == 12)
        {
            $ptime = ($year+1).'-00';
        }else{
            $month =(string)($month+1);
            if(strlen($month) == 1)
            {
                $ptime = (string)$year.'-0'.$month.'-00 00:00:00';
            }else{
                $ptime = (string)$year.'-'.$month.'-00 00:00:00';
            }
        }

        //消费列表
        if($account['wechat_type'] == 0)
        {
            $list = db('consume')
                ->field('e.*,p.stroe as sh_name,p.id as pid,r.cons_name as co_name')
                ->alias('e')
                ->join('blue_consumer r','r.cid = e.uid','left')
                ->join('blue_shops p','p.id = e.mid','left')
                ->where('uid='.$account['uid'].' and e.time>"'.$stime.'" and e.time <"'.$ptime.'"')
                ->limit(10)
                ->order('id','desc')
                ->select();
            foreach($list as &$v)
            {
                $shop = db('wechat_account')
                    ->where('uid ='.$v['pid'].' and wechat_type=1')
                    ->find();
                if($shop)$v['wechat'] = $shop;
                $v['time'] = substr($v['time'],0,11);
            }
            $data = [
                'status'=>0,
                'type'=>0,
                'list'=>$list
            ];
            print json_encode($data);exit;
        }
        else
        {
            $list = db('consume')
                ->alias('e')
                ->field('e.*,p.name as sh_name,r.cons_name as co_name,r.cons_sid')
                ->join('blue_consumer r','r.cid = e.uid','left')
                ->join('blue_shops p','p.id = e.mid','left')
                ->where('mid='.$account['uid'].' and e.time>"'.$stime.'" and e.time <"'.$ptime.'"' )
                ->order('id','desc')
                ->limit(10)
                ->select();
            foreach($list as &$v)
            {
                $user = db('wechat_account')
                    ->where('uid ='.$v['cons_sid'].' and wechat_type=0')
                    ->find();
                if($user)$v['wechat'] = $user;
                $v['time'] = substr($v['time'],0,11);
            }
            $data = [
                'status'=>0,
                'type'=>1,
                'list'=>$list
            ];
            print json_encode($data);exit;
        }
    }

    //获得收款二维码
    public function getGaqrcode()
    {
//        $code = input('post.code');
//        $openid = $this->getOpenid($code);
//        $account = $this->getAccWithwechat($openid);
        $account = \think\Cache::get('user');
        if(!$account)
        {
            print json_encode(['status'=>false]);exit;
        }
        //没有该账号
        if(!$account)
        {
            print json_encode(['status'=>1]);
            exit;
        }

        if($account['wechat_type'] ==1 )
        {
            $mid = $account['uid'];//商家id
            $shop = db('shops')->where('id='.$mid)->find();//商家名称
            $data = [
                'name'=>$shop['name'],
                'type'=>1,//表示支付码
                'status'=>1,//商家
                'mid'=>$mid,
                'time'=>time()
            ];


            \think\Loader::import('phpqrcode.phpqrcode',EXTEND_PATH);
            $errorCorrectionLevel = 'L';//容错级别
            $matrixPointSize = 6;//生成图片大小
            $url = 'upload/qrcode/'.date('Y-m-d',time());
            //最后图片
            $img = $url.'/'.$this->randStr(8).'.png';
            $data['qrurl'] = $img;
            if(!file_exists('../'.$url))
            {
                mkdir('../'.$url,0777,true);
            }
            $qrcode_url = $url.'/'.$this->randStr(8).'.png';

            $res = \QRcode::png(json_encode($data), '../'.$qrcode_url , $errorCorrectionLevel, $matrixPointSize, 2);
            $rootUrl = substr($_SERVER['REQUEST_URI'],0,strpos($_SERVER['REQUEST_URI'], 'public'));

            $logo =  $account['avatarUrl']; //logo
            $QR = '../'.$qrcode_url; //已经生成的二维码

            if($logo !== FALSE){
                $QR = imagecreatefromstring(file_get_contents($QR));
                $logo = imagecreatefromstring(file_get_contents($logo));
                $QR_width = imagesx($QR);//二维码图片宽度
                $QR_height = imagesy($QR);//二维码图片高度
                $logo_width = imagesx($logo);//logo图片宽度
                $logo_height = imagesy($logo);//logo图片高度
                $logo_qr_width = $QR_width / 5;
                $scale = $logo_width/$logo_qr_width;
                $logo_qr_height = $logo_height/$scale;
                $from_width = ($QR_width - $logo_qr_width) / 2;
                //重新组合图片并调整大小
                imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
            }


            //输出图片
            imagepng($QR, '../'.$img);
            unlink('../'.$qrcode_url);
            print json_encode(['status'=>0,'qrurl'=>$rootUrl.$img]);
            exit;
        }
        //不是商家
        else
        {
            print json_encode(['status'=>2,'mess'=>'不是商家']);
            exit;
        }
    }
}
