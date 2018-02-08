<?php
namespace app\admin\controller;
use app\admin\controller\BaseController;
use think\Db;
use think\Loader;

class Partner extends BaseController
{

    public function messRemind()
    {
        $url = "http://114.255.71.158:8061/getfee/?username=hgkj&password=".md5( 'Hgkj0116')."&epid=123591";
        $data = [
            'username'=>'hgkj',
            'password'=>md5( 'Hgkj0116'),
            'epid'=>123591,
        ];
        $ch = curl_init() ;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_POST, 1);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch) ;
        curl_close($ch) ;
        var_dump($output);
    }



    public function lists()
    {
        $lists = \Db('shops')
            ->select();
        $this->assign(
            [
                'lists'=>$lists
            ]
        );
        return $this->fetch('article-list');
    }


    public function add_partner()
    {
        return $this->fetch();
    }

    //添加商家操作
    public function doAdd()
    {
        $data = \think\Request::instance()->post();
        //判断手机，身份证是否注册
        $varify = db('shops')->where('phone ="'.$data['phone'].'" or id_card="'.$data['id_card'].'"')->find();
        if($varify)
        {
            return json(['s'=>false,'d'=>'手机或者身份证已注册']);
            exit;
        }

        //短信提示
        $res = $this->sendMessForparnter($data['phone'],$data['name'],$data['stroe']);
        if($res != '00')
        {
            return json(['s'=>false,'d'=>'短信提示失败，请检查电话号码']);
            exit;
        }
        $data['loginid'] = $this->randStr(6).date('md',time());
        $data['status'] = 0;
        $data['passwd'] = md5('666666');
        $data['createtime'] = date('Y-m-d h:i:s');
        $res = db('shops')->insert($data);
    }

    //选取账号
    public function randStr($num)
    {
        $chars = 'qwertyuiopasdfghjklmnbvcxzQWERTYUIOPLKJHGFDSAZXCVBNM';
        $rands = '';
        for($i=0;$i<$num;$i++)
        {
            $seed = rand(0,51);
            $rands .= substr($chars,$seed,1);
        }
        return $rands;
    }

    //导入excel
    public function inputExcel()
    {
        $file = '';
        \think\Loader::import('PHPExcel.PHPExcel',EXTEND_PATH);
        $reader = \PHPExcel_IOFactory::createReader('Excel2007');
        $PHPExcel = $reader->load($file);
        $objWorksheet = $PHPExcel->getSheet(0);

        for($i = 0;$i< 50;$i++)
        {
            for($j = 0 ;$j < 50;$j++ )
            {
                $val = $objWorksheet->getCell(chr(65+$i).(1+$j))->getValue();
                if(trim( ucwords($val) ) == 'S')
                {
                    $col = $i+1;
                    $row = $j+1;
                    break 2;
                }
            }
        }
        if(!$col)
        {
            $this->ajaxReturn(['status'=>false,'mess'=>'没有开始标志'],'json');
        }

        $n = 1;
        $data = [];
        do
        {
            $data[$n]['stroe'] = (string)$objWorksheet->getCell(chr(65+$col).$row)->getValue();
            $data[$n]['name'] = (string)$objWorksheet->getCell(chr(65+$col+1).$row)->getValue();
            $data[$n]['phone'] = (string)$objWorksheet->getCell(chr(65+$col+2).$row)->getValue();
            $data[$n]['id_card'] = (string)$objWorksheet->getCell(chr(65+$col+3).$row)->getValue();
            $data[$n]['profit'] = (string)$objWorksheet->getCell(chr(65+$col+4).$row)->getValue();
            $data[$n]['pro_person'] = (string)$objWorksheet->getCell(chr(65+$col+4).$row)->getValue();
            $data[$n]['pro_account'] = (string)$objWorksheet->getCell(chr(65+$col+4).$row)->getValue();
            $data[$n]['address'] = (string)$objWorksheet->getCell(chr(65+$col+4).$row)->getValue();

            $row++;
            $n++;
            $next = $objWorksheet->getCell(chr($col+65).$row)->getValue();
        }
        while(trim($next) != '');
        if(!empty($data))
        {
            $this->error();
        }
    }

    public function editor()
    {
        $id = \think\Request::instance()->param('id');
        $shoper = \think\Db::name('shops')->find($id);
        $this->assign('shop',$shoper);
        return $this->fetch();
    }

    public function doEditor()
    {
        $data= \think\Request::instance()->post();
        //判断手机，身份证是否注册
        $varify = db('shops')->where('(phone ="'.$data['phone'].'" or id_card="'.$data['id_card'].'") and id <>'.$data['id'])->find();
        if($varify)
        {
            return json(['s'=>false,'d'=>'手机或者身份证已注册']);
            exit;
        }
        $res = \think\Db::name('shops')->update($data);
    }

    public function change()
    {
        $id = \think\Request::instance()->get('id');
        $status = \think\Request::instance()->get('status');
        $res = \think\Db::name('shops')->update(['id'=>$id,'status'=>$status]);
    }
}