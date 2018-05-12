<?php
namespace app\admin\controller;
use app\admin\controller\BaseController;
use think\Db;
use vendor\PHPExcel;
class Consume extends BaseController
{
    public function lists()
    {
        $back_url = $_SERVER['HTTP_REFERER'];
        $uid = input('get.id');
        if( $uid )
        {
            $lists = Db::table('blue_consume')
                ->alias('c')
                ->join('blue_consumer b','b.cid=c.uid','left')
                ->join('blue_shops s','c.mid=s.id','left')
                ->where('uid='.$uid)
                ->order('c.id DESC')
                ->select();
        }
        else
        {
            $lists = Db::table('blue_consume')
                ->alias('c')
                ->join('blue_consumer b','b.cid=c.uid','left')
                ->join('blue_shops s','c.mid=s.id','left')
                ->order('c.id DESC')
                ->select();
        }
        return view('article-list',['lists'=>$lists,'back_url'=>$back_url]);
    }

    public function editor()
    {
        $id = \think\Request::instance()->param('id');
        $consume = Db::table('blue_consume')
            ->where('id='.$id)
            ->find();
        return view('editor',['con'=>$consume]);
    }

    /**
     *  消费者列表
     */
    public function consumerlist()
    {
        $list = $this->consumers();

        $count = count($list);
        $this->assign("list",$list);
        $this->assign("count",$count);
        return $this->fetch();
    }

    /**
     *  消费者添加
     *  初始密码是111111  => 6 个 1
     */
    public function consumeradd()
    {
        if (! $_POST) {

            $memstand = $this->memberstand();
            $memtype = $this->membertype();
            
            $this->assign("memstand",$memstand);
            $this->assign("memtype",$memtype);
            return $this->fetch();
        }else{
            //  盐
            $str = '222222';
            $_POST['at_time']  = date("Y-m-d H:i:s", time());      //  创建时间
            $_POST['up_time']  = date("Y-m-d H:i:s", time());     //  修改时间
            $_POST['gt_time']  = date("Y-m-d H:i:s", time());    //  消费时间
            $_POST['c_status'] = 0;
            $_POST['cons_pwd'] = md5($str."111111".$str);
            
            $re = db("consumer")->insert($_POST);
            if ($re) {
                $this->success("操作成功！",'admin/member/successHtml',1);
            }else{
                $this->error("操作失败，请重新操作！");
            }
        }
    }

    /**
     *  消费者编辑
     */
    public function consumeredit()
    {
        if (! $_POST) {
            $cid = $_GET['cid'];
            $cons = db("consumer")
                  ->where('cid',$cid)
                  ->find();
            $memstand = $this->memberstand();
            $memtype = $this->membertype();
            
            $this->assign("memstand",$memstand);
            $this->assign("memtype",$memtype);
            $this->assign("cons",$cons);
            return $this->fetch();
        }else{
            $_POST['up_time'] = date("Y-m-d H:i:s", time());
            $cid = $_POST['cid'];
            $re = db("consumer")
                ->where("cid",$cid)
                ->update($_POST);
            if ($re) {
                $this->success("操作成功！",'admin/member/successHtml',1);
            }else{
                $this->error("操作失败，请重新操作！");
            }
        }
    }


    /**
    *** 消费者停用
    **/ 
        public function consumerout()
        {
            $mid = $_POST['mid'];
            $data['status'] = 0;
            $re = db("consumer")
                ->where("cid",$mid)
                ->setField('c_status',1);
            if ($re) {
                $data['status'] = 1;
            }

            return $data;
        }

    /**
    *** 消费者启用
    **/ 
        public function consumerstart()
        {
            $mid = $_POST['mid'];
            $data['status'] = 0;
            $re = db("consumer")
                ->where("cid",$mid)
                ->setField('c_status',0);
            if ($re) {
                $data['status'] = 1;
            }

            return $data;
        }

    /**
    *** 消费者检索
    **/ 
        public function consumersearch()
        {

            $s = $_POST['con_search'];
            $list = $this->search($s);
            $count = count($list);

            $this->assign("list",$list);
            $this->assign("count",$count);
            $this->assign("search",$s);
            return $this->fetch();

        }


    /**
    *** 消费记录检索
    **/ 
        public function consumesearch()
        {
            $back_url = $_SERVER['HTTP_REFERER'];
            $be = input("param.be");
            $end = input("param.end");
            $s = input("param.search");

            if ($be == '' || $be == null) {
                $be = "00-00-00";
            }

            if ($end == '' || $end == null) {
                $end = date("Y-m-d", time());
            }
            $end = $end." 23:59:59";

            $str = 'be='.$be.'&end='.$end.'&search='.$s;

            $search = db("consume")
                    ->alias('c')
                    ->join("shops bs", "c.mid = bs.id")
                    ->join("consumer bc", "c.uid = bc.cid")
                    ->whereTime("c.time", '>=', $be)
                    ->whereTime("c.time", '<', $end)
                    ->where("(bc.cons_name like '%".$s."%') OR (bs.stroe like '%".$s."%')")
                    ->select();

            return view('consumesearch',['lists'=>$search,'back_url'=>$back_url,'str'=>$str]);

        }


