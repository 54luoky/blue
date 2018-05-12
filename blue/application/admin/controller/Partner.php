<?php
namespace app\admin\controller;
use app\admin\controller\BaseController;
use think\Db;
use think\Loader;

class Partner extends BaseController
{
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
//        if($res != '00')
//        {
//            return json(['s'=>false,'d'=>'短信提示失败，请检查电话号码']);
//            exit;
//        }
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



    /**
     *  商家检索
     */
    public function partnerseardh()
    {
        $s = $_POST['par_search'];
        $lists = $this->search($s);

        $this->assign("lists",$lists);
        $this->assign("search",$s);
        return $this->fetch();
    }


   /**
    *** 批量导出商家
    **/ 
        public function partnertoexcel()
        {
            $res = \Db('shops')->select();
            $this->toexcel($res);
        }

   /**
    *** 批量导出检索商家
    **/ 
        public function partnersearchtoexcel()
        {
            $s = input('param.s');
            $res = $this->search($s);
            $this->toexcel($res);
        }

/*******************  商家公共函数  *******************/ 

    /**
    *** 商家检索数据
    **/
        public function search($s)
        {
            $lists = db("shops")
               ->where("stroe",'like','%'.$s.'%')
               ->whereOr("name",'like','%'.$s.'%')
               ->whereOr("phone",'like','%'.$s.'%')
               ->whereOr("id_card",'like','%'.$s.'%')
               ->whereOr("pro_person",'like','%'.$s.'%')
               ->whereOr("address",'like','%'.$s.'%')
               ->whereOr("setup_id",'like','%'.$s.'%')
               ->select();
            return $lists;
        }

    /**
    *** 商家数据Excel
    **/
        public function toexcel($res)
        {
            \think\Loader::import("PHPExcel.PHPExcel",EXTEND_PATH);
            \think\Loader::import("PHPExcel.PHPExcel.Worksheet.Drawing",EXTEND_PATH);
            \think\Loader::import("PHPExcel.PHPExcel.Writer.Excel2007",EXTEND_PATH);
            $objPHPExcel = new \PHPExcel();
            $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);

            $objActSheet = $objPHPExcel->getActiveSheet();

            // 水平居中（位置很重要，建议在最初始位置）
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('A')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('B')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('C')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('D')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('E')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('F')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('G')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('H')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('I')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('J')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('K')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('L')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);

            //  设置excels第一行
            
            $objActSheet->setCellValue('A1','店名');
            $objActSheet->setCellValue('B1','营业执照注册号');
            $objActSheet->setCellValue('C1','经营人');
            $objActSheet->setCellValue('D1','电话');
            $objActSheet->setCellValue('E1','身份证号码');
            $objActSheet->setCellValue('F1','用户号');
            $objActSheet->setCellValue('G1','资金');
            $objActSheet->setCellValue('H1','收款方式');
            $objActSheet->setCellValue('I1','收款人');
            $objActSheet->setCellValue('J1','收款账号');
            $objActSheet->setCellValue('K1','地址');
            $objActSheet->setCellValue('L1','状态');

            // 设置个表格宽度
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(9);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(12);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(12);
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(6);
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
            $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(9);
            $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(60);
            $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(6);

            // 垂直居中
            $objPHPExcel->getActiveSheet()->getstyle('A')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('B')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('C')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('D')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('E')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('F')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('G')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('H')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('I')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('J')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('K')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('L')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);

            //其他行数据
            $row = 2;
            foreach($res as $k=>$v)
            {
                 $objActSheet->setCellValue('A'.$row,$v['stroe']);
                 $objActSheet->setCellValue('B'.$row,$v['setup_id']);
                 $objActSheet->setCellValue('C'.$row,$v['name']);
                 $objActSheet->setCellValue('D'.$row,$v['phone']);
                 $objActSheet->setCellValue('E'.$row,$v['id_card']);
                 $objActSheet->setCellValue('F'.$row,$v['loginid']);
                 $objActSheet->setCellValue('G'.$row,$v['fund']);
                 $objActSheet->setCellValue('H'.$row,$v['profit']);
                 $objActSheet->setCellValue('I'.$row,$v['pro_person']);
                 $objActSheet->setCellValue('J'.$row,$v['pro_account']);
                 $objActSheet->setCellValue('K'.$row,$v['address']);

                 switch($v['status'])
                 {
                     case 0:
                         $pact = '启用';
                         break;
                     case 1:
                         $pact = '停用';
                         break;
                 }
                 $objActSheet->setCellValue('L'.$row,$pact);

                 $row++;
            }


            $fileName = '商家表';
            $date = date("Y-m-d H:i:s",time());
            $fileName .= "_{$date}.xls";

            $fileName = iconv("utf-8", "gb2312", $fileName);

            $objPHPExcel->setActiveSheetIndex(0);
            header('Content-Type: application/vnd.ms-excel');
            header("Content-Disposition: attachment;filename=\"$fileName\"");
            header('Cache-Control: max-age=0');

            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save('php://output'); //文件通过浏览器下载
        }


}