    /**
    *** 消费记录报表
    **/ 
        public function consumestatistics()
        {
            $back_url = $_SERVER['HTTP_REFERER'];
            $be = input("param.be");
            $end = input("param.end");

            if ($be == '' || $be == null) {
                $be = "00-00-00";
            }

            if ($end == '' || $end == null) {
                $end = date("Y-m-d", time());
            }
            $end = $end." 23:59:59";

            $str = 'be='.$be.'&end='.$end;

            $search = db("consume")
                    ->alias('c')
                    ->field('bs.*,sum(c.tip) as tip')
                    ->join("shops bs", "c.mid = bs.id")
                    ->whereTime("c.time", '>=', $be)
                    ->whereTime("c.time", '<=', $end)
                    ->group("c.mid")
                    ->select();

            return view('consumestatistics',['lists'=>$search,'back_url'=>$back_url,'str'=>$str]);

        }

    /**
    *** 批量导出消费者
    **/ 
        public function consumertoexcel()
        {
            $res = $this->consumers();
            $this->toexcel($res);
        }


    /**
    *** 批量导出检索消费者数据
    **/ 
        public function consumersearchtoexcel()
        {
            $s = input('param.s');
            $res = $this->search($s);
            $this->toexcel($res);
        }



    /**
    *** 消费记录数据导出Excel
    **/
        public function consumetoexcel()
        {
            $re = db("consume")
                ->alias('c')
                ->join("shops bs", "c.mid = bs.id")
                ->join("consumer bc", "c.uid = bc.cid")
                ->select();
            $this->consumeexcel($re);
        }


    /**
    *** 消费记录检索数据导出Excel
    **/
        public function consumesearchtoexcel()
        {
            $be = input("param.be");
            $end = input("param.end");
            $s = input("param.search");

            if ($be == '' || $be == null) {
                $be = "00-00-00";
            }

            if ($end == '' || $end == null) {
                $end = date("Y-m-d", time());
            }
            
            $search = db("consume")
                    ->alias('c')
                    ->join("shops bs", "c.mid = bs.id")
                    ->join("consumer bc", "c.uid = bc.cid")
                    ->whereTime("c.time", '>=', $be)
                    ->whereTime("c.time", '<', $end)
                    ->where("(bc.cons_name like '%".$s."%') OR (bs.stroe like '%".$s."%')")
                    ->select();

            $this->consumeexcel($search);
        }


    /**
    *** 消费记录报表数据导出Excel
    **/
        public function consumestatisticstoexcel()
        {
            $be = input("param.be");
            $end = input("param.end");

            if ($be == '' || $be == null) {
                $be = "00-00-00";
            }

            if ($end == '' || $end == null) {
                $end = date("Y-m-d", time());
            }

            $search = db("consume")
                    ->alias('c')
                    ->field('bs.*,sum(c.tip) as tip')
                    ->join("shops bs", "c.mid = bs.id")
                    ->whereTime("c.time", '>=', $be)
                    ->whereTime("c.time", '<', $end)
                    ->group("c.mid")
                    ->select();

            $this->statisticsexcel($search);
        }







/*******************  消费者公共函数  *******************/ 


    /**
    *** 消费者总数据
    **/ 
        static function consumers()
        {
            $list = db("consumer")
                  ->alias("c")
                  ->join("member_type t", "c.cons_tid = t.id")
                  ->order("c.cid DESC")
                  ->select();

            foreach ($list as $k => $v) {
                $sid = $v['cons_sid'];
                if ($sid) {
                    $stand = db("standard")
                           ->where("id", $sid)
                           ->find();
                    $list[$k]['standard_name'] = $stand['standard_name'];
                }else{
                    $list[$k]['standard_name'] = '';
                }
            }

            return $list;
        }


    /**
    *** 消费者检索数据
    **/
        public function search($s)
        {

            //  区域表字段模糊查询
            $mt = db("member_type")
                 ->field("id")
                ->where("type_name",'like','%'.$s.'%')
                ->select();

            $tidarr = [999999999];
            if($mt)
            {
                foreach ($mt as $k => $v) {
                    $tidarr[$k] = $v['id'];
                }
            }
                
            //  标准表字段模糊查询
            $sd = db("standard")
                 ->field("id")
                ->where("standard_name",'like','%'.$s.'%')
                ->select();

            $sidarr = [999999999];
            if($sd)
            {
                foreach ($sd as $k => $v) {
                    $sidarr[$k] = $v['id'];
                }
            }

            //  消费者表字段模糊查询
            $list = db("consumer")
                  ->alias("c")
                  ->where("c.cons_name",'like','%'.$s.'%')
                  ->whereOr("c.cons_phone",'like','%'.$s.'%')
                  ->whereOr("c.cons_tid",'in',$tidarr)
                  ->whereOr("c.cons_sid",'in',$sidarr)
                  ->join("member_type t", "c.cons_tid = t.id")
                  ->order("c.cid DESC")
                  ->select();

            foreach ($list as $k => $v) {
                $sid = $v['cons_sid'];
                if ($sid) {
                    $stand = db("standard")
                           ->where("id", $sid)
                           ->find();
                    $list[$k]['standard_name'] = $stand['standard_name'];
                }else{
                    $list[$k]['standard_name'] = '';
                }
            }
            return $list;
        }

    /**
    *** 消费者数据Excel
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
            
            $objActSheet->setCellValue('A1','姓名');
            $objActSheet->setCellValue('B1','手机');
            $objActSheet->setCellValue('C1','区域');
            $objActSheet->setCellValue('D1','套餐标准');
            $objActSheet->setCellValue('E1','可提现资金（总）');
            $objActSheet->setCellValue('F1','不可提现资金（总）');
            $objActSheet->setCellValue('G1','是否群组');
            $objActSheet->setCellValue('H1','充值金额');
            $objActSheet->setCellValue('I1','最近消费时间');
            $objActSheet->setCellValue('J1','状态');
            $objActSheet->setCellValue('K1','可提现资金（余）');
            $objActSheet->setCellValue('L1','不可提现资金（余）');

            // 设置个表格宽度
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(9);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(12);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(34);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(34);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(19);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(19);
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(9);
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(9);
            $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(19);
            $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(6);
            $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);

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
                 $objActSheet->setCellValue('A'.$row,$v['cons_name']);
                 $objActSheet->setCellValue('B'.$row,$v['cons_phone']);
                 $objActSheet->setCellValue('C'.$row,$v['type_name']);
                 $objActSheet->setCellValue('D'.$row,$v['standard_name']);
                 $objActSheet->setCellValue('E'.$row,$v['cons_balance']);
                 $objActSheet->setCellValue('F'.$row,$v['cons_balancs']);

                 switch($v['cons_rele_mark'])
                 {
                     case 1:
                         $timeing = '是';
                         break;
                     case 2:
                         $timeing = '否';
                         break;
                 }
                 $objActSheet->setCellValue('G'.$row,$timeing);
                 $objActSheet->setCellValue('H'.$row,$v['cons_amount']);
                 $objActSheet->setCellValue('I'.$row,$v['gt_time']);

                 switch($v['c_status'])
                 {
                     case 0:
                         $pact = '启用';
                         break;
                     case 1:
                         $pact = '停用';
                         break;
                 }
                 $objActSheet->setCellValue('J'.$row,$pact);
                 $objActSheet->setCellValue('K'.$row,$v['cons_amount']);
                 $objActSheet->setCellValue('L'.$row,$v['cons_amount']);

                 $row++;
            }


            $fileName = '消费者表';
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

    /**
    *** 消费记录数据Excel
    **/
        public function consumeexcel($res)
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

            //  设置excels第一行
            
            $objActSheet->setCellValue('A1','消费者姓名');
            $objActSheet->setCellValue('B1','消费者号码');
            $objActSheet->setCellValue('C1','消费店名');
            $objActSheet->setCellValue('D1','消费金额');
            $objActSheet->setCellValue('E1','消费时间');

            // 设置个表格宽度
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(12);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(32);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);

            // 垂直居中
            $objPHPExcel->getActiveSheet()->getstyle('A')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('B')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('C')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('D')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('E')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);

            //其他行数据
            $row = 2;
            foreach($res as $k=>$v)
            {
                 $objActSheet->setCellValue('A'.$row,$v['cons_name']);
                 $objActSheet->setCellValue('B'.$row,$v['cons_phone']);
                 $objActSheet->setCellValue('C'.$row,$v['stroe']);
                 $objActSheet->setCellValue('D'.$row,$v['tip']);
                 $objActSheet->setCellValue('E'.$row,$v['time']);

                 $row++;
            }


            $fileName = '消费记录表';
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

    /**
    *** 消费记录报表数据Excel
    **/
        public function statisticsexcel($res)
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

            //  设置excels第一行
            
            $objActSheet->setCellValue('A1','商家店名');
            $objActSheet->setCellValue('B1','商家经营人');
            $objActSheet->setCellValue('C1','商家电话号码');
            $objActSheet->setCellValue('D1','消费总金额');

            // 设置个表格宽度
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(32);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);

            // 垂直居中
            $objPHPExcel->getActiveSheet()->getstyle('A')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('B')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('C')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('D')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);

            //其他行数据
            $row = 2;
            foreach($res as $k=>$v)
            {
                 $objActSheet->setCellValue('A'.$row,$v['stroe']);
                 $objActSheet->setCellValue('B'.$row,$v['name']);
                 $objActSheet->setCellValue('C'.$row,$v['phone']);
                 $objActSheet->setCellValue('D'.$row,$v['tip']);

                 $row++;
            }


            $fileName = '消费记录报表';
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